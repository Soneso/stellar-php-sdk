<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\AssetTypeCreditAlphanum12;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;

class QueryTest extends TestCase
{
    public function testQueryAccounts(): void
    {

        $sdk = StellarSDK::getTestNetInstance();
        $accountKeyPair = KeyPair::random();
        $accountId = $accountKeyPair->getAccountId();
        FriendBot::fundTestAccount($accountId);
        $account = $sdk->requestAccount($accountId);
        $requestBuilder = $sdk->accounts()->forSigner($accountId);
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getAccounts()->toArray()[0]->getAccountId() == $accountId);

        $testKeyPairs = array();
        array_push($testKeyPairs, KeyPair::random());
        array_push($testKeyPairs, KeyPair::random());
        array_push($testKeyPairs, KeyPair::random());

        $issuerKp = KeyPair::random();
        $issuerAccountId = $issuerKp->getAccountId();

        $createAccount = (new CreateAccountOperationBuilder($issuerAccountId, "100"))->build();
        $transactionBuilder = (new TransactionBuilder($account))
            ->addOperation($createAccount);

        foreach ($testKeyPairs as $kp) {
            $createAccount = (new CreateAccountOperationBuilder($kp->getAccountId(), "100"))->build();
            $transactionBuilder->addOperation($createAccount);
            $sop = (new SetOptionsOperationBuilder())->setSourceAccount($kp->getAccountId())->setSigner($accountKeyPair->getXdrSignerKey(), 1)->build();
            $transactionBuilder->addOperation($sop);
        }

