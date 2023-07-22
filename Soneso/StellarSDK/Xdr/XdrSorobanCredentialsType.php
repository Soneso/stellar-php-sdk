<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSorobanCredentialsType
{
    public int $value;

    const SOROBAN_CREDENTIALS_SOURCE_ACCOUNT = 0;
    const SOROBAN_CREDENTIALS_ADDRESS = 1;

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

    public static function decode(XdrBuffer $xdr): XdrSorobanCredentialsType
    {
        $value = $xdr->readInteger32();
        return new XdrSorobanCredentialsType($value);
    }

    public static function SOROBAN_CREDENTIALS_SOURCE_ACCOUNT() : XdrSorobanCredentialsType {
        return new XdrSorobanCredentialsType(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT);
    }

    public static function SOROBAN_CREDENTIALS_ADDRESS() : XdrSorobanCredentialsType {
        return new XdrSorobanCredentialsType(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS);
    }
}