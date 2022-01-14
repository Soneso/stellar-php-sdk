<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrClaimClaimableBalanceOperation
{
    private XdrClaimableBalanceID $balanceID;

    public function __construct(XdrClaimableBalanceID $balanceID) {
        $this->balanceID = $balanceID;
    }

    /**
     * @return XdrClaimableBalanceID
     */
    public function getBalanceID(): XdrClaimableBalanceID
    {
        return $this->balanceID;
    }

    public function encode() : string {
        return $this->balanceID->encode();
    }

    public static function decode(XdrBuffer $xdr) :  XdrClaimClaimableBalanceOperation {
        $balanceID = XdrClaimableBalanceID::decode($xdr);
        return new XdrClaimClaimableBalanceOperation($balanceID);
    }
}