<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrBeginSponsoringFutureReservesResult
{
    private XdrBeginSponsoringFutureReservesResultCode $resultCode;

    /**
     * @return XdrBeginSponsoringFutureReservesResultCode
     */
    public function getResultCode(): XdrBeginSponsoringFutureReservesResultCode
    {
        return $this->resultCode;
    }

    /**
     * @param XdrBeginSponsoringFutureReservesResultCode $resultCode
     */
    public function setResultCode(XdrBeginSponsoringFutureReservesResultCode $resultCode): void
    {
        $this->resultCode = $resultCode;
    }

    public function encode(): string
    {
        return $this->resultCode->encode();
    }

    public static function decode(XdrBuffer $xdr):XdrBeginSponsoringFutureReservesResult {
        $result = new XdrBeginSponsoringFutureReservesResult();
        $resultCode = XdrBeginSponsoringFutureReservesResultCode::decode($xdr);
        $result->resultCode = $resultCode;
        return $result;
    }
}