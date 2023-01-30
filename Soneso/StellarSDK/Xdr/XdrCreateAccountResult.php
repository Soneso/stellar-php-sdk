<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrCreateAccountResult
{
    private XdrOperationResultCode $resultCode;

    /**
     * @return XdrOperationResultCode
     */
    public function getResultCode(): XdrOperationResultCode
    {
        return $this->resultCode;
    }

    /**
     * @param XdrOperationResultCode $resultCode
     */
    public function setResultCode(XdrOperationResultCode $resultCode): void
    {
        $this->resultCode = $resultCode;
    }

    public function encode(): string {
        return $this->resultCode->encode();
    }

    public static function decode(XdrBuffer $xdr):XdrCreateAccountResult {
        $result = new XdrCreateAccountResult();
        $resultCode = XdrOperationResultCode::decode($xdr);
        $result->resultCode = $resultCode;
        return $result;
    }
}