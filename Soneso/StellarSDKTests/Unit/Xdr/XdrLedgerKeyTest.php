<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrConfigSettingID;
use Soneso\StellarSDK\Xdr\XdrContractDataDurability;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryChange;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyBase;
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

    // A claimable balance key and a config setting key, with the XDR-JSON the
    // stellar-xdr reference decoder writes for them.
    private const BALANCE_ID = 'BAAM5KYU5255X7RFUGBQ4OPDCHBBQCCG352JI65CJI4GXAYUZS5GMISN2Q';
    private const CLAIMABLE_BALANCE_KEY = 'AAAABAAAAADOqxTuu9v+JaGDDjnjEcIYCEbfdJR7oko4a4MUzLpmIg==';
    private const CLAIMABLE_BALANCE_KEY_JSON = '{"claimable_balance":{"balance_id":"' . self::BALANCE_ID . '"}}';
    private const CONFIG_SETTING_KEY = 'AAAACAAAAAo=';
    private const CONFIG_SETTING_KEY_JSON = '{"config_setting":{"config_setting_id":"state_archival"}}';

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

    // XDR-JSON Tests

    public static function keyWithMemberCases(): array
    {
        return [
            'claimable balance' => [self::CLAIMABLE_BALANCE_KEY, self::CLAIMABLE_BALANCE_KEY_JSON,
                '{"claimable_balance":{"$schema":"https://schema","balance_id":"' . self::BALANCE_ID . '"}}'],
            'config setting' => [self::CONFIG_SETTING_KEY, self::CONFIG_SETTING_KEY_JSON,
                '{"config_setting":{"$schema":"https://schema","config_setting_id":"state_archival"}}'],
        ];
    }

    #[DataProvider('keyWithMemberCases')]
    public function testJsonWritesAndReadsTheKeyMember(string $xdr, string $json, string $jsonWithSchema): void
    {
        foreach ([XdrLedgerKey::class, XdrLedgerKeyBase::class] as $class) {
            $this->assertSame($json, $class::fromBase64Xdr($xdr)->toJson());
            $this->assertSame($xdr, $class::fromJson($json)->toBase64Xdr());
            $this->assertSame($xdr, $class::fromJson($jsonWithSchema)->toBase64Xdr());
        }
    }

    public static function keyWithoutValidMemberCases(): array
    {
        return [
            'claimable balance without member' => ['{"claimable_balance":"' . self::BALANCE_ID . '"}',
                'Expected object for XdrLedgerKeyClaimableBalance JSON value, got string'],
            'config setting without member' => ['{"config_setting":"state_archival"}',
                'Expected object for XdrLedgerKeyConfigSetting JSON value, got string'],
            'claimable balance missing member' => ['{"claimable_balance":{}}',
                'Missing required field balance_id for XdrLedgerKeyClaimableBalance'],
            'config setting missing member' => ['{"config_setting":{}}',
                'Missing required field config_setting_id for XdrLedgerKeyConfigSetting'],
            'claimable balance extra member' => ['{"claimable_balance":{"balance_id":"' . self::BALANCE_ID . '","amount":"1"}}',
                "Unknown field in JSON input for XdrLedgerKeyClaimableBalance: 'amount'"],
            'config setting extra member' => ['{"config_setting":{"config_setting_id":"state_archival","value":"1"}}',
                "Unknown field in JSON input for XdrLedgerKeyConfigSetting: 'value'"],
            'claimable balance id as hex' => [
                '{"claimable_balance":{"balance_id":"00000000ceab14eebbdbfe25a1830e39e311c2180846df74947ba24a386b8314ccba6622"}}',
                'B-strkey must be 58 characters long, 72 characters given'],
            'config setting id as XDR name' => ['{"config_setting":{"config_setting_id":"CONFIG_SETTING_STATE_ARCHIVAL"}}',
                'Unknown XdrConfigSettingID JSON value: CONFIG_SETTING_STATE_ARCHIVAL'],
        ];
    }

    #[DataProvider('keyWithoutValidMemberCases')]
    public function testJsonRefusesAKeyWithoutAValidMemberObject(string $json, string $message): void
    {
        foreach ([XdrLedgerKey::class, XdrLedgerKeyBase::class] as $class) {
            try {
                $class::fromJson($json);
                $this->fail($class . ' accepted ' . $json);
            } catch (InvalidArgumentException $e) {
                $this->assertSame($message, $e->getMessage());
            }
        }
    }

    public static function removedEntryCases(): array
    {
        // LedgerEntryChange values of type removed that carry the two keys, with their reference XDR-JSON.
        return [
            'claimable balance' => ['AAAAAgAAAAQAAAAAzqsU7rvb/iWhgw454xHCGAhG33SUe6JKOGuDFMy6ZiI=',
                '{"removed":' . self::CLAIMABLE_BALANCE_KEY_JSON . '}'],
            'config setting' => ['AAAAAgAAAAgAAAAK', '{"removed":' . self::CONFIG_SETTING_KEY_JSON . '}'],
        ];
    }

    #[DataProvider('removedEntryCases')]
    public function testJsonOfARemovedEntryCarriesTheKeyMember(string $xdr, string $json): void
    {
        $this->assertSame($json, XdrLedgerEntryChange::fromBase64Xdr($xdr)->toJson());
        $this->assertSame($xdr, XdrLedgerEntryChange::fromJson($json)->toBase64Xdr());
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
