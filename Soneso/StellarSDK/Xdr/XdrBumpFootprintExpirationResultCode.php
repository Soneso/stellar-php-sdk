<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrBumpFootprintExpirationResultCode
{
    public int $value;

    const BUMP_FOOTPRINT_EXPIRATION_SUCCESS = 0;
    const BUMP_FOOTPRINT_EXPIRATION_MALFORMED = -1;
    const BUMP_FOOTPRINT_EXPIRATION_RESOURCE_LIMIT_EXCEEDED = -2;

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

    public static function decode(XdrBuffer $xdr): XdrBumpFootprintExpirationResultCode
    {
        $value = $xdr->readInteger32();
        return new XdrBumpFootprintExpirationResultCode($value);
    }
}