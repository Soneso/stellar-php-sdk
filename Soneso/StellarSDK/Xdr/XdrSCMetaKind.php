<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCMetaKind
{
    public int $value;

    const SC_META_V0 = 0;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function SC_META_V0() :  XdrSCMetaKind {
        return new XdrSCMetaKind(XdrSCMetaKind::SC_META_V0);
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

    public static function decode(XdrBuffer $xdr): XdrSCMetaKind
    {
        $value = $xdr->readInteger32();
        return new XdrSCMetaKind($value);
    }
}