<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrCreateClaimableBalanceResultCode
{

    private int $value;

    /// Success.
    const SUCCESS = 0;

    const MALFORMED = -1;

    const LOW_RESERVE = -2;

    const NO_TRUST = -3;

    const NOT_AUTHORIZED = -4;

    const UNDERFUNDED = -5;

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

    public static function decode(XdrBuffer $xdr) : XdrCreateClaimableBalanceResultCode {
        $value = $xdr->readInteger32();
        return new XdrCreateClaimableBalanceResultCode($value);
    }
}