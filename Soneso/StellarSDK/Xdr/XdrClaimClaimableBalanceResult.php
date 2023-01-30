<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrClaimClaimableBalanceResult
{
    private XdrClaimClaimableBalanceResultCode $resultCode;

    /**
     * @return XdrClaimClaimableBalanceResultCode
     */
    public function getResultCode(): XdrClaimClaimableBalanceResultCode
    {
        return $this->resultCode;
    }

    /**
     * @param XdrClaimClaimableBalanceResultCode $resultCode
     */
    public function setResultCode(XdrClaimClaimableBalanceResultCode $resultCode): void
    {
        $this->resultCode = $resultCode;
    }

    public function encode(): string
    {
        return $this->resultCode->encode();
    }

    public static function decode(XdrBuffer $xdr):XdrClaimClaimableBalanceResult {
        $result = new XdrClaimClaimableBalanceResult();
        $resultCode = XdrClaimClaimableBalanceResultCode::decode($xdr);
        $result->resultCode = $resultCode;
        return $result;
    }
}