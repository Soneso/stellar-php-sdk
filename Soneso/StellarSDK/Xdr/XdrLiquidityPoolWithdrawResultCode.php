<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.



namespace Soneso\StellarSDK\Xdr;

class XdrLiquidityPoolWithdrawResultCode
{
    private int $value;

    /// Success.
    const SUCCESS = 0;

    /// bad input.
    const MALFORMED = -1;

    /// no trust line for one of the assets
    const NO_TRUST = -2;

    /// not enough balance of the pool share
    const UNDERFUNDED = -3;

    /// would go above limit for one of the assets
    const LINE_FULL = -4;

    /// didn't withdraw enough
    const UNDER_MINIMUM = -5;

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

    public static function decode(XdrBuffer $xdr) : XdrLiquidityPoolWithdrawResultCode {
        $value = $xdr->readInteger32();
        return new XdrLiquidityPoolWithdrawResultCode($value);
    }
}