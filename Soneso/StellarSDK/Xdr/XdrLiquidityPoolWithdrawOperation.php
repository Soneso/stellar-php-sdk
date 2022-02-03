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
    private BigInteger $minAmountA;
    private BigInteger $minAmountB;

    /**
     * @param string $liquidityPoolID
     * @param BigInteger $amount
     * @param BigInteger $minAmountA
     * @param BigInteger $minAmountB
     */
    public function __construct(string $liquidityPoolID, BigInteger $amount, BigInteger $minAmountA, BigInteger $minAmountB)
    {
        $this->liquidityPoolID = $liquidityPoolID;
        $this->amount = $amount;
        $this->minAmountA = $minAmountA;
        $this->minAmountB = $minAmountB;
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
    public function getMinAmountA(): BigInteger
    {
        return $this->minAmountA;
    }

    /**
     * @return BigInteger
     */
    public function getMinAmountB(): BigInteger
    {
        return $this->minAmountB;
    }


    public function encode(): string {
        $poolIdBytes = pack("H*", $this->liquidityPoolID);
        if (strlen($poolIdBytes) > 32) {
            $poolIdBytes = substr($poolIdBytes, -32);
        }
        $bytes = XdrEncoder::opaqueFixed($poolIdBytes, 32);
        $bytes .= XdrEncoder::bigInteger64($this->amount);
        $bytes .= XdrEncoder::bigInteger64($this->minAmountA);
        $bytes .= XdrEncoder::bigInteger64($this->minAmountB);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrLiquidityPoolWithdrawOperation {
        $liquidityPoolID = bin2hex($xdr->readOpaqueFixed(32));
        $amount = $xdr->readBigInteger64();
        $minAmountA = $xdr->readBigInteger64();
        $minAmountB = $xdr->readBigInteger64();
        return new XdrLiquidityPoolWithdrawOperation($liquidityPoolID, $amount, $minAmountA, $minAmountB);
    }
}