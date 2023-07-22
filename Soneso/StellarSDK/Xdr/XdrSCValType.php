<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

// https://github.com/stellar/stellar-xdr/blob/next/Stellar-contract.x#L21
class XdrSCValType
{
    public int $value;

    const SCV_BOOL = 0;
    const SCV_VOID = 1;
    const SCV_ERROR = 2;
    const SCV_U32 = 3;
    const SCV_I32 = 4;
    const SCV_U64 = 5;
    const SCV_I64 = 6;
    const SCV_TIMEPOINT = 7;
    const SCV_DURATION = 8;
    const SCV_U128 = 9;
    const SCV_I128 = 10;
    const SCV_U256 = 11;
    const SCV_I256 = 12;
    const SCV_BYTES = 13;
    const SCV_STRING = 14;
    const SCV_SYMBOL = 15;
    const SCV_VEC = 16;
    const SCV_MAP = 17;
    const SCV_ADDRESS = 18;
    const SCV_CONTRACT_INSTANCE = 19;
    const SCV_LEDGER_KEY_CONTRACT_INSTANCE = 20;
    const SCV_LEDGER_KEY_NONCE = 21;

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


    public static function BOOL() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_BOOL);
    }

    public static function VOID() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_VOID);
    }

    public static function ERROR() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_ERROR);
    }

    public static function U32() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_U32);
    }

    public static function I32() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_I32);
    }

    public static function U64 () :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_U64);
    }

    public static function I64 () :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_I64);
    }

    public static function TIMEPOINT() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_TIMEPOINT);
    }

    public static function DURATION() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_DURATION);
    }

    public static function U128() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_U128);
    }

    public static function I128() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_I128);
    }

    public static function U256() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_U256);
    }

    public static function I256() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_I256);
    }

    public static function BYTES() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_BYTES);
    }

    public static function STRING() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_STRING);
    }

    public static function SYMBOL() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_SYMBOL);
    }

    public static function VEC() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_VEC);
    }

    public static function MAP() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_MAP);
    }

    public static function ADDRESS() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_ADDRESS);
    }

    public static function SCV_CONTRACT_INSTANCE() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_CONTRACT_INSTANCE);
    }

    public static function SCV_LEDGER_KEY_CONTRACT_INSTANCE() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_LEDGER_KEY_CONTRACT_INSTANCE);
    }

    public static function LEDGER_KEY_NONCE() :  XdrSCValType {
        return new XdrSCValType(XdrSCValType::SCV_LEDGER_KEY_NONCE);
    }

}