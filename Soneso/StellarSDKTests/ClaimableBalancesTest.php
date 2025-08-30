<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Claimant;
use Soneso\StellarSDK\ClaimClaimableBalanceOperationBuilder;
use Soneso\StellarSDK\CreateClaimableBalanceOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Responses\Effects\ClaimableBalanceCreatedEffectResponse;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDK\Util\FuturenetFriendBot;

class ClaimableBalancesTest extends TestCase
{

    private string $testOn = 'testnet'; // 'futurenet'
    private Network $network;
    private StellarSDK $sdk;

    public function setUp(): void
    {
        if ($this->testOn === 'testnet') {
            $this->network = Network::testnet();
            $this->sdk = StellarSDK::getTestNetInstance();
        } elseif ($this->testOn === 'futurenet') {
            $this->network = Network::futurenet();
            $this->sdk = StellarSDK::getFutureNetInstance();
        }
    }
    public function testClaimableBalance(): void
    {

        $sourceAccountKeyPair = KeyPair::random();
        $sourceAccountId = $sourceAccountKeyPair->getAccountId();
        if ($this->testOn == 'testnet') {
            FriendBot::fundTestAccount($sourceAccountId);
        } elseif($this->testOn == 'futurenet') {
            FuturenetFriendBot::fundTestAccount($sourceAccountId);
        }

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

        $sourceAccount = $this->sdk->requestAccount($sourceAccountId);
        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($opb->build())->build();

        $transaction->sign($sourceAccountKeyPair, $this->network);

        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $requestBuilder = $this->sdk->effects()->forAccount($sourceAccountId)->limit(5)->order("desc");
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
        print(StrKey::encodeClaimableBalanceIdHex($bId) . PHP_EOL);
        $requestBuilder = $this->sdk->claimableBalances()->forClaimant($fistClaimantId);
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getClaimableBalances()->count() > 0);

        $cb = $response->getClaimableBalances()->toArray()[0];
        if ($this->testOn == 'testnet') {
            FriendBot::fundTestAccount($fistClaimantId);
        } elseif($this->testOn == 'futurenet') {
            FuturenetFriendBot::fundTestAccount($fistClaimantId);
        }
        // test also strkey claimable balance id
        $strKeyBalanceId = StrKey::encodeClaimableBalanceIdHex($cb->getBalanceId());
        $opc = new ClaimClaimableBalanceOperationBuilder($strKeyBalanceId);
        $claimant = $this->sdk->requestAccount($fistClaimantId);
        $transaction = (new TransactionBuilder($claimant))
            ->addOperation($opc->build())->build();

        $transaction->sign($firstClaimantKp, $this->network);

        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);
    }
}