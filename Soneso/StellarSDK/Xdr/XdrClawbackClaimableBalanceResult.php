<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrClawbackClaimableBalanceResult
{
    private XdrClawbackClaimableBalanceResultCode $resultCode;

    public function __construct(XdrClawbackClaimableBalanceResultCode $resultCode) {
        $this->resultCode = $resultCode;
    }

    /**
     * @return XdrClawbackClaimableBalanceResultCode
     */
    public function getResultCode(): XdrClawbackClaimableBalanceResultCode
    {
        return $this->resultCode;
    }

    public function encode(): string
    {
        return $this->resultCode->encode();
    }

    public static function decode(XdrBuffer $xdr):XdrClawbackClaimableBalanceResult {
        $resultCode = XdrClawbackClaimableBalanceResultCode::decode($xdr);
        return new XdrClawbackClaimableBalanceResult($resultCode);
    }
}