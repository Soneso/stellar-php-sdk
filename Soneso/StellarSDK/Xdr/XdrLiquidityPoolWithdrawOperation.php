<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.



namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

class XdrLiquidityPoolWithdrawOperation
{
    private string $liquidityPoolID; //hash
    private BigInteger $amount;
    private BigInteger $maxAmountA;
    private BigInteger $maxAmountB;

    /**
     * @param string $liquidityPoolID
     * @param BigInteger $amount
     * @param BigInteger $maxAmountA
     * @param BigInteger $maxAmountB
     */
    public function __construct(string $liquidityPoolID, BigInteger $amount, BigInteger $maxAmountA, BigInteger $maxAmountB)
    {
        $this->liquidityPoolID = $liquidityPoolID;
        $this->amount = $amount;
        $this->maxAmountA = $maxAmountA;
        $this->maxAmountB = $maxAmountB;
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
    public function getAmount(): BigInteger
    {
        return $this->amount;
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
    public function encode(): string {
        $bytes = XdrEncoder::opaqueFixed($this->liquidityPoolID, 32);
        $bytes .= XdrEncoder::bigInteger64($this->amount);
        $bytes .= XdrEncoder::bigInteger64($this->maxAmountA);
        $bytes .= XdrEncoder::bigInteger64($this->maxAmountB);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrLiquidityPoolWithdrawOperation {
        $liquidityPoolID = $xdr->readOpaqueFixed(32);
        $amount = $xdr->readBigInteger64();
        $maxAmountA = $xdr->readBigInteger64();
        $maxAmountB = $xdr->readBigInteger64();
        return new XdrLiquidityPoolWithdrawOperation($liquidityPoolID, $amount, $maxAmountA, $maxAmountB);
    }
}