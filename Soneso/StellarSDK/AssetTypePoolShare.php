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

/**
 * Represents a liquidity pool share asset in the Stellar network.
 *
 * Liquidity pool shares are synthetic assets that represent participation in
 * a liquidity pool. They are automatically created when depositing assets into
 * a constant product liquidity pool and burned when withdrawing.
 *
 * The pool is defined by two reserve assets (assetA and assetB) which must be
 * provided in a specific canonical order: Native < AlphaNum4 < AlphaNum12,
 * then by code lexicographically, then by issuer lexicographically.
 *
 * @package Soneso\StellarSDK
 * @see LiquidityPoolDepositOperation
 * @see LiquidityPoolWithdrawOperation
 * @link https://developers.stellar.org Stellar developer docs
 */
class AssetTypePoolShare extends Asset
{
    /**
     * The first reserve asset in the liquidity pool (in canonical order).
     *
     * @var Asset
     */
    private Asset $assetA;

    /**
     * The second reserve asset in the liquidity pool (in canonical order).
     *
     * @var Asset
     */
    private Asset $assetB;

    /**
     * Constructs a new liquidity pool share asset.
     *
     * Creates a pool share asset from two reserve assets. The assets must be
     * provided in canonical order and pass validation checks:
     * - Neither asset can be a pool share asset
     * - Both assets cannot be native XLM
     * - Assets must be ordered: Native < AlphaNum4 < AlphaNum12, then by code, then by issuer
     *
     * @param Asset $assetA The first reserve asset (in canonical order)
     * @param Asset $assetB The second reserve asset (in canonical order)
     *
     * @throws \RuntimeException If either asset is a pool share type
     * @throws \RuntimeException If both assets are native XLM
     * @throws \RuntimeException If assets are not in correct canonical order
     */
    public function __construct(Asset $assetA, Asset $assetB) {

        if (Asset::TYPE_POOL_SHARE == $assetA->getType() || Asset::TYPE_POOL_SHARE == $assetB->getType()) {
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
     * Gets the first reserve asset in the liquidity pool.
     *
     * @return Asset The first asset (in canonical order)
     */
    public function getAssetA(): Asset
    {
        return $this->assetA;
    }

    /**
     * Gets the second reserve asset in the liquidity pool.
     *
     * @return Asset The second asset (in canonical order)
     */
    public function getAssetB(): Asset
    {
        return $this->assetB;
    }

    /**
     * Gets the asset type identifier.
     *
     * @return string Always returns Asset::TYPE_POOL_SHARE for liquidity pool shares
     *
     * @inheritDoc
     */
    public function getType(): string
    {
        return Asset::TYPE_POOL_SHARE;
    }

    /**
     * Converts the liquidity pool share asset to XDR format.
     *
     * Creates an XDR representation of the pool share asset by encoding both
     * reserve assets and the constant product parameters. The resulting XDR
     * can be used in operations that work with liquidity pools.
     *
     * @return XdrAsset The XDR representation of this pool share asset
     */
    public function toXdr(): XdrAsset
    {
        $lp = new XdrLiquidityPoolConstantProductParameters($this->assetA->toXdr(), $this->assetB->toXdr());
        $poolParameters = new XdrLiquidityPoolParameters(new XdrLiquidityPoolType(XdrLiquidityPoolType::LIQUIDITY_POOL_CONSTANT_PRODUCT), $lp);
        $xdrAsset = new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_POOL_SHARE));
        $xdrAsset->setLiquidityPool($poolParameters);
        return $xdrAsset;
    }
}