<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Constants\StellarConstants;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum12;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum4;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrChangeTrustAsset;

/**
 * Represents an issued asset with a 5-12 character alphanumeric code
 *
 * AlphaNum12 assets are identified by a longer code (5-12 characters) and the
 * issuer's account ID. These are used for assets with longer names like MYCOIN,
 * TOKENXYZ, etc.
 *
 * Usage:
 * <code>
 * $token = new AssetTypeCreditAlphanum12("MYTOKEN", "GISSUER...");
 * // or use the factory method
 * $token = Asset::createNonNativeAsset("MYTOKEN", "GISSUER...");
 * </code>
 *
 * @package Soneso\StellarSDK
 * @see AssetTypeCreditAlphanum4 For assets with 1-4 character codes
 * @since 1.0.0
 */
class AssetTypeCreditAlphanum12 extends AssetTypeCreditAlphanum
{

    /**
     * Creates an AlphaNum12 asset with code validation
     *
     * @param string $code Asset code (5-12 characters)
     * @param string $issuer Issuer account ID (public key starting with G)
     * @throws \RuntimeException If the code length is invalid
     */
    public function __construct(string $code, string $issuer) {
        $codeLen = strlen($code);
        if ($codeLen < StellarConstants::ASSET_CODE_ALPHANUMERIC_12_MIN_LENGTH || $codeLen > StellarConstants::ASSET_CODE_ALPHANUMERIC_12_MAX_LENGTH) {
            throw new \RuntimeException("invalid asset code length: " . $codeLen);
        }
        parent::__construct($code, $issuer);
    }

    /**
     * Returns the asset type identifier
     *
     * @return string Always returns "credit_alphanum12"
     */
    public function getType(): string {
        return Asset::TYPE_CREDIT_ALPHANUM_12;
    }

    /**
     * Converts this asset to its XDR representation
     *
     * @return XdrAsset XDR format of this AlphaNum12 asset
     */
    public function toXdr(): XdrAsset
    {
        $xrAssetType = new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12);
        $xdrAsset = new XdrAsset($xrAssetType);
        $xdrIssuer = new XdrAccountID($this->getIssuer());
        $a12 = new XdrAssetAlphaNum12($this->getCode(), $xdrIssuer);
        $xdrAsset->setAlphaNum12($a12);
        return $xdrAsset;
    }
}