<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Core;

use Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\AssetTypeCreditAlphanum12;
use Soneso\StellarSDK\AssetTypeNative;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;
use function PHPUnit\Framework\assertFalse;

class AssetTest extends TestCase
{
    private string $issuerAccountId = "GAKL4XMWLXQKYNYR6ZDVLZT5FXQK3PKC4GZW7OKJX4KQLJKRWBWDXNYK";

    public function setUp(): void
    {
        error_reporting(E_ALL);
    }

    public function testNativeAsset()
    {
        $asset = Asset::native();
        assertEquals(Asset::TYPE_NATIVE, $asset->getType());
        assertEquals(AssetTypeNative::class, get_class($asset));
    }

    public function testCreateNonNativeAssetAlphaNum4()
    {
        $asset = Asset::createNonNativeAsset("USD", $this->issuerAccountId);

        assertEquals(AssetTypeCreditAlphanum4::class, get_class($asset));
        assertEquals("USD", $asset->getCode());
        assertEquals($this->issuerAccountId, $asset->getIssuer());
        assertEquals(Asset::TYPE_CREDIT_ALPHANUM_4, $asset->getType());
    }

    public function testCreateNonNativeAssetAlphaNum12()
    {
        $asset = Asset::createNonNativeAsset("LONGCODE", $this->issuerAccountId);

        assertEquals(AssetTypeCreditAlphanum12::class, get_class($asset));
        assertEquals("LONGCODE", $asset->getCode());
        assertEquals($this->issuerAccountId, $asset->getIssuer());
        assertEquals(Asset::TYPE_CREDIT_ALPHANUM_12, $asset->getType());
    }

    public function testCreateNonNativeAssetMaxLength4()
    {
        $asset = Asset::createNonNativeAsset("ABCD", $this->issuerAccountId);
        assertEquals(AssetTypeCreditAlphanum4::class, get_class($asset));
    }

    public function testCreateNonNativeAssetMinLength12()
    {
        $asset = Asset::createNonNativeAsset("ABCDE", $this->issuerAccountId);
        assertEquals(AssetTypeCreditAlphanum12::class, get_class($asset));
    }

    public function testCreateNonNativeAssetMaxLength12()
    {
        $asset = Asset::createNonNativeAsset("ABCDEFGHIJKL", $this->issuerAccountId);
        assertEquals(AssetTypeCreditAlphanum12::class, get_class($asset));
        assertEquals("ABCDEFGHIJKL", $asset->getCode());
    }

    public function testCreateNonNativeAssetInvalidCodeTooShort()
    {
        $this->expectException(RuntimeException::class);
        Asset::createNonNativeAsset("", $this->issuerAccountId);
    }

    public function testCreateNonNativeAssetInvalidCodeTooLong()
    {
        $this->expectException(RuntimeException::class);
        Asset::createNonNativeAsset("ABCDEFGHIJKLM", $this->issuerAccountId);
    }

    public function testCreateAssetWithType()
    {
        $asset = Asset::create(Asset::TYPE_NATIVE);
        assertEquals(AssetTypeNative::class, get_class($asset));

        $asset = Asset::create(Asset::TYPE_CREDIT_ALPHANUM_4, "USD", $this->issuerAccountId);
        assertEquals(AssetTypeCreditAlphanum4::class, get_class($asset));
        assertEquals("USD", $asset->getCode());

        $asset = Asset::create(Asset::TYPE_CREDIT_ALPHANUM_12, "TESTCOIN", $this->issuerAccountId);
        assertEquals(AssetTypeCreditAlphanum12::class, get_class($asset));
        assertEquals("TESTCOIN", $asset->getCode());
    }

    public function testCreateAssetMissingCode()
    {
        $this->expectException(RuntimeException::class);
        Asset::create(Asset::TYPE_CREDIT_ALPHANUM_4, null, $this->issuerAccountId);
    }

    public function testCreateAssetMissingIssuer()
    {
        $this->expectException(RuntimeException::class);
        Asset::create(Asset::TYPE_CREDIT_ALPHANUM_4, "USD", null);
    }

    public function testCreateAssetUnsupportedType()
    {
        $this->expectException(RuntimeException::class);
        Asset::create("unsupported_type");
    }

    public function testCanonicalFormNative()
    {
        $asset = Asset::native();
        $canonical = Asset::canonicalForm($asset);
        assertEquals("native", $canonical);
    }

    public function testCanonicalFormCreditAsset()
    {
        $asset = Asset::createNonNativeAsset("USD", $this->issuerAccountId);
        $canonical = Asset::canonicalForm($asset);
        assertEquals("USD:" . $this->issuerAccountId, $canonical);
    }

    public function testCreateFromCanonicalFormNative()
    {
        $asset = Asset::createFromCanonicalForm("native");
        assertNotNull($asset);
        assertEquals(AssetTypeNative::class, get_class($asset));

        $asset = Asset::createFromCanonicalForm("XLM");
        assertNotNull($asset);
        assertEquals(AssetTypeNative::class, get_class($asset));
    }

