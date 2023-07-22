<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSorobanAuthorizedFunctionType
{
    public int $value;

    const SOROBAN_AUTHORIZED_FUNCTION_TYPE_CONTRACT_FN = 0;
    const SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_HOST_FN = 1;

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

    public static function decode(XdrBuffer $xdr): XdrSorobanAuthorizedFunctionType
    {
        $value = $xdr->readInteger32();
        return new XdrSorobanAuthorizedFunctionType($value);
    }

    public static function SOROBAN_AUTHORIZED_FUNCTION_TYPE_CONTRACT_FN() : XdrSorobanAuthorizedFunctionType {
        return new XdrSorobanAuthorizedFunctionType(XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CONTRACT_FN);
    }

    public static function SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_HOST_FN() : XdrSorobanAuthorizedFunctionType {
        return new XdrSorobanAuthorizedFunctionType(XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_HOST_FN);
    }
}