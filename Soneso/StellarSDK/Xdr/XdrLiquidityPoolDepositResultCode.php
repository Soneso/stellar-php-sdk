<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLiquidityPoolDepositResultCode
{
    private int $value;

    /// Success.
    const SUCCESS = 0;

    /// bad input.
    const MALFORMED = -1;

    /// no trust line for one of the assets
    const NO_TRUST = -2;

    /// not authorized for one of the assets
    const NOT_AUTHORIZED = -3;

    /// not enough balance for one of the assets
    const UNDERFUNDED = -4;

    public function __construct(int $value) {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    public function encode(): string {
        return XdrEncoder::integer32($this->value);
    }

    public static function decode(XdrBuffer $xdr) : XdrLiquidityPoolDepositResultCode {
        $value = $xdr->readInteger32();
        return new XdrLiquidityPoolDepositResultCode($value);
    }
}