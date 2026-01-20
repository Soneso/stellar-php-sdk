<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use PHPUnit\Framework\TestCase;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrAccountEntry;
use Soneso\StellarSDK\Xdr\XdrAccountEntryExt;
use Soneso\StellarSDK\Xdr\XdrAccountEntryV1;
use Soneso\StellarSDK\Xdr\XdrAccountEntryV1Ext;
use Soneso\StellarSDK\Xdr\XdrAccountEntryV2;
use Soneso\StellarSDK\Xdr\XdrAccountEntryV2Ext;
use Soneso\StellarSDK\Xdr\XdrMuxedAccount;
use Soneso\StellarSDK\Xdr\XdrMuxedAccountMed25519;
use Soneso\StellarSDK\Xdr\XdrSigner;
use Soneso\StellarSDK\Xdr\XdrSignerKey;
use Soneso\StellarSDK\Xdr\XdrSignerKeyType;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;
use Soneso\StellarSDK\Xdr\XdrLiabilities;
use Soneso\StellarSDK\Xdr\XdrBuffer;

class XdrAccountTest extends TestCase
{
    private const TEST_ACCOUNT_ID = "GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H";
    private const TEST_ACCOUNT_ID_2 = "GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ";

    /**
     * Test XdrAccountID encode/decode round-trip
     */
    public function testXdrAccountIDRoundTrip(): void
    {
        $original = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrAccountID::decode(new XdrBuffer($encoded));

        // Verify account ID matches
        $this->assertEquals($original->getAccountId(), $decoded->getAccountId());
        $this->assertEquals(self::TEST_ACCOUNT_ID, $decoded->getAccountId());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrAccountID with different account IDs
     */
    public function testXdrAccountIDMultipleAccounts(): void
    {
        $accounts = [
            self::TEST_ACCOUNT_ID,
            self::TEST_ACCOUNT_ID_2,
            "GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ",
        ];

        foreach ($accounts as $accountId) {
            $xdrAccountId = XdrAccountID::fromAccountId($accountId);
            $encoded = $xdrAccountId->encode();
            $decoded = XdrAccountID::decode(new XdrBuffer($encoded));

            $this->assertEquals($accountId, $decoded->getAccountId());
        }
    }

    /**
     * Test XdrMuxedAccount with ed25519
     */
    public function testXdrMuxedAccountEd25519RoundTrip(): void
    {
        // Create ed25519 key (32 bytes)
        $ed25519Key = str_repeat("\x01", 32);

        $original = new XdrMuxedAccount($ed25519Key, null);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrMuxedAccount::decode(new XdrBuffer($encoded));

        // Verify fields match
        $this->assertEquals($original->getDiscriminant(), $decoded->getDiscriminant());
        $this->assertEquals($original->getEd25519(), $decoded->getEd25519());
        $this->assertNull($decoded->getMed25519());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrMuxedAccount with med25519
     */
    public function testXdrMuxedAccountMed25519RoundTrip(): void
    {
        $ed25519Key = str_repeat("\x02", 32);
        $id = 12345;

        $med25519 = new XdrMuxedAccountMed25519($id, $ed25519Key);
        $original = new XdrMuxedAccount(null, $med25519);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrMuxedAccount::decode(new XdrBuffer($encoded));

        // Verify fields match
        $this->assertEquals($original->getDiscriminant(), $decoded->getDiscriminant());
        $this->assertNull($decoded->getEd25519());
        $this->assertNotNull($decoded->getMed25519());
        $this->assertEquals($id, $decoded->getMed25519()->getId());
        $this->assertEquals($ed25519Key, $decoded->getMed25519()->getEd25519());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrMuxedAccountMed25519 encode/decode round-trip
     */
    public function testXdrMuxedAccountMed25519DirectRoundTrip(): void
    {
        $ed25519Key = str_repeat("\x03", 32);
        $id = 9876543210;

        $original = new XdrMuxedAccountMed25519($id, $ed25519Key);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrMuxedAccountMed25519::decode(new XdrBuffer($encoded));

        // Verify fields match
        $this->assertEquals($original->getId(), $decoded->getId());
        $this->assertEquals($original->getEd25519(), $decoded->getEd25519());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSigner encode/decode round-trip
     */
    public function testXdrSignerRoundTrip(): void
    {
        $signerKey = new XdrSignerKey();
        $signerKey->setType(new XdrSignerKeyType(XdrSignerKeyType::ED25519));
        $signerKey->setEd25519(str_repeat("\x04", 32));

        $weight = 100;
        $original = new XdrSigner($signerKey, $weight);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrSigner::decode(new XdrBuffer($encoded));

        // Verify fields match
        $this->assertEquals($original->getWeight(), $decoded->getWeight());
        $this->assertEquals($original->getKey()->getType()->getValue(), $decoded->getKey()->getType()->getValue());
        $this->assertEquals($original->getKey()->getEd25519(), $decoded->getKey()->getEd25519());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSignerKey with different types
     */
    public function testXdrSignerKeyDifferentTypes(): void
    {
        // Test ED25519
        $signerKey = new XdrSignerKey();
        $signerKey->setType(new XdrSignerKeyType(XdrSignerKeyType::ED25519));
        $signerKey->setEd25519(str_repeat("\x05", 32));

        $encoded = $signerKey->encode();
        $decoded = XdrSignerKey::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSignerKeyType::ED25519, $decoded->getType()->getValue());
        $this->assertEquals(str_repeat("\x05", 32), $decoded->getEd25519());
        $this->assertNull($decoded->getPreAuthTx());
        $this->assertNull($decoded->getHashX());

        // Test PRE_AUTH_TX
        $signerKey = new XdrSignerKey();
        $signerKey->setType(new XdrSignerKeyType(XdrSignerKeyType::PRE_AUTH_TX));
        $signerKey->setPreAuthTx(str_repeat("\x06", 32));

        $encoded = $signerKey->encode();
        $decoded = XdrSignerKey::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSignerKeyType::PRE_AUTH_TX, $decoded->getType()->getValue());
        $this->assertEquals(str_repeat("\x06", 32), $decoded->getPreAuthTx());
        $this->assertNull($decoded->getEd25519());
        $this->assertNull($decoded->getHashX());

        // Test HASH_X
        $signerKey = new XdrSignerKey();
        $signerKey->setType(new XdrSignerKeyType(XdrSignerKeyType::HASH_X));
        $signerKey->setHashX(str_repeat("\x07", 32));

        $encoded = $signerKey->encode();
        $decoded = XdrSignerKey::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSignerKeyType::HASH_X, $decoded->getType()->getValue());
        $this->assertEquals(str_repeat("\x07", 32), $decoded->getHashX());
        $this->assertNull($decoded->getEd25519());
        $this->assertNull($decoded->getPreAuthTx());
    }

    /**
     * Test XdrAccountEntry encode/decode round-trip
     */
    public function testXdrAccountEntryRoundTrip(): void
    {
        $accountID = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $balance = new BigInteger(1000000000);
        $seqNum = new XdrSequenceNumber(new BigInteger(12345678));
        $numSubEntries = 5;
        $inflationDest = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID_2);
        $flags = 3;
        $homeDomain = "example.com";
        $thresholds = "\x01\x02\x03\x04";

        $signer = new XdrSigner(
            $this->createSignerKey(XdrSignerKeyType::ED25519, str_repeat("\x08", 32)),
            50
        );
        $signers = [$signer];

        $ext = new XdrAccountEntryExt(0, null);

        $original = new XdrAccountEntry(
            $accountID,
            $balance,
            $seqNum,
            $numSubEntries,
            $inflationDest,
            $flags,
            $homeDomain,
            $thresholds,
            $signers,
            $ext
        );

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrAccountEntry::decode(new XdrBuffer($encoded));

        // Verify all fields match
        $this->assertEquals($original->getAccountID()->getAccountId(), $decoded->getAccountID()->getAccountId());
        $this->assertEquals($original->getBalance()->toString(), $decoded->getBalance()->toString());
        $this->assertEquals($original->getSeqNum()->getValue()->toString(), $decoded->getSeqNum()->getValue()->toString());
        $this->assertEquals($original->getNumSubEntries(), $decoded->getNumSubEntries());
        $this->assertEquals($original->getInflationDest()->getAccountId(), $decoded->getInflationDest()->getAccountId());
        $this->assertEquals($original->getFlags(), $decoded->getFlags());
        $this->assertEquals($original->getHomeDomain(), $decoded->getHomeDomain());
        $this->assertEquals($original->getThresholds(), $decoded->getThresholds());
        $this->assertCount(count($original->getSigners()), $decoded->getSigners());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrAccountEntry with extension V1
     */
    public function testXdrAccountEntryWithExtensionV1RoundTrip(): void
    {
        $accountID = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $balance = new BigInteger(2000000000);
        $seqNum = new XdrSequenceNumber(new BigInteger(87654321));
        $numSubEntries = 3;
        $flags = 1;
        $homeDomain = "stellar.org";
        $thresholds = "\x0A\x14\x1E\x28";
        $signers = [];

        // Create extension V1
        $liabilities = new XdrLiabilities(
            new BigInteger(100000),
            new BigInteger(200000)
        );
        $v1Ext = new XdrAccountEntryV1Ext(0, null);
        $v1 = new XdrAccountEntryV1($liabilities, $v1Ext);
        $ext = new XdrAccountEntryExt(1, $v1);

        $original = new XdrAccountEntry(
            $accountID,
            $balance,
            $seqNum,
            $numSubEntries,
            null,
            $flags,
            $homeDomain,
            $thresholds,
            $signers,
            $ext
        );

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrAccountEntry::decode(new XdrBuffer($encoded));

        // Verify all fields match
        $this->assertEquals($original->getAccountID()->getAccountId(), $decoded->getAccountID()->getAccountId());
        $this->assertEquals($original->getBalance()->toString(), $decoded->getBalance()->toString());
        $this->assertEquals($original->getSeqNum()->getValue()->toString(), $decoded->getSeqNum()->getValue()->toString());
        $this->assertNull($decoded->getInflationDest());
        $this->assertEquals(1, $decoded->getExt()->getDiscriminant());
        $this->assertNotNull($decoded->getExt()->getV1());
        $this->assertEquals(
            $original->getExt()->getV1()->getLiabilities()->getBuying()->toString(),
            $decoded->getExt()->getV1()->getLiabilities()->getBuying()->toString()
        );
        $this->assertEquals(
            $original->getExt()->getV1()->getLiabilities()->getSelling()->toString(),
            $decoded->getExt()->getV1()->getLiabilities()->getSelling()->toString()
        );

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrAccountEntry with empty home domain
     */
    public function testXdrAccountEntryEmptyHomeDomain(): void
    {
        $accountID = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $balance = new BigInteger(500000000);
        $seqNum = new XdrSequenceNumber(new BigInteger(1));
        $numSubEntries = 0;
        $flags = 0;
        $homeDomain = "";
        $thresholds = "\x00\x00\x00\x00";
        $signers = [];
        $ext = new XdrAccountEntryExt(0, null);

        $original = new XdrAccountEntry(
            $accountID,
            $balance,
            $seqNum,
            $numSubEntries,
            null,
            $flags,
            $homeDomain,
            $thresholds,
            $signers,
            $ext
        );

        $encoded = $original->encode();
        $decoded = XdrAccountEntry::decode(new XdrBuffer($encoded));

        $this->assertEquals("", $decoded->getHomeDomain());
        $this->assertEmpty($decoded->getSigners());

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrAccountEntry with multiple signers
     */
    public function testXdrAccountEntryMultipleSigners(): void
    {
        $accountID = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $balance = new BigInteger(3000000000);
        $seqNum = new XdrSequenceNumber(new BigInteger(999));
        $numSubEntries = 10;
        $flags = 7;
        $homeDomain = "test.stellar.org";
        $thresholds = "\xFF\xFE\xFD\xFC";

        $signers = [
            new XdrSigner($this->createSignerKey(XdrSignerKeyType::ED25519, str_repeat("\x11", 32)), 10),
            new XdrSigner($this->createSignerKey(XdrSignerKeyType::PRE_AUTH_TX, str_repeat("\x22", 32)), 20),
            new XdrSigner($this->createSignerKey(XdrSignerKeyType::HASH_X, str_repeat("\x33", 32)), 30),
        ];

        $ext = new XdrAccountEntryExt(0, null);

        $original = new XdrAccountEntry(
            $accountID,
            $balance,
            $seqNum,
            $numSubEntries,
            null,
            $flags,
            $homeDomain,
            $thresholds,
            $signers,
            $ext
        );

        $encoded = $original->encode();
        $decoded = XdrAccountEntry::decode(new XdrBuffer($encoded));

        $this->assertCount(3, $decoded->getSigners());
        $this->assertEquals(10, $decoded->getSigners()[0]->getWeight());
        $this->assertEquals(20, $decoded->getSigners()[1]->getWeight());
        $this->assertEquals(30, $decoded->getSigners()[2]->getWeight());

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Helper method to create XdrSignerKey
     */
    private function createSignerKey(int $type, string $value): XdrSignerKey
    {
        $signerKey = new XdrSignerKey();
        $signerKey->setType(new XdrSignerKeyType($type));

        switch ($type) {
            case XdrSignerKeyType::ED25519:
                $signerKey->setEd25519($value);
                break;
            case XdrSignerKeyType::PRE_AUTH_TX:
                $signerKey->setPreAuthTx($value);
                break;
            case XdrSignerKeyType::HASH_X:
                $signerKey->setHashX($value);
                break;
        }

        return $signerKey;
    }
}
