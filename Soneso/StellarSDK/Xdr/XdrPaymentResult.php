<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrPaymentResult
{
    private XdrPaymentResultCode $resultCode;

    /**
     * @return XdrPaymentResultCode
     */
    public function getResultCode(): XdrPaymentResultCode
    {
        return $this->resultCode;
    }

    /**
     * @param XdrPaymentResultCode $resultCode
     */
    public function setResultCode(XdrPaymentResultCode $resultCode): void
    {
        $this->resultCode = $resultCode;
    }

    public function encode(): string {
        return $this->resultCode->encode();
    }

    public static function decode(XdrBuffer $xdr):XdrPaymentResult {
        $result = new XdrPaymentResult();
        $resultCode = XdrPaymentResultCode::decode($xdr);
        $result->resultCode = $resultCode;
        return $result;
    }
}