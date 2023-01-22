<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLedgerEntryChangeType
{
    public int $value;

    const LEDGER_ENTRY_CREATED = 0;
    const LEDGER_ENTRY_UPDATED = 1;
    const LEDGER_ENTRY_REMOVED = 2;
    const LEDGER_ENTRY_STATE = 3;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    public function encode(): string
    {
        return XdrEncoder::integer32($this->value);
    }

    public static function decode(XdrBuffer $xdr): XdrLedgerEntryChangeType
    {
        $value = $xdr->readInteger32();
        return new XdrLedgerEntryChangeType($value);
    }
}