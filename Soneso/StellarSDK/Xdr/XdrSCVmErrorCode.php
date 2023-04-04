<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCVmErrorCode
{
    public int $value;

    const VM_UNKNOWN = 0;
    const VM_VALIDATION = 1;
    const VM_INSTANTIATION = 2;
    const VM_FUNCTION = 3;
    const VM_TABLE = 4;
    const VM_MEMORY = 5;
    const VM_GLOBAL = 6;
    const VM_VALUE = 7;
    const VM_TRAP_UNREACHABLE = 8;
    const VM_TRAP_MEMORY_ACCESS_OUT_OF_BOUNDS = 9;
    const VM_TRAP_TABLE_ACCESS_OUT_OF_BOUNDS = 10;
    const VM_TRAP_ELEM_UNINITIALIZED = 11;
    const VM_TRAP_DIVISION_BY_ZERO = 12;
    const VM_TRAP_INTEGER_OVERFLOW = 13;
    const VM_TRAP_INVALID_CONVERSION_TO_INT = 14;
    const VM_TRAP_STACK_OVERFLOW = 15;
    const VM_TRAP_UNEXPECTED_SIGNATURE = 16;
    const VM_TRAP_MEM_LIMIT_EXCEEDED = 17;
    const VM_TRAP_CPU_LIMIT_EXCEEDED = 18;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function UNKNOWN() : XdrSCVmErrorCode {
        return new XdrSCVmErrorCode(XdrSCVmErrorCode::VM_UNKNOWN);
    }

    public static function VALIDATION() : XdrSCVmErrorCode {
        return new XdrSCVmErrorCode(XdrSCVmErrorCode::VM_VALIDATION);
    }

    public static function INSTANTIATION() : XdrSCVmErrorCode {
        return new XdrSCVmErrorCode(XdrSCVmErrorCode::VM_INSTANTIATION);
    }

    public static function FUNCTION() : XdrSCVmErrorCode {
        return new XdrSCVmErrorCode(XdrSCVmErrorCode::VM_FUNCTION);
    }

    public static function TABLE() : XdrSCVmErrorCode {
        return new XdrSCVmErrorCode(XdrSCVmErrorCode::VM_TABLE);
    }

    public static function MEMORY() : XdrSCVmErrorCode {
        return new XdrSCVmErrorCode(XdrSCVmErrorCode::VM_MEMORY);
    }

    public static function GLOBAL() : XdrSCVmErrorCode {
        return new XdrSCVmErrorCode(XdrSCVmErrorCode::VM_GLOBAL);
    }

    public static function VALUE() : XdrSCVmErrorCode {
        return new XdrSCVmErrorCode(XdrSCVmErrorCode::VM_VALUE);
    }

    public static function TRAP_UNREACHABLE() : XdrSCVmErrorCode {
        return new XdrSCVmErrorCode(XdrSCVmErrorCode::VM_TRAP_UNREACHABLE);
    }

    public static function TRAP_MEMORY_ACCESS_OUT_OF_BOUNDS() : XdrSCVmErrorCode {
        return new XdrSCVmErrorCode(XdrSCVmErrorCode::VM_TRAP_MEMORY_ACCESS_OUT_OF_BOUNDS);
    }

    public static function TRAP_TABLE_ACCESS_OUT_OF_BOUNDS() : XdrSCVmErrorCode {
        return new XdrSCVmErrorCode(XdrSCVmErrorCode::VM_TRAP_TABLE_ACCESS_OUT_OF_BOUNDS);
    }

    public static function TRAP_ELEM_UNINITIALIZED() : XdrSCVmErrorCode {
        return new XdrSCVmErrorCode(XdrSCVmErrorCode::VM_TRAP_ELEM_UNINITIALIZED);
    }

    public static function TRAP_DIVISION_BY_ZERO() : XdrSCVmErrorCode {
        return new XdrSCVmErrorCode(XdrSCVmErrorCode::VM_TRAP_DIVISION_BY_ZERO);
    }

    public static function TRAP_INTEGER_OVERFLOW() : XdrSCVmErrorCode {
        return new XdrSCVmErrorCode(XdrSCVmErrorCode::VM_TRAP_INTEGER_OVERFLOW);
    }

    public static function TRAP_INVALID_CONVERSION_TO_INT() : XdrSCVmErrorCode {
        return new XdrSCVmErrorCode(XdrSCVmErrorCode::VM_TRAP_INVALID_CONVERSION_TO_INT);
    }

    public static function TRAP_STACK_OVERFLOW() : XdrSCVmErrorCode {
        return new XdrSCVmErrorCode(XdrSCVmErrorCode::VM_TRAP_STACK_OVERFLOW);
    }

    public static function TRAP_UNEXPECTED_SIGNATURE() : XdrSCVmErrorCode {
        return new XdrSCVmErrorCode(XdrSCVmErrorCode::VM_TRAP_UNEXPECTED_SIGNATURE);
    }

    public static function TRAP_MEM_LIMIT_EXCEEDED() : XdrSCVmErrorCode {
        return new XdrSCVmErrorCode(XdrSCVmErrorCode::VM_TRAP_MEM_LIMIT_EXCEEDED);
    }

    public static function TRAP_CPU_LIMIT_EXCEEDED() : XdrSCVmErrorCode {
        return new XdrSCVmErrorCode(XdrSCVmErrorCode::VM_TRAP_CPU_LIMIT_EXCEEDED);
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

    public static function decode(XdrBuffer $xdr): XdrSCVmErrorCode
    {
        $value = $xdr->readInteger32();
        return new XdrSCVmErrorCode($value);
    }
}