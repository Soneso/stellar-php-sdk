<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum4;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum12;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrChangeTrustAsset;
use Soneso\StellarSDK\Xdr\XdrTrustlineAsset;
use Soneso\StellarSDK\Xdr\XdrBuffer;

class XdrAssetTest extends TestCase
{
    private const TEST_ISSUER = "GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H";
    private const TEST_ISSUER_2 = "GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ";

    /**
     * Test XdrAsset native type encode/decode round-trip
     */
    public function testXdrAssetNativeRoundTrip(): void
    {
        $original = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrAsset::decode(new XdrBuffer($encoded));

        // Verify type matches
        $this->assertEquals($original->getType()->getValue(), $decoded->getType()->getValue());
        $this->assertEquals(XdrAssetType::ASSET_TYPE_NATIVE, $decoded->getType()->getValue());
        $this->assertNull($decoded->getAlphaNum4());
        $this->assertNull($decoded->getAlphaNum12());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrAsset with AlphaNum4
     */
    public function testXdrAssetAlphaNum4RoundTrip(): void
    {
        $assetCode = "USD";
        $issuer = XdrAccountID::fromAccountId(self::TEST_ISSUER);
        $alphaNum4 = new XdrAssetAlphaNum4($assetCode, $issuer);

        $original = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $original->setAlphaNum4($alphaNum4);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrAsset::decode(new XdrBuffer($encoded));

        // Verify all fields match
        $this->assertEquals(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getAlphaNum4());
        $this->assertEquals($assetCode, $decoded->getAlphaNum4()->getAssetCode());
        $this->assertEquals($issuer->getAccountId(), $decoded->getAlphaNum4()->getIssuer()->getAccountId());
        $this->assertNull($decoded->getAlphaNum12());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrAsset with AlphaNum12
     */
    public function testXdrAssetAlphaNum12RoundTrip(): void
    {
        $assetCode = "MYCOIN12";
        $issuer = XdrAccountID::fromAccountId(self::TEST_ISSUER);
        $alphaNum12 = new XdrAssetAlphaNum12($assetCode, $issuer);

        $original = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $original->setAlphaNum12($alphaNum12);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrAsset::decode(new XdrBuffer($encoded));

        // Verify all fields match
        $this->assertEquals(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getAlphaNum12());
        $this->assertEquals($assetCode, $decoded->getAlphaNum12()->getAssetCode());
        $this->assertEquals($issuer->getAccountId(), $decoded->getAlphaNum12()->getIssuer()->getAccountId());
        $this->assertNull($decoded->getAlphaNum4());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrAssetType encode/decode round-trip
     */
    public function testXdrAssetTypeRoundTrip(): void
    {
        $types = [
            XdrAssetType::ASSET_TYPE_NATIVE,
            XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4,
            XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12,
            XdrAssetType::ASSET_TYPE_POOL_SHARE,
        ];

        foreach ($types as $typeValue) {
            $original = new XdrAssetType($typeValue);

            $encoded = $original->encode();
            $this->assertNotEmpty($encoded);

            $decoded = XdrAssetType::decode(new XdrBuffer($encoded));

            $this->assertEquals($original->getValue(), $decoded->getValue());
            $this->assertEquals($typeValue, $decoded->getValue());

            $reEncoded = $decoded->encode();
            $this->assertEquals($encoded, $reEncoded);
        }
    }

    /**
     * Test XdrAssetAlphaNum4 direct encode/decode round-trip
     */
    public function testXdrAssetAlphaNum4DirectRoundTrip(): void
    {
        $assetCode = "EUR";
        $issuer = XdrAccountID::fromAccountId(self::TEST_ISSUER);

        $original = new XdrAssetAlphaNum4($assetCode, $issuer);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrAssetAlphaNum4::decode(new XdrBuffer($encoded));

        // Verify fields match
        $this->assertEquals($original->getAssetCode(), $decoded->getAssetCode());
        $this->assertEquals($original->getIssuer()->getAccountId(), $decoded->getIssuer()->getAccountId());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrAssetAlphaNum4 with various asset codes
     */
    public function testXdrAssetAlphaNum4VariousAssetCodes(): void
    {
        $assetCodes = [
            "A",
            "AB",
            "ABC",
            "ABCD",
            "BTC",
            "ETH",
            "USD",
        ];

        foreach ($assetCodes as $assetCode) {
            $issuer = XdrAccountID::fromAccountId(self::TEST_ISSUER);
            $original = new XdrAssetAlphaNum4($assetCode, $issuer);

            $encoded = $original->encode();
            $decoded = XdrAssetAlphaNum4::decode(new XdrBuffer($encoded));

            $this->assertEquals($assetCode, $decoded->getAssetCode());
            $this->assertEquals($issuer->getAccountId(), $decoded->getIssuer()->getAccountId());
        }
    }

    /**
     * Test XdrAssetAlphaNum12 direct encode/decode round-trip
     */
    public function testXdrAssetAlphaNum12DirectRoundTrip(): void
    {
        $assetCode = "LONGCOINNAME";
        $issuer = XdrAccountID::fromAccountId(self::TEST_ISSUER);

        $original = new XdrAssetAlphaNum12($assetCode, $issuer);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrAssetAlphaNum12::decode(new XdrBuffer($encoded));

        // Verify fields match
        $this->assertEquals($original->getAssetCode(), $decoded->getAssetCode());
        $this->assertEquals($original->getIssuer()->getAccountId(), $decoded->getIssuer()->getAccountId());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrAssetAlphaNum12 with various asset codes
     */
    public function testXdrAssetAlphaNum12VariousAssetCodes(): void
    {
        $assetCodes = [
            "ABCDE",
            "ABCDEF",
            "MYCOIN",
            "STELLARCOIN",
            "LONGCOIN123",
        ];

        foreach ($assetCodes as $assetCode) {
            $issuer = XdrAccountID::fromAccountId(self::TEST_ISSUER_2);
            $original = new XdrAssetAlphaNum12($assetCode, $issuer);

            $encoded = $original->encode();
            $decoded = XdrAssetAlphaNum12::decode(new XdrBuffer($encoded));

            $this->assertEquals($assetCode, $decoded->getAssetCode());
            $this->assertEquals($issuer->getAccountId(), $decoded->getIssuer()->getAccountId());
        }
    }

    /**
     * Test XdrChangeTrustAsset native
     */
    public function testXdrChangeTrustAssetNativeRoundTrip(): void
    {
        $original = new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrChangeTrustAsset::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrAssetType::ASSET_TYPE_NATIVE, $decoded->getType()->getValue());
        $this->assertNull($decoded->getAlphaNum4());
        $this->assertNull($decoded->getAlphaNum12());
        $this->assertNull($decoded->getLiquidityPool());

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrChangeTrustAsset with AlphaNum4
     */
    public function testXdrChangeTrustAssetAlphaNum4RoundTrip(): void
    {
        $assetCode = "XLM";
        $issuer = XdrAccountID::fromAccountId(self::TEST_ISSUER);
        $alphaNum4 = new XdrAssetAlphaNum4($assetCode, $issuer);

        $original = new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $original->setAlphaNum4($alphaNum4);

        $encoded = $original->encode();
        $decoded = XdrChangeTrustAsset::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getAlphaNum4());
        $this->assertEquals($assetCode, $decoded->getAlphaNum4()->getAssetCode());

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrChangeTrustAsset with AlphaNum12
     */
    public function testXdrChangeTrustAssetAlphaNum12RoundTrip(): void
    {
        $assetCode = "BESTCOIN";
        $issuer = XdrAccountID::fromAccountId(self::TEST_ISSUER_2);
        $alphaNum12 = new XdrAssetAlphaNum12($assetCode, $issuer);

        $original = new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $original->setAlphaNum12($alphaNum12);

        $encoded = $original->encode();
        $decoded = XdrChangeTrustAsset::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getAlphaNum12());
        $this->assertEquals($assetCode, $decoded->getAlphaNum12()->getAssetCode());

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrChangeTrustAsset fromXdrAsset conversion
     */
    public function testXdrChangeTrustAssetFromXdrAssetConversion(): void
    {
        // Test native
        $xdrAsset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $changeTrustAsset = XdrChangeTrustAsset::fromXdrAsset($xdrAsset);

        $this->assertEquals(XdrAssetType::ASSET_TYPE_NATIVE, $changeTrustAsset->getType()->getValue());

        // Test AlphaNum4
        $assetCode = "JPY";
        $issuer = XdrAccountID::fromAccountId(self::TEST_ISSUER);
        $alphaNum4 = new XdrAssetAlphaNum4($assetCode, $issuer);

        $xdrAsset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $xdrAsset->setAlphaNum4($alphaNum4);
        $changeTrustAsset = XdrChangeTrustAsset::fromXdrAsset($xdrAsset);

        $this->assertEquals(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4, $changeTrustAsset->getType()->getValue());
        $this->assertNotNull($changeTrustAsset->getAlphaNum4());
        $this->assertEquals($assetCode, $changeTrustAsset->getAlphaNum4()->getAssetCode());

        // Test AlphaNum12
        $assetCode = "CUSTOMCOIN";
        $alphaNum12 = new XdrAssetAlphaNum12($assetCode, $issuer);

        $xdrAsset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $xdrAsset->setAlphaNum12($alphaNum12);
        $changeTrustAsset = XdrChangeTrustAsset::fromXdrAsset($xdrAsset);

        $this->assertEquals(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12, $changeTrustAsset->getType()->getValue());
        $this->assertNotNull($changeTrustAsset->getAlphaNum12());
        $this->assertEquals($assetCode, $changeTrustAsset->getAlphaNum12()->getAssetCode());
    }

    /**
     * Test XdrTrustlineAsset native
     */
    public function testXdrTrustlineAssetNativeRoundTrip(): void
    {
        $original = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrTrustlineAsset::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrAssetType::ASSET_TYPE_NATIVE, $decoded->getType()->getValue());
        $this->assertNull($decoded->getAlphaNum4());
        $this->assertNull($decoded->getAlphaNum12());

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrTrustlineAsset with AlphaNum4
     */
    public function testXdrTrustlineAssetAlphaNum4RoundTrip(): void
    {
        $assetCode = "CAD";
        $issuer = XdrAccountID::fromAccountId(self::TEST_ISSUER);
        $alphaNum4 = new XdrAssetAlphaNum4($assetCode, $issuer);

        $original = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $original->setAlphaNum4($alphaNum4);

        $encoded = $original->encode();
        $decoded = XdrTrustlineAsset::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getAlphaNum4());
        $this->assertEquals($assetCode, $decoded->getAlphaNum4()->getAssetCode());

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrTrustlineAsset with AlphaNum12
     */
    public function testXdrTrustlineAssetAlphaNum12RoundTrip(): void
    {
        $assetCode = "MYASSET2023";
        $issuer = XdrAccountID::fromAccountId(self::TEST_ISSUER_2);
        $alphaNum12 = new XdrAssetAlphaNum12($assetCode, $issuer);

        $original = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $original->setAlphaNum12($alphaNum12);

        $encoded = $original->encode();
        $decoded = XdrTrustlineAsset::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getAlphaNum12());
        $this->assertEquals($assetCode, $decoded->getAlphaNum12()->getAssetCode());

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrTrustlineAsset with pool share
     */
    public function testXdrTrustlineAssetPoolShareRoundTrip(): void
    {
        $poolId = str_repeat("\xAB", 32);

        $original = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_POOL_SHARE));
        $original->setPoolId($poolId);

        $encoded = $original->encode();
        $decoded = XdrTrustlineAsset::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrAssetType::ASSET_TYPE_POOL_SHARE, $decoded->getType()->getValue());
        $this->assertEquals($poolId, $decoded->getPoolId());

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrTrustlineAsset fromXdrAsset conversion
     */
    public function testXdrTrustlineAssetFromXdrAssetConversion(): void
    {
        // Test native
        $xdrAsset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $trustlineAsset = XdrTrustlineAsset::fromXdrAsset($xdrAsset);

        $this->assertEquals(XdrAssetType::ASSET_TYPE_NATIVE, $trustlineAsset->getType()->getValue());

        // Test AlphaNum4
        $assetCode = "GBP";
        $issuer = XdrAccountID::fromAccountId(self::TEST_ISSUER);
        $alphaNum4 = new XdrAssetAlphaNum4($assetCode, $issuer);

        $xdrAsset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $xdrAsset->setAlphaNum4($alphaNum4);
        $trustlineAsset = XdrTrustlineAsset::fromXdrAsset($xdrAsset);

        $this->assertEquals(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4, $trustlineAsset->getType()->getValue());
        $this->assertNotNull($trustlineAsset->getAlphaNum4());
        $this->assertEquals($assetCode, $trustlineAsset->getAlphaNum4()->getAssetCode());

        // Test AlphaNum12
        $assetCode = "MYCOIN2025";
        $alphaNum12 = new XdrAssetAlphaNum12($assetCode, $issuer);

        $xdrAsset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $xdrAsset->setAlphaNum12($alphaNum12);
        $trustlineAsset = XdrTrustlineAsset::fromXdrAsset($xdrAsset);

        $this->assertEquals(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12, $trustlineAsset->getType()->getValue());
        $this->assertNotNull($trustlineAsset->getAlphaNum12());
        $this->assertEquals($assetCode, $trustlineAsset->getAlphaNum12()->getAssetCode());
    }

    /**
     * Test multiple assets in sequence
     */
    public function testMultipleAssetsSequence(): void
    {
        $assets = [
            new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE)),
        ];

        $assetCode1 = "USD";
        $issuer1 = XdrAccountID::fromAccountId(self::TEST_ISSUER);
        $asset2 = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $asset2->setAlphaNum4(new XdrAssetAlphaNum4($assetCode1, $issuer1));
        $assets[] = $asset2;

        $assetCode2 = "VERYLONGCOIN";
        $issuer2 = XdrAccountID::fromAccountId(self::TEST_ISSUER_2);
        $asset3 = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $asset3->setAlphaNum12(new XdrAssetAlphaNum12($assetCode2, $issuer2));
        $assets[] = $asset3;

        foreach ($assets as $original) {
            $encoded = $original->encode();
            $decoded = XdrAsset::decode(new XdrBuffer($encoded));

            $this->assertEquals($original->getType()->getValue(), $decoded->getType()->getValue());

            $reEncoded = $decoded->encode();
            $this->assertEquals($encoded, $reEncoded);
        }
    }
}
