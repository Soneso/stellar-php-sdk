<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum12;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum4;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrChangeTrustAsset;

class AssetTypeCreditAlphanum12 extends AssetTypeCreditAlphanum
{

    public function __construct(string $code, string $issuer) {
        $codeLen = strlen($code);
        if ($codeLen < 5 || $codeLen > 12) {
            throw new \RuntimeException("invalid asset code length: " . $codeLen);
        }
        parent::__construct($code, $issuer);
    }

    /**
     * @inheritDoc
     */
    public function getType(): string {
        return Asset::TYPE_CREDIT_ALPHANUM_12;
    }

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