<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCObjectType
{
    public int $value;

    const SCO_VEC = 0;
    const SCO_MAP = 1;
    const SCO_U64 = 2;
    const SCO_I64 = 3;
    const SCO_U128 = 4;
    const SCO_I128 = 5;
    const SCO_BYTES = 6;
    const SCO_CONTRACT_CODE = 7;
    const SCO_ACCOUNT_ID = 8;

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

    public static function decode(XdrBuffer $xdr): XdrSCObjectType
    {
        $value = $xdr->readInteger32();
        return new XdrSCObjectType($value);
    }

    public static function VEC() :  XdrSCObjectType {
        return new XdrSCObjectType(XdrSCObjectType::SCO_VEC);
    }

    public static function MAP() :  XdrSCObjectType {
        return new XdrSCObjectType(XdrSCObjectType::SCO_MAP);
    }

    public static function U64() :  XdrSCObjectType {
        return new XdrSCObjectType(XdrSCObjectType::SCO_U64);
    }

    public static function I64() :  XdrSCObjectType {
        return new XdrSCObjectType(XdrSCObjectType::SCO_I64);
    }

    public static function U128() :  XdrSCObjectType {
        return new XdrSCObjectType(XdrSCObjectType::SCO_U128);
    }

    public static function I128() :  XdrSCObjectType {
        return new XdrSCObjectType(XdrSCObjectType::SCO_I128);
    }

    public static function BYTES() :  XdrSCObjectType {
        return new XdrSCObjectType(XdrSCObjectType::SCO_BYTES);
    }

    public static function CONTRACT_CODE() :  XdrSCObjectType {
        return new XdrSCObjectType(XdrSCObjectType::SCO_CONTRACT_CODE);
    }

    public static function ACCOUNT_ID() :  XdrSCObjectType {
        return new XdrSCObjectType(XdrSCObjectType::SCO_ACCOUNT_ID);
    }
}