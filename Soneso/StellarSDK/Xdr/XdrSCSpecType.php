<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecType
{
    public int $value;

    const SC_SPEC_TYPE_VAL = 0;

    // Types with no parameters.
    const SC_SPEC_TYPE_U32 = 1;
    const SC_SPEC_TYPE_I32 = 2;
    const SC_SPEC_TYPE_U64 = 3;
    const SC_SPEC_TYPE_I64 = 4;
    const SC_SPEC_TYPE_U128 = 5;
    const SC_SPEC_TYPE_I128 = 6;
    const SC_SPEC_TYPE_BOOL = 7;
    const SC_SPEC_TYPE_SYMBOL = 8;
    const SC_SPEC_TYPE_BITSET = 9;
    const SC_SPEC_TYPE_STATUS = 10;
    const SC_SPEC_TYPE_BYTES = 11;
    const SC_SPEC_TYPE_INVOKER = 12;
    const SC_SPEC_TYPE_ACCOUNT_ID = 13;

    // Types with parameters.
    const SC_SPEC_TYPE_OPTION = 1000;
    const SC_SPEC_TYPE_RESULT = 1001;
    const SC_SPEC_TYPE_VEC = 1002;
    const SC_SPEC_TYPE_SET = 1003;
    const SC_SPEC_TYPE_MAP = 1004;
    const SC_SPEC_TYPE_TUPLE = 1005;
    const SC_SPEC_TYPE_BYTES_N = 1006;

    // User defined types.
    const SC_SPEC_TYPE_UDT = 2000;

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

    public static function decode(XdrBuffer $xdr): XdrSCSpecType
    {
        $value = $xdr->readInteger32();
        return new XdrSCSpecType($value);
    }
}