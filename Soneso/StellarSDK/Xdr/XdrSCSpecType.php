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
    const SC_SPEC_TYPE_BOOL = 1;
    const SC_SPEC_TYPE_VOID = 2;
    const SC_SPEC_TYPE_STATUS = 3;
    const SC_SPEC_TYPE_U32 = 4;
    const SC_SPEC_TYPE_I32 = 5;
    const SC_SPEC_TYPE_U64 = 6;
    const SC_SPEC_TYPE_I64 = 7;
    const SC_SPEC_TYPE_TIMEPOINT = 8;
    const SC_SPEC_TYPE_DURATION = 9;
    const SC_SPEC_TYPE_U128 = 10;
    const SC_SPEC_TYPE_I128 = 11;
    const SC_SPEC_TYPE_U256 = 12;
    const SC_SPEC_TYPE_I256 = 13;
    const SC_SPEC_TYPE_BYTES = 14;
    const SC_SPEC_TYPE_STRING = 16;
    const SC_SPEC_TYPE_SYMBOL = 17;
    const SC_SPEC_TYPE_ADDRESS = 19;

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

    public static function VAL() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_VAL);
    }

    public static function BOOL() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_BOOL);
    }

    public static function VOID() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_VOID);
    }

    public static function STATUS() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_STATUS);
    }

    public static function U32() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_U32);
    }

    public static function I32() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_I32);
    }

    public static function U64() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_U64);
    }

    public static function I64() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_I64);
    }

    public static function TIMEPOINT() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_TIMEPOINT);
    }

    public static function DURATION() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_DURATION);
    }

    public static function I128() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_I128);
    }

    public static function U128() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_U128);
    }

    public static function I256() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_I256);
    }

    public static function U256() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_U256);
    }

    public static function BYTES() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_BYTES);
    }

    public static function STRING() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_STRING);
    }

    public static function SYMBOL() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_SYMBOL);
    }


    public static function ADDRESS() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_ADDRESS);
    }

    public static function OPTION() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_OPTION);
    }

    public static function RESULT() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_RESULT);
    }

    public static function VEC() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_VEC);
    }

    public static function SET() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_SET);
    }

    public static function MAP() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_MAP);
    }

    public static function TUPLE() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_TUPLE);
    }

    public static function BYTES_N() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_BYTES_N);
    }

    public static function UDT() :  XdrSCSpecType {
        return new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_UDT);
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