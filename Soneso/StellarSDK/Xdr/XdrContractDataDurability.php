<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractDataDurability
{
    public int $value;

    const TEMPORARY = 0;
    const PERSISTENT = 1;

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

    public static function decode(XdrBuffer $xdr): XdrContractDataDurability
    {
        $value = $xdr->readInteger32();
        return new XdrContractDataDurability($value);
    }

    public static function TEMPORARY() : XdrContractDataDurability {
        return new XdrContractDataDurability(XdrContractDataDurability::TEMPORARY);
    }

    public static function PERSISTENT() : XdrContractDataDurability {
        return new XdrContractDataDurability(XdrContractDataDurability::PERSISTENT);
    }
}