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
    const SC_ADDRESS_TYPE_MUXED_ACCOUNT = 2;
    const SC_ADDRESS_TYPE_CLAIMABLE_BALANCE = 3;
    const SC_ADDRESS_TYPE_LIQUIDITY_POOL = 4;

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

    public static function SC_ADDRESS_TYPE_MUXED_ACCOUNT() :  XdrSCAddressType {
        return new XdrSCAddressType(XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT);
    }

    public static function SC_ADDRESS_TYPE_CLAIMABLE_BALANCE() :  XdrSCAddressType {
        return new XdrSCAddressType(XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE);
    }

    public static function SC_ADDRESS_TYPE_LIQUIDITY_POOL() :  XdrSCAddressType {
        return new XdrSCAddressType(XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL);
    }
}