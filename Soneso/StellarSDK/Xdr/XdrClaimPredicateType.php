<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrClaimPredicateType
{
    private int $value;

    const UNCONDITIONAL = 0;
    const AND = 1;
    const OR = 2;
    const NOT = 3;
    const BEFORE_ABSOLUTE_TIME = 4;
    const BEFORE_RELATIVE_TIME = 5;

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

    public static function decode(XdrBuffer $xdr) : XdrClaimPredicateType {
        $value = $xdr->readInteger32();
        return new XdrClaimPredicateType($value);
    }
}