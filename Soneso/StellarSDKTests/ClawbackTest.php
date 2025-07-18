<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\AccountFlag;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\Claimant;
use Soneso\StellarSDK\ClawbackClaimableBalanceOperationBuilder;
use Soneso\StellarSDK\ClawbackOperationBuilder;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\CreateClaimableBalanceOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Responses\Effects\ClaimableBalanceClawedBackEffectResponse;
use Soneso\StellarSDK\Responses\Effects\TrustlineFlagsUpdatedEffectResponse;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\SetTrustLineFlagsOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDK\Util\FuturenetFriendBot;
use Soneso\StellarSDK\Xdr\XdrTrustLineFlags;

class ClawbackTest extends TestCase
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
    public function testClawbackAndClaimableBalanceClawback(): void
    {

        $masterAccountKeyPair = KeyPair::random();
        $masterAccountId = $masterAccountKeyPair->getAccountId();
        if ($this->testOn == 'testnet') {
            FriendBot::fundTestAccount($masterAccountId);
        } elseif($this->testOn == 'futurenet') {
            FuturenetFriendBot::fundTestAccount($masterAccountId);
        }

        $destinationAccountKeyPair = KeyPair::random();
        $destinationAccountId = $destinationAccountKeyPair->getAccountId();

        $masterAccount = $this->sdk->requestAccount($masterAccountId);
        $createAccountBuilder = new CreateAccountOperationBuilder($destinationAccountId, "100");
        $transaction = (new TransactionBuilder($masterAccount))
            ->addOperation($createAccountBuilder->build())->build();

        $transaction->sign($masterAccountKeyPair, $this->network);

        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $skyIssuerAccountKeyPair = KeyPair::random();
        $skyIssuerAccountId = $skyIssuerAccountKeyPair->getAccountId();

        $createAccountBuilder = new CreateAccountOperationBuilder($skyIssuerAccountId, "100");
        $transaction = (new TransactionBuilder($masterAccount))
            ->addOperation($createAccountBuilder->build())->build();

        $transaction->sign($masterAccountKeyPair, $this->network);

        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $skyIssuerAccount = $this->sdk->requestAccount($skyIssuerAccountId);
        $setOp = (new SetOptionsOperationBuilder())->setSetFlags(AccountFlag::AUTH_CLAWBACK_ENABLED_FLAG | AccountFlag::AUTH_REVOCABLE_FLAG)->build();
        $transaction = (new TransactionBuilder($skyIssuerAccount))
            ->addOperation($setOp)->build();
        $transaction->sign($skyIssuerAccountKeyPair, $this->network);
        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $sky = new AssetTypeCreditAlphanum4("SKY", $skyIssuerAccountId);
        $limit = "10000";
        $destinationAccount = $this->sdk->requestAccount($destinationAccountId);
        $ctOp = (new ChangeTrustOperationBuilder($sky, $limit))->build();
        $transaction = (new TransactionBuilder($destinationAccount))
            ->addOperation($ctOp)->build();
        $transaction->sign($destinationAccountKeyPair, $this->network);
        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $payOp = (new PaymentOperationBuilder($destinationAccountId, $sky, "100"))->build();
        $transaction = (new TransactionBuilder($skyIssuerAccount))
            ->addOperation($payOp)->build();
        $transaction->sign($skyIssuerAccountKeyPair, $this->network);
        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $destinationAccount = $this->sdk->requestAccount($destinationAccountId);
        $found = false;
        foreach($destinationAccount->getBalances() as $balance) {
            if ($balance->getAssetType() != Asset::TYPE_NATIVE && $balance->getAssetCode() == "SKY") {
                $this->assertTrue(floatval($balance->getBalance()) > 99);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);

        $destMuxed = MuxedAccount::fromAccountId($destinationAccountId);
        $clawbackOp = (new ClawbackOperationBuilder($sky, $destMuxed, "80"))->build();
        $transaction = (new TransactionBuilder($skyIssuerAccount))
            ->addOperation($clawbackOp)->build();
        $transaction->sign($skyIssuerAccountKeyPair, $this->network);
        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $destinationAccount = $this->sdk->requestAccount($destinationAccountId);
        $found = false;
        foreach($destinationAccount->getBalances() as $balance) {
            if ($balance->getAssetType() != Asset::TYPE_NATIVE && $balance->getAssetCode() == "SKY") {
                $this->assertTrue(floatval($balance->getBalance()) < 30);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);

        $claimantAccountKeyPair = KeyPair::random();
        $claimantAccountId = $claimantAccountKeyPair->getAccountId();
        $createAccountBuilder = new CreateAccountOperationBuilder($claimantAccountId, "100");
        $transaction = (new TransactionBuilder($masterAccount))
            ->addOperation($createAccountBuilder->build())->build();

        $transaction->sign($masterAccountKeyPair, $this->network);

        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $claimantAccount = $this->sdk->requestAccount($claimantAccountId);
        $transaction = (new TransactionBuilder($claimantAccount))
            ->addOperation($ctOp)->build();
        $transaction->sign($claimantAccountKeyPair, $this->network);
        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $enabled = false;
        foreach($destinationAccount->getBalances() as $balance) {
            if ($balance->getAssetType() != Asset::TYPE_NATIVE && $balance->getAssetCode() == "SKY"
            && $balance->getIsClawbackEnabled()) {
                $enabled = true;
                break;
            }
        }
        $this->assertTrue($enabled);

        $claimant = new Claimant($claimantAccountId, Claimant::predicateUnconditional());
        $claimants = array();
        array_push($claimants, $claimant);
        $cCBOp = (new CreateClaimableBalanceOperationBuilder($claimants, $sky, "10.00"))->build();
        $transaction = (new TransactionBuilder($destinationAccount))
            ->addOperation($cCBOp)->build();
        $transaction->sign($destinationAccountKeyPair, $this->network);
        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $requestBuilder = $this->sdk->claimableBalances()->forClaimant($claimantAccountId);
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getClaimableBalances()->count() > 0);

        $cb = $response->getClaimableBalances()->toArray()[0];
        $balanceId = $cb->getBalanceId();
        $clawbackOp = (new ClawbackClaimableBalanceOperationBuilder($balanceId))->build();
        $transaction = (new TransactionBuilder($skyIssuerAccount))
            ->addOperation($clawbackOp)->build();
        $transaction->sign($skyIssuerAccountKeyPair, $this->network);
        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $requestBuilder = $this->sdk->claimableBalances()->forClaimant($claimantAccountId);
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getClaimableBalances()->count() == 0);


        $requestBuilder = $this->sdk->effects()->forAccount($skyIssuerAccountId)->limit(5)->order("desc");
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getEffects()->count() > 0);

        $bId = "";
        foreach ($response->getEffects() as $effect) {
            if ($effect instanceof ClaimableBalanceClawedBackEffectResponse) {
                $bId = $effect->getBalanceId();
                break;
            }
        }
        $this->assertNotEquals("", $bId);

        // clear trustline clawback enabled flag
        $setTrustlineFlagsOp = (new SetTrustLineFlagsOperationBuilder($claimantAccountId, $sky, XdrTrustLineFlags::TRUSTLINE_CLAWBACK_ENABLED_FLAG, 0))->build();
        $transaction = (new TransactionBuilder($skyIssuerAccount))
            ->addOperation($setTrustlineFlagsOp)->build();
        $transaction->sign($skyIssuerAccountKeyPair, $this->network);
        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $requestBuilder = $this->sdk->effects()->forAccount($skyIssuerAccountId)->limit(5)->order("desc");
        $response = $requestBuilder->execute();
        $this->assertTrue($response->getEffects()->count() > 0);

        $ok = false;
        foreach ($response->getEffects() as $effect) {
            if ($effect instanceof TrustLineFlagsUpdatedEffectResponse && !$effect->getClawbackEnabledFlag()) {
                $ok = true;
                break;
            }
        }
        $this->assertTrue($ok);

        $claimantAccount = $this->sdk->requestAccount($claimantAccountId);
        $ok = false;
        foreach($claimantAccount->getBalances() as $balance) {
            if ($balance->getAssetType() != Asset::TYPE_NATIVE && $balance->getAssetCode() == "SKY"
                && !$balance->getIsClawbackEnabled()) {
                $ok = true;
                break;
            }
        }
        $this->assertTrue($ok);
    }
}