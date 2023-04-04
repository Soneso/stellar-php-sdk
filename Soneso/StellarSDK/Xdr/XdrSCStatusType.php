<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCStatusType
{
    public int $value;

    const SST_OK = 0;
    const SST_UNKNOWN_ERROR = 1;
    const SST_HOST_VALUE_ERROR = 2;
    const SST_HOST_OBJECT_ERROR = 3;
    const SST_HOST_FUNCTION_ERROR = 4;
    const SST_HOST_STORAGE_ERROR = 5;
    const SST_HOST_CONTEXT_ERROR = 6;
    const SST_VM_ERROR = 7;
    const SST_CONTRACT_ERROR = 8;
    const SST_HOST_AUTH_ERROR = 9;

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

    public static function decode(XdrBuffer $xdr): XdrSCStatusType
    {
        $value = $xdr->readInteger32();
        return new XdrSCStatusType($value);
    }

    public static function OK() : XdrSCStatusType {
        return new XdrSCStatusType(XdrSCStatusType::SST_OK);
    }

    public static function UNKNOWN_ERROR() : XdrSCStatusType {
        return new XdrSCStatusType(XdrSCStatusType::SST_UNKNOWN_ERROR);
    }

    public static function HOST_VALUE_ERROR() : XdrSCStatusType {
        return new XdrSCStatusType(XdrSCStatusType::SST_HOST_VALUE_ERROR);
    }

    public static function HOST_OBJECT_ERROR() : XdrSCStatusType {
        return new XdrSCStatusType(XdrSCStatusType::SST_HOST_OBJECT_ERROR);
    }

    public static function HOST_FUNCTION_ERROR() : XdrSCStatusType {
        return new XdrSCStatusType(XdrSCStatusType::SST_HOST_FUNCTION_ERROR);
    }

    public static function HOST_STORAGE_ERROR() : XdrSCStatusType {
        return new XdrSCStatusType(XdrSCStatusType::SST_HOST_STORAGE_ERROR);
    }

    public static function HOST_CONTEXT_ERROR() : XdrSCStatusType {
        return new XdrSCStatusType(XdrSCStatusType::SST_HOST_CONTEXT_ERROR);
    }

    public static function VM_ERROR() : XdrSCStatusType {
        return new XdrSCStatusType(XdrSCStatusType::SST_VM_ERROR);
    }

    public static function CONTRACT_ERROR() : XdrSCStatusType {
        return new XdrSCStatusType(XdrSCStatusType::SST_CONTRACT_ERROR);
    }

    public static function HOST_AUTH_ERROR() : XdrSCStatusType {
        return new XdrSCStatusType(XdrSCStatusType::SST_HOST_AUTH_ERROR);
    }
}