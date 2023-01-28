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
use Soneso\StellarSDK\PathPaymentStrictSendOperationBuilder;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Price;
use Soneso\StellarSDK\Responses\Effects\LiquidityPoolCreatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\LiquidityPoolDepositedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\LiquidityPoolWithdrewEffectResponse;
use Soneso\StellarSDK\Responses\Effects\TrustlineCreatedEffectResponse;
use Soneso\StellarSDK\Responses\Operations\ChangeTrustOperationResponse;
use Soneso\StellarSDK\Responses\Operations\LiquidityPoolDepositOperationResponse;
use Soneso\StellarSDK\Responses\Operations\LiquidityPoolWithdrawOperationResponse;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDKTests\TestUtils;

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
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);
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
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

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
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);
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
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

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
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        // TEST WITHDRAW  NATIVE
        $op = (new LiquidityPoolWithdrawOperationBuilder($nativeLiquidityPoolId, "1", "1","1"))->build();
        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($op)->build();
        $transaction->sign($sourceAccountKeyPair, Network::testnet());

        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        // QUERY TESTING

        $requestBuilder = $sdk->effects()->forLiquidityPool($nonNativeLiquidityPoolId)->limit(4)->order("asc");
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getEffects()->count() == 4);
        $effectsArray = $response->getEffects()->toArray();
        $this->assertTrue($effectsArray[0] instanceof TrustlineCreatedEffectResponse);
        $this->assertTrue($effectsArray[1] instanceof LiquidityPoolCreatedEffectResponse);
        $this->assertTrue($effectsArray[2] instanceof LiquidityPoolDepositedEffectResponse);
        $this->assertTrue($effectsArray[3] instanceof LiquidityPoolWithdrewEffectResponse);

        $requestBuilder = $sdk->transactions()->forLiquidityPool($nonNativeLiquidityPoolId)->limit(1)->order("asc");
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getTransactions()->count() == 1);

        $tr  = $response->getTransactions()->toArray()[0];
        $requestBuilder = $sdk->effects()->forTransaction($tr->getHash())->limit(3)->order("asc");
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getEffects()->count() > 0);

        $requestBuilder = $sdk->operations()->forLiquidityPool($nonNativeLiquidityPoolId)->limit(3)->order("asc");
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getOperations()->count() == 3);
        $operationsArray = $response->getOperations()->toArray();
        $this->assertTrue($operationsArray[0] instanceof ChangeTrustOperationResponse);
        $this->assertTrue($operationsArray[1] instanceof LiquidityPoolDepositOperationResponse);
        $this->assertTrue($operationsArray[2] instanceof LiquidityPoolWithdrawOperationResponse);

        $lp = $sdk->requestLiquidityPool($nonNativeLiquidityPoolId);
        $this->assertTrue($lp->getFee() == 30);
        $this->assertTrue($lp->getPoolId() == $nonNativeLiquidityPoolId);

        $requestBuilder = $sdk->transactions()->forLiquidityPool($nonNativeLiquidityPoolId)->limit(1)->order("asc");
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getTransactions()->count() == 1);


        $accXKp = KeyPair::random();
        $accXId = $accXKp->getAccountId();
        $accYKp = KeyPair::random();
        $accYId = $accYKp->getAccountId();

        FriendBot::fundTestAccount($accXId);
        FriendBot::fundTestAccount($accYId);

        $accX = $sdk->requestAccount($accXId);
        $ctOpB1 = (new ChangeTrustOperationBuilder($assetA, "98398398293"))->setSourceAccount($accXId)->build();
        $ctOpB2 = (new ChangeTrustOperationBuilder($assetB, "98398398293"))->setSourceAccount($accYId)->build();

        $transaction = (new TransactionBuilder($accX))
            ->addOperation($ctOpB1)->addOperation($ctOpB2)->build();
        $transaction->sign($accXKp, Network::testnet());
        $transaction->sign($accYKp, Network::testnet());

        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $pop1 = (new PaymentOperationBuilder($accXId, $assetA, "19999191"))->setSourceAccount($assetAIssueAccountId)->build();
        $sourceAccount = $sdk->requestAccount($assetAIssueAccountId);
        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($pop1)->build();
        $transaction->sign($assetAIssueAccountKeyPair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $opb = new PathPaymentStrictSendOperationBuilder($assetA,"10", $accYId, $assetB, "1");
        $transaction = (new TransactionBuilder($accX))
            ->addOperation($opb->build())->build();
        $transaction->sign($accXKp, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $requestBuilder = $sdk->trades()->forLiquidityPool($nonNativeLiquidityPoolId)->order("asc");
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getTrades()->count() > 0);
        $this->assertTrue($response->getTrades()->toArray()[0]->getBaseLiquidityPoolId() == $nonNativeLiquidityPoolId);
    }
}