<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSetTrustLineFlagsResultCode
{
    private int $value;

    const SUCCESS = 0;

    const MALFORMED = -1;

    const NO_TRUST_LINE = -2;

    const CANT_REVOKE = -3;

    const INVALID_STATE = -4;

    const LOW_RESERVE = -5;

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

    public static function decode(XdrBuffer $xdr) : XdrSetTrustLineFlagsResultCode {
        $value = $xdr->readInteger32();
        return new XdrSetTrustLineFlagsResultCode($value);
    }
}