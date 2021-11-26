<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrTrustlineAsset extends XdrAsset
{
    private string $poolId;

    /**
     * @return string
     */
    public function getPoolId(): string
    {
        return $this->poolId;
    }

    /**
     * @param string $poolId
     */
    public function setPoolId(string $poolId): void
    {
        $this->poolId = $poolId;
    } //hash

    public function encode() : string {
        $bytes = parent::encode();
        if ($this->type->getValue() == XdrAssetType::ASSET_TYPE_POOL_SHARE) {
            $bytes .= XdrEncoder::opaqueFixed($this->poolId, 32);
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrTrustlineAsset {
        $type = $xdr->readInteger32();
        $result = new XdrTrustlineAsset(new XdrAssetType($type));
        switch ($type) {
            case XdrAssetType::ASSET_TYPE_NATIVE:
                break;
            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4:
                $alphanum4 = XdrAssetAlphaNum4::decode($xdr);
                $result->setAlphaNum4($alphanum4);
                break;
            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12:
                $alphanum12 = XdrAssetAlphaNum12::decode($xdr);
                $result->setAlphaNum12($alphanum12);
                break;
            case XdrAssetType::ASSET_TYPE_POOL_SHARE:
                $poolId = $xdr->readOpaqueFixed(32);
                $result->setPoolId($poolId);
                break;
        }
        return $result;
    }

    public static function fromXdrAsset(XdrAsset $xdrAsset) : XdrTrustlineAsset {
        $result = new XdrTrustlineAsset($xdrAsset->getType());
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
                if ($xdrAsset instanceof XdrTrustlineAsset) {
                    $result->setPoolId($xdrAsset->getPoolId());
                }
                break;
        }
        return $result;
    }
}