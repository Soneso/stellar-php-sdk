<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use PHPUnit\Framework\TestCase;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrContractCodeEntry;
use Soneso\StellarSDK\Xdr\XdrContractCodeEntryExt;
use Soneso\StellarSDK\Xdr\XdrDataEntry;
use Soneso\StellarSDK\Xdr\XdrDataEntryExt;
use Soneso\StellarSDK\Xdr\XdrDataValueMandatory;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryData;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrTTLEntry;

/**
 * Unit tests for XdrLedgerEntryData
 *
 * Tests ledger entry data encoding, decoding, and getters/setters
 * for various entry types.
 */
class XdrLedgerEntryDataTest extends TestCase
{
    private const TEST_ACCOUNT_ID = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';

    public function testConstructor(): void
    {
        $type = new XdrLedgerEntryType(XdrLedgerEntryType::ACCOUNT);
        $data = new XdrLedgerEntryData($type);

        $this->assertEquals(XdrLedgerEntryType::ACCOUNT, $data->getType()->value);
        $this->assertNull($data->getAccount());
        $this->assertNull($data->getTrustline());
        $this->assertNull($data->getOffer());
        $this->assertNull($data->getData());
        $this->assertNull($data->getClaimableBalance());
        $this->assertNull($data->getLiquidityPool());
        $this->assertNull($data->getContractData());
        $this->assertNull($data->getContractCode());
        $this->assertNull($data->getConfigSetting());
        $this->assertNull($data->getTtlEntry());
    }

    public function testSetType(): void
    {
        $type = new XdrLedgerEntryType(XdrLedgerEntryType::ACCOUNT);
        $data = new XdrLedgerEntryData($type);

        $newType = new XdrLedgerEntryType(XdrLedgerEntryType::DATA);
        $data->setType($newType);

        $this->assertEquals(XdrLedgerEntryType::DATA, $data->getType()->value);
    }

