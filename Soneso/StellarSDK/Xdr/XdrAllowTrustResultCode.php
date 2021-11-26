<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrAllowTrustResultCode
{

    private int $value;

    /// Success.
    const SUCCESS = 0;

    /// Asset is not ASSET_TYPE_ALPHANUM.
    const MALFORMED = -1;

    /// Trustor does not have a trustline.
    const NO_TRUST_LINE = -2;

    /// Source account does not require trust.
    const TRUST_NOT_REQUIRED = -3;

    /// Source account can't revoke trust.
    const CANT_REVOKE = -4;

    /// Trusting self is not allowed.
    const SELF_NOT_ALLOWED = -5;

    /// Claimable balances can't be created on revoke due to low reserves.
    const LOW_RESERVE  = -6;

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

    public static function decode(XdrBuffer $xdr) : XdrAllowTrustResultCode {
        $value = $xdr->readInteger32();
        return new XdrAllowTrustResultCode($value);
    }
}