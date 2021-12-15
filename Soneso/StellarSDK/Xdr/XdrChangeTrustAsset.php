<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrChangeTrustAsset extends XdrAsset
{
    private ?XdrLiquidityPoolParameters $liquidityPool = null;

    /**
     * @return XdrLiquidityPoolParameters|null
     */
    public function getLiquidityPool(): ?XdrLiquidityPoolParameters
    {
        return $this->liquidityPool;
    }

    /**
     * @param XdrLiquidityPoolParameters|null $liquidityPool
     */
    public function setLiquidityPool(?XdrLiquidityPoolParameters $liquidityPool): void
    {
        $this->liquidityPool = $liquidityPool;
    }


    public function encode() : string {
        $bytes = parent::encode();
        if ($this->type->getValue() == XdrAssetType::ASSET_TYPE_POOL_SHARE && $this->liquidityPool != null) {
            $bytes .= $this->liquidityPool->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrChangeTrustAsset {
        $type = $xdr->readInteger32();
        $result = new XdrChangeTrustAsset(new XdrAssetType($type));
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
                $liquidityPool = XdrLiquidityPoolParameters::decode($xdr);
                $result->setLiquidityPool($liquidityPool);
                break;
        }
        return $result;
    }

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
                if ($xdrAsset instanceof XdrChangeTrustAsset) {
                    $result->setLiquidityPool($xdrAsset->getLiquidityPool());
                }
                break;
        }
        return $result;
    }
}