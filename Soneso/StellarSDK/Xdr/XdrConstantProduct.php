<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

class XdrConstantProduct
{
    public XdrLiquidityPoolConstantProductParameters $params;
    public BigInteger $reserveA;
    public BigInteger $reserveB;
    public BigInteger $totalPoolShares;
    public int $poolSharesTrustLineCount;

    /**
     * @param XdrLiquidityPoolConstantProductParameters $params
     * @param BigInteger $reserveA
     * @param BigInteger $reserveB
     * @param BigInteger $totalPoolShares
     * @param int $poolSharesTrustLineCount
     */
    public function __construct(XdrLiquidityPoolConstantProductParameters $params, BigInteger $reserveA, BigInteger $reserveB, BigInteger $totalPoolShares, int $poolSharesTrustLineCount)
    {
        $this->params = $params;
        $this->reserveA = $reserveA;
        $this->reserveB = $reserveB;
        $this->totalPoolShares = $totalPoolShares;
        $this->poolSharesTrustLineCount = $poolSharesTrustLineCount;
    }


    public function encode(): string {
        $bytes = $this->params->encode();
        $bytes .= XdrEncoder::bigInteger64($this->reserveA);
        $bytes .= XdrEncoder::bigInteger64($this->reserveB);
        $bytes .= XdrEncoder::bigInteger64($this->totalPoolShares);
        $bytes .= XdrEncoder::integer64($this->poolSharesTrustLineCount);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrConstantProduct {
        $params = XdrLiquidityPoolConstantProductParameters::decode($xdr);
        $reserveA = $xdr->readBigInteger64();
        $reserveB = $xdr->readBigInteger64();
        $totalPoolShares = $xdr->readBigInteger64();
        $poolSharesTrustLineCount = $xdr->readInteger64();

        return new XdrConstantProduct($params, $reserveA, $reserveB, $totalPoolShares, $poolSharesTrustLineCount);
    }

    /**
     * @return XdrLiquidityPoolConstantProductParameters
     */
    public function getParams(): XdrLiquidityPoolConstantProductParameters
    {
        return $this->params;
    }

    /**
     * @param XdrLiquidityPoolConstantProductParameters $params
     */
    public function setParams(XdrLiquidityPoolConstantProductParameters $params): void
    {
        $this->params = $params;
    }

    /**
     * @return BigInteger
     */
    public function getReserveA(): BigInteger
    {
        return $this->reserveA;
    }

    /**
     * @param BigInteger $reserveA
     */
    public function setReserveA(BigInteger $reserveA): void
    {
        $this->reserveA = $reserveA;
    }

    /**
     * @return BigInteger
     */
    public function getReserveB(): BigInteger
    {
        return $this->reserveB;
    }

    /**
     * @param BigInteger $reserveB
     */
    public function setReserveB(BigInteger $reserveB): void
    {
        $this->reserveB = $reserveB;
    }

    /**
     * @return BigInteger
     */
    public function getTotalPoolShares(): BigInteger
    {
        return $this->totalPoolShares;
    }

    /**
     * @param BigInteger $totalPoolShares
     */
    public function setTotalPoolShares(BigInteger $totalPoolShares): void
    {
        $this->totalPoolShares = $totalPoolShares;
    }

    /**
     * @return int
     */
    public function getPoolSharesTrustLineCount(): int
    {
        return $this->poolSharesTrustLineCount;
    }

    /**
     * @param int $poolSharesTrustLineCount
     */
    public function setPoolSharesTrustLineCount(int $poolSharesTrustLineCount): void
    {
        $this->poolSharesTrustLineCount = $poolSharesTrustLineCount;
    }
}