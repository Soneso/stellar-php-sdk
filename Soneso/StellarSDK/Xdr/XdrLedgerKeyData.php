<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLedgerKeyData
{
    private XdrAccountID $accountID;
    private string $dataName; // string64

    public function __construct(XdrAccountID $accountID, string $dataName) {
        $this->accountID = $accountID;
        $this->dataName = $dataName;
    }

    /**
     * @return XdrAccountID
     */
    public function getAccountID(): XdrAccountID
    {
        return $this->accountID;
    }

    /**
     * @return int|string
     */
    public function getDataName(): int|string
    {
        return $this->dataName;
    }

    public function encode(): string {
        $bytes = $this->accountID->encode();
        $bytes .= XdrEncoder::string($this->dataName, 64);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrLedgerKeyData {
        $acc = XdrAccountID::decode($xdr);
        $dataName = $xdr->readString(64);
        return new XdrLedgerKeyData($acc, $dataName);
    }
}