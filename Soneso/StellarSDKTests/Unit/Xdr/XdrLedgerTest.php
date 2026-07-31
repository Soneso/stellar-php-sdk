<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use PHPUnit\Framework\TestCase;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Xdr\XdrAccountEntry;
use Soneso\StellarSDK\Xdr\XdrAccountEntryExt;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrContractDataDurability;
use Soneso\StellarSDK\Xdr\XdrLedgerEntry;
use Soneso\StellarSDK\Xdr\XdrLedgerCloseValueSignature;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryData;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryExt;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryV1;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryV1Ext;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrNodeID;
use Soneso\StellarSDK\Xdr\XdrPublicKey;
use Soneso\StellarSDK\Xdr\XdrPublicKeyType;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;
use Soneso\StellarSDK\Xdr\XdrStellarValueExt;
use Soneso\StellarSDK\Xdr\XdrStellarValueProposedValue;
use Soneso\StellarSDK\Xdr\XdrStellarValueType;

class XdrLedgerTest extends TestCase
{
    private const TEST_ACCOUNT_ID = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';
    private const TEST_ACCOUNT_ID_2 = 'GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ';

    public function testXdrLedgerEntryTypeStaticMethods(): void
    {
        $this->assertEquals(XdrLedgerEntryType::ACCOUNT, XdrLedgerEntryType::ACCOUNT()->getValue());
        $this->assertEquals(XdrLedgerEntryType::TRUSTLINE, XdrLedgerEntryType::TRUSTLINE()->getValue());
        $this->assertEquals(XdrLedgerEntryType::OFFER, XdrLedgerEntryType::OFFER()->getValue());
        $this->assertEquals(XdrLedgerEntryType::DATA, XdrLedgerEntryType::DATA()->getValue());
        $this->assertEquals(XdrLedgerEntryType::CLAIMABLE_BALANCE, XdrLedgerEntryType::CLAIMABLE_BALANCE()->getValue());
        $this->assertEquals(XdrLedgerEntryType::LIQUIDITY_POOL, XdrLedgerEntryType::LIQUIDITY_POOL()->getValue());
        $this->assertEquals(XdrLedgerEntryType::CONTRACT_DATA, XdrLedgerEntryType::CONTRACT_DATA()->getValue());
        $this->assertEquals(XdrLedgerEntryType::CONTRACT_CODE, XdrLedgerEntryType::CONTRACT_CODE()->getValue());
        $this->assertEquals(XdrLedgerEntryType::CONFIG_SETTING, XdrLedgerEntryType::CONFIG_SETTING()->getValue());
        $this->assertEquals(XdrLedgerEntryType::TTL, XdrLedgerEntryType::EXPIRATION()->getValue());
    }

    public function testXdrLedgerEntryBase64Methods(): void
    {
        $lastModifiedLedgerSeq = 98765;

        $accountId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $balance = new BigInteger(5000000000);
        $seqNum = new XdrSequenceNumber(new BigInteger(11111));
        $numSubEntries = 2;
        $inflationDest = null;
        $flags = 0;
        $homeDomain = "example.com";
        $thresholds = chr(1) . chr(1) . chr(1) . chr(1);
        $signers = [];
        $accountExt = new XdrAccountEntryExt(0);

        $accountEntry = new XdrAccountEntry(
            $accountId,
            $balance,
            $seqNum,
            $numSubEntries,
            $flags,
            $homeDomain,
            $thresholds,
            $signers,
            $accountExt
        );

        $ledgerEntryData = new XdrLedgerEntryData(XdrLedgerEntryType::ACCOUNT());
        $ledgerEntryData->setAccount($accountEntry);

        $ext = new XdrLedgerEntryExt(0);
        $ledgerEntry = new XdrLedgerEntry($lastModifiedLedgerSeq, $ledgerEntryData, $ext);

        $base64 = base64_encode($ledgerEntry->encode());
        $decoded = XdrLedgerEntry::fromBase64Xdr($base64);

        $this->assertEquals($lastModifiedLedgerSeq, $decoded->getLastModifiedLedgerSeq());
        $this->assertEquals($homeDomain, $decoded->getData()->getAccount()->getHomeDomain());
    }

    public function testXdrLedgerKeyForContractData(): void
    {
        $contractIdHex = str_pad('1234567890abcdef', 64, '0', STR_PAD_LEFT);
        $contract = XdrSCAddress::forContractId($contractIdHex);
        $key = XdrSCVal::forU32(123);
        $durability = XdrContractDataDurability::PERSISTENT();

        $ledgerKey = XdrLedgerKey::forContractData($contract, $key, $durability);

        $this->assertEquals(XdrLedgerEntryType::CONTRACT_DATA, $ledgerKey->getType()->getValue());
        $this->assertNotNull($ledgerKey->getContractData());

        $encoded = $ledgerKey->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerKey::decode($xdrBuffer);

        $this->assertEquals($ledgerKey->getType()->getValue(), $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getContractData());
    }

