<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCValType
{
    public int $value;

    const SCV_U63 = 0;
    const SCV_U32 = 1;
    const SCV_I32 = 2;
    const SCV_STATIC = 3;
    const SCV_OBJECT = 4;
    const SCV_SYMBOL = 5;
    const SCV_BITSET = 6;
    const SCV_STATUS = 7;

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

    public static function decode(XdrBuffer $xdr): XdrSCValType
    {
        $value = $xdr->readInteger32();
        return new XdrSCValType($value);
    }

    public static function U63() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_U63);
    }

    public static function U32() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_U32);
    }

    public static function I32() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_I32);
    }

    public static function SYMBOL() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_SYMBOL);
    }

    public static function BITSET() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_BITSET);
    }

    public static function STATIC() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_STATIC);
    }

    public static function OBJECT() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_OBJECT);
    }

    public static function STATUS() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_OBJECT);
    }
}