<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrConfigSettingID;
use Soneso\StellarSDK\Xdr\XdrContractDataDurability;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyContractCode;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyTTL;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;

/**
 * Unit tests for XdrLedgerKey
 *
 * Tests encoding, decoding, factory methods, and getters/setters
 * for all ledger key types.
 */
class XdrLedgerKeyTest extends TestCase
{
    private const TEST_ACCOUNT_ID = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';
    private const TEST_ACCOUNT_ID_2 = 'GBFUYQUPRAG2YHKBBQSWKHFZRH5N4NIWKK3OVMUDLF7R6453BN4OUAVR';

    // Factory Method Tests

    public function testForAccountId(): void
    {
        $key = XdrLedgerKey::forAccountId(self::TEST_ACCOUNT_ID);

        $this->assertEquals(XdrLedgerEntryType::ACCOUNT, $key->getType()->getValue());
        $this->assertNotNull($key->getAccount());
        $this->assertEquals(self::TEST_ACCOUNT_ID, $key->getAccount()->getAccountID()->getAccountId());
    }

    public function testForTrustLine(): void
    {
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $key = XdrLedgerKey::forTrustLine(self::TEST_ACCOUNT_ID, $asset);

        $this->assertEquals(XdrLedgerEntryType::TRUSTLINE, $key->getType()->getValue());
        $this->assertNotNull($key->getTrustLine());
    }

    public function testForOffer(): void
    {
        $key = XdrLedgerKey::forOffer(self::TEST_ACCOUNT_ID, 12345);

        $this->assertEquals(XdrLedgerEntryType::OFFER, $key->getType()->getValue());
        $this->assertNotNull($key->getOffer());
    }

    public function testForData(): void
    {
        $key = XdrLedgerKey::forData(self::TEST_ACCOUNT_ID, "test-data-name");

        $this->assertEquals(XdrLedgerEntryType::DATA, $key->getType()->getValue());
        $this->assertNotNull($key->getData());
    }

    public function testForClaimableBalanceId(): void
    {
        $balanceId = "00000000" . str_repeat("ab", 32);
        $key = XdrLedgerKey::forClaimableBalanceId($balanceId);

        $this->assertEquals(XdrLedgerEntryType::CLAIMABLE_BALANCE, $key->getType()->getValue());
        $this->assertNotNull($key->getBalanceID());
    }

    public function testForLiquidityPoolIdHex(): void
    {
        $poolId = str_repeat("cd", 32);
        $key = XdrLedgerKey::forLiquidityPoolId($poolId);

        $this->assertEquals(XdrLedgerEntryType::LIQUIDITY_POOL, $key->getType()->getValue());
        $this->assertEquals($poolId, $key->getLiquidityPoolID());
    }

    public function testForContractData(): void
    {
        $contract = XdrSCAddress::forAccountId(self::TEST_ACCOUNT_ID);
        $keyVal = new XdrSCVal(new XdrSCValType(XdrSCValType::SCV_BOOL));
        $keyVal->b = true;
        $durability = new XdrContractDataDurability(XdrContractDataDurability::PERSISTENT);

        $key = XdrLedgerKey::forContractData($contract, $keyVal, $durability);

        $this->assertEquals(XdrLedgerEntryType::CONTRACT_DATA, $key->getType()->getValue());
        $this->assertNotNull($key->getContractData());
    }

    public function testForContractCode(): void
    {
        $codeHash = str_repeat("\xef", 32);
        $key = XdrLedgerKey::forContractCode($codeHash);

        $this->assertEquals(XdrLedgerEntryType::CONTRACT_CODE, $key->getType()->getValue());
        $this->assertNotNull($key->getContractCode());
    }

    public function testForConfigSettingID(): void
    {
        $settingId = XdrConfigSettingID::CONFIG_SETTING_CONTRACT_MAX_SIZE_BYTES();
        $key = XdrLedgerKey::forConfigSettingID($settingId);

        $this->assertEquals(XdrLedgerEntryType::CONFIG_SETTING, $key->getType()->getValue());
        $this->assertNotNull($key->getConfigSetting());
    }

    public function testForTTL(): void
    {
        $keyHash = str_repeat("\x11", 32);
        $key = XdrLedgerKey::forTTL($keyHash);

        $this->assertEquals(XdrLedgerEntryType::TTL, $key->getType()->getValue());
        $this->assertNotNull($key->getTtl());
    }

