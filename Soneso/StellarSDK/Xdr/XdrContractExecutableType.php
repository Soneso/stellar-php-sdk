<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractExecutableType
{
    public int $value;

    const CONTRACT_EXECUTABLE_WASM = 0;
    const CONTRACT_EXECUTABLE_TOKEN = 1;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function CONTRACT_EXECUTABLE_WASM() :  XdrContractExecutableType {
        return new XdrContractExecutableType(XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM);
    }

    public static function CONTRACT_EXECUTABLE_TOKEN() :  XdrContractExecutableType {
        return new XdrContractExecutableType(XdrContractExecutableType::CONTRACT_EXECUTABLE_TOKEN);
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

    public static function decode(XdrBuffer $xdr): XdrContractExecutableType
    {
        $value = $xdr->readInteger32();
        return new XdrContractExecutableType($value);
    }
}