    public function testCreateFromCanonicalFormAlphaNum4()
    {
        $canonical = "USD:" . $this->issuerAccountId;
        $asset = Asset::createFromCanonicalForm($canonical);

        assertNotNull($asset);
        assertEquals(AssetTypeCreditAlphanum4::class, get_class($asset));
        assertEquals("USD", $asset->getCode());
        assertEquals($this->issuerAccountId, $asset->getIssuer());
    }

    public function testCreateFromCanonicalFormAlphaNum12()
    {
        $canonical = "TESTCOIN:" . $this->issuerAccountId;
        $asset = Asset::createFromCanonicalForm($canonical);

        assertNotNull($asset);
        assertEquals(AssetTypeCreditAlphanum12::class, get_class($asset));
        assertEquals("TESTCOIN", $asset->getCode());
        assertEquals($this->issuerAccountId, $asset->getIssuer());
    }

    public function testCreateFromCanonicalFormInvalid()
    {
        $asset = Asset::createFromCanonicalForm("invalid");
        assertNull($asset);

        $asset = Asset::createFromCanonicalForm("USD:INVALID:EXTRA");
        assertNull($asset);

        $asset = Asset::createFromCanonicalForm("TOOLONGASSETCODE:" . $this->issuerAccountId);
        assertNull($asset);
    }

    public function testFromJson()
    {
        $json = [
            'asset_type' => Asset::TYPE_NATIVE
        ];
        $asset = Asset::fromJson($json);
        assertEquals(AssetTypeNative::class, get_class($asset));

        $json = [
            'asset_type' => Asset::TYPE_CREDIT_ALPHANUM_4,
            'asset_code' => 'USD',
            'asset_issuer' => $this->issuerAccountId
        ];
        $asset = Asset::fromJson($json);
        assertEquals(AssetTypeCreditAlphanum4::class, get_class($asset));
        assertEquals("USD", $asset->getCode());
        assertEquals($this->issuerAccountId, $asset->getIssuer());
    }

    public function testToXdr()
    {
        $asset = Asset::native();
        $xdr = $asset->toXdr();
        assertNotNull($xdr);

        $asset = Asset::createNonNativeAsset("USD", $this->issuerAccountId);
        $xdr = $asset->toXdr();
        assertNotNull($xdr);
        assertEquals("USD", $xdr->getAlphaNum4()->getAssetCode());
    }

    public function testFromXdrNative()
    {
        $asset = Asset::native();
        $xdr = $asset->toXdr();
        $parsed = Asset::fromXdr($xdr);

        assertEquals(AssetTypeNative::class, get_class($parsed));
    }

    public function testFromXdrAlphaNum4()
    {
        $asset = Asset::createNonNativeAsset("USD", $this->issuerAccountId);
        $xdr = $asset->toXdr();
        $parsed = Asset::fromXdr($xdr);

        assertEquals(AssetTypeCreditAlphanum4::class, get_class($parsed));
        assertEquals("USD", $parsed->getCode());
        assertEquals($this->issuerAccountId, $parsed->getIssuer());
    }

    public function testFromXdrAlphaNum12()
    {
        $asset = Asset::createNonNativeAsset("TESTCOIN", $this->issuerAccountId);
        $xdr = $asset->toXdr();
        $parsed = Asset::fromXdr($xdr);

        assertEquals(AssetTypeCreditAlphanum12::class, get_class($parsed));
        assertEquals("TESTCOIN", $parsed->getCode());
        assertEquals($this->issuerAccountId, $parsed->getIssuer());
    }

    public function testToXdrChangeTrustAsset()
    {
        $asset = Asset::createNonNativeAsset("USD", $this->issuerAccountId);
        $xdr = $asset->toXdrChangeTrustAsset();
        assertNotNull($xdr);
    }

    public function testToXdrTrustlineAsset()
    {
        $asset = Asset::createNonNativeAsset("USD", $this->issuerAccountId);
        $xdr = $asset->toXdrTrustlineAsset();
        assertNotNull($xdr);
    }

    public function testEquals()
    {
        $asset1 = Asset::native();
        $asset2 = Asset::native();
        assertEquals($asset1->getType(), $asset2->getType());

        $asset3 = Asset::createNonNativeAsset("USD", $this->issuerAccountId);
        $asset4 = Asset::createNonNativeAsset("USD", $this->issuerAccountId);
        assertEquals($asset3->getCode(), $asset4->getCode());
        assertEquals($asset3->getIssuer(), $asset4->getIssuer());

        $asset5 = Asset::createNonNativeAsset("EUR", $this->issuerAccountId);
        assertTrue($asset3->getCode() !== $asset5->getCode());
    }
}
