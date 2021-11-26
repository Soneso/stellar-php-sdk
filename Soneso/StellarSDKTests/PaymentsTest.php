<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace StellarSDKTests;

use DateTime;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeNative;
use Soneso\StellarSDK\ChangeTrustOperation;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\CreateAccountOperation;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Responses\Operations\CreateAccountOperationResponse;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;

class PaymentsTest extends TestCase
{

    public function testBI(): void
    {
        $bi = new BigInteger("100000.8.01");
        print($bi->toString());
        $this->assertNotNull($bi);
    }
    public function testEffectsPage(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $requestBuilder = $sdk->payments()->forAccount("GAOF7ARG3ZAVUA63GCLXG5JQTMBAH3ZFYHGLGJLDXGDSXQRHD72LLGOB");
        $response = $requestBuilder->execute();
        foreach ($response->getOperations() as $payment) {
            $this->assertTrue(($payment instanceof CreateAccountOperationResponse || $payment instanceof PaymentOperationResponse));
            $this->assertGreaterThan(0, strlen($payment->getOperationId()));
        }
    }

    public function testNativeAndNonNativePayment(): void {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPair = KeyPair::random();
        $this->assertNotNull($keyPair->getPrivateKey());
        $acountId = $keyPair->getAccountId();
        print(PHP_EOL. "1:".$acountId. ":" . $keyPair->getSecretSeed() . PHP_EOL);
        FriendBot::fundTestAccount($acountId);
        $response = $sdk->requestAccount($acountId);
        $this->assertEquals($acountId, $response->getAccountId());
        $this->assertGreaterThan(0, strlen($response->getSequenceNumber()->toString()));
        $this->assertGreaterThan(0, strlen($response->getIncrementedSequenceNumber()->toString()));

        $keyPair2 = KeyPair::random();
        $acountId2 = $keyPair2->getAccountId();
        print(PHP_EOL. "2:".$acountId2 . ":" . $keyPair2->getSecretSeed() . PHP_EOL);

        $builder = new TransactionBuilder($response);
        $createAccountOp = new CreateAccountOperation($acountId2, "120");
        $memo = Memo::id(19929182111);
        $builder->setMemo($memo);
        $builder->addOperation($createAccountOp);
        $transaction = $builder->build();
        $transaction->sign($keyPair, Network::testnet());
        $submitTxResponse = $sdk->submitTransaction($transaction);
        $this->assertNotNull($submitTxResponse);

        $account2 = $sdk->requestAccount($acountId2);
        $builder = Transaction::builder($account2);
        $paymentOpBuilder = new PaymentOperationBuilder($acountId, Asset::native(), "12.32");
        $builder->addOperation($paymentOpBuilder->build());
        $transaction = $builder->build();
        $transaction->sign($keyPair2, Network::testnet());
        $submitTxResponse = $sdk->submitTransaction($transaction);
        $this->assertNotNull($submitTxResponse);

        $keyPair3 = KeyPair::random();
        $acountId3 = $keyPair3->getAccountId();
        print(PHP_EOL. "3:".$acountId3 . ":" . $keyPair3->getSecretSeed() . PHP_EOL);
        FriendBot::fundTestAccount($acountId3);
        $account1 = $sdk->requestAccount($acountId);
        $builder = Transaction::builder($account1);
        $assetIXO = Asset::createNonNativeAsset("IXO", $acountId3);
        $chTrustOpBuilder = new ChangeTrustOperationBuilder($assetIXO);
        $chTrustOpBuilder->setSourceAccount($acountId);
        $chop1 =  $chTrustOpBuilder->build();
        $chTrustOpBuilder->setSourceAccount($acountId2);
        $chop2 =  $chTrustOpBuilder->build();
        $strHash32 = "9e76beeae3ca55ea1efea80f8fb32ef0";
        $memo = Memo::hash($strHash32);
        $builder->setMemo($memo);
        $builder->addOperation($chop1);
        $builder->addOperation($chop2);
        $from = new DateTime();
        $from->modify("-1 day");
        $to = new DateTime();
        $to->modify("+1 day");
        $timeBounds = new TimeBounds($from, $to);
        $builder->setTimeBounds($timeBounds);
        $transaction = $builder->build();
        $transaction->sign($keyPair, Network::testnet());
        $transaction->sign($keyPair2, Network::testnet());
        $submitTxResponse = $sdk->submitTransaction($transaction);
        $this->assertNotNull($submitTxResponse);
        $this->assertEquals($submitTxResponse->getMemo()->getValue(), $strHash32);

        $account3 = $sdk->requestAccount($acountId3);
        $paymentOpBuilder = new PaymentOperationBuilder($acountId, $assetIXO, "200002.12223");
        $pop1 = $paymentOpBuilder->build();
        $paymentOpBuilder = new PaymentOperationBuilder($acountId2, $assetIXO, "2828");
        $pop2 = $paymentOpBuilder->build();
        $builder = Transaction::builder($account3);
        $memo = Memo::text("jaja, kuppal s");
        $builder->setMemo($memo);
        $builder->addOperation($pop1);
        $builder->addOperation($pop2);
        $transaction = $builder->build();
        $transaction->sign($keyPair3, Network::testnet());
        $submitTxResponse = $sdk->submitTransaction($transaction);
        $this->assertNotNull($submitTxResponse);
    }
}