<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace StellarSDKTests;

use Exception;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum12;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\ManageBuyOfferOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Responses\Effects\EffectResponse;
use Soneso\StellarSDK\Responses\Ledger\LedgerResponse;
use Soneso\StellarSDK\Responses\Operations\CreateAccountOperationResponse;
use Soneso\StellarSDK\Responses\Operations\OperationResponse;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use Soneso\StellarSDK\Responses\Transaction\TransactionResponse;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDKTests\TestUtils;

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
        $submitResponse = $sdk->submitTransaction($transaction);
        $this->assertTrue($submitResponse->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $submitResponse);

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
        $submitResponse = $sdk->submitTransaction($transaction);
        $this->assertTrue($submitResponse->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $submitResponse);

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

    public function testQueryForClaimableBalance(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        // get balance id from ClaimableBalancesTest
        $bId = "00000000983412d3194c218af78d0e863f1c88c25c0a091e3301bf2e60554ee06e346aa4";
        $response = $sdk->operations()->forClaimableBalance($bId)->limit(1)->order("desc")->execute();
        $this->assertTrue($response->getOperations()->count() == 1);
        $response = $sdk->transactions()->forClaimableBalance($bId)->limit(1)->order("desc")->execute();
        $this->assertTrue($response->getTransactions()->count() == 1);
    }

    public function testQueryLedgers(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $response = $sdk->ledgers()->limit(1)->order("desc")->execute();
        $this->assertTrue($response->getLedgers()->count() == 1);
        $seq = $response->getLedgers()->toArray()[0]->getSequence()->toString();
        $response = $sdk->requestLedger($seq);
        $this->assertEquals($response->getSequence()->toString(), $seq);
    }

    public function testQueryOffersAndOrderBook(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $issuerKp = KeyPair::random();
        $issuerAccountId = $issuerKp->getAccountId();
        $buyerKp = KeyPair::random();
        $buyerAccountId = $buyerKp->getAccountId();

        FriendBot::fundTestAccount($buyerAccountId);
        $buyerAccount = $sdk->requestAccount($buyerAccountId);
        $createAccount = (new CreateAccountOperationBuilder($issuerAccountId, "100"))->build();
        $transaction = (new TransactionBuilder($buyerAccount))->addOperation($createAccount)->build();
        $transaction->sign($buyerKp, Network::testnet());
        $submitResponse = $sdk->submitTransaction($transaction);
        $this->assertTrue($submitResponse->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $submitResponse);

        $astroDollar = new AssetTypeCreditAlphanum12("ASTRO", $issuerAccountId);
        $ctob = (new ChangeTrustOperationBuilder($astroDollar, "10000"))->build();
        $transaction = (new TransactionBuilder($buyerAccount))->addOperation($ctob)->build();
        $transaction->sign($buyerKp, Network::testnet());
        $submitResponse = $sdk->submitTransaction($transaction);
        $this->assertTrue($submitResponse->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $submitResponse);

        $amountBuying = "100";
        $price = "0.5";

        $ms = (new ManageBuyOfferOperationBuilder(Asset::native(),$astroDollar, $amountBuying, $price))->build();
        $transaction = (new TransactionBuilder($buyerAccount))->addOperation($ms)->build();
        $transaction->sign($buyerKp, Network::testnet());
        $submitResponse = $sdk->submitTransaction($transaction);
        $this->assertTrue($submitResponse->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $submitResponse);

        $response = $sdk->offers()->forAccount($buyerAccountId)->execute();
        $this->assertTrue($response->getOffers()->count() == 1);
        $offer = $response->getOffers()->toArray()[0];
        $this->assertTrue($offer->getBuying()->getCode() == $astroDollar->getCode());
        $this->assertTrue($offer->getSelling()->getType() == Asset::TYPE_NATIVE);
        $offerAmount = floatval($offer->getAmount());
        $offerPrice = floatval($offer->getPrice());

        $r = round($offerPrice * $offerAmount);
        $this->assertTrue($r == intval($amountBuying));
        $this->assertTrue($offer->getSeller() == $buyerAccountId);

        $offers = $sdk->offers()->forBuyingAsset($astroDollar)->execute()->getOffers();
        $this->assertTrue($offers->count() == 1);
        $offer2 = $response->getOffers()->toArray()[0];
        $this->assertEquals($offer->getOfferId(), $offer2->getOfferId());

        $response = $sdk->orderBook()->forBuyingAsset($astroDollar)->forSellingAsset(Asset::native())->limit(1)->execute();
        $offerAmount = floatval($response->getAsks()->toArray()[0]->getAmount());
        $offerPrice = floatval($response->getAsks()->toArray()[0]->getPrice());
        $r = round($offerPrice * $offerAmount);
        $this->assertTrue($r == intval($amountBuying));
        $this->assertTrue($response->getCounter()->getCode() == $astroDollar->getCode());
        $this->assertTrue($response->getBase()->getType() == Asset::TYPE_NATIVE);
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

    public function testPaging(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $response = $sdk->ledgers()->execute();
        $this->assertTrue($response->getLedgers()->count() > 0);
        $next = $response->getNextPage();
        $this->assertNotNull($next);
        $this->assertTrue($next->getLedgers()->count() > 0);
        $prev = $next->getPreviousPage();
        $this->assertNotNull($prev);
        $this->assertTrue($prev->getLedgers()->count() > 0);
        $count = $prev->getLedgers()->count();
        $this->assertEquals($response->getLedgers()->toArray()[0]->getHash(), $prev->getLedgers()->toArray()[$count - 1]->getHash());

        $response = $sdk->transactions()->execute();
        $this->assertTrue($response->getTransactions()->count() > 0);
        $next = $response->getNextPage();
        $this->assertNotNull($next);
        $this->assertTrue($next->getTransactions()->count() > 0);
        $prev = $next->getPreviousPage();
        $this->assertNotNull($prev);
        $this->assertTrue($prev->getTransactions()->count() > 0);
        $count = $prev->getTransactions()->count();
        $this->assertEquals($response->getTransactions()->toArray()[0]->getHash(), $prev->getTransactions()->toArray()[$count - 1]->getHash());

        $response = $sdk->operations()->execute();
        $this->assertTrue($response->getOperations()->count() > 0);
        $next = $response->getNextPage();
        $this->assertNotNull($next);
        $this->assertTrue($next->getOperations()->count() > 0);
        $prev = $next->getPreviousPage();
        $this->assertNotNull($prev);
        $this->assertTrue($prev->getOperations()->count() > 0);
        $count = $prev->getOperations()->count();
        $this->assertEquals($response->getOperations()->toArray()[0]->getOperationId(), $prev->getOperations()->toArray()[$count - 1]->getOperationId());
    }


    public function testStreamPayments():void {
        $sdk = StellarSDK::getTestNetInstance();
        $keypair1 = KeyPair::random();
        $keypair2 = KeyPair::random();
        $acc1Id = $keypair1->getAccountId();
        $acc2Id = $keypair2->getAccountId();
        FriendBot::fundTestAccount($acc1Id);
        FriendBot::fundTestAccount($acc2Id);

        $pid = pcntl_fork();

        if ($pid == 0) {
            $sdk->payments()->forAccount($acc2Id)->cursor("now")->stream(function(OperationResponse $payment) {
                printf('Payment operation %s id %s' . PHP_EOL, get_class($payment), $payment->getOperationId());
                if ($payment instanceof PaymentOperationResponse && floatval($payment->getAmount()) == 100.00) {
                    exit(1);
                }
            });
        }

        $acc1 = $sdk->requestAccount($acc1Id);
        $paymentOperation = (new PaymentOperationBuilder($acc2Id, Asset::native(), "100"))->build();
        $transaction = (new TransactionBuilder($acc1))
            ->addOperation($paymentOperation)
            ->build();
        $transaction->sign($keypair1, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        while (pcntl_waitpid(0, $status) != -1) {
            $status = pcntl_wexitstatus($status);
            echo "Completed with status: $status \n";
            $this->assertTrue($status == 1);
        }
    }

    public function testStreamOperations(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $found = false;
        try {
            $sdk->operations()->cursor("now")->stream(function(OperationResponse $operation) {
                printf('Operation id %s' . PHP_EOL, $operation->getOperationId());
                if ($operation instanceof CreateAccountOperationResponse) {
                    throw new Exception("stop");
                }
            });
        } catch (Exception $e) {
            if ($e->getMessage() == "stop") {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    public function testStreamLedgers(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $found = false;
        try {
            $sdk->ledgers()->cursor("now")->stream(function(LedgerResponse $ledger) {
                printf('Ledger sequence %s' . PHP_EOL, $ledger->getSequence()->toString());
                throw new Exception("stop");
            });
        } catch (Exception $e) {
            if ($e->getMessage() == "stop") {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    //

    public function testStreamTransactions(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $found = false;
        try {
            $sdk->transactions()->cursor("now")->stream(function(TransactionResponse $transaction) {
                printf('transaction hash %s' . PHP_EOL, $transaction->getHash());
                throw new Exception("stop");
            });
        } catch (Exception $e) {
            if ($e->getMessage() == "stop") {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    public function testStreamEffects(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $found = false;
        try {
            $sdk->effects()->cursor("now")->stream(function(EffectResponse $effect) {
                printf('Effect id %s' . PHP_EOL, $effect->getEffectId());
                throw new Exception("stop");
            });
        } catch (Exception $e) {
            if ($e->getMessage() == "stop") {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }
}

