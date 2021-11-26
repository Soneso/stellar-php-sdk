<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrBeginSponsoringFutureReservesOperation
{
    private XdrAccountID $sponsoredID;

    public function __construct(XdrAccountID $sponsoredID) {
        $this->sponsoredID = $sponsoredID;
    }

    /**
     * @return XdrAccountID
     */
    public function getSponsoredID(): XdrAccountID
    {
        return $this->sponsoredID;
    }

    public function encode() : string {
        return $this->sponsoredID->encode();
    }

    public static function decode(XdrBuffer $xdr): XdrBeginSponsoringFutureReservesOperation {
        $sponsoredID = XdrAccountID::decode($xdr);
        return new XdrBeginSponsoringFutureReservesOperation($sponsoredID);
    }
}