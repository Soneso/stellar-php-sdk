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

    /**
     * Test that decoding an unknown enum value throws InvalidArgumentException
     */
    public function testEnumDecodeThrowsOnUnknownValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown enum value: 99');

        $invalidXdr = new XdrBuffer(\Soneso\StellarSDK\Xdr\XdrEncoder::integer32(99));
        XdrAssetType::decode($invalidXdr);
    }

    /**
     * Test enum decode validation with negative-value enum (XdrTransactionResultCode)
     */
    public function testEnumDecodeThrowsOnUnknownNegativeValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown enum value: -99');

        $invalidXdr = new XdrBuffer(\Soneso\StellarSDK\Xdr\XdrEncoder::integer32(-99));
        \Soneso\StellarSDK\Xdr\XdrTransactionResultCode::decode($invalidXdr);
    }

    /**
     * Test enum decode validation with non-contiguous enum (XdrCryptoKeyType has gap: 0-3, 256)
     */
    public function testEnumDecodeThrowsOnValueInGap(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown enum value: 5');

        $invalidXdr = new XdrBuffer(\Soneso\StellarSDK\Xdr\XdrEncoder::integer32(5));
        \Soneso\StellarSDK\Xdr\XdrCryptoKeyType::decode($invalidXdr);
    }
}
