<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrBeginSponsoringFutureReservesResultCode
{

    private int $value;

    const SUCCESS = 0;

    const MALFORMED = -1;

    const ALREADY_SPONSORED = -2;

    const RECURSIVE = -3;

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

    public static function decode(XdrBuffer $xdr) : XdrBeginSponsoringFutureReservesResultCode {
        $value = $xdr->readInteger32();
        return new XdrBeginSponsoringFutureReservesResultCode($value);
    }
}