    public function testXdrLedgerKeyForContractCode(): void
    {
        $codeHashHex = str_pad('fedcba', 64, '0', STR_PAD_LEFT);
        $codeHashBytes = hex2bin($codeHashHex);
        $ledgerKey = XdrLedgerKey::forContractCode($codeHashBytes);

        $this->assertEquals(XdrLedgerEntryType::CONTRACT_CODE, $ledgerKey->getType()->getValue());
        $this->assertNotNull($ledgerKey->getContractCode());

        $encoded = $ledgerKey->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerKey::decode($xdrBuffer);

        $this->assertEquals($ledgerKey->getType()->getValue(), $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getContractCode());
    }

    public function testXdrLedgerKeyBase64Methods(): void
    {
        $ledgerKey = XdrLedgerKey::forAccountId(self::TEST_ACCOUNT_ID);

        $base64 = $ledgerKey->toBase64Xdr();
        $decoded = XdrLedgerKey::fromBase64Xdr($base64);

        $this->assertEquals($ledgerKey->getType()->getValue(), $decoded->getType()->getValue());
        $this->assertEquals(
            $ledgerKey->getAccount()->getAccountId()->getAccountId(),
            $decoded->getAccount()->getAccountId()->getAccountId()
        );
    }

    public function testXdrLedgerFootprintEncodeDecode(): void
    {
        $readOnlyKey1 = XdrLedgerKey::forAccountId(self::TEST_ACCOUNT_ID);
        $readOnlyKey2 = XdrLedgerKey::forAccountId(self::TEST_ACCOUNT_ID_2);
        $readWriteKey1 = XdrLedgerKey::forData(self::TEST_ACCOUNT_ID, "data1");
        $readWriteKey2 = XdrLedgerKey::forData(self::TEST_ACCOUNT_ID_2, "data2");

        $footprint = new XdrLedgerFootprint(
            [$readOnlyKey1, $readOnlyKey2],
            [$readWriteKey1, $readWriteKey2]
        );

        $encoded = $footprint->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerFootprint::decode($xdrBuffer);

        $this->assertCount(2, $decoded->getReadOnly());
        $this->assertCount(2, $decoded->getReadWrite());

        $this->assertEquals(
            $readOnlyKey1->getAccount()->getAccountId()->getAccountId(),
            $decoded->getReadOnly()[0]->getAccount()->getAccountId()->getAccountId()
        );

        $this->assertEquals(
            $readWriteKey1->getData()->getDataName(),
            $decoded->getReadWrite()[0]->getData()->getDataName()
        );
    }

    public function testXdrLedgerFootprintEmpty(): void
    {
        $footprint = new XdrLedgerFootprint([], []);

        $encoded = $footprint->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerFootprint::decode($xdrBuffer);

        $this->assertCount(0, $decoded->getReadOnly());
        $this->assertCount(0, $decoded->getReadWrite());
    }

    public function testXdrLedgerFootprintBase64Methods(): void
    {
        $readOnlyKey = XdrLedgerKey::forAccountId(self::TEST_ACCOUNT_ID);
        $readWriteKey = XdrLedgerKey::forData(self::TEST_ACCOUNT_ID, "test_data");

        $footprint = new XdrLedgerFootprint([$readOnlyKey], [$readWriteKey]);

        $base64 = $footprint->toBase64Xdr();
        $decoded = XdrLedgerFootprint::fromBase64Xdr($base64);

        $this->assertCount(1, $decoded->getReadOnly());
        $this->assertCount(1, $decoded->getReadWrite());
        $this->assertEquals("test_data", $decoded->getReadWrite()[0]->getData()->getDataName());
    }

    public function testXdrLedgerEntryExtV0(): void
    {
        $ext = new XdrLedgerEntryExt(0);

        $encoded = $ext->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerEntryExt::decode($xdrBuffer);

        $this->assertEquals(0, $decoded->getDiscriminant());
        $this->assertNull($decoded->getV1());
    }

    public function testXdrLedgerEntryExtV1(): void
    {
        $sponsoringId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $v1Ext = new XdrLedgerEntryV1Ext(0);
        $v1 = new XdrLedgerEntryV1($v1Ext, $sponsoringId);
        $ext = new XdrLedgerEntryExt(1);
        $ext->v1 = $v1;

        $encoded = $ext->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerEntryExt::decode($xdrBuffer);

        $this->assertEquals(1, $decoded->getDiscriminant());
        $this->assertNotNull($decoded->getV1());
        $this->assertEquals(
            $sponsoringId->getAccountId(),
            $decoded->getV1()->getSponsoringId()->getAccountId()
        );
    }

