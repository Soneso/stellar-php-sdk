<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrPreconditionType
{
    private int $value;

    const NONE = 0;
    const TIME = 1;
    const V2 = 2;

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

    public static function decode(XdrBuffer $xdr) : XdrPreconditionType {
        $value = $xdr->readInteger32();
        return new XdrPreconditionType($value);
    }
}