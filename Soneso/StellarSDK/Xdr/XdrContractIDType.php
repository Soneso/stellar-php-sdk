<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractIDType
{
    public int $value;

    const CONTRACT_ID_FROM_SOURCE_ACCOUNT = 0;
    const CONTRACT_ID_FROM_ED25519_PUBLIC_KEY = 1;
    const CONTRACT_ID_FROM_ASSET = 2;

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

    public static function decode(XdrBuffer $xdr): XdrContractIDType
    {
        $value = $xdr->readInteger32();
        return new XdrContractIDType($value);
    }
}