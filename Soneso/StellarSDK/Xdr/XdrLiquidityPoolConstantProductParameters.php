<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLiquidityPoolConstantProductParameters
{
    const LIQUIDITY_POOL_FEE_V18 = 30;

    private XdrAsset $assetA;
    private XdrAsset $assetB;
    private int $fee;

    public function __construct(XdrAsset $assetA, XdrAsset $assetB, ?int $fee = self::LIQUIDITY_POOL_FEE_V18) {
        $this->assetA = $assetA;
        $this->assetB = $assetB;
        $this->fee = $fee;
    }

    /**
     * @return XdrAsset
     */
    public function getAssetA(): XdrAsset
    {
        return $this->assetA;
    }

    /**
     * @return XdrAsset
     */
    public function getAssetB(): XdrAsset
    {
        return $this->assetB;
    }

    /**
     * @return int|null
     */
    public function getFee(): ?int
    {
        return $this->fee;
    }

    public function encode(): string {
        $bytes = $this->assetA->encode();
        $bytes .= $this->assetB->encode();
        $bytes .= XdrEncoder::integer32($this->fee);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr): XdrLiquidityPoolConstantProductParameters {
        $assetA = XdrAsset::decode($xdr);
        $assetB = XdrAsset::decode($xdr);
        $fee = $xdr->readInteger32();
        return new XdrLiquidityPoolConstantProductParameters($assetA, $assetB, $fee);
    }
}