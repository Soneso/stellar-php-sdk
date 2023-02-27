<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLedgerEntryType
{
    public int $value;

    const ACCOUNT = 0;
    const TRUSTLINE = 1;
    const OFFER = 2;
    const DATA = 3;
    const CLAIMABLE_BALANCE = 4;
    const LIQUIDITY_POOL = 5;
    const CONTRACT_DATA = 6;
    const CONTRACT_CODE = 7;
    const CONFIG_SETTING = 8;

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

    public static function ACCOUNT() : XdrLedgerEntryType {
        return new XdrLedgerEntryType(XdrLedgerEntryType::ACCOUNT);
    }

    public static function TRUSTLINE() : XdrLedgerEntryType {
        return new XdrLedgerEntryType(XdrLedgerEntryType::TRUSTLINE);
    }

    public static function OFFER() : XdrLedgerEntryType {
        return new XdrLedgerEntryType(XdrLedgerEntryType::OFFER);
    }

    public static function DATA() : XdrLedgerEntryType {
        return new XdrLedgerEntryType(XdrLedgerEntryType::DATA);
    }

    public static function CLAIMABLE_BALANCE() : XdrLedgerEntryType {
        return new XdrLedgerEntryType(XdrLedgerEntryType::CLAIMABLE_BALANCE);
    }

    public static function LIQUIDITY_POOL() : XdrLedgerEntryType {
        return new XdrLedgerEntryType(XdrLedgerEntryType::LIQUIDITY_POOL);
    }

    public static function CONTRACT_DATA() : XdrLedgerEntryType {
        return new XdrLedgerEntryType(XdrLedgerEntryType::CONTRACT_DATA);
    }

    public static function CONTRACT_CODE() : XdrLedgerEntryType {
        return new XdrLedgerEntryType(XdrLedgerEntryType::CONTRACT_CODE);
    }

    public static function CONFIG_SETTING() : XdrLedgerEntryType {
        return new XdrLedgerEntryType(XdrLedgerEntryType::CONFIG_SETTING);
    }
}