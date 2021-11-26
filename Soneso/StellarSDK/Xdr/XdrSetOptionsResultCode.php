<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrSetOptionsResultCode
{
    private int $value;

    /// Success.
    const SUCCESS = 0;

    /// Not enough funds to add a signer.
    const LOW_RESERVE = -1;

    /// Max number of signers already reached.
    const TOO_MANY_SIGNERS = -2;

    /// Invalid combination of clear/set flags.
    const BAD_FLAGS = -3;

    /// Inflation account does not exist.
    const INVALID_INFLATION = -4;

    /// Can no longer change this option.
    const CANT_CHANGE  = -5;

    /// Can't set an unknown flag.
    const UNKNOWN_FLAG  = -6;

    /// Bad value for weight/threshold.
    const THRESHOLD_OUT_OF_RANGE = -7;

    /// Signer cannot be masterkey.
    const BAD_SIGNER = -8;

    /// Malformed home domain.
    const INVALID_HOME_DOMAIN = -9;

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

    public static function decode(XdrBuffer $xdr) : XdrSetOptionsResultCode {
        $value = $xdr->readInteger32();
        return new XdrSetOptionsResultCode($value);
    }
}