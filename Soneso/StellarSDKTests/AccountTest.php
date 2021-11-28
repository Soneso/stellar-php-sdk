<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\AccountMergeOperationBuilder;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\BumpSequenceOperationBuilder;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Price;
use Soneso\StellarSDK\SetOptionsOperation;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrSignerKey;
use Soneso\StellarSDK\Xdr\XdrSignerKeyType;

final class AccountTest extends TestCase
{
    private string $accountId = "GAZKB7OEYRUVL6TSBXI74D2IZS4JRCPBXJZ37MDDYAEYBOMHXUYIX5YL";

    public function testSetAccountOptions(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
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

        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($setOptionsOperation)
            ->addMemo(Memo::text("test set options"))
            ->build();

        $transaction->sign($keyPairA, Network::testnet());

        $response = $sdk->submitTransaction($transaction);
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

    public function testFindAccountforAsset(): void
    {
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

        $iomAsset = new AssetTypeCreditAlphanum4("IOM", $accountCId);

        $changeTrustOperation = (new ChangeTrustOperationBuilder($iomAsset, "200999"))->build();
        $accountA->incrementSequenceNumber();
        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($changeTrustOperation)
            ->build();
        $transaction->sign($keyPairA, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

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

    public function testExistingAccount(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $response = $sdk->requestAccount($this->accountId);
        $this->assertEquals($this->accountId, $response->getAccountId());
        $this->assertEquals("1429395180879874", $response->getSequenceNumber());
        $this->assertEquals(0, $response->getSubentryCount());
        $this->assertEquals(367594, $response->getLastModifiedLedger());
        $this->assertEquals("2021-10-07T18:01:40Z", $response->getLastModifiedTime());

        $thresholds = $response->getThresholds();
        $this->assertEquals(0, $thresholds->getLowThreshold());
        $this->assertEquals(0, $thresholds->getMedThreshold());
        $this->assertEquals(0, $thresholds->getHighThreshold());

        $flags = $response->getFlags();
        $this->assertEquals(false, $flags->isAuthRequired());
        $this->assertEquals(false, $flags->isAuthRevocable());
        $this->assertEquals(false, $flags->isAuthImmutable());
        $this->assertEquals(false, $flags->isAuthClawbackEnabled());

        $balances = $response->getBalances();
        $this->assertEquals(1, $balances->count());
        foreach ($balances as $balance) {
            $this->assertEquals("native", $balance->getAssetType());
            $this->assertEquals("9999.9999800", $balance->getBalance());
            $this->assertEquals("0.0000000", $balance->getBuyingLiabilities());
            $this->assertEquals(0.0000000, $balance->getSellingLiabilities());
        }

        $signers = $response->getSigners();
        $this->assertEquals(1, $signers->count());
        foreach ($signers as $signer) {
            $this->assertEquals(1, $signer->getWeight());
            $this->assertEquals($this->accountId, $signer->getKey());
            $this->assertEquals("ed25519_public_key", $signer->getType());
        }
        $this->assertEquals(0, $response->getNumSponsoring());
        $this->assertEquals(0, $response->getNumSponsored());
        $this->assertEquals($this->accountId, $response->getPagingToken());
        $this->assertNull($response->getSponsor());
        $this->assertNull($response->getHomeDomain());
        $this->assertNull($response->getInflationDestination());
    }

    public function testNotExistingAccount(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $response = null;
        try {
            $response = $sdk->accounts()->account("GBJLUCOKH54MR2J4XDONCMH6BCAG4C6PZLQ2QGZ25GUHWQJIGVWTKCXH");
        } catch (HorizonRequestException $e) {
            $this->assertEquals("404", $e->getStatusCode());
            $horizonErrorResponse = $e->getHorizonErrorResponse();
            $this->assertNotNull($horizonErrorResponse);
            $this->assertEquals("https://stellar.org/horizon-errors/not_found",$horizonErrorResponse->getType());
            $this->assertEquals("Resource Missing" ,$horizonErrorResponse->getTitle());
            $this->assertEquals(404 ,$horizonErrorResponse->getStatus());
            $this->assertEquals("The resource at the url requested was not found.  This usually occurs for one of two reasons:  The url requested is not valid, or no data in our database could be found with the parameters provided." ,$horizonErrorResponse->getDetail());
        }
        $this->assertNull($response);
    }
    public function testAccountsForAsset(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $asset = Asset::createNonNativeAsset("SONESO", "GAOF7ARG3ZAVUA63GCLXG5JQTMBAH3ZFYHGLGJLDXGDSXQRHD72LLGOB");

        $response = $sdk->accounts()->forAsset($asset)->limit(2)->order("asc")->execute();
        $this->assertNotNull($response);
        $this->assertNotNull($response->getLinks()->getNext());
        $this->assertNotNull($response->getLinks()->getPrev());
        $this->assertNotNull($response->getLinks()->getSelf());
        $this->assertGreaterThan(0, $response->getAccounts()->count());
        foreach ($response->getAccounts() as $account) {

            $this->assertGreaterThan(1, $account->getBalances()->count());
            $assetFound = false;
            foreach ($account->getBalances() as $balance) {
                if("SONESO" == $balance->getAssetCode()) {
                    $assetFound = true;
                    break;
                }
            }
            $this->assertEquals(true, $assetFound);
        }
    }

    public function testNewRandomAccount(): void {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPair = KeyPair::random();
        $acountId = $keyPair->getAccountId();
        FriendBot::fundTestAccount($acountId);
        $response = $sdk->requestAccount($acountId);
        $this->assertEquals($acountId, $response->getAccountId());
        $this->assertGreaterThan(0, strlen($response->getSequenceNumber()->toString()));
        $this->assertGreaterThan(0, strlen($response->getIncrementedSequenceNumber()->toString()));
    }

    public function testStrKeyAccount(): void
    {
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

    public function testAccountIDXdr(): void {
        $ac = "GC4PVZ2ZWIQKQVVMBCUSMAFGWKYY7HILA4PJIBS4FUQIFZV5F7R6EHRY";
        $xdr = XdrAccountID::fromAccountId($ac);
        $encoded = $xdr->encode();
        $xdr = XdrAccountID::decode(new XdrBuffer($encoded));
        $this->assertEquals($ac, $xdr->getAccountId());
    }

    public function testCreateNewAccount(): void {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPair = KeyPair::random();
        $this->assertNotNull($keyPair->getPrivateKey());
        $acountId = $keyPair->getAccountId();
        print(PHP_EOL. "1:".$acountId. ":" . $keyPair->getSecretSeed() . PHP_EOL);
        FriendBot::fundTestAccount($acountId);
        $response = $sdk->requestAccount($acountId);
        $this->assertEquals($acountId, $response->getAccountId());
        $this->assertGreaterThan(0, strlen($response->getSequenceNumber()->toString()));
        $this->assertGreaterThan(0, strlen($response->getIncrementedSequenceNumber()->toString()));

        $keyPair2 = KeyPair::random();
        $acountId2 = $keyPair2->getAccountId();
        print(PHP_EOL. "2:".$acountId2 . ":" . $keyPair2->getSecretSeed() . PHP_EOL);

        $builder = new TransactionBuilder($response);

        $createAccountOpBuilder = new CreateAccountOperationBuilder($acountId2, "120");
        $builder->addOperation($createAccountOpBuilder->build());
        $transaction = $builder->build();
        $transaction->sign($keyPair, Network::testnet());
        $submitTxResponse = $sdk->submitTransaction($transaction);
        $this->assertNotNull($submitTxResponse);
    }

    public function testBumpSequence(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPair = KeyPair::random();
        $this->assertNotNull($keyPair->getPrivateKey());
        $acountId = $keyPair->getAccountId();
        FriendBot::fundTestAccount($acountId);
        $response = $sdk->requestAccount($acountId);
        $this->assertEquals($acountId, $response->getAccountId());
        $bpOpBuilder = new BumpSequenceOperationBuilder($response->getSequenceNumber()->add(new BigInteger(100)));
        $builder = new TransactionBuilder($response);
        $builder->addOperation($bpOpBuilder->build());
        $transaction = $builder->build();
        $transaction->sign($keyPair, Network::testnet());
        $submitTxResponse = $sdk->submitTransaction($transaction);
        $this->assertNotNull($submitTxResponse);
    }

    public function testAccountMerge(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPair1 = KeyPair::random();
        $keyPair2 = KeyPair::random();
        $acountId1 = $keyPair1->getAccountId();
        $acountId2 = $keyPair2->getAccountId();
        FriendBot::fundTestAccount($acountId1);
        FriendBot::fundTestAccount($acountId2);
        $response = $sdk->requestAccount($acountId1);
        $this->assertEquals($acountId1, $response->getAccountId());
        $opBuilder = new AccountMergeOperationBuilder($acountId2);
        $builder = new TransactionBuilder($response);
        $builder->addOperation($opBuilder->build());
        $transaction = $builder->build();
        $transaction->sign($keyPair1, Network::testnet());
        $submitTxResponse = $sdk->submitTransaction($transaction);
        $this->assertNotNull($submitTxResponse);
    }
}

