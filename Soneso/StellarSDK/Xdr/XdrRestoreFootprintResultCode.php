<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrRestoreFootprintResultCode
{
    public int $value;

    const RESTORE_FOOTPRINT_SUCCESS = 0;
    const RESTORE_FOOTPRINT_MALFORMED = -1;
    const RESTORE_FOOTPRINT_RESOURCE_LIMIT_EXCEEDED = -2;

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

    public static function decode(XdrBuffer $xdr): XdrRestoreFootprintResultCode
    {
        $value = $xdr->readInteger32();
        return new XdrRestoreFootprintResultCode($value);
    }
}