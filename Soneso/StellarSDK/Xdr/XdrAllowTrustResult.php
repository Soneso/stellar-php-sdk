<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrAllowTrustResult
{

    private XdrAllowTrustResultCode $resultCode;

    /**
     * @return XdrAllowTrustResultCode
     */
    public function getResultCode(): XdrAllowTrustResultCode
    {
        return $this->resultCode;
    }

    /**
     * @param XdrAllowTrustResultCode $resultCode
     */
    public function setResultCode(XdrAllowTrustResultCode $resultCode): void
    {
        $this->resultCode = $resultCode;
    }


    public function encode(): string
    {
        return $this->resultCode->encode();
    }

    public static function decode(XdrBuffer $xdr):XdrAllowTrustResult {
        $result = new XdrAllowTrustResult();
        $resultCode = XdrAllowTrustResultCode::decode($xdr);
        $result->resultCode = $resultCode;
        return $result;
    }
}