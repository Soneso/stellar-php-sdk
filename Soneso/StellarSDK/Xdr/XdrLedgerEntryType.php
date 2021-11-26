<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLedgerEntryType
{
    private int $value;

    const ACCOUNT = 0;
    const TRUSTLINE = 1;
    const OFFER = 2;
    const DATA = 3;
    const CLAIMABLE_BALANCE = 4;
    const LIQUIDITY_POOL = 5;

    public function __construct(int $value) {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    public function encode(): string {
        return XdrEncoder::integer32($this->value);
    }

    public static function decode(XdrBuffer $xdr) : XdrLedgerEntryType {
        $value = $xdr->readInteger32();
        return new XdrLedgerEntryType($value);
    }
}