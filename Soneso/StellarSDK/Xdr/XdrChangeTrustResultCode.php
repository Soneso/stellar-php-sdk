<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrChangeTrustResultCode
{

    private int $value;

    /// Success.
    const SUCCESS = 0;

    /// Bad input.
    const MALFORMED = -1;

    /// Could not find issuer.
    const NO_ISSUER = -2;

    /// Cannot drop limit below balance. Cannot create with a limit of 0.
    const INVALID_LIMIT = -3;

    /// Not enough funds to create a new trust line.
    const LOW_RESERVE = -4;

    /// Trusting self is not allowed.
    const SELF_NOT_ALLOWED = -5;

    /// Asset trustline is missing for pool.
    const TRUST_LINE_MISSING = -6;

    /// Asset trustline is still referenced in a pool.
    const CANNOT_DELETE = -7;

    /// Asset trustline is deauthorized.
    const NOT_AUTH_MAINTAIN_LIABILITIES = -8;

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

    public static function decode(XdrBuffer $xdr) : XdrChangeTrustResultCode {
        $value = $xdr->readInteger32();
        return new XdrChangeTrustResultCode($value);
    }
}