    // Encode/Decode Round Trip Tests

    public function testAccountEncodeDecodeRoundTrip(): void
    {
        $original = XdrLedgerKey::forAccountId(self::TEST_ACCOUNT_ID);

        $encoded = $original->encode();
        $decoded = XdrLedgerKey::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryType::ACCOUNT, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getAccount());
        $this->assertEquals(self::TEST_ACCOUNT_ID, $decoded->getAccount()->getAccountID()->getAccountId());
    }

    public function testTrustLineEncodeDecodeRoundTrip(): void
    {
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $original = XdrLedgerKey::forTrustLine(self::TEST_ACCOUNT_ID, $asset);

        $encoded = $original->encode();
        $decoded = XdrLedgerKey::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryType::TRUSTLINE, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getTrustLine());
    }

    public function testOfferEncodeDecodeRoundTrip(): void
    {
        $original = XdrLedgerKey::forOffer(self::TEST_ACCOUNT_ID, 99999);

        $encoded = $original->encode();
        $decoded = XdrLedgerKey::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryType::OFFER, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getOffer());
    }

    public function testDataEncodeDecodeRoundTrip(): void
    {
        $original = XdrLedgerKey::forData(self::TEST_ACCOUNT_ID, "my-data");

        $encoded = $original->encode();
        $decoded = XdrLedgerKey::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryType::DATA, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getData());
    }

    public function testClaimableBalanceEncodeDecodeRoundTrip(): void
    {
        $balanceId = "00000000" . str_repeat("ab", 32);
        $original = XdrLedgerKey::forClaimableBalanceId($balanceId);

        $encoded = $original->encode();
        $decoded = XdrLedgerKey::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryType::CLAIMABLE_BALANCE, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getBalanceID());
    }

    public function testLiquidityPoolEncodeDecodeRoundTrip(): void
    {
        $poolId = str_repeat("cd", 32);
        $original = XdrLedgerKey::forLiquidityPoolId($poolId);

        $encoded = $original->encode();
        $decoded = XdrLedgerKey::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryType::LIQUIDITY_POOL, $decoded->getType()->getValue());
        $this->assertEquals($poolId, $decoded->getLiquidityPoolID());
    }

    public function testContractDataEncodeDecodeRoundTrip(): void
    {
        $contract = XdrSCAddress::forAccountId(self::TEST_ACCOUNT_ID);
        $keyVal = new XdrSCVal(new XdrSCValType(XdrSCValType::SCV_BOOL));
        $keyVal->b = true;
        $durability = new XdrContractDataDurability(XdrContractDataDurability::PERSISTENT);

        $original = XdrLedgerKey::forContractData($contract, $keyVal, $durability);

        $encoded = $original->encode();
        $decoded = XdrLedgerKey::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryType::CONTRACT_DATA, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getContractData());
    }

    public function testContractCodeEncodeDecodeRoundTrip(): void
    {
        $codeHash = str_repeat("\xef", 32);
        $original = XdrLedgerKey::forContractCode($codeHash);

        $encoded = $original->encode();
        $decoded = XdrLedgerKey::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryType::CONTRACT_CODE, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getContractCode());
    }

    public function testConfigSettingEncodeDecodeRoundTrip(): void
    {
        $settingId = XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COMPUTE_V0();
        $original = XdrLedgerKey::forConfigSettingID($settingId);

        $encoded = $original->encode();
        $decoded = XdrLedgerKey::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryType::CONFIG_SETTING, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getConfigSetting());
    }

    public function testTTLEncodeDecodeRoundTrip(): void
    {
        $keyHash = str_repeat("\x22", 32);
        $original = XdrLedgerKey::forTTL($keyHash);

        $encoded = $original->encode();
        $decoded = XdrLedgerKey::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryType::TTL, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getTtl());
    }

    // Base64 Round Trip Tests

    public function testToBase64XdrAndFromBase64Xdr(): void
    {
        $original = XdrLedgerKey::forAccountId(self::TEST_ACCOUNT_ID);

        $base64 = $original->toBase64Xdr();
        $decoded = XdrLedgerKey::fromBase64Xdr($base64);

        $this->assertEquals(XdrLedgerEntryType::ACCOUNT, $decoded->getType()->getValue());
        $this->assertEquals(self::TEST_ACCOUNT_ID, $decoded->getAccount()->getAccountID()->getAccountId());
    }

    // Getter/Setter Tests

    public function testSetType(): void
    {
        $key = new XdrLedgerKey(new XdrLedgerEntryType(XdrLedgerEntryType::ACCOUNT));
        $this->assertEquals(XdrLedgerEntryType::ACCOUNT, $key->getType()->getValue());

        $key->setType(new XdrLedgerEntryType(XdrLedgerEntryType::DATA));
        $this->assertEquals(XdrLedgerEntryType::DATA, $key->getType()->getValue());
    }

    public function testSetAccount(): void
    {
        $key = XdrLedgerKey::forAccountId(self::TEST_ACCOUNT_ID);
        $this->assertNotNull($key->getAccount());

        $key->setAccount(null);
        $this->assertNull($key->getAccount());
    }

    public function testSetTrustLine(): void
    {
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $key = XdrLedgerKey::forTrustLine(self::TEST_ACCOUNT_ID, $asset);
        $this->assertNotNull($key->getTrustLine());

        $key->setTrustLine(null);
        $this->assertNull($key->getTrustLine());
    }

    public function testSetOffer(): void
    {
        $key = XdrLedgerKey::forOffer(self::TEST_ACCOUNT_ID, 123);
        $this->assertNotNull($key->getOffer());

        $key->setOffer(null);
        $this->assertNull($key->getOffer());
    }

    public function testSetData(): void
    {
        $key = XdrLedgerKey::forData(self::TEST_ACCOUNT_ID, "test");
        $this->assertNotNull($key->getData());

        $key->setData(null);
        $this->assertNull($key->getData());
    }

    public function testSetBalanceID(): void
    {
        $balanceId = "00000000" . str_repeat("ab", 32);
        $key = XdrLedgerKey::forClaimableBalanceId($balanceId);
        $this->assertNotNull($key->getBalanceID());

        $key->setBalanceID(null);
        $this->assertNull($key->getBalanceID());
    }

    public function testSetLiquidityPoolID(): void
    {
        $poolId = str_repeat("cd", 32);
        $key = XdrLedgerKey::forLiquidityPoolId($poolId);
        $this->assertEquals($poolId, $key->getLiquidityPoolID());

        $newPoolId = str_repeat("ef", 32);
        $key->setLiquidityPoolID($newPoolId);
        $this->assertEquals($newPoolId, $key->getLiquidityPoolID());

        $key->setLiquidityPoolID(null);
        $this->assertNull($key->getLiquidityPoolID());
    }

    public function testSetContractData(): void
    {
        $contract = XdrSCAddress::forAccountId(self::TEST_ACCOUNT_ID);
        $keyVal = new XdrSCVal(new XdrSCValType(XdrSCValType::SCV_BOOL));
        $keyVal->b = true;
        $durability = new XdrContractDataDurability(XdrContractDataDurability::PERSISTENT);

        $key = XdrLedgerKey::forContractData($contract, $keyVal, $durability);
        $this->assertNotNull($key->getContractData());

        $key->setContractData(null);
        $this->assertNull($key->getContractData());
    }

    public function testSetContractCode(): void
    {
        $codeHash = str_repeat("\xef", 32);
        $key = XdrLedgerKey::forContractCode($codeHash);
        $this->assertNotNull($key->getContractCode());

        $key->setContractCode(null);
        $this->assertNull($key->getContractCode());
    }

    public function testSetConfigSetting(): void
    {
        $settingId = XdrConfigSettingID::CONFIG_SETTING_CONTRACT_MAX_SIZE_BYTES();
        $key = XdrLedgerKey::forConfigSettingID($settingId);
        $this->assertNotNull($key->getConfigSetting());

        $key->setConfigSetting(null);
        $this->assertNull($key->getConfigSetting());
    }

    public function testSetTtl(): void
    {
        $keyHash = str_repeat("\x11", 32);
        $key = XdrLedgerKey::forTTL($keyHash);
        $this->assertNotNull($key->getTtl());

        $key->setTtl(null);
        $this->assertNull($key->getTtl());
    }
}
