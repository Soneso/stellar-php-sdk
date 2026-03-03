<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use InvalidArgumentException;
use Soneso\StellarSDK\Constants\StellarConstants;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrChangeTrustAsset;
use Soneso\StellarSDK\Xdr\XdrTrustlineAsset;

/**
 * Base class representing an asset on the Stellar network
 *
 * Assets are representations of value on Stellar. This abstract class defines the interface
 * for all asset types including native XLM, issued assets, and liquidity pool shares.
 *
 * Asset Types:
 * - Native: XLM, the native cryptocurrency of Stellar
 * - Credit Alphanum 4: Issued assets with codes 1-4 characters (e.g., USD, BTC)
 * - Credit Alphanum 12: Issued assets with codes 5-12 characters (e.g., MYCOIN)
 * - Pool Share: Liquidity pool shares representing participation in an AMM pool
 *
 * Usage:
 * <code>
 * // Native asset (XLM)
 * $xlm = Asset::native();
 *
 * // Issued asset
 * $usd = Asset::createNonNativeAsset("USD", "GISSUER...");
 *
 * // From canonical form
 * $asset = Asset::createFromCanonicalForm("USD:GISSUER...");
 * </code>
 *
 * @package Soneso\StellarSDK
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 */
abstract class Asset {

    public const TYPE_NATIVE = "native";
    public const TYPE_CREDIT_ALPHANUM_4 = "credit_alphanum4";
    public const TYPE_CREDIT_ALPHANUM_12 = "credit_alphanum12";
    public const TYPE_POOL_SHARE = "liquidty_pool_shares";

    /**
     * Returns the type of this asset
     *
     * @return string One of TYPE_NATIVE, TYPE_CREDIT_ALPHANUM_4, TYPE_CREDIT_ALPHANUM_12, or TYPE_POOL_SHARE
     */
    public abstract function getType(): string;

    /**
     * Creates an asset from its type, code, and issuer
     *
     * @param string $type One of the TYPE_* constants
     * @param string|null $code Asset code (required for non-native assets)
     * @param string|null $issuer Issuer account ID (required for non-native assets)
     * @return Asset The created asset
     * @throws \RuntimeException If parameters are invalid or type is unsupported
     */
    public static function create(string $type, ?string $code = null, ?string $issuer = null) : Asset {
        if (Asset::TYPE_NATIVE == $type) {
            return new AssetTypeNative();
        } else if (Asset::TYPE_CREDIT_ALPHANUM_4 == $type || Asset::TYPE_CREDIT_ALPHANUM_12 == $type) {
            if ($code === null) {
                throw new \RuntimeException("asset code can not be null");
            }

            if ($issuer === null) {
                throw new \RuntimeException("asset issuer can not be null");
            }

            return Asset::createNonNativeAsset($code, $issuer);

        }
        throw new \RuntimeException("unsupported asset type: " . $type);
    }

    /**
     * Creates a non-native asset with automatic type detection based on code length
     *
     * The asset type (AlphaNum4 or AlphaNum12) is determined automatically based on
     * the length of the asset code.
     *
     * @param string $code Asset code (1-12 characters)
     * @param string $issuer Issuer account ID (public key starting with G)
     * @return AssetTypeCreditAlphanum The created asset (AlphaNum4 or AlphaNum12)
     * @throws \RuntimeException If the code length is invalid
     */
    public static function createNonNativeAsset(string $code, string $issuer) : AssetTypeCreditAlphanum {
        $codeLen = strlen($code);
        if ($codeLen >= StellarConstants::ASSET_CODE_MIN_LENGTH && $codeLen <= StellarConstants::ASSET_CODE_ALPHANUMERIC_4_MAX_LENGTH) {
            return new AssetTypeCreditAlphanum4($code, $issuer);
        } else if ($codeLen > StellarConstants::ASSET_CODE_ALPHANUMERIC_4_MAX_LENGTH && $codeLen <= StellarConstants::ASSET_CODE_ALPHANUMERIC_12_MAX_LENGTH) {
            return new AssetTypeCreditAlphanum12($code, $issuer);
        } else {
            throw new \RuntimeException("invalid asset code length: " . $codeLen);
        }
    }

    /**
     * Returns the canonical string representation of an asset
     *
     * Format:
     * - Native: "native"
     * - Issued: "CODE:ISSUER" (e.g., "USD:GABC...")
     *
     * @param Asset $asset The asset to convert
     * @return string The canonical form
     * @throws \RuntimeException If the asset type is unsupported
     */
    public static function canonicalForm(Asset $asset) : string {
        if ($asset instanceof AssetTypeNative) {
            return "native";
        } else if ($asset instanceof AssetTypeCreditAlphanum) {
            return $asset->getCode() . ":" . $asset->getIssuer();
        }
        throw new \RuntimeException("unsupported asset type: " . $asset->getType());
    }

