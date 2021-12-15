<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum12;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\AssetTypePoolShare;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\LiquidityPoolDepositOperationBuilder;
use Soneso\StellarSDK\LiquidityPoolWithdrawOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Price;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;

class AmmTest extends TestCase
{
    public function testAmm(): void
    {

        // SET UP TEST
        $sdk = StellarSDK::getTestNetInstance();

        $sourceAccountKeyPair = KeyPair::random();
        $sourceAccountId = $sourceAccountKeyPair->getAccountId();

        $assetAIssueAccountKeyPair = KeyPair::random();
        $assetAIssueAccountId = $assetAIssueAccountKeyPair->getAccountId();
        $assetBIssueAccountKeyPair = KeyPair::random();
        $assetBIssueAccountId = $assetBIssueAccountKeyPair->getAccountId();

        $assetA= new AssetTypeCreditAlphanum4("SDK", $assetAIssueAccountId);
        $assetB= new AssetTypeCreditAlphanum12("PHPSTAR", $assetBIssueAccountId);
        $assetNative = Asset::native();

        FriendBot::fundTestAccount($sourceAccountId);
        FriendBot::fundTestAccount($assetAIssueAccountId);
        FriendBot::fundTestAccount($assetBIssueAccountId);

        $sourceAccount = $sdk->requestAccount($sourceAccountId);

        $ctOpB1 = (new ChangeTrustOperationBuilder($assetA, "98398398293"))->build();
        $ctOpB2 = (new ChangeTrustOperationBuilder($assetB, "98398398293"))->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($ctOpB1)->addOperation($ctOpB2)->build();
        $transaction->sign($sourceAccountKeyPair, Network::testnet());

        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $pop1 = (new PaymentOperationBuilder($sourceAccountId, $assetA, "19999191"))->setSourceAccount($assetAIssueAccountId)->build();
        $pop2 = (new PaymentOperationBuilder($sourceAccountId, $assetB, "19999191"))->setSourceAccount($assetBIssueAccountId)->build();

        $sourceAccount = $sdk->requestAccount($assetAIssueAccountId);

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($pop1)->addOperation($pop2)->build();
        $transaction->sign($assetAIssueAccountKeyPair, Network::testnet());
        $transaction->sign($assetBIssueAccountKeyPair, Network::testnet());

        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        // TEST CREATE POOL SHARE TRUSTLINE NON NATIVE
        $sourceAccount = $sdk->requestAccount($sourceAccountId);
        $poolShareAsset = new AssetTypePoolShare($assetA, $assetB);
        $chOp = (new ChangeTrustOperationBuilder($poolShareAsset, "98398398293"))->build();
        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($chOp)->build();
        $transaction->sign($sourceAccountKeyPair, Network::testnet());

        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $requestBuilder = $sdk->liquidityPools()->forReserves(Asset::canonicalForm($assetA),
            Asset::canonicalForm($assetB))->limit(4)->order("asc");
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getLiquidityPools()->count() > 0);

        $nonNativeLiquidityPoolId = $response->getLiquidityPools()->toArray()[0]->getPoolId();

        // TEST CREATE POOL SHARE TRUSTLINE NATIVE
        $poolShareAsset = new AssetTypePoolShare($assetNative, $assetB);
        $chOp = (new ChangeTrustOperationBuilder($poolShareAsset, "98398398293"))->build();
        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($chOp)->build();
        $transaction->sign($sourceAccountKeyPair, Network::testnet());

        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        $requestBuilder = $sdk->liquidityPools()->forReserves(Asset::canonicalForm($assetNative),
            Asset::canonicalForm($assetB))->limit(4)->order("asc");
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getLiquidityPools()->count() > 0);

        $nativeLiquidityPoolId = $response->getLiquidityPools()->toArray()[0]->getPoolId();

        // TEST DEPOSIT NON NATIVE
        $op = (new LiquidityPoolDepositOperationBuilder($nonNativeLiquidityPoolId,"250.0","250.0",
            Price::fromString("1.0"),Price::fromString("2.0")))->build();
        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($op)->build();
        $transaction->sign($sourceAccountKeyPair, Network::testnet());

        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        // TEST DEPOSIT NATIVE
        $op = (new LiquidityPoolDepositOperationBuilder($nativeLiquidityPoolId,"250.0","250.0",
            Price::fromString("1.0"),Price::fromString("2.0")))->build();
        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($op)->build();
        $transaction->sign($sourceAccountKeyPair, Network::testnet());

        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        // TEST WITHDRAW NON NATIVE
        $op = (new LiquidityPoolWithdrawOperationBuilder($nonNativeLiquidityPoolId, "100", "100","100"))->build();
        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($op)->build();
        $transaction->sign($sourceAccountKeyPair, Network::testnet());

        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        // TEST WITHDRAW  NATIVE
        $op = (new LiquidityPoolWithdrawOperationBuilder($nativeLiquidityPoolId, "1", "1","1"))->build();
        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($op)->build();
        $transaction->sign($sourceAccountKeyPair, Network::testnet());

        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
    }
}