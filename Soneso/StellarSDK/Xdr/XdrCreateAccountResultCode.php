<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrCreateAccountResultCode
{
    private int $value;

    /// Account was created.
    const SUCCESS = 0;

    /// Invalid destination.
    const MALFORMED = -1;

    /// Not enough funds in source account.
    const UNDERFUNDED = -2;

    /// Would create an account below the min reserve.
    const LOW_RESERVE = -3;

    /// Account already exists.
    const ACCOUNT_ALREADY_EXIST = -4;

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

    public static function decode(XdrBuffer $xdr) : XdrCreateAccountResultCode {
        $value = $xdr->readInteger32();
        return new XdrCreateAccountResultCode($value);
    }
}