    private function createTestLcValueSignature(): XdrLedgerCloseValueSignature
    {
        $publicKey = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519));
        $publicKey->ed25519 = str_repeat("\xab", 32);
        return new XdrLedgerCloseValueSignature(new XdrNodeID($publicKey), "\x01\x02\x03\x04");
    }

    private function createTestProposedValue(): XdrStellarValueProposedValue
    {
        return new XdrStellarValueProposedValue(
            str_repeat("\x11", 32),
            str_repeat("\x22", 32),
            7,
            $this->createTestLcValueSignature()
        );
    }

    public function testXdrStellarValueExtProposedValueRoundtrip(): void
    {
        $type = XdrStellarValueType::STELLAR_VALUE_EMPTY_TX_SET();
        $this->assertEquals(XdrStellarValueType::STELLAR_VALUE_EMPTY_TX_SET, $type->getValue());

        $proposedValue = $this->createTestProposedValue();
        $ext = new XdrStellarValueExt($type);
        $ext->setProposedValue($proposedValue);
        $this->assertSame($proposedValue, $ext->getProposedValue());

        $decoded = XdrStellarValueExt::fromBase64Xdr($ext->toBase64Xdr());

        $this->assertEquals($ext->encode(), $decoded->encode());
        $this->assertEquals(XdrStellarValueType::STELLAR_VALUE_EMPTY_TX_SET, $decoded->getV()->getValue());
        $this->assertNotNull($decoded->getProposedValue());
        $this->assertEquals(7, $decoded->getProposedValue()->getPreviousLedgerVersion());
        $this->assertEquals(str_repeat("\x11", 32), $decoded->getProposedValue()->getTxSetHash());
    }

    public function testXdrStellarValueProposedValueAccessors(): void
    {
        $proposedValue = $this->createTestProposedValue();

        $newTxSetHash = str_repeat("\x33", 32);
        $newPreviousLedgerHash = str_repeat("\x44", 32);
        $newSignature = $this->createTestLcValueSignature();
        $newSignature->setSignature("\x09\x08\x07");

        $proposedValue->setTxSetHash($newTxSetHash);
        $proposedValue->setPreviousLedgerHash($newPreviousLedgerHash);
        $proposedValue->setPreviousLedgerVersion(42);
        $proposedValue->setLcValueSignature($newSignature);

        $this->assertSame($newTxSetHash, $proposedValue->getTxSetHash());
        $this->assertSame($newPreviousLedgerHash, $proposedValue->getPreviousLedgerHash());
        $this->assertSame(42, $proposedValue->getPreviousLedgerVersion());
        $this->assertSame($newSignature, $proposedValue->getLcValueSignature());

        $decoded = XdrStellarValueProposedValue::fromBase64Xdr($proposedValue->toBase64Xdr());
        $this->assertEquals($proposedValue->encode(), $decoded->encode());
        $this->assertEquals(42, $decoded->getPreviousLedgerVersion());
        $this->assertEquals("\x09\x08\x07", $decoded->getLcValueSignature()->getSignature());
    }

    public function testXdrStellarValueProposedValueJsonRoundtripIgnoresSchemaKey(): void
    {
        $proposedValue = $this->createTestProposedValue();

        $jsonValue = $proposedValue->toJsonValue();
        $this->assertEquals(str_repeat('11', 32), $jsonValue['tx_set_hash']);
        $this->assertEquals(str_repeat('22', 32), $jsonValue['previous_ledger_hash']);
        $this->assertEquals(7, $jsonValue['previous_ledger_version']);

        $jsonValue['$schema'] = 'https://example.org/schema.json';
        $restored = XdrStellarValueProposedValue::fromJsonValue($jsonValue);

        $this->assertEquals($proposedValue->encode(), $restored->encode());
    }

    public function testXdrStellarValueProposedValueFromJsonValueRejectsMalformedInput(): void
    {
        // Non-object input.
        try {
            XdrStellarValueProposedValue::fromJsonValue('not-an-object');
            $this->fail('Expected InvalidArgumentException for non-object input');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Expected object for XdrStellarValueProposedValue', $e->getMessage());
        }

        // Missing required fields, one at a time.
        $valid = $this->createTestProposedValue()->toJsonValue();
        $requiredFields = ['tx_set_hash', 'previous_ledger_hash', 'previous_ledger_version', 'lc_value_signature'];
        foreach ($requiredFields as $field) {
            $incomplete = $valid;
            unset($incomplete[$field]);
            try {
                XdrStellarValueProposedValue::fromJsonValue($incomplete);
                $this->fail('Expected InvalidArgumentException for missing ' . $field);
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('Missing required field ' . $field, $e->getMessage());
            }
        }
    }

    public function testXdrStellarValueProposedValueFromBase64XdrRejectsInvalidBase64(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        XdrStellarValueProposedValue::fromBase64Xdr('%%%not-base64%%%');
    }
}
