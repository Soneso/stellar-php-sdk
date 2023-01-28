<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrEndSponsoringFutureReservesResult
{
    private XdrEndSponsoringFutureReservesResultCode $resultCode;

    /**
     * @return XdrEndSponsoringFutureReservesResultCode
     */
    public function getResultCode(): XdrEndSponsoringFutureReservesResultCode
    {
        return $this->resultCode;
    }

    /**
     * @param XdrEndSponsoringFutureReservesResultCode $resultCode
     */
    public function setResultCode(XdrEndSponsoringFutureReservesResultCode $resultCode): void
    {
        $this->resultCode = $resultCode;
    }

    public function encode(): string
    {
        return $this->resultCode->encode();
    }


    public static function decode(XdrBuffer $xdr):XdrEndSponsoringFutureReservesResult {
        $result = new XdrEndSponsoringFutureReservesResult();
        $resultCode = XdrEndSponsoringFutureReservesResultCode::decode($xdr);
        $result->resultCode = $resultCode;
        return $result;
    }
}