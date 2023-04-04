<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCStatic
{
    public int $value;

    const SCS_VOID = 0;
    const SCS_TRUE = 1;
    const SCS_FALSE = 2;
    const SCS_LEDGER_KEY_CONTRACT_CODE = 3;

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

    public static function decode(XdrBuffer $xdr): XdrSCStatic
    {
        $value = $xdr->readInteger32();
        return new XdrSCStatic($value);
    }

    public static function TRUE() : XdrSCStatic {
        return new XdrSCStatic(XdrSCStatic::SCS_TRUE);
    }

    public static function FALSE() : XdrSCStatic {
        return new XdrSCStatic(XdrSCStatic::SCS_FALSE);
    }

    public static function VOID() : XdrSCStatic {
        return new XdrSCStatic(XdrSCStatic::SCS_VOID);
    }

    public static function LEDGER_KEY_CONTRACT_CODE() : XdrSCStatic {
        return new XdrSCStatic(XdrSCStatic::SCS_LEDGER_KEY_CONTRACT_CODE);
    }
}