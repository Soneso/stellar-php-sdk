<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

class XdrClaimLiquidityAtom
{
    private string $liquidityPoolID; //hash
    private XdrAsset $assetSold;
    private BigInteger $amountSold;
    private XdrAsset $assetBought;
    private BigInteger $amountBought;

    public function __construct(string $liquidityPoolID, XdrAsset $assetSold, BigInteger $amountSold, XdrAsset $assetBought, BigInteger $amountBought) {
        $this->liquidityPoolID = $liquidityPoolID;
        $this->assetSold = $assetSold;
        $this->amountSold = $amountSold;
        $this->assetBought = $assetBought;
        $this->amountBought = $amountBought;
    }

    /**
     * @return string
     */
    public function getLiquidityPoolID(): string
    {
        return $this->liquidityPoolID;
    }

    /**
     * @return XdrAsset
     */
    public function getAssetSold(): XdrAsset
    {
        return $this->assetSold;
    }

    /**
     * @return BigInteger
     */
    public function getAmountSold(): BigInteger
    {
        return $this->amountSold;
    }

    /**
     * @return XdrAsset
     */
    public function getAssetBought(): XdrAsset
    {
        return $this->assetBought;
    }

    /**
     * @return BigInteger
     */
    public function getAmountBought(): BigInteger
    {
        return $this->amountBought;
    }

    public function encode() : string {
        $bytes = XdrEncoder::opaqueFixed($this->liquidityPoolID, 32);
        $bytes .= $this->assetSold->encode();
        $bytes .= XdrEncoder::bigInteger64($this->amountSold);
        $bytes .= $this->assetBought->encode();
        $bytes .= XdrEncoder::bigInteger64($this->amountBought);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrClaimLiquidityAtom {
        $liquidityPoolID = $xdr->readOpaqueFixed(32);
        $assetSold = XdrAsset::decode($xdr);
        $amountSold = $xdr->readBigInteger64();
        $assetBought = XdrAsset::decode($xdr);
        $amountBought = $xdr->readBigInteger64();
        return new XdrClaimLiquidityAtom($liquidityPoolID, $assetSold, $amountSold, $assetBought, $amountBought);
    }
}