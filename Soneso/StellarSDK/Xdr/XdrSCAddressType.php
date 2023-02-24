<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCAddressType
{
    public int $value;

    const SC_ADDRESS_TYPE_ACCOUNT = 0;
    const SC_ADDRESS_TYPE_CONTRACT = 1;

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

    public static function decode(XdrBuffer $xdr): XdrSCAddressType
    {
        $value = $xdr->readInteger32();
        return new XdrSCAddressType($value);
    }

    public static function SC_ADDRESS_TYPE_ACCOUNT() :  XdrSCAddressType {
        return new XdrSCAddressType(XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT);
    }

    public static function SC_ADDRESS_TYPE_CONTRACT() :  XdrSCAddressType {
        return new XdrSCAddressType(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT);
    }
}