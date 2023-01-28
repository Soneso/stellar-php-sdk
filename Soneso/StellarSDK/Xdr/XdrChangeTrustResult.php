<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrChangeTrustResult
{

    private XdrChangeTrustResultCode $resultCode;

    /**
     * @return XdrChangeTrustResultCode
     */
    public function getResultCode(): XdrChangeTrustResultCode
    {
        return $this->resultCode;
    }

    /**
     * @param XdrChangeTrustResultCode $resultCode
     */
    public function setResultCode(XdrChangeTrustResultCode $resultCode): void
    {
        $this->resultCode = $resultCode;
    }

    public function encode(): string
    {
        return $this->resultCode->encode();
    }

    public static function decode(XdrBuffer $xdr):XdrChangeTrustResult {
        $result = new XdrChangeTrustResult();
        $resultCode = XdrChangeTrustResultCode::decode($xdr);
        $result->resultCode = $resultCode;
        return $result;
    }
}