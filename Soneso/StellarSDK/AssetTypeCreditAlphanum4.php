<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum4;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrChangeTrustAsset;

class AssetTypeCreditAlphanum4 extends AssetTypeCreditAlphanum
{

    public function __construct(string $code, string $issuer) {
        $codeLen = strlen($code);
        if ($codeLen < 1 || $codeLen > 4) {
            throw new \RuntimeException("invalid asset code length: " . $codeLen);
        }
        parent::__construct($code, $issuer);
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return Asset::TYPE_CREDIT_ALPHANUM_4;
    }

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