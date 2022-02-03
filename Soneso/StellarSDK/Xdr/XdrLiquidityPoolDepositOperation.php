<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

class XdrLiquidityPoolDepositOperation
{
    private string $liquidityPoolID; //hash
    private BigInteger $maxAmountA;
    private BigInteger $maxAmountB;
    private XdrPrice $minPrice;
    private XdrPrice $maxPrice;

    /**
     * @param string $liquidityPoolID
     * @param BigInteger $maxAmountA
     * @param BigInteger $maxAmountB
     * @param XdrPrice $minPrice
     * @param XdrPrice $maxPrice
     */
    public function __construct(string $liquidityPoolID, BigInteger $maxAmountA, BigInteger $maxAmountB, XdrPrice $minPrice, XdrPrice $maxPrice)
    {
        $this->liquidityPoolID = $liquidityPoolID;
        $this->maxAmountA = $maxAmountA;
        $this->maxAmountB = $maxAmountB;
        $this->minPrice = $minPrice;
        $this->maxPrice = $maxPrice;
    }

    /**
     * @return string
     */
    public function getLiquidityPoolID(): string
    {
        return $this->liquidityPoolID;
    }

    /**
     * @return BigInteger
     */
    public function getMaxAmountA(): BigInteger
    {
        return $this->maxAmountA;
    }

    /**
     * @return BigInteger
     */
    public function getMaxAmountB(): BigInteger
    {
        return $this->maxAmountB;
    }

    /**
     * @return XdrPrice
     */
    public function getMinPrice(): XdrPrice
    {
        return $this->minPrice;
    }

    /**
     * @return XdrPrice
     */
    public function getMaxPrice(): XdrPrice
    {
        return $this->maxPrice;
    }

    public function encode(): string {
        $poolIdBytes = pack("H*", $this->liquidityPoolID);
        if (strlen($poolIdBytes) > 32) {
            $poolIdBytes = substr($poolIdBytes, -32);
        }
        $bytes = XdrEncoder::opaqueFixed($poolIdBytes, 32);
        $bytes .= XdrEncoder::bigInteger64($this->maxAmountA);
        $bytes .= XdrEncoder::bigInteger64($this->maxAmountB);
        $bytes .= $this->minPrice->encode();
        $bytes .= $this->maxPrice->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrLiquidityPoolDepositOperation {
        $liquidityPoolID = bin2hex($xdr->readOpaqueFixed(32));
        $maxAmountA = $xdr->readBigInteger64();
        $maxAmountB = $xdr->readBigInteger64();
        $minPrice = XdrPrice::decode($xdr);
        $maxPrice = XdrPrice::decode($xdr);
        return new XdrLiquidityPoolDepositOperation($liquidityPoolID, $maxAmountA, $maxAmountB, $minPrice, $maxPrice);
    }
}