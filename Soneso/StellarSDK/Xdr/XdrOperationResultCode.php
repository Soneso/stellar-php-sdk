<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrOperationResultCode
{
    private int $value;

    /// Inner object result is valid.
    const INNER = 0;

    /// Too few valid signatures / wrong network.
    const BAD_AUTH = -1;

    /// Source account was not found.
    const NO_ACCOUNT = -2;

    /// Operation not supported at this time.
    const NOT_SUPPORTED = -3;

    /// Max number of subentries already reached.
    const TOO_MANY_SUBENTRIES = -4;

    /// Operation did too much work.
    const EXCEEDED_WORK_LIMIT = -5;

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

    public static function decode(XdrBuffer $xdr) : XdrOperationResultCode {
        $value = $xdr->readInteger32();
        return new XdrOperationResultCode($value);
    }
}