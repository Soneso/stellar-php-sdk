<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCErrorType
{
    public int $value;

    const SCE_CONTRACT = 0;
    const SCE_WASM_VM = 1;
    const SCE_CONTEXT = 2;
    const SCE_STORAGE = 3;
    const SCE_OBJECT = 4;
    const SCE_CRYPTO = 5;
    const SCE_EVENTS = 6;
    const SCE_BUDGET = 7;
    const SCE_VALUE = 8;
    const SCE_AUTH = 9;

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

    public static function decode(XdrBuffer $xdr): XdrSCErrorType
    {
        $value = $xdr->readInteger32();
        return new XdrSCErrorType($value);
    }

    public static function SCE_CONTRACT() : XdrSCErrorType {
        return new XdrSCErrorType(XdrSCErrorType::SCE_CONTRACT);
    }

    public static function SCE_WASM_VM() : XdrSCErrorType {
        return new XdrSCErrorType(XdrSCErrorType::SCE_WASM_VM);
    }

    public static function SCE_CONTEXT() : XdrSCErrorType {
        return new XdrSCErrorType(XdrSCErrorType::SCE_CONTEXT);
    }

    public static function SCE_STORAGE() : XdrSCErrorType {
        return new XdrSCErrorType(XdrSCErrorType::SCE_STORAGE);
    }

    public static function SCE_OBJECT() : XdrSCErrorType {
        return new XdrSCErrorType(XdrSCErrorType::SCE_OBJECT);
    }

    public static function SCE_CRYPTO() : XdrSCErrorType {
        return new XdrSCErrorType(XdrSCErrorType::SCE_CRYPTO);
    }

    public static function SCE_EVENTS() : XdrSCErrorType {
        return new XdrSCErrorType(XdrSCErrorType::SCE_EVENTS);
    }

    public static function SCE_BUDGET() : XdrSCErrorType {
        return new XdrSCErrorType(XdrSCErrorType::SCE_BUDGET);
    }

    public static function SCE_VALUE() : XdrSCErrorType {
        return new XdrSCErrorType(XdrSCErrorType::SCE_VALUE);
    }

    public static function SCE_AUTH() : XdrSCErrorType {
        return new XdrSCErrorType(XdrSCErrorType::SCE_AUTH);
    }
}