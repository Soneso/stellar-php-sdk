<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\AllowTrustOperationBuilder;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum12;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\CreatePassiveSellOfferOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Price;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDKTests\TestUtils;

class TrustTest  extends TestCase
{

    public function testChangeTrust(): void
    {
        $sdk = StellarSDK::getTestNetInstance();

        $issuerKeyPair = KeyPair::random();
        $trustorKeyPair = KeyPair::random();

        $issuerAccountId = $issuerKeyPair->getAccountId();
        $trustorAccountId = $trustorKeyPair->getAccountId();

        FriendBot::fundTestAccount($issuerAccountId);

        $issuerAccount = $sdk->requestAccount($issuerAccountId);

        $createAccountOperation = (new CreateAccountOperationBuilder($trustorAccountId, "10"))->build();
        $transaction = (new TransactionBuilder($issuerAccount))
            ->addOperation($createAccountOperation)
            ->build();

        $transaction->sign($issuerKeyPair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $astroDollar = new AssetTypeCreditAlphanum12("ASTRO", $issuerAccountId);

        $trustorAccount = $sdk->requestAccount($trustorAccountId);

        $changeTrustOperation = (new ChangeTrustOperationBuilder($astroDollar, "10000"))->build();
        $transaction = (new TransactionBuilder($trustorAccount))
            ->addOperation($changeTrustOperation)
            ->build();
        $transaction->sign($trustorKeyPair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $trustorAccount = $sdk->requestAccount($trustorAccountId);
        $found = false;
        foreach($trustorAccount->getBalances() as $balance) {
            if ($balance->getAssetType() != Asset::TYPE_NATIVE && $balance->getAssetCode() == $astroDollar->getCode()) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);

        // update trustline, change limit
        $limit = "40000";
        $changeTrustOperation = (new ChangeTrustOperationBuilder($astroDollar, $limit))->build();
        $transaction = (new TransactionBuilder($trustorAccount))
            ->addOperation($changeTrustOperation)
            ->build();
        $transaction->sign($trustorKeyPair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $trustorAccount = $sdk->requestAccount($trustorAccountId);
        $found = false;
        foreach($trustorAccount->getBalances() as $balance) {
            if ($balance->getAssetType() != Asset::TYPE_NATIVE && $balance->getAssetCode() == $astroDollar->getCode()) {
                $this->assertTrue(floatval($balance->getLimit()) == floatval($limit));
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);

        // delete trustline.
        $limit = "0";
        $changeTrustOperation = (new ChangeTrustOperationBuilder($astroDollar, $limit))->build();
        $transaction = (new TransactionBuilder($trustorAccount))
            ->addOperation($changeTrustOperation)
            ->build();
        $transaction->sign($trustorKeyPair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $trustorAccount = $sdk->requestAccount($trustorAccountId);
        $found = false;
        foreach($trustorAccount->getBalances() as $balance) {
            if ($balance->getAssetType() != Asset::TYPE_NATIVE && $balance->getAssetCode() == $astroDollar->getCode()) {
                $found = true;
                break;
            }
        }
        $this->assertFalse($found);
    }

    public function testAllowTrust(): void
    {
        $sdk = StellarSDK::getTestNetInstance();

        $issuerKeyPair = KeyPair::random();
        $trustorKeyPair = KeyPair::random();

        $issuerAccountId = $issuerKeyPair->getAccountId();
        $trustorAccountId = $trustorKeyPair->getAccountId();

        FriendBot::fundTestAccount($issuerAccountId);

        $issuerAccount = $sdk->requestAccount($issuerAccountId);

        $createAccountOperation = (new CreateAccountOperationBuilder($trustorAccountId, "100"))->build();
        $transaction = (new TransactionBuilder($issuerAccount))
            ->addOperation($createAccountOperation)
            ->build();

        $transaction->sign($issuerKeyPair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $sop = (new SetOptionsOperationBuilder())->setSetFlags(3)->build();
        $transaction = (new TransactionBuilder($issuerAccount))
            ->addOperation($sop)
            ->build();

        $transaction->sign($issuerKeyPair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $issuerAccount = $sdk->requestAccount($issuerAccountId);
        print($issuerAccountId);
        $this->assertTrue($issuerAccount->getFlags()->isAuthRequired());
        $this->assertTrue($issuerAccount->getFlags()->isAuthRevocable());
        $this->assertFalse($issuerAccount->getFlags()->isAuthImmutable());

        $astroDollar = new AssetTypeCreditAlphanum12("ASTRO", $issuerAccountId);

        $trustorAccount = $sdk->requestAccount($trustorAccountId);

        $changeTrustOperation = (new ChangeTrustOperationBuilder($astroDollar, "10000"))->build();
        $transaction = (new TransactionBuilder($trustorAccount))
            ->addOperation($changeTrustOperation)
            ->build();
        $transaction->sign($trustorKeyPair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $trustorAccount = $sdk->requestAccount($trustorAccountId);
        $found = false;
        foreach($trustorAccount->getBalances() as $balance) {
            if ($balance->getAssetType() != Asset::TYPE_NATIVE && $balance->getAssetCode() == $astroDollar->getCode()) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);

        $paymentOperation = (new PaymentOperationBuilder($trustorAccountId, $astroDollar, "100"))->build();
        $transaction = (new TransactionBuilder($issuerAccount))
            ->addOperation($paymentOperation)
            ->build();
        $transaction->sign($issuerKeyPair, Network::testnet());
        $ex = false;
        try {
            $response = $sdk->submitTransaction($transaction);
        } catch (HorizonRequestException $e) {
            $ex = true;
        }
        $this->assertTrue($ex); // not authorized.

        $allowTrustOperation = (new AllowTrustOperationBuilder($trustorAccountId, $astroDollar->getCode(), true, false))->build();
        $transaction = (new TransactionBuilder($issuerAccount))
            ->addOperation($allowTrustOperation)
            ->build();
        $transaction->sign($issuerKeyPair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $paymentOperation = (new PaymentOperationBuilder($trustorAccountId, $astroDollar, "100"))->build();
        $transaction = (new TransactionBuilder($issuerAccount))
            ->addOperation($paymentOperation)
            ->build();
        $transaction->sign($issuerKeyPair, Network::testnet());
        $ex = false;
        try {
            $response = $sdk->submitTransaction($transaction);
            $this->assertTrue($response->isSuccessful());
            TestUtils::resultDeAndEncodingTest($this, $transaction, $response);
        } catch (HorizonRequestException $e) {
            $ex = true;
        }
        $this->assertFalse($ex); // authorized.

        $amountSelling = "100";
        $price = "0.5";
        $cpso = (new CreatePassiveSellOfferOperationBuilder($astroDollar, Asset::native(), $amountSelling, Price::fromString($price)))->build();
        $transaction = (new TransactionBuilder($trustorAccount))
            ->addOperation($cpso)
            ->build();
        $transaction->sign($trustorKeyPair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $requestBuilder = $sdk->offers()->forAccount($trustorAccountId);
        $response = $requestBuilder->execute();
        $offers = $response->getOffers()->toArray();
        $offer = $offers[0];
        $this->assertTrue($offer->getBuying()->getType() == Asset::TYPE_NATIVE);
        $this->assertTrue($offer->getSelling()->getType() == Asset::TYPE_CREDIT_ALPHANUM_12);

        $allowTrustOperation = (new AllowTrustOperationBuilder($trustorAccountId, $astroDollar->getCode(), false, false))->build();
        $transaction = (new TransactionBuilder($issuerAccount))
            ->addOperation($allowTrustOperation)
            ->build();
        $transaction->sign($issuerKeyPair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $requestBuilder = $sdk->offers()->forAccount($trustorAccountId);
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getOffers()->count() == 0);

        $trustorAccount = $sdk->requestAccount($trustorAccountId);
        $found = false;
        foreach($trustorAccount->getBalances() as $balance) {
            if ($balance->getAssetType() != Asset::TYPE_NATIVE && $balance->getAssetCode() == $astroDollar->getCode()) {
                $this->assertTrue(floatval($balance->getBalance()) == floatval("100"));
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);

        $allowTrustOperation = (new AllowTrustOperationBuilder($trustorAccountId, $astroDollar->getCode(), true, false))->build();
        $transaction = (new TransactionBuilder($issuerAccount))
            ->addOperation($allowTrustOperation)
            ->build();
        $transaction->sign($issuerKeyPair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $cpso = (new CreatePassiveSellOfferOperationBuilder($astroDollar, Asset::native(), $amountSelling, Price::fromString($price)))->build();
        $transaction = (new TransactionBuilder($trustorAccount))
            ->addOperation($cpso)
            ->build();
        $transaction->sign($trustorKeyPair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $requestBuilder = $sdk->offers()->forAccount($trustorAccountId);
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getOffers()->count() == 1);


        $allowTrustOperation = (new AllowTrustOperationBuilder($trustorAccountId, $astroDollar->getCode(), false, true))->build();
        $transaction = (new TransactionBuilder($issuerAccount))
            ->addOperation($allowTrustOperation)
            ->build();
        $transaction->sign($issuerKeyPair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $requestBuilder = $sdk->offers()->forAccount($trustorAccountId);
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getOffers()->count() == 1);

        $paymentOperation = (new PaymentOperationBuilder($trustorAccountId, $astroDollar, "100"))->build();
        $transaction = (new TransactionBuilder($issuerAccount))
            ->addOperation($paymentOperation)
            ->build();
        $transaction->sign($issuerKeyPair, Network::testnet());
        $ex = false;
        try {
            $response = $sdk->submitTransaction($transaction);
        } catch (HorizonRequestException $e) {
            $ex = true;
        }
        $this->assertTrue($ex); // not authorized.

    }

    public function testIssue16(): void
    {
        $envelop = 'AAAAAgAAAAB+1X4E8MAjncM+MMh+9sbJsyh+VzCr8wxTFJ6hA+aNXQAAAGQAAAAAAAAAAQAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEAAAAAAAAAAQAAAAB+1X4E8MAjncM+MMh+9sbJsyh+VzCr8wxTFJ6hA+aNXQAAAAJBQUFBQTAwMAAAAAAAAAAAftV+BPDAI53DPjDIfvbGybMoflcwq/MMUxSeoQPmjV0AAASMJzlQAAAAAAAAAAAA';

        $transaction = \Soneso\StellarSDK\Transaction::fromEnvelopeBase64XdrString($envelop);

        $operations = $transaction->getOperations();

        $paymentOp = $operations[0];

        $asset = $paymentOp->getAsset();

        $this->assertSame('AAAAA000', $asset->getCode());

    }
}