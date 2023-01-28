<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrCreateClaimableBalanceResult
{
    public XdrCreateClaimableBalanceResultCode $code;
    public ?XdrClaimableBalanceID $balanceID = null;

    /**
     * @return XdrCreateClaimableBalanceResultCode
     */
    public function getCode(): XdrCreateClaimableBalanceResultCode
    {
        return $this->code;
    }

    /**
     * @return XdrClaimableBalanceID|null
     */
    public function getBalanceID(): ?XdrClaimableBalanceID
    {
        return $this->balanceID;
    }

    public function encode(): string
    {
        $bytes = $this->code->encode();
        if ($this->code->getValue() == XdrCreateClaimableBalanceResultCode::SUCCESS) {
            $bytes .= $this->balanceID->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):XdrCreateClaimableBalanceResult {
        $result = new XdrCreateClaimableBalanceResult();
        $resultCode = XdrCreateClaimableBalanceResultCode::decode($xdr);
        $result->code = $resultCode;
        if ($resultCode->getValue() == XdrCreateClaimableBalanceResultCode::SUCCESS) {
            $result->balanceID = XdrClaimableBalanceID::decode($xdr);
        }
        return $result;
    }
}