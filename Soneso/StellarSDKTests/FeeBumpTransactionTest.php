<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\FeeBumpTransactionBuilder;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDKTests\TestUtils;

class FeeBumpTransactionTest extends TestCase
{
    public function testFeeBumpTransaction(): void
    {
        $sdk = StellarSDK::getTestNetInstance();

        $sourceKeyPair = KeyPair::random();
        $sourceId = $sourceKeyPair->getAccountId();
        FriendBot::fundTestAccount($sourceId);

        $destinationKeyPair = KeyPair::random();
        $destinationId = $destinationKeyPair->getAccountId();

        $payerKeyPair = KeyPair::random();
        $payerId = $payerKeyPair->getAccountId();
        FriendBot::fundTestAccount($payerId);

        $sourceAccount = $sdk->requestAccount($sourceId);
        $createAccountOp = (new CreateAccountOperationBuilder($destinationId, "10"))->build();
        $innerTx = (new TransactionBuilder($sourceAccount))
            ->addOperation($createAccountOp)
            ->build();
        $innerTx->sign($sourceKeyPair, Network::testnet());

        $feeBump = (new FeeBumpTransactionBuilder($innerTx))->setBaseFee(200)->setFeeAccount($payerId)->build();
        $feeBump->sign($payerKeyPair, Network::testnet());
        $response = $sdk->submitTransaction($feeBump);
        $this->assertTrue($response->isSuccessful());

        TestUtils::resultDeAndEncodingTest($this, $feeBump, $response);

        $found = false;
        $destinationAccount = $sdk->requestAccount($destinationId);
        foreach($destinationAccount->getBalances() as $balance) {
            if ($balance->getAssetType() == Asset::TYPE_NATIVE) {
                $this->assertTrue(floatval($balance->getBalance()) > 9.0);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);

        $transaction = $sdk->requestTransaction($response->getHash());
        $this->assertNotNull($transaction);
        $feeBumpTransaction = $transaction->getFeeBumpTransactionResponse();
        $this->assertNotNull($feeBumpTransaction);
        $innerTransaction = $transaction->getInnerTransactionResponse();
        $this->assertNotNull($innerTransaction);
        $transaction = $sdk->requestTransaction($transaction->getInnerTransactionResponse()->getHash());
        $this->assertEquals($transaction->getSourceAccount(), $sourceId);
    }

    public function testFeeBumpTransactionMuxedAccounts(): void
    {
        $sdk = StellarSDK::getTestNetInstance();

        $sourceKeyPair = KeyPair::random();
        $sourceId = $sourceKeyPair->getAccountId();
        FriendBot::fundTestAccount($sourceId);

        $destinationKeyPair = KeyPair::random();
        $destinationId = $destinationKeyPair->getAccountId();

        $payerKeyPair = KeyPair::random();
        $payerId = $payerKeyPair->getAccountId();
        FriendBot::fundTestAccount($payerId);

        $muxedSourceAccount = new MuxedAccount($sourceId, 97839283928292);
        $muxedPayerAccount = new MuxedAccount($payerId, 24242423737333);

        $sourceAccount = $sdk->requestAccount($sourceId);
        $createAccountOp = (new CreateAccountOperationBuilder($destinationId, "10"))->setMuxedSourceAccount($muxedSourceAccount)->build();
        $innerTx = (new TransactionBuilder($sourceAccount))
            ->addOperation($createAccountOp)
            ->build();
        $innerTx->sign($sourceKeyPair, Network::testnet());

        $feeBump = (new FeeBumpTransactionBuilder($innerTx))->setBaseFee(200)->setMuxedFeeAccount($muxedPayerAccount)->build();
        $feeBump->sign($payerKeyPair, Network::testnet());
        $response = $sdk->submitTransaction($feeBump);
        $this->assertTrue($response->isSuccessful());

        TestUtils::resultDeAndEncodingTest($this, $feeBump, $response);

        $found = false;
        $destinationAccount = $sdk->requestAccount($destinationId);
        foreach($destinationAccount->getBalances() as $balance) {
            if ($balance->getAssetType() == Asset::TYPE_NATIVE) {
                $this->assertTrue(floatval($balance->getBalance()) > 9.0);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);

        $transaction = $sdk->requestTransaction($response->getHash());
        $this->assertNotNull($transaction);
        $feeBumpTransaction = $transaction->getFeeBumpTransactionResponse();
        $this->assertNotNull($feeBumpTransaction);
        $innerTransaction = $transaction->getInnerTransactionResponse();
        $this->assertNotNull($innerTransaction);
        $transaction = $sdk->requestTransaction($transaction->getInnerTransactionResponse()->getHash());
        $this->assertEquals($transaction->getSourceAccount(), $sourceId);
    }
}