<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use Exception;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\AccountMergeOperationBuilder;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\BumpSequenceOperationBuilder;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\ManageDataOperationBuilder;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse;
use Soneso\StellarSDK\SetOptionsOperation;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDK\Xdr\XdrSignerKey;
use Soneso\StellarSDK\Xdr\XdrSignerKeyType;
use Soneso\StellarSDK\Xdr\XdrTransactionMeta;

final class AccountTest extends TestCase
{

    public function testSetAccountOptions(): void {
        $sdk = StellarSDK::getTestNetInstance();
        $isValid = true;
        try {
            KeyPair::fromAccountId("GBEJWZEYDCJIKBW7PZKIJPRHD6WSPNETCEDV5UWRLDBLKXA7QT2DTLVF");
        } catch (Exception $e) {
            $isValid = false;
        }
        if ($isValid) {
            self::fail();
        }
        $keyPairA = KeyPair::random();
        $accountId = $keyPairA->getAccountId();
        FriendBot::fundTestAccount($accountId);
        $accountA = $sdk->requestAccount($accountId);
        $seqNr = $accountA->getSequenceNumber();

        $keyPairB = KeyPair::random();
        $bkey = new XdrSignerKey();
        $bkey->setType(new XdrSignerKeyType(XdrSignerKeyType::ED25519));
        $bkey->setEd25519($keyPairB->getPublicKey());

        $newHomeDomain = "www".rand(1, 10000).".com";

        $setOptionsOperation = (new SetOptionsOperationBuilder())
            ->setHomeDomain($newHomeDomain)
            ->setSigner($bkey, 6)
            ->setHighThreshold(5)
            ->setMediumThreshold(3)
            ->setLowThreshold(2)
            ->setMasterKeyWeight(5)
            ->setSetFlags(2)
            ->build();

        // test issue #7
        $xdrTest = $setOptionsOperation->toXdr();
        $setOpTest = SetOptionsOperation::fromXdr($xdrTest);
        self::assertEquals($setOptionsOperation->getHomeDomain(), $setOpTest->getHomeDomain());

        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($setOptionsOperation)
            ->addMemo(Memo::text("test set options"))
            ->build();

        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);

        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);


        $this->assertTrue($response->isSuccessful());

        $accountA = $sdk->requestAccount($accountId);
        $this->assertTrue($accountA->getSequenceNumber() > $seqNr);
        $this->assertTrue($accountA->getHomeDomain() === $newHomeDomain);
        $this->assertTrue($accountA->getThresholds()->getLowThreshold() === 2);
        $this->assertTrue($accountA->getThresholds()->getMedThreshold() === 3);
        $this->assertTrue($accountA->getThresholds()->getHighThreshold() === 5);

        $aFound = false;
        $bFound = false;
        foreach($accountA->getSigners() as $signer) {
            if ($signer->getKey() == $accountA->getAccountId()) {
                $aFound = true;
                $this->assertTrue($signer->getWeight() === 5);
            }
            else if ($signer->getKey() == $keyPairB->getAccountId()) {
                $bFound = true;
                $this->assertTrue($signer->getWeight() === 6);
            }
        }
        $this->assertTrue($aFound);
        $this->assertTrue($bFound);

        $this->assertTrue($accountA->getFlags()->isAuthRequired() == false);
        $this->assertTrue($accountA->getFlags()->isAuthRevocable() == true);
        $this->assertTrue($accountA->getFlags()->isAuthImmutable() == false);
    }

    public function testFindAccountforAsset(): void {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPairA = KeyPair::random();
        $accountAId = $keyPairA->getAccountId();
        FriendBot::fundTestAccount($accountAId);
        $accountA = $sdk->requestAccount($accountAId);

        $keyPairC = KeyPair::random();
        $accountCId = $keyPairC->getAccountId();

        $createAccountOperation = (new CreateAccountOperationBuilder($accountCId, "10"))->build();
        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($createAccountOperation)
            ->build();

        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $iomAsset = new AssetTypeCreditAlphanum4("IOM", $accountCId);

        $changeTrustOperation = (new ChangeTrustOperationBuilder($iomAsset, "200999"))->build();
        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($changeTrustOperation)
            ->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        // Find account for asset
        $response = $sdk->accounts()->forAsset($iomAsset)->execute();
        $this->assertGreaterThan(0, $response->getAccounts()->count());
        $found = false;
        foreach ($response->getAccounts() as $account) {
            $this->assertTrue($account->getAccountId() === $accountAId);
            $found = true;
        }
        $this->assertTrue($found);
    }

    public function testAccountMerge(): void {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPairX = KeyPair::random();
        $keyPairY = KeyPair::random();
        $accountXId = $keyPairX->getAccountId();
        $accountYId = $keyPairY->getAccountId();
        FriendBot::fundTestAccount($accountXId);
        FriendBot::fundTestAccount($accountYId);

        $accountMergeOperation = (new AccountMergeOperationBuilder($accountXId))->build();
        $accountY = $sdk->requestAccount($accountYId);
        $transaction = (new TransactionBuilder($accountY))
            ->addOperation($accountMergeOperation)
            ->build();

        $transaction->sign($keyPairY, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $this->assertFalse($sdk->accountExists($accountYId));
    }

    public function testAccountMergeMuxedAccounts(): void {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPairX = KeyPair::random();
        $keyPairY = KeyPair::random();
        $accountXId = $keyPairX->getAccountId();
        $accountYId = $keyPairY->getAccountId();
        FriendBot::fundTestAccount($accountXId);
        FriendBot::fundTestAccount($accountYId);

        $muxedDestination = new MuxedAccount($accountXId, 1919198222);
        $muxedSource = new MuxedAccount($accountYId, 99999999);
        $accountMergeOperation = (AccountMergeOperationBuilder::forMuxedDestinationAccount($muxedDestination))
            ->setMuxedSourceAccount($muxedSource)
            ->build();

        $accountY = $sdk->requestAccount($accountYId);
        $transaction = (new TransactionBuilder($accountY))
            ->addOperation($accountMergeOperation)
            ->build();

        $transaction->sign($keyPairY, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $this->assertFalse($sdk->accountExists($accountYId));
    }

    public function testBumpSequence(): void {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPair = KeyPair::random();
        $acountId = $keyPair->getAccountId();
        FriendBot::fundTestAccount($acountId);

        $account = $sdk->requestAccount($acountId);

        $seqNr = $account->getSequenceNumber();
        $bumpTo = $seqNr->add(new BigInteger(10));
        $bumpSequenceOperation = (new BumpSequenceOperationBuilder($bumpTo))->build();
        $transaction = (new TransactionBuilder($account))
            ->addOperation($bumpSequenceOperation)
            ->build();

        $transaction->sign($keyPair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $account = $sdk->requestAccount($acountId);
        $this->assertEquals($bumpTo, $account->getSequenceNumber());
    }

    public function testManageData(): void {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPair = KeyPair::random();
        $acountId = $keyPair->getAccountId();
        FriendBot::fundTestAccount($acountId);

        $account = $sdk->requestAccount($acountId);

        $key = "soneso";
        $value = "is cool!";
        $manageDataOperation = (new ManageDataOperationBuilder($key, $value))->build();
        $transaction = (new TransactionBuilder($account))
            ->addOperation($manageDataOperation)
            ->build();

        $transaction->sign($keyPair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $account = $sdk->requestAccount($acountId);
        $this->assertTrue($account->getData()->get($key) === $value);
    }

    public function testStrKeyAccount(): void {
        $accountId = "GA5SRA3BGOEN6ASL33AVTC2QV7G2PV3DU4A3VDMPEIEZVF2H4Z5YV6CC";
        $tb = StrKey::decodeAccountId($accountId);
        $bt = StrKey::encodeAccountId($tb);
        $this->assertEquals($accountId, $bt);

        $muxAccountId = "MA5SRA3BGOEN6ASL33AVTC2QV7G2PV3DU4A3VDMPEIEZVF2H4Z5YUAAAAAAACL7RNP5CM";
        $tb = StrKey::decodeMuxedAccountId($muxAccountId);
        $bt = StrKey::encodeMuxedAccountId($tb);
        $this->assertEquals($muxAccountId, $bt);
    }

    public function testMuxedAccount(): void {
        $accountId = "GA5SRA3BGOEN6ASL33AVTC2QV7G2PV3DU4A3VDMPEIEZVF2H4Z5YV6CC";
        $id = 19919211;
        $muxAccount = MuxedAccount::fromAccountId($accountId);
        $this->assertEquals($accountId, $muxAccount->getAccountId());

        $muxAccountId = "MA5SRA3BGOEN6ASL33AVTC2QV7G2PV3DU4A3VDMPEIEZVF2H4Z5YUAAAAAAACL7RNP5CM";
        $muxAccount = MuxedAccount::fromAccountId($muxAccountId);
        $this->assertEquals($muxAccountId, $muxAccount->getAccountId());
        $muxAccount = new MuxedAccount($accountId, $id);
        $this->assertEquals($muxAccountId, $muxAccount->getAccountId());
    }

    public function testIssue15(): void
    {
        $sdk = StellarSDK::getTestNetInstance();

        $keyPairA = KeyPair::random();
        $accountId = $keyPairA->getAccountId();
        FriendBot::fundTestAccount($accountId);
        $accountA = $sdk->requestAccount($accountId);
        
        $zero = 0;
        $setOptionsOperation = (new SetOptionsOperationBuilder())
            ->setHighThreshold($zero)
            ->setMediumThreshold($zero)
            ->setLowThreshold($zero)
            ->setMasterKeyWeight($zero)
            ->build();

        self::assertSame(0, $setOptionsOperation->getMasterKeyWeight());
        self::assertSame(0, $setOptionsOperation->getMediumThreshold());
        self::assertSame(0, $setOptionsOperation->getLowThreshold());
        self::assertSame(0, $setOptionsOperation->getHighThreshold());

        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($setOptionsOperation)
            ->build();

        $transaction->sign($keyPairA, Network::testnet());

        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
    }
}

