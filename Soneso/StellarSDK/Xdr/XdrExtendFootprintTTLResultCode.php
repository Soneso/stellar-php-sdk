<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrExtendFootprintTTLResultCode
{
    public int $value;

    const EXTEND_FOOTPRINT_TTL_SUCCESS = 0;
    const EXTEND_FOOTPRINT_TTL_MALFORMED = -1;
    const EXTEND_FOOTPRINT_TTL_RESOURCE_LIMIT_EXCEEDED = -2;
    const EXTEND_FOOTPRINT_TTL_INSUFFICIENT_REFUNDABLE_FEE = -3;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    public function encode(): string
    {
        return XdrEncoder::integer32($this->value);
    }

    public static function decode(XdrBuffer $xdr): XdrExtendFootprintTTLResultCode
    {
        $value = $xdr->readInteger32();
        return new XdrExtendFootprintTTLResultCode($value);
    }
}