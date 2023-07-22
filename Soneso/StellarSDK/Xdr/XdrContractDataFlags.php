<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractDataFlags
{
    public int $value;

    const NO_AUTOBUMP = 0x1;

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

    public static function decode(XdrBuffer $xdr): XdrContractDataFlags
    {
        $value = $xdr->readInteger32();
        return new XdrContractDataFlags($value);
    }

    public static function NO_AUTOBUMP() : XdrContractDataFlags {
        return new XdrContractDataFlags(XdrContractDataFlags::NO_AUTOBUMP);
    }
}