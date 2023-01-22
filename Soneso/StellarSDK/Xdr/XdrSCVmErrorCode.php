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