    public function testDataEntryEncodeDecodeRoundTrip(): void
    {
        $accountId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $dataValue = new XdrDataValueMandatory("test-value");
        $ext = new XdrDataEntryExt(0);
        $dataEntry = new XdrDataEntry($accountId, "test-name", $dataValue, $ext);

        $ledgerData = new XdrLedgerEntryData(new XdrLedgerEntryType(XdrLedgerEntryType::DATA));
        $ledgerData->data = $dataEntry;

        $encoded = $ledgerData->encode();
        $decoded = XdrLedgerEntryData::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryType::DATA, $decoded->getType()->value);
        $this->assertNotNull($decoded->getData());
        $this->assertEquals("test-name", $decoded->getData()->dataName);
    }

    public function testTTLEntryEncodeDecodeRoundTrip(): void
    {
        $keyHash = str_repeat("\xab", 32);
        $ttlEntry = new XdrTTLEntry($keyHash, 12345);

        $ledgerData = new XdrLedgerEntryData(new XdrLedgerEntryType(XdrLedgerEntryType::TTL));
        $ledgerData->ttlEntry = $ttlEntry;

        $encoded = $ledgerData->encode();
        $decoded = XdrLedgerEntryData::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryType::TTL, $decoded->getType()->value);
        $this->assertNotNull($decoded->getTtlEntry());
        $this->assertEquals(12345, $decoded->getTtlEntry()->liveUntilLedgerSeq);
        $this->assertEquals($keyHash, $decoded->getTtlEntry()->keyHash);
    }

    public function testContractCodeEntryEncodeDecodeRoundTrip(): void
    {
        $ext = new XdrContractCodeEntryExt(0, null);
        $cHash = str_repeat("\xcd", 32);
        $code = new XdrDataValueMandatory("test-wasm-code");
        $contractCodeEntry = new XdrContractCodeEntry($ext, $cHash, $code);

        $ledgerData = new XdrLedgerEntryData(new XdrLedgerEntryType(XdrLedgerEntryType::CONTRACT_CODE));
        $ledgerData->contractCode = $contractCodeEntry;

        $encoded = $ledgerData->encode();
        $decoded = XdrLedgerEntryData::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryType::CONTRACT_CODE, $decoded->getType()->value);
        $this->assertNotNull($decoded->getContractCode());
        $this->assertEquals($cHash, $decoded->getContractCode()->cHash);
    }

    public function testFromBase64Xdr(): void
    {
        $keyHash = str_repeat("\xef", 32);
        $ttlEntry = new XdrTTLEntry($keyHash, 99999);

        $ledgerData = new XdrLedgerEntryData(new XdrLedgerEntryType(XdrLedgerEntryType::TTL));
        $ledgerData->ttlEntry = $ttlEntry;

        $base64 = $ledgerData->toBase64Xdr();
        $decoded = XdrLedgerEntryData::fromBase64Xdr($base64);

        $this->assertEquals(XdrLedgerEntryType::TTL, $decoded->getType()->value);
        $this->assertEquals(99999, $decoded->getTtlEntry()->liveUntilLedgerSeq);
    }

    public function testSetData(): void
    {
        $ledgerData = new XdrLedgerEntryData(new XdrLedgerEntryType(XdrLedgerEntryType::DATA));
        $this->assertNull($ledgerData->getData());

        $accountId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $dataValue = new XdrDataValueMandatory("value");
        $ext = new XdrDataEntryExt(0);
        $dataEntry = new XdrDataEntry($accountId, "name", $dataValue, $ext);

        $ledgerData->setData($dataEntry);
        $this->assertNotNull($ledgerData->getData());
        $this->assertEquals("name", $ledgerData->getData()->dataName);

        $ledgerData->setData(null);
        $this->assertNull($ledgerData->getData());
    }

    public function testSetTtlEntry(): void
    {
        $ledgerData = new XdrLedgerEntryData(new XdrLedgerEntryType(XdrLedgerEntryType::TTL));
        $this->assertNull($ledgerData->getTtlEntry());

        $keyHash = str_repeat("\x11", 32);
        $ttlEntry = new XdrTTLEntry($keyHash, 1000);

        $ledgerData->setTtlEntry($ttlEntry);
        $this->assertNotNull($ledgerData->getTtlEntry());
        $this->assertEquals(1000, $ledgerData->getTtlEntry()->liveUntilLedgerSeq);

        $ledgerData->setTtlEntry(null);
        $this->assertNull($ledgerData->getTtlEntry());
    }

    public function testSetContractCode(): void
    {
        $ledgerData = new XdrLedgerEntryData(new XdrLedgerEntryType(XdrLedgerEntryType::CONTRACT_CODE));
        $this->assertNull($ledgerData->getContractCode());

        $ext = new XdrContractCodeEntryExt(0, null);
        $cHash = str_repeat("\x22", 32);
        $code = new XdrDataValueMandatory("code");
        $contractCode = new XdrContractCodeEntry($ext, $cHash, $code);

        $ledgerData->setContractCode($contractCode);
        $this->assertNotNull($ledgerData->getContractCode());

        $ledgerData->setContractCode(null);
        $this->assertNull($ledgerData->getContractCode());
    }

    public function testSetAccount(): void
    {
        $ledgerData = new XdrLedgerEntryData(new XdrLedgerEntryType(XdrLedgerEntryType::ACCOUNT));
        $this->assertNull($ledgerData->getAccount());

        $ledgerData->setAccount(null);
        $this->assertNull($ledgerData->getAccount());
    }

    public function testSetTrustline(): void
    {
        $ledgerData = new XdrLedgerEntryData(new XdrLedgerEntryType(XdrLedgerEntryType::TRUSTLINE));
        $this->assertNull($ledgerData->getTrustline());

        $ledgerData->setTrustline(null);
        $this->assertNull($ledgerData->getTrustline());
    }

    public function testSetOffer(): void
    {
        $ledgerData = new XdrLedgerEntryData(new XdrLedgerEntryType(XdrLedgerEntryType::OFFER));
        $this->assertNull($ledgerData->getOffer());

        $ledgerData->setOffer(null);
        $this->assertNull($ledgerData->getOffer());
    }

    public function testSetClaimableBalance(): void
    {
        $ledgerData = new XdrLedgerEntryData(new XdrLedgerEntryType(XdrLedgerEntryType::CLAIMABLE_BALANCE));
        $this->assertNull($ledgerData->getClaimableBalance());

        $ledgerData->setClaimableBalance(null);
        $this->assertNull($ledgerData->getClaimableBalance());
    }

    public function testSetLiquidityPool(): void
    {
        $ledgerData = new XdrLedgerEntryData(new XdrLedgerEntryType(XdrLedgerEntryType::LIQUIDITY_POOL));
        $this->assertNull($ledgerData->getLiquidityPool());

        $ledgerData->setLiquidityPool(null);
        $this->assertNull($ledgerData->getLiquidityPool());
    }

    public function testSetContractData(): void
    {
        $ledgerData = new XdrLedgerEntryData(new XdrLedgerEntryType(XdrLedgerEntryType::CONTRACT_DATA));
        $this->assertNull($ledgerData->getContractData());

        $ledgerData->setContractData(null);
        $this->assertNull($ledgerData->getContractData());
    }

    public function testSetConfigSetting(): void
    {
        $ledgerData = new XdrLedgerEntryData(new XdrLedgerEntryType(XdrLedgerEntryType::CONFIG_SETTING));
        $this->assertNull($ledgerData->getConfigSetting());

        $ledgerData->setConfigSetting(null);
        $this->assertNull($ledgerData->getConfigSetting());
    }
}
