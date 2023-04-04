<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecEntryKind
{
    public int $value;

    const SC_SPEC_ENTRY_FUNCTION_V0 = 0;
    const SC_SPEC_ENTRY_UDT_STRUCT_V0 = 1;
    const SC_SPEC_ENTRY_UDT_UNION_V0 = 2;
    const SC_SPEC_ENTRY_UDT_ENUM_V0 = 3;
    const SC_SPEC_ENTRY_UDT_ERROR_ENUM_V0 = 4;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function FUNCTION_V0() :  XdrSCSpecEntryKind {
        return new XdrSCSpecEntryKind(XdrSCSpecEntryKind::SC_SPEC_ENTRY_FUNCTION_V0);
    }

    public static function UDT_STRUCT_V0() :  XdrSCSpecEntryKind {
        return new XdrSCSpecEntryKind(XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_STRUCT_V0);
    }

    public static function UDT_UNION_V0() :  XdrSCSpecEntryKind {
        return new XdrSCSpecEntryKind(XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_UNION_V0);
    }

    public static function UDT_ENUM_V0() :  XdrSCSpecEntryKind {
        return new XdrSCSpecEntryKind(XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ENUM_V0);
    }

    public static function UDT_ERROR_ENUM_V0 () :  XdrSCSpecEntryKind {
        return new XdrSCSpecEntryKind(XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ERROR_ENUM_V0);
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

    public static function decode(XdrBuffer $xdr): XdrSCSpecEntryKind
    {
        $value = $xdr->readInteger32();
        return new XdrSCSpecEntryKind($value);
    }
}