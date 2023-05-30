<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractEventType
{
    public int $value;

    const CONTRACT_EVENT_TYPE_SYSTEM = 0;
    const CONTRACT_EVENT_TYPE_CONTRACT = 1;
    const CONTRACT_EVENT_TYPE_DIAGNOSTIC = 2;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function SYSTEM() :  XdrContractEventType {
        return new XdrContractEventType(XdrContractEventType::CONTRACT_EVENT_TYPE_SYSTEM);
    }

    public static function CONTRACT() :  XdrContractEventType {
        return new XdrContractEventType(XdrContractEventType::CONTRACT_EVENT_TYPE_CONTRACT);
    }

    public static function DIAGNOSTIC() :  XdrContractEventType {
        return new XdrContractEventType(XdrContractEventType::CONTRACT_EVENT_TYPE_DIAGNOSTIC);
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

    public static function decode(XdrBuffer $xdr): XdrContractEventType
    {
        $value = $xdr->readInteger32();
        return new XdrContractEventType($value);
    }
}