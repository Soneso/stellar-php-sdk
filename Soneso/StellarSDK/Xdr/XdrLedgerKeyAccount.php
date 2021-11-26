<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLedgerKeyAccount
{
    private XdrAccountID $accountID;

    public function __construct(XdrAccountID $accountID) {
        $this->accountID = $accountID;
    }

    /**
     * @return XdrAccountID
     */
    public function getAccountID(): XdrAccountID
    {
        return $this->accountID;
    }

    public function encode(): string {
        return $this->accountID->encode();
    }

    public static function decode(XdrBuffer $xdr) : XdrLedgerKeyAccount {
        $acc = XdrAccountID::decode($xdr);
        return new XdrLedgerKeyAccount($acc);
    }
}