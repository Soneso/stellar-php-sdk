<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCHostValErrorCode
{
    public int $value;

    const HOST_VALUE_UNKNOWN_ERROR = 0;
    const HOST_VALUE_RESERVED_TAG_VALUE = 1;
    const HOST_VALUE_UNEXPECTED_VAL_TYPE = 2;
    const HOST_VALUE_U63_OUT_OF_RANGE = 3;
    const HOST_VALUE_U32_OUT_OF_RANGE = 4;
    const HOST_VALUE_STATIC_UNKNOWN = 5;
    const HOST_VALUE_MISSING_OBJECT = 6;
    const HOST_VALUE_SYMBOL_TOO_LONG = 7;
    const HOST_VALUE_SYMBOL_BAD_CHAR = 8;
    const HOST_VALUE_SYMBOL_CONTAINS_NON_UTF8 = 9;
    const HOST_VALUE_BITSET_TOO_MANY_BITS = 10;
    const HOST_VALUE_STATUS_UNKNOWN = 11;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function UNKNOWN_ERROR() : XdrSCHostValErrorCode {
        return new XdrSCHostValErrorCode(XdrSCHostValErrorCode::HOST_VALUE_UNKNOWN_ERROR);
    }

    public static function RESERVED_TAG_VALUE() : XdrSCHostValErrorCode {
        return new XdrSCHostValErrorCode(XdrSCHostValErrorCode::HOST_VALUE_RESERVED_TAG_VALUE);
    }

    public static function UNEXPECTED_VAL_TYPE() : XdrSCHostValErrorCode {
        return new XdrSCHostValErrorCode(XdrSCHostValErrorCode::HOST_VALUE_UNEXPECTED_VAL_TYPE);
    }

    public static function U63_OUT_OF_RANGE() : XdrSCHostValErrorCode {
        return new XdrSCHostValErrorCode(XdrSCHostValErrorCode::HOST_VALUE_U63_OUT_OF_RANGE);
    }

    public static function U32_OUT_OF_RANGE() : XdrSCHostValErrorCode {
        return new XdrSCHostValErrorCode(XdrSCHostValErrorCode::HOST_VALUE_U32_OUT_OF_RANGE);
    }

    public static function STATIC_UNKNOWN() : XdrSCHostValErrorCode {
        return new XdrSCHostValErrorCode(XdrSCHostValErrorCode::HOST_VALUE_STATIC_UNKNOWN);
    }

    public static function MISSING_OBJECT() : XdrSCHostValErrorCode {
        return new XdrSCHostValErrorCode(XdrSCHostValErrorCode::HOST_VALUE_MISSING_OBJECT);
    }

    public static function SYMBOL_TOO_LONG() : XdrSCHostValErrorCode {
        return new XdrSCHostValErrorCode(XdrSCHostValErrorCode::HOST_VALUE_SYMBOL_TOO_LONG);
    }

    public static function SYMBOL_BAD_CHAR() : XdrSCHostValErrorCode {
        return new XdrSCHostValErrorCode(XdrSCHostValErrorCode::HOST_VALUE_SYMBOL_BAD_CHAR);
    }

    public static function SYMBOL_CONTAINS_NON_UTF8() : XdrSCHostValErrorCode {
        return new XdrSCHostValErrorCode(XdrSCHostValErrorCode::HOST_VALUE_SYMBOL_CONTAINS_NON_UTF8);
    }

    public static function BITSET_TOO_MANY_BITS() : XdrSCHostValErrorCode {
        return new XdrSCHostValErrorCode(XdrSCHostValErrorCode::HOST_VALUE_BITSET_TOO_MANY_BITS);
    }

    public static function STATUS_UNKNOWN() : XdrSCHostValErrorCode {
        return new XdrSCHostValErrorCode(XdrSCHostValErrorCode::HOST_VALUE_STATUS_UNKNOWN);
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

    public static function decode(XdrBuffer $xdr): XdrSCHostValErrorCode
    {
        $value = $xdr->readInteger32();
        return new XdrSCHostValErrorCode($value);
    }
}