        $transaction = $transactionBuilder->build();
        $transaction->sign($accountKeyPair, Network::testnet());
        foreach ($testKeyPairs as $kp) {
            $transaction->sign($kp, Network::testnet());
        }
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $requestBuilder = $sdk->accounts()->forSigner($accountId);
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getAccounts()->count() == 4);

        $requestBuilder = $sdk->accounts()->forSigner($accountId)->limit(2)->order("desc");
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getAccounts()->count() == 2);

        $astroDollar = new AssetTypeCreditAlphanum12("ASTRO", $issuerAccountId);
        $ct = (new ChangeTrustOperationBuilder($astroDollar, "20000"))->setSourceAccount($accountId)->build();
        $transactionBuilder = (new TransactionBuilder($account))->addOperation($ct);

        foreach ($testKeyPairs as $kp) {
            $ct = (new ChangeTrustOperationBuilder($astroDollar, "20000"))->setSourceAccount($kp->getAccountId())->build();
            $transactionBuilder->addOperation($ct);
        }

        $transaction = $transactionBuilder->build();
        $transaction->sign($accountKeyPair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        $requestBuilder = $sdk->accounts()->forAsset($astroDollar);
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getAccounts()->count() == 4);

        $requestBuilder = $sdk->accounts()->forAsset($astroDollar)->limit(2)->order("desc");;
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getAccounts()->count() == 2);
    }

    public function testQueryAssets(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $response = $sdk->assets()->forAssetCode("ASTRO")->limit(5)->order("desc")->execute();
        $this->assertTrue($response->getAssets()->count() > 0);
        $this->assertTrue($response->getAssets()->count() < 6);
        $issuer = $response->getAssets()->toArray()[0]->getAssetIssuer();
        $response = $sdk->assets()->forAssetIssuer($issuer)->limit(5)->order("desc")->execute();
        $this->assertTrue($response->getAssets()->count() > 0);
        $this->assertTrue($response->getAssets()->count() < 6);
    }

    public function testQueryEffects(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $response = $sdk->assets()->forAssetCode("USD")->limit(5)->order("desc")->execute();
        $this->assertTrue($response->getAssets()->count() > 0);
        $this->assertTrue($response->getAssets()->count() < 6);
        $issuer = $response->getAssets()->toArray()[0]->getAssetIssuer();
        $response = $sdk->effects()->forAccount($issuer)->limit(3)->order("asc")->execute();
        $this->assertTrue($response->getEffects()->count() > 0);
        $this->assertTrue($response->getEffects()->count() < 4);
        $response = $sdk->ledgers()->limit(1)->order("desc")->execute();
        $this->assertTrue($response->getLedgers()->count() == 1);
        $ledgerSeq = $response->getLedgers()->toArray()[0]->getSequence();
        $response = $sdk->effects()->forLedger($ledgerSeq->toString())->limit(3)->order("asc")->execute();
        $this->assertTrue($response->getEffects()->count() > 0);
        $response = $sdk->transactions()->forLedger($ledgerSeq->toString())->limit(1)->order("desc")->execute();
        $this->assertTrue($response->getTransactions()->count() > 0);
        $trHash = $response->getTransactions()->toArray()[0]->getHash();
        $response = $sdk->effects()->forTransaction($trHash)->limit(3)->order("asc")->execute();
        $this->assertTrue($response->getEffects()->count() > 0);
        $response = $sdk->operations()->forLedger($ledgerSeq->toString())->limit(10)->order("desc")->execute();
        $this->assertTrue($response->getOperations()->count() > 0);
        $found = false;
        foreach ($response->getOperations() as $op) {
            $opId = $op->getOperationId();
            $response = $sdk->effects()->forOperation($opId)->limit(3)->order("asc")->execute();
            if ($response->getEffects()->count() > 0) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testQueryRoot(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $response = $sdk->root();
        $this->assertGreaterThan(17, $response->getCurrentProtocolVersion());
        $this->assertGreaterThan(17, $response->getCoreSupportedProtocolVersion());
        $this->assertTrue(str_starts_with($response->getHorizonVersion(),"2"));
        $this->asserttrue(str_starts_with($response->getCoreVersion(), "stellar-core"));
        $this->assertGreaterThan(0, $response->getIngestLatestLedger());
        $this->assertGreaterThan(0, $response->getHistoryLatestLedger());
        $this->assertNotNull($response->getHistoryLatestLedgerClosedAt());
        $this->assertNotNull($response->getHistoryElderLedger());
        $this->assertNotNull($response->getCoreLatestLedger());
        $this->assertEquals("Test SDF Network ; September 2015", $response->getNetworkPassphrase());
    }

    public function testQueryFeeStats(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $response = $sdk->requestFeeStats();
        $this->assertGreaterThan(0,strlen($response->getLastLedger()));
        $this->assertGreaterThan(0,strlen($response->getLastLedgerBaseFee()));
        $this->assertGreaterThan(0,strlen($response->getLedgerCapacityUsage()));
        $feeCharged = $response->getFeeCharged();
        $this->assertGreaterThan(0,strlen($feeCharged->getMax()));
        $this->assertGreaterThan(0,strlen($feeCharged->getMin()));
        $this->assertGreaterThan(0,strlen($feeCharged->getMode()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP10()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP20()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP30()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP40()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP50()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP60()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP70()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP80()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP90()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP95()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP99()));
        $maxFee = $response->getMaxFee();
        $this->assertGreaterThan(0,strlen($maxFee->getMax()));
        $this->assertGreaterThan(0,strlen($maxFee->getMin()));
        $this->assertGreaterThan(0,strlen($maxFee->getMode()));
        $this->assertGreaterThan(0,strlen($maxFee->getP10()));
        $this->assertGreaterThan(0,strlen($maxFee->getP20()));
        $this->assertGreaterThan(0,strlen($maxFee->getP30()));
        $this->assertGreaterThan(0,strlen($maxFee->getP40()));
        $this->assertGreaterThan(0,strlen($maxFee->getP50()));
        $this->assertGreaterThan(0,strlen($maxFee->getP60()));
        $this->assertGreaterThan(0,strlen($maxFee->getP70()));
        $this->assertGreaterThan(0,strlen($maxFee->getP80()));
        $this->assertGreaterThan(0,strlen($maxFee->getP90()));
        $this->assertGreaterThan(0,strlen($maxFee->getP95()));
        $this->assertGreaterThan(0,strlen($maxFee->getP99()));
    }
}

