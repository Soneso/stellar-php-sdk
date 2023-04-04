<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCEnvMetaKind
{
    public int $value;

    const SC_ENV_META_KIND_INTERFACE_VERSION = 0;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function INTERFACE_VERSION() :  XdrSCEnvMetaKind {
        return new XdrSCEnvMetaKind(XdrSCEnvMetaKind::SC_ENV_META_KIND_INTERFACE_VERSION);
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

    public static function decode(XdrBuffer $xdr): XdrSCEnvMetaKind
    {
        $value = $xdr->readInteger32();
        return new XdrSCEnvMetaKind($value);
    }
}