<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrLiquidityPoolWithdrawResult
{
    private XdrLiquidityPoolWithdrawResultCode $resultCode;

    /**
     * @return XdrLiquidityPoolWithdrawResultCode
     */
    public function getResultCode(): XdrLiquidityPoolWithdrawResultCode
    {
        return $this->resultCode;
    }

    /**
     * @param XdrLiquidityPoolWithdrawResultCode $resultCode
     */
    public function setResultCode(XdrLiquidityPoolWithdrawResultCode $resultCode): void
    {
        $this->resultCode = $resultCode;
    }

    public function encode(): string
    {
        return $this->resultCode->encode();
    }

    public static function decode(XdrBuffer $xdr):XdrLiquidityPoolWithdrawResult {
        $result = new XdrLiquidityPoolWithdrawResult();
        $resultCode = XdrLiquidityPoolWithdrawResultCode::decode($xdr);
        $result->resultCode = $resultCode;
        return $result;
    }
}