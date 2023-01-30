<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Claimant;
use Soneso\StellarSDK\ClaimClaimableBalanceOperationBuilder;
use Soneso\StellarSDK\CreateClaimableBalanceOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Responses\Effects\ClaimableBalanceCreatedEffectResponse;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDKTests\TestUtils;

class ClaimableBalancesTest extends TestCase
{

    public function testClaimableBalance(): void
    {
        $sdk = StellarSDK::getTestNetInstance();

        $sourceAccountKeyPair = KeyPair::random();
        $sourceAccountId = $sourceAccountKeyPair->getAccountId();
        FriendBot::fundTestAccount($sourceAccountId);

        $firstClaimantKp = KeyPair::random();
        $fistClaimantId = $firstClaimantKp->getAccountId();
        $secondClaimantKp = KeyPair::random();

        $firstClaimant = new Claimant($fistClaimantId, Claimant::predicateUnconditional());
        $predicateA = Claimant::predicateBeforeRelativeTime(100);
        $predicateB = Claimant::predicateBeforeAbsoluteTime(1634000400);
        $predicateC = Claimant::predicateNot($predicateA);
        $predicateD = Claimant::predicateAnd($predicateC, $predicateB);
        $predicateE = Claimant::predicateBeforeAbsoluteTime(1601671345);
        $predicateF = Claimant::predicateOr($predicateD, $predicateE);
        $secondClaimant = new Claimant($secondClaimantKp->getAccountId(), $predicateF);
        $claimants = array();
        array_push($claimants, $firstClaimant);
        array_push($claimants, $secondClaimant);
        $opb = new CreateClaimableBalanceOperationBuilder($claimants, Asset::native(), "12.33");

        $sourceAccount = $sdk->requestAccount($sourceAccountId);
        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($opb->build())->build();

        $transaction->sign($sourceAccountKeyPair, Network::testnet());

        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $requestBuilder = $sdk->effects()->forAccount($sourceAccountId)->limit(5)->order("desc");
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getEffects()->count() > 0);

        $bId = "";
        foreach ($response->getEffects() as $effect) {
            if ($effect instanceof ClaimableBalanceCreatedEffectResponse) {
                $bId = $effect->getBalanceId();
                break;
            }
        }
        $this->assertNotEquals("", $bId);
        print($bId . PHP_EOL);
        $requestBuilder = $sdk->claimableBalances()->forClaimant($fistClaimantId);
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getClaimableBalances()->count() > 0);

        $cb = $response->getClaimableBalances()->toArray()[0];
        FriendBot::fundTestAccount($fistClaimantId);

        $opc = new ClaimClaimableBalanceOperationBuilder($cb->getBalanceId());
        $claimant = $sdk->requestAccount($fistClaimantId);
        $transaction = (new TransactionBuilder($claimant))
            ->addOperation($opc->build())->build();

        $transaction->sign($firstClaimantKp, Network::testnet());

        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);
    }
}