    /**
     * Creates an asset from its canonical string representation
     *
     * Accepted formats:
     * - "native" or "XLM" for native XLM
     * - "CODE:ISSUER" for issued assets (e.g., "USD:GABC...")
     *
     * @param string $canonicalForm The canonical asset string
     * @return Asset|null The created asset, or null if the format is invalid
     */
    public static function createFromCanonicalForm(string $canonicalForm) : ?Asset {
        if ($canonicalForm == 'XLM' || $canonicalForm == "native") {
            return new AssetTypeNative();
        } else {
            $components = explode(":", $canonicalForm);
            if (count($components) == 2) {
                $code = $components[0];
                $issuer = $components[1];
                if (strlen($code) <= StellarConstants::ASSET_CODE_ALPHANUMERIC_4_MAX_LENGTH) {
                    return new AssetTypeCreditAlphanum4($code, $issuer);
                } else if (strlen($code) <= StellarConstants::ASSET_CODE_ALPHANUMERIC_12_MAX_LENGTH) {
                    return new AssetTypeCreditAlphanum12($code, $issuer);
                }
            }
        }
        return null;
    }

    /**
     * Creates the native XLM asset
     *
     * @return AssetTypeNative The native XLM asset
     */
    public static function native() : AssetTypeNative {
        return new AssetTypeNative();
    }

    /**
     * Creates an asset from JSON data (typically from Horizon responses)
     *
     * @param array $json JSON data with asset_type, asset_code, and asset_issuer fields
     * @return Asset The created asset
     */
    public static function fromJson(array $json) : Asset {
        if (Asset::TYPE_NATIVE == $json['asset_type']) {
            return new AssetTypeNative();
        } else {
            return Asset::createNonNativeAsset($json['asset_code'], $json['asset_issuer']);
        }
    }

    /**
     * Converts this asset to its XDR representation
     *
     * @return XdrAsset XDR format of this asset
     */
    public abstract function toXdr() : XdrAsset;

    /**
     * Converts this asset to XDR ChangeTrustAsset format
     *
     * @return XdrChangeTrustAsset XDR format for change trust operations
     */
    public function toXdrChangeTrustAsset(): XdrChangeTrustAsset
    {
        return XdrChangeTrustAsset::fromXdrAsset($this->toXdr());
    }

    /**
     * Creates an Asset from its XDR representation
     *
     * @param XdrAsset $xdrAsset The XDR asset data
     * @return Asset The reconstructed asset
     * @throws InvalidArgumentException If the asset type is unknown
     */
    public static function fromXdr(XdrAsset $xdrAsset) : Asset {
        switch ($xdrAsset->getType()->getValue()) {
            case XdrAssetType::ASSET_TYPE_NATIVE:
                return new AssetTypeNative();
            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4:
                $assetCode4 = $xdrAsset->getAlphaNum4()->getAssetCode();
                $issuer = $xdrAsset->getAlphaNum4()->getIssuer()->getAccountId();
                return new AssetTypeCreditAlphanum4($assetCode4, $issuer);
            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12:
                $assetCode12 = $xdrAsset->getAlphaNum12()->getAssetCode();
                $issuer = $xdrAsset->getAlphaNum12()->getIssuer()->getAccountId();
                return new AssetTypeCreditAlphanum12($assetCode12, $issuer);
            case XdrAssetType::ASSET_TYPE_POOL_SHARE:
                if ($xdrAsset instanceof XdrChangeTrustAsset) {
                    $a = $xdrAsset->getLiquidityPool()->getConstantProduct()->getAssetA();
                    $b = $xdrAsset->getLiquidityPool()->getConstantProduct()->getAssetB();
                    return new AssetTypePoolShare(Asset::fromXdr($a), Asset::fromXdr($b));
                } else {
                    throw new InvalidArgumentException("Unknown pool share asset type");
                }
            default:
                throw new InvalidArgumentException("Unknown asset type " . $xdrAsset->getType()->getValue());
        }
    }

    /**
     * Converts this asset to XDR TrustlineAsset format
     *
     * @return XdrTrustlineAsset XDR format for trustline operations
     */
    public function toXdrTrustlineAsset(): XdrTrustlineAsset
    {
        return XdrTrustlineAsset::fromXdrAsset($this->toXdr());
    }
}