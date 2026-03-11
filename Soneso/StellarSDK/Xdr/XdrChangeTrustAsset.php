<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrChangeTrustAsset extends XdrChangeTrustAssetBase
{
    public static function fromXdrAsset(XdrAsset $xdrAsset) : XdrChangeTrustAsset {
        $result = new XdrChangeTrustAsset($xdrAsset->getType());
        switch ($xdrAsset->getType()->getValue()) {
            case XdrAssetType::ASSET_TYPE_NATIVE:
                break;
            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4:
                $result->setAlphaNum4($xdrAsset->getAlphaNum4());
                break;
            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12:
                $result->setAlphaNum12($xdrAsset->getAlphaNum12());
                break;
            case XdrAssetType::ASSET_TYPE_POOL_SHARE:
                throw new \InvalidArgumentException('XdrAsset cannot represent ASSET_TYPE_POOL_SHARE. Use XdrChangeTrustAsset directly.');
        }
        return $result;
    }
}
