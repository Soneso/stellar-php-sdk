<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrManageDataResultCode
{
    private int $value;

    /// Success.
    const SUCCESS = 0;

    /// The network hasn't moved to this protocol change yet.
    const NOT_SUPPORTED_YET = -1;

    /// Trying to remove a Data Entry that isn't there.
    const NAME_NOT_FOUND = -2;

    /// Not enough funds to create a new Data Entry.
    const LOW_RESERVE = -3;

    /// Name not a valid string.
    const INVALID_NAME = -4;

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

    public static function decode(XdrBuffer $xdr) : XdrManageDataResultCode {
        $value = $xdr->readInteger32();
        return new XdrManageDataResultCode($value);
    }
}