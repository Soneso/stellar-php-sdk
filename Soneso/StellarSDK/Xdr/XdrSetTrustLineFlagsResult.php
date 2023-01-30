<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSetTrustLineFlagsResult
{
    private XdrSetTrustLineFlagsResultCode $resultCode;

    /**
     * @return XdrSetTrustLineFlagsResultCode
     */
    public function getResultCode(): XdrSetTrustLineFlagsResultCode
    {
        return $this->resultCode;
    }

    /**
     * @param XdrSetTrustLineFlagsResultCode $resultCode
     */
    public function setResultCode(XdrSetTrustLineFlagsResultCode $resultCode): void
    {
        $this->resultCode = $resultCode;
    }

    public function encode(): string
    {
        return $this->resultCode->encode();
    }

    public static function decode(XdrBuffer $xdr):XdrSetTrustLineFlagsResult {
        $result = new XdrSetTrustLineFlagsResult();
        $resultCode = XdrSetTrustLineFlagsResultCode::decode($xdr);
        $result->resultCode = $resultCode;
        return $result;
    }
}