<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrChangeTrustAsset;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolConstantProductParameters;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolParameters;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolType;

class AssetTypePoolShare extends Asset
{
    private Asset $assetA;
    private Asset $assetB;

    public function __construct(Asset $assetA, Asset $assetB) {

        if (Asset::TYPE_POOL_SHARE == $assetA->getType() || Asset::TYPE_POOL_SHARE == $assetA->getType()) {
            throw new \RuntimeException("Asset can not be of type Asset::TYPE_POOL_SHARE");
        }
        if ($assetA->getType() == $assetB->getType() && Asset::TYPE_NATIVE == $assetA->getType()) {
            throw new \RuntimeException("Assets can not be both of type Asset::TYPE_NATIVE");
        }
        $sortError = false;
        if (strlen($assetA->getType()) > strlen($assetB->getType())) {
            $sortError = true;
        } else if (strlen($assetA->getType()) == strlen($assetB->getType())) {
            if($assetA instanceof AssetTypeCreditAlphanum && $assetB instanceof AssetTypeCreditAlphanum) {
                $codeCompare = strcmp($assetA->getCode(), $assetB->getCode());
                if ($codeCompare > 0 || ($codeCompare == 0 && strcmp($assetA->getIssuer(), $assetB->getIssuer()) > 0)) {
                    $sortError = true;
                }
            }
        }
        if ($sortError) {
            throw new \RuntimeException("Assets are in wrong order. Sort by: Native < AlphaNum4 < AlphaNum12, then by Code, then by Issuer, using lexicographic ordering.");
        }

        $this->assetA = $assetA;
        $this->assetB = $assetB;
    }

    /**
     * @return Asset
     */
    public function getAssetA(): Asset
    {
        return $this->assetA;
    }

    /**
     * @return Asset
     */
    public function getAssetB(): Asset
    {
        return $this->assetB;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return Asset::TYPE_POOL_SHARE;
    }

    public function toXdr(): XdrAsset
    {
        $lp = new XdrLiquidityPoolConstantProductParameters($this->assetA->toXdr(), $this->assetB->toXdr());
        $poolParameters = new XdrLiquidityPoolParameters(new XdrLiquidityPoolType(XdrLiquidityPoolType::LIQUIDITY_POOL_CONSTANT_PRODUCT), $lp);
        $xdrAsset = new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_POOL_SHARE));
        $xdrAsset->setLiquidityPool($poolParameters);
        return $xdrAsset;
    }
}