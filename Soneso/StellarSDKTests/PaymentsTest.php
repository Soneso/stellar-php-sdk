<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace StellarSDKTests;

use DateTime;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\AssetTypeNative;
use Soneso\StellarSDK\ChangeTrustOperation;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\CreateAccountOperation;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\ManageSellOfferOperationBuilder;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PathPaymentStrictReceiveOperation;
use Soneso\StellarSDK\PathPaymentStrictSendOperationBuilder;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Price;
use Soneso\StellarSDK\Responses\Operations\CreateAccountOperationResponse;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;

class PaymentsTest extends TestCase
{

    public function testSendNativePayment(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPairA = KeyPair::random();
        $accountAId = $keyPairA->getAccountId();
        FriendBot::fundTestAccount($accountAId);
        $accountA = $sdk->requestAccount($accountAId);

        $keyPairC = KeyPair::random();
        $accountCId = $keyPairC->getAccountId();

        $createAccountOperation = (new CreateAccountOperationBuilder($accountCId, "10"))->build();
        $transaction = (new TransactionBuilder($accountA))->addOperation($createAccountOperation)->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        // send 100 XLM payment form A to C
        $paymentOperation = (new PaymentOperationBuilder($accountCId, Asset::native(), "100"))->build();
        $transaction = (new TransactionBuilder($accountA))->addOperation($paymentOperation)->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $found = false;
        $accountC = $sdk->requestAccount($accountCId);
        foreach($accountC->getBalances() as $balance) {
            if ($balance->getAssetType() == Asset::TYPE_NATIVE) {
                $this->assertTrue(floatval($balance->getBalance()) > 100.00);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
        $response = $sdk->payments()->forAccount($accountCId)->execute();
        $found = false;
        foreach ($response->getOperations() as $operation) {
            if($operation instanceof PaymentOperationResponse) {
                $this->assertTrue($operation->getSourceAccount() === $accountAId);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testSendNativePaymentMuxedAccounts(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPairA = KeyPair::random();
        $accountAId = $keyPairA->getAccountId();
        FriendBot::fundTestAccount($accountAId);
        $accountA = $sdk->requestAccount($accountAId);

        $keyPairC = KeyPair::random();
        $accountCId = $keyPairC->getAccountId();

        $createAccountOperation = (new CreateAccountOperationBuilder($accountCId, "10"))->build();
        $transaction = (new TransactionBuilder($accountA))->addOperation($createAccountOperation)->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        // send 100 XLM payment form A to C
        $muxedSource = new MuxedAccount($accountAId, 1909291282);
        $muxedDestination = new MuxedAccount($accountCId, 999919919);

        $paymentOperation = (PaymentOperationBuilder::forMuxedDestinationAccount($muxedDestination, Asset::native(), "100"))
            ->setMuxedSourceAccount($muxedSource)->build();
        $accountA->setMuxedAccountMed25519Id(888181818);
        $transaction = (new TransactionBuilder($accountA))->addOperation($paymentOperation)->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $transactionHash = $response->getHash();
        $this->assertTrue($response->isSuccessful());

        $found = false;
        $accountC = $sdk->requestAccount($accountCId);
        foreach($accountC->getBalances() as $balance) {
            if ($balance->getAssetType() == Asset::TYPE_NATIVE) {
                $this->assertTrue(floatval($balance->getBalance()) > 100.00);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
        $response = $sdk->payments()->forAccount($accountCId)->execute();
        $found = false;
        foreach ($response->getOperations() as $operation) {
            if($operation instanceof PaymentOperationResponse) {
                $this->assertTrue($operation->getSourceAccount() === $accountAId);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);

        $response = $sdk->transactions()->forAccount($accountCId)->execute();
        $found = false;
        foreach ($response->getTransactions() as $transaction) {
            if($transaction->getHash() == $transactionHash) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testSendNativePaymentWithMaxOperationFee(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPairA = KeyPair::random();
        $accountAId = $keyPairA->getAccountId();
        FriendBot::fundTestAccount($accountAId);
        $accountA = $sdk->requestAccount($accountAId);

        $keyPairC = KeyPair::random();
        $accountCId = $keyPairC->getAccountId();

        $createAccountOperation = (new CreateAccountOperationBuilder($accountCId, "10"))->build();
        $transaction = (new TransactionBuilder($accountA))->addOperation($createAccountOperation)->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        // send 100 XLM payment form A to C
        $paymentOperation = (new PaymentOperationBuilder($accountCId, Asset::native(), "100"))->build();
        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($paymentOperation)
            ->setMaxOperationFee(300)
            ->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $found = false;
        $accountC = $sdk->requestAccount($accountCId);
        foreach($accountC->getBalances() as $balance) {
            if ($balance->getAssetType() == Asset::TYPE_NATIVE) {
                $this->assertTrue(floatval($balance->getBalance()) > 100.00);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
        $response = $sdk->payments()->forAccount($accountCId)->execute();
        $found = false;
        foreach ($response->getOperations() as $operation) {
            if($operation instanceof PaymentOperationResponse) {
                $this->assertTrue($operation->getSourceAccount() === $accountAId);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testSendNonNativePayment() {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPairA = KeyPair::random();
        $accountAId = $keyPairA->getAccountId();
        FriendBot::fundTestAccount($accountAId);
        $accountA = $sdk->requestAccount($accountAId);

        $keyPairB = KeyPair::random();
        $keyPairC = KeyPair::random();
        $accountBId = $keyPairB->getAccountId();
        $accountCId = $keyPairC->getAccountId();

        $createAccountBOperation = (new CreateAccountOperationBuilder($accountBId, "10"))->build();
        $createAccountCOperation = (new CreateAccountOperationBuilder($accountCId, "10"))->build();
        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($createAccountBOperation)
            ->addOperation($createAccountCOperation)
            ->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $iomAsset = new AssetTypeCreditAlphanum4("IOM", $accountAId);
        $changeTrustBOperation = (new ChangeTrustOperationBuilder($iomAsset, "200999"))
            ->setSourceAccount($accountBId)->build();
        $changeTrustCOperation = (new ChangeTrustOperationBuilder($iomAsset, "200999"))
            ->setSourceAccount($accountCId)->build();
        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($changeTrustBOperation)
            ->addOperation($changeTrustCOperation)
            ->build();
        $transaction->sign($keyPairA, Network::testnet());
        $transaction->sign($keyPairB, Network::testnet());
        $transaction->sign($keyPairC, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        // send 100 IOM non native payment from A to C
        $paymentOperation = (new PaymentOperationBuilder($accountCId, $iomAsset, "100"))->build();
        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($paymentOperation)
            ->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $found = false;
        $accountC = $sdk->requestAccount($accountCId);
        foreach($accountC->getBalances() as $balance) {
            if ($balance->getAssetCode() == "IOM") {
                $this->assertTrue(floatval($balance->getBalance()) > 90.00);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);

        // send 50.09 IOM non native payment from C to B
        $paymentOperation = (new PaymentOperationBuilder($accountBId, $iomAsset, "50.9"))->build();
        $transaction = (new TransactionBuilder($accountC))
            ->addOperation($paymentOperation)
            ->build();
        $transaction->sign($keyPairC, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $found = false;
        $accountB = $sdk->requestAccount($accountBId);
        foreach($accountC->getBalances() as $balance) {
            if ($balance->getAssetCode() == "IOM") {
                $this->assertTrue(floatval($balance->getBalance()) > 40.00);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testSendNonNativePaymentMuxedAccounts() {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPairA = KeyPair::random();
        $accountAId = $keyPairA->getAccountId();
        FriendBot::fundTestAccount($accountAId);
        $accountA = $sdk->requestAccount($accountAId);

        $keyPairB = KeyPair::random();
        $keyPairC = KeyPair::random();
        $accountBId = $keyPairB->getAccountId();
        $accountCId = $keyPairC->getAccountId();

        $muxedBAccount = new MuxedAccount($accountBId, 1012929292);
        $muxedAAccount = new MuxedAccount($accountAId, 9999999999);
        $muxedCAccount = new MuxedAccount($accountCId, 5353535353);

        $createAccountBOperation = (new CreateAccountOperationBuilder($accountBId, "10"))
            ->setMuxedSourceAccount($muxedAAccount)->build();
        $createAccountCOperation = (new CreateAccountOperationBuilder($accountCId, "10"))
            ->setMuxedSourceAccount($muxedAAccount)->build();
        $accountA->setMuxedAccountMed25519Id(9999999999);
        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($createAccountBOperation)
            ->addOperation($createAccountCOperation)
            ->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $iomAsset = new AssetTypeCreditAlphanum4("IOM", $accountAId);
        $changeTrustBOperation = (new ChangeTrustOperationBuilder($iomAsset, "200999"))
            ->setMuxedSourceAccount($muxedBAccount)->build();
        $changeTrustCOperation = (new ChangeTrustOperationBuilder($iomAsset, "200999"))
            ->setMuxedSourceAccount($muxedCAccount)->build();
        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($changeTrustBOperation)
            ->addOperation($changeTrustCOperation)
            ->build();
        $transaction->sign($keyPairA, Network::testnet());
        $transaction->sign($keyPairB, Network::testnet());
        $transaction->sign($keyPairC, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        // send 100 IOM non native payment from A to C
        $paymentOperation = (PaymentOperationBuilder::forMuxedDestinationAccount($muxedCAccount, $iomAsset, "100"))->build();
        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($paymentOperation)
            ->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $found = false;
        $accountC = $sdk->requestAccount($accountCId);
        foreach($accountC->getBalances() as $balance) {
            if ($balance->getAssetCode() == "IOM") {
                $this->assertTrue(floatval($balance->getBalance()) > 90.00);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);

        // send 50.09 IOM non native payment from C to B
        $paymentOperation = (PaymentOperationBuilder::forMuxedDestinationAccount($muxedBAccount, $iomAsset, "50.9"))->build();
        $accountC->setMuxedAccountMed25519Id(5353535353);
        $transaction = (new TransactionBuilder($accountC))
            ->addOperation($paymentOperation)
            ->build();
        $transaction->sign($keyPairC, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $found = false;
        $accountB = $sdk->requestAccount($accountBId);
        foreach($accountC->getBalances() as $balance) {
            if ($balance->getAssetCode() == "IOM") {
                $this->assertTrue(floatval($balance->getBalance()) > 40.00);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testPaymentStrictSendReceive()
    {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPairA = KeyPair::random();
        $accountAId = $keyPairA->getAccountId();
        FriendBot::fundTestAccount($accountAId);
        $accountA = $sdk->requestAccount($accountAId);

        $keyPairB = KeyPair::random();
        $keyPairC = KeyPair::random();
        $keyPairD = KeyPair::random();
        $keyPairE = KeyPair::random();
        $accountBId = $keyPairB->getAccountId();
        $accountCId = $keyPairC->getAccountId();
        $accountDId = $keyPairD->getAccountId();
        $accountEId = $keyPairE->getAccountId();

        $transaction = (new TransactionBuilder($accountA))
            ->addOperation((new CreateAccountOperationBuilder($accountBId, "10"))->build())
            ->addOperation((new CreateAccountOperationBuilder($accountCId, "10"))->build())
            ->addOperation((new CreateAccountOperationBuilder($accountDId, "10"))->build())
            ->addOperation((new CreateAccountOperationBuilder($accountEId, "10"))->build())
            ->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $accountB = $sdk->requestAccount($accountBId);
        $accountC = $sdk->requestAccount($accountCId);
        $accountD = $sdk->requestAccount($accountDId);
        $accountE = $sdk->requestAccount($accountEId);

        $iomAsset = new AssetTypeCreditAlphanum4("IOM", $accountAId);
        $ecoAsset = new AssetTypeCreditAlphanum4("ECO", $accountAId);
        $moonAsset = new AssetTypeCreditAlphanum4("MOON", $accountAId);

        $ctIOMOp = new ChangeTrustOperationBuilder($iomAsset, "200999");
        $ctECOOp = new ChangeTrustOperationBuilder($ecoAsset, "200999");
        $ctMOONOp = new ChangeTrustOperationBuilder($moonAsset, "200999");

        $transaction = (new TransactionBuilder($accountC))->addOperation($ctIOMOp->build())->build();
        $transaction->sign($keyPairC, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $transaction = (new TransactionBuilder($accountB))
            ->addOperation($ctIOMOp->build())
            ->addOperation($ctECOOp->build())->build();
        $transaction->sign($keyPairB, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $transaction = (new TransactionBuilder($accountD))
            ->addOperation($ctECOOp->build())
            ->addOperation($ctMOONOp->build())->build();
        $transaction->sign($keyPairD, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $transaction = (new TransactionBuilder($accountE))->addOperation($ctMOONOp->build())->build();
        $transaction->sign($keyPairE, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $transaction = (new TransactionBuilder($accountA))
            ->addOperation((new PaymentOperationBuilder($accountCId, $iomAsset, "100"))->build())
            ->addOperation((new PaymentOperationBuilder($accountBId, $iomAsset, "100"))->build())
            ->addOperation((new PaymentOperationBuilder($accountBId, $ecoAsset, "100"))->build())
            ->addOperation((new PaymentOperationBuilder($accountDId, $moonAsset, "100"))->build())
            ->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $sellOfferOp = (new ManageSellOfferOperationBuilder($ecoAsset, $iomAsset, "100", "0.5"))->build();
        $transaction = (new TransactionBuilder($accountB))->addOperation($sellOfferOp)->build();
        $transaction->sign($keyPairB, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $sellOfferOp = (new ManageSellOfferOperationBuilder($moonAsset, $ecoAsset, "100", "0.5"))->build();
        $transaction = (new TransactionBuilder($accountD))->addOperation($sellOfferOp)->build();
        $transaction->sign($keyPairD, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $destinationAssets = [$moonAsset];
        $exceptionThrown = false;
        try {
            $strictSendPaths = $sdk->findStrictSendPaths()
                ->forSourceAsset($iomAsset)
                ->forSourceAmount("10")
                ->forDestinationAccount($accountEId)
                ->forDestinationAssets($destinationAssets)
                ->execute();
        } catch (RuntimeException $e) {
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown);
        sleep(3);
        $strictSendPaths = $sdk->findStrictSendPaths()
            ->forSourceAsset($iomAsset)
            ->forSourceAmount("10")
            ->forDestinationAccount($accountEId)
            ->execute();
        $this->assertTrue($strictSendPaths->getPaths()->count() > 0);
        $pathArr = array();
        foreach($strictSendPaths->getPaths() as $path) {
            $this->assertTrue(floatval($path->getDestinationAmount()) == 40);
            $this->assertTrue($path->getDestinationAssetType() == "credit_alphanum4");
            $this->assertTrue($path->getDestinationAssetCode() == "MOON");
            $this->assertTrue($path->getDestinationAssetIssuer() == $accountAId);
            $this->assertTrue(floatval($path->getSourceAmount()) == 10);
            $this->assertTrue($path->getSourceAssetType() == "credit_alphanum4");
            $this->assertTrue($path->getSourceAssetCode() == "IOM");
            $this->assertTrue($path->getDestinationAssetIssuer() == $accountAId);
            $this->assertTrue($path->getPath()->count() > 0);
            $found = false;
            foreach($path->getPath() as $pathAsset) {
                if (!$found && $pathAsset instanceof AssetTypeCreditAlphanum4 && $pathAsset->getCode() == $ecoAsset->getCode()) {
                    $found = true;
                }
                $this->assertTrue($found);
                array_push($pathArr, $pathAsset);
            }
            break;
        }

        $strictSendOp = (new PathPaymentStrictSendOperationBuilder($iomAsset, "10", $accountEId, $moonAsset, "38"))
            ->setPath($pathArr)
            ->build();
        $transaction = (new TransactionBuilder($accountC))->addOperation($strictSendOp)->build();
        $transaction->sign($keyPairC, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $found = false;
        $accountE = $sdk->requestAccount($accountEId);
        foreach($accountE->getBalances() as $balance) {
            if ($balance->getAssetType() != Asset::TYPE_NATIVE && $balance->getAssetCode() == "MOON") {
                $this->assertTrue(floatval($balance->getBalance()) > 39);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

}