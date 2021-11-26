<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrClawbackResultCode
{
    private int $value;

    const SUCCESS = 0;

    const MALFORMED = -1;

    const NOT_ENABLED = -2;

    const NO_TRUST = -3;

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

    public static function decode(XdrBuffer $xdr) : XdrClawbackResultCode {
        $value = $xdr->readInteger32();
        return new XdrClawbackResultCode($value);
    }
}