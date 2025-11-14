<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Constants\StellarConstants;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum4;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrChangeTrustAsset;

/**
 * Represents an issued asset with a 1-4 character alphanumeric code
 *
 * AlphaNum4 assets are identified by a short code (1-4 characters) and the
 * issuer's account ID. Common examples include USD, EUR, BTC, etc.
 *
 * Usage:
 * <code>
 * $usd = new AssetTypeCreditAlphanum4("USD", "GISSUER...");
 * // or use the factory method
 * $usd = Asset::createNonNativeAsset("USD", "GISSUER...");
 * </code>
 *
 * @package Soneso\StellarSDK
 * @see AssetTypeCreditAlphanum12 For assets with 5-12 character codes
 * @since 1.0.0
 */
class AssetTypeCreditAlphanum4 extends AssetTypeCreditAlphanum
{

    /**
     * Creates an AlphaNum4 asset with code validation
     *
     * @param string $code Asset code (1-4 characters)
     * @param string $issuer Issuer account ID (public key starting with G)
     * @throws \RuntimeException If the code length is invalid
     */
    public function __construct(string $code, string $issuer) {
        $codeLen = strlen($code);
        if ($codeLen < StellarConstants::ASSET_CODE_MIN_LENGTH || $codeLen > StellarConstants::ASSET_CODE_ALPHANUMERIC_4_MAX_LENGTH) {
            throw new \RuntimeException("invalid asset code length: " . $codeLen);
        }
        parent::__construct($code, $issuer);
    }

    /**
     * Returns the asset type identifier
     *
     * @return string Always returns "credit_alphanum4"
     */
    public function getType(): string
    {
        return Asset::TYPE_CREDIT_ALPHANUM_4;
    }

    /**
     * Converts this asset to its XDR representation
     *
     * @return XdrAsset XDR format of this AlphaNum4 asset
     */
    public function toXdr(): XdrAsset
    {
        $xrAssetType = new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4);
        $xdrAsset = new XdrAsset($xrAssetType);
        $xdrIssuer = new XdrAccountID($this->getIssuer());
        $a4 = new XdrAssetAlphaNum4($this->getCode(), $xdrIssuer);
        $xdrAsset->setAlphaNum4($a4);
        return $xdrAsset;
    }
}