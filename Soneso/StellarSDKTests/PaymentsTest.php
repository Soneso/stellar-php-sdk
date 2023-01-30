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
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\LedgerBounds;
use Soneso\StellarSDK\ManageDataOperationBuilder;
use Soneso\StellarSDK\ManageSellOfferOperationBuilder;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PathPaymentStrictReceiveOperationBuilder;
use Soneso\StellarSDK\PathPaymentStrictSendOperationBuilder;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Responses\Operations\CreateAccountOperationResponse;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\TransactionPreconditions;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDKTests\TestUtils;

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
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        // send 100 XLM payment form A to C
        $paymentOperation = (new PaymentOperationBuilder($accountCId, Asset::native(), "100"))->build();
        $transaction = (new TransactionBuilder($accountA))->addOperation($paymentOperation)->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

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

    public function testSendNativePaymentWithPreconditions(): void
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
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $precond = new TransactionPreconditions();
        $testTb = new TimeBounds((new DateTime)->setTimestamp(1652110741), (new DateTime)->setTimestamp(1752110741));
        $precond->setTimeBounds($testTb);
        $testLb =  new LedgerBounds(1,1892052);
        $precond->setLedgerBounds($testLb);
        sleep(6);
        $precond->setMinSeqAge(1);
        $precond->setMinSeqLedgerGap(1);
        $testSeqNr = $accountA->getSequenceNumber();
        $precond->setMinSeqNumber($testSeqNr);

        // send 100 XLM payment form A to C
        $paymentOperation = (new PaymentOperationBuilder($accountCId, Asset::native(), "100"))->build();
        $transaction = (new TransactionBuilder($accountA))->addOperation($paymentOperation)->setPreconditions($precond)->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $txHash = $response->getHash();
        //print($txHash);
        $trans = $sdk->requestTransaction($txHash);
        $this->assertNotNull($trans->getPreconditions());
        $conds = $trans->getPreconditions();
        $this->assertNotNull($conds->getTimeBounds());
        $this->assertEquals("1652110741", $conds->getTimeBounds()->getMinTime());
        $this->assertEquals("1752110741", $conds->getTimeBounds()->getMaxTime());
        $this->assertNotNull($conds->getLedgerBounds());
        $this->assertEquals(1, $conds->getLedgerBounds()->getMinLedger());
        $this->assertEquals(1892052, $conds->getLedgerBounds()->getMaxLedger());
        $this->assertEquals($testSeqNr, new BigInteger($conds->getMinAccountSequence()));
        $this->assertEquals("1", $conds->getMinAccountSequenceAge());
        $this->assertEquals(1, $conds->getMinAccountSequenceLedgerGap());

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

        $accountA = $sdk->requestAccount($accountAId);
        $this->assertNotNull($accountA->getSequenceLedger());
        $this->assertNotNull($accountA->getSequenceTime());
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
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

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
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

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
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

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
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

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
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        // send 100 IOM non native payment from A to C
        $paymentOperation = (new PaymentOperationBuilder($accountCId, $iomAsset, "100"))->build();
        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($paymentOperation)
            ->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

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
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

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
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

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
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        // send 100 IOM non native payment from A to C
        $paymentOperation = (PaymentOperationBuilder::forMuxedDestinationAccount($muxedCAccount, $iomAsset, "100"))->build();
        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($paymentOperation)
            ->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

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
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

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
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

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
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $transaction = (new TransactionBuilder($accountE))->addOperation($ctMOONOp->build())->build();
        $transaction->sign($keyPairE, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $transaction = (new TransactionBuilder($accountA))
            ->addOperation((new PaymentOperationBuilder($accountCId, $iomAsset, "100"))->build())
            ->addOperation((new PaymentOperationBuilder($accountBId, $iomAsset, "100"))->build())
            ->addOperation((new PaymentOperationBuilder($accountBId, $ecoAsset, "100"))->build())
            ->addOperation((new PaymentOperationBuilder($accountDId, $moonAsset, "100"))->build())
            ->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $sellOfferOp = (new ManageSellOfferOperationBuilder($ecoAsset, $iomAsset, "100", "0.5"))->build();
        $transaction = (new TransactionBuilder($accountB))->addOperation($sellOfferOp)->build();
        $transaction->sign($keyPairB, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $sellOfferOp = (new ManageSellOfferOperationBuilder($moonAsset, $ecoAsset, "100", "0.5"))->build();
        $transaction = (new TransactionBuilder($accountD))->addOperation($sellOfferOp)->build();
        $transaction->sign($keyPairD, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

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
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

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

        $sourceAssets = [$iomAsset];
        $exceptionThrown = false;
        try {
            $strictSendPaths = $sdk->findStrictReceivePaths()
                ->forDestinationAsset($moonAsset)
                ->forDestinationAmount("8")
                ->forSourceAccount($accountCId)
                ->forSourceAssets($sourceAssets)
                ->execute();
        } catch (RuntimeException $e) {
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown);
        sleep(3);
        $strictReceivePaths = $sdk->findStrictReceivePaths()
            ->forDestinationAsset($moonAsset)
            ->forDestinationAmount("8")
            ->forSourceAssets($sourceAssets)
            ->execute();
        $this->assertTrue($strictReceivePaths->getPaths()->count() > 0);

        $pathArr = array();
        foreach($strictReceivePaths->getPaths() as $path) {
            $this->assertTrue(floatval($path->getDestinationAmount()) == 8);
            $this->assertTrue($path->getDestinationAssetType() == "credit_alphanum4");
            $this->assertTrue($path->getDestinationAssetCode() == "MOON");
            $this->assertTrue($path->getDestinationAssetIssuer() == $accountAId);
            $this->assertTrue(floatval($path->getSourceAmount()) == 2);
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

        $strictReceiveOp = (new PathPaymentStrictReceiveOperationBuilder($iomAsset, "2", $accountEId, $moonAsset, "8"))
            ->setPath($pathArr)
            ->build();
        $transaction = (new TransactionBuilder($accountC))->addOperation($strictReceiveOp)->build();
        $transaction->sign($keyPairC, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $found = false;
        $accountE = $sdk->requestAccount($accountEId);
        foreach($accountE->getBalances() as $balance) {
            if ($balance->getAssetType() != Asset::TYPE_NATIVE && $balance->getAssetCode() == "MOON") {
                $this->assertTrue(floatval($balance->getBalance()) > 47);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testPaymentStrictSendReceiveMuxedAccounts()
    {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPairA = KeyPair::random();
        $accountAId = $keyPairA->getAccountId();
        FriendBot::fundTestAccount($accountAId);
        $accountA = $sdk->requestAccount($accountAId);

        $keyPairB = KeyPair::random();
        $keyPairC = KeyPair::random();
        $keyPairD = KeyPair::random();
        $accountBId = $keyPairB->getAccountId();
        $accountCId = $keyPairC->getAccountId();
        $accountDId = $keyPairD->getAccountId();

        $muxedAAccount = new MuxedAccount($accountAId, 111111111111);
        $muxedBAccount = new MuxedAccount($accountBId, 222222222222);
        $muxedCAccount = new MuxedAccount($accountCId, 333333333333);
        $muxedDAccount = new MuxedAccount($accountDId, 444444444444);

        $transaction = (new TransactionBuilder($accountA))
            ->addOperation((new CreateAccountOperationBuilder($accountBId, "10"))->setMuxedSourceAccount($muxedAAccount)->build())
            ->addOperation((new CreateAccountOperationBuilder($accountCId, "10"))->setMuxedSourceAccount($muxedAAccount)->build())
            ->addOperation((new CreateAccountOperationBuilder($accountDId, "10"))->setMuxedSourceAccount($muxedAAccount)->build())
            ->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $accountB = $sdk->requestAccount($accountBId);
        $accountC = $sdk->requestAccount($accountCId);
        $accountD = $sdk->requestAccount($accountDId);

        $iomAsset = new AssetTypeCreditAlphanum4("IOM", $accountAId);
        $ecoAsset = new AssetTypeCreditAlphanum4("ECO", $accountAId);

        $ctIOMOp = new ChangeTrustOperationBuilder($iomAsset, "200999");
        $ctECOOp = new ChangeTrustOperationBuilder($ecoAsset, "200999");

        $transaction = (new TransactionBuilder($accountC))->addOperation($ctIOMOp->build())->build();
        $transaction->sign($keyPairC, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $transaction = (new TransactionBuilder($accountB))
            ->addOperation($ctIOMOp->build())
            ->addOperation($ctECOOp->build())->build();
        $transaction->sign($keyPairB, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $transaction = (new TransactionBuilder($accountD))
            ->addOperation($ctECOOp->build())->build();
        $transaction->sign($keyPairD, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $transaction = (new TransactionBuilder($accountA))
            ->addOperation((PaymentOperationBuilder::forMuxedDestinationAccount($muxedCAccount, $iomAsset, "100"))->build())
            ->addOperation((PaymentOperationBuilder::forMuxedDestinationAccount($muxedBAccount, $iomAsset, "100"))->build())
            ->addOperation((PaymentOperationBuilder::forMuxedDestinationAccount($muxedBAccount, $ecoAsset, "100"))->build())
            ->addOperation((PaymentOperationBuilder::forMuxedDestinationAccount($muxedDAccount, $ecoAsset, "100"))->build())
            ->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $sellOfferOp = (new ManageSellOfferOperationBuilder($ecoAsset, $iomAsset, "30", "0.5"))
            ->setMuxedSourceAccount($muxedBAccount)->build();
        $transaction = (new TransactionBuilder($accountB))->addOperation($sellOfferOp)->build();
        $transaction->sign($keyPairB, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $strictSendOp = (PathPaymentStrictSendOperationBuilder::forMuxedDestinationAccount($iomAsset, "10", $muxedDAccount, $ecoAsset, "18"))
            ->setMuxedSourceAccount($muxedCAccount)
            ->build();
        $transaction = (new TransactionBuilder($accountC))->addOperation($strictSendOp)->build();
        $transaction->sign($keyPairC, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $found = false;
        $accountD = $sdk->requestAccount($accountDId);
        foreach($accountD->getBalances() as $balance) {
            if ($balance->getAssetType() != Asset::TYPE_NATIVE && $balance->getAssetCode() == "ECO") {
                $this->assertTrue(floatval($balance->getBalance()) > 19);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);

        $strictReceiveOp = (PathPaymentStrictReceiveOperationBuilder::forMuxedDestinationAccount($iomAsset, "2", $muxedDAccount, $ecoAsset, "3"))
            ->setMuxedSourceAccount($muxedCAccount)
            ->build();
        $transaction = (new TransactionBuilder($accountC))->addOperation($strictReceiveOp)->build();
        $transaction->sign($keyPairC, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $found = false;
        $accountD = $sdk->requestAccount($accountDId);
        foreach($accountD->getBalances() as $balance) {
            if ($balance->getAssetType() != Asset::TYPE_NATIVE && $balance->getAssetCode() == "ECO") {
                $this->assertTrue(floatval($balance->getBalance()) > 22);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testQueryPayments()
    {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPairA = KeyPair::random();
        $accountAId = $keyPairA->getAccountId();
        FriendBot::fundTestAccount($accountAId);
        $accountA = $sdk->requestAccount($accountAId);

        $keyPairB = KeyPair::random();
        $keyPairC = KeyPair::random();
        $keyPairD = KeyPair::random();
        $accountBId = $keyPairB->getAccountId();
        $accountCId = $keyPairC->getAccountId();
        $accountDId = $keyPairD->getAccountId();

        $transaction = (new TransactionBuilder($accountA))
            ->addOperation((new CreateAccountOperationBuilder($accountBId, "10"))->build())
            ->addOperation((new CreateAccountOperationBuilder($accountCId, "10"))->build())
            ->addOperation((new CreateAccountOperationBuilder($accountDId, "10"))->build())
            ->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $transaction = (new TransactionBuilder($accountA))
            ->addOperation((new PaymentOperationBuilder($accountBId, Asset::native(),"10"))->build())
            ->addOperation((new PaymentOperationBuilder($accountCId, Asset::native(),"10"))->build())
            ->addOperation((new PaymentOperationBuilder($accountDId, Asset::native(),"10"))->build())
            ->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $payments = $sdk->payments()->forAccount($accountAId)->order("desc")->execute();
        $this->assertTrue($payments->getOperations()->count() > 0);
        $createAccTransactionHash = "";
        $paymentTransactionHash = "";
        foreach($payments->getOperations() as $operation) {
            if ($operation instanceof PaymentOperationResponse) {
                $paymentTransactionHash = $operation->getTransactionHash();
            } else if ($operation instanceof CreateAccountOperationResponse) {
                $createAccTransactionHash = $operation->getTransactionHash();
            }
        }
        $this->assertTrue(strlen($createAccTransactionHash) > 0);
        $this->assertTrue(strlen($paymentTransactionHash) > 0);
        $payments = $sdk->payments()->forTransaction($createAccTransactionHash)->order("desc")->execute();
        $this->assertTrue($payments->getOperations()->count() > 0);
        $payments = $sdk->payments()->forTransaction($paymentTransactionHash)->order("desc")->execute();
        $this->assertTrue($payments->getOperations()->count() > 0);
        $transactionResponse = $sdk->requestTransaction($paymentTransactionHash);
        $this->assertNotNull($transactionResponse->getLedger());
        $payments = $sdk->payments()->forLedger(strval($transactionResponse->getLedger()))->order("desc")->execute();
        $this->assertTrue($payments->getOperations()->count() > 0);

    }

    public function testCheckMemoRequirements(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPairA = KeyPair::random();
        $accountAId = $keyPairA->getAccountId();
        $keyPairB = KeyPair::random();
        $accountBId = $keyPairB->getAccountId();

        FriendBot::fundTestAccount($accountAId);
        FriendBot::fundTestAccount($accountBId);
        $accountA = $sdk->requestAccount($accountAId);
        $accountB = $sdk->requestAccount($accountBId);

        $key = "config.memo_required";
        $value = "1";
        $manageDataOperation = (new ManageDataOperationBuilder($key, $value))->build();
        $transaction = (new TransactionBuilder($accountB))
            ->addOperation($manageDataOperation)
            ->build();

        $transaction->sign($keyPairB, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $paymentOperation = (new PaymentOperationBuilder($accountBId, Asset::native(), "100"))->build();
        $transaction = (new TransactionBuilder($accountA))->addOperation($paymentOperation)->build();

        $destination = $sdk->checkMemoRequired($transaction);

        $this->assertTrue($destination == $accountBId);
    }

    public function testIssue8(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPairA = KeyPair::random();
        $accountAId = $keyPairA->getAccountId();
        FriendBot::fundTestAccount($accountAId);
        $accountA = $sdk->requestAccount($accountAId);

        $keyPairC = KeyPair::random();
        $accountCId = $keyPairC->getAccountId();

        $cond = new TransactionPreconditions();
        $cond->setMinSeqNumber(new BigInteger(91891891));
        $cond->setMinSeqAge(181811);
        $cond->setMinSeqLedgerGap(1991);

        $createAccountOperation = (new CreateAccountOperationBuilder($accountCId, "10"))->build();
        $transaction = (new TransactionBuilder($accountA))->addOperation($createAccountOperation)->setPreconditions($cond)->build();
        $envelope = $transaction->toEnvelopeXdrBase64();

        $transaction2 = Transaction::fromEnvelopeBase64XdrString($envelope);
        if ($transaction2 instanceof  Transaction) {
            self::assertEquals($transaction2->getSourceAccount()->getAccountId(), $accountAId);
        } else {
            self::fail();
        }
    }
}