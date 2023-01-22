<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractIDPublicKeyType
{
    public int $value;

    const CONTRACT_ID_PUBLIC_KEY_SOURCE_ACCOUNT = 0;
    const CONTRACT_ID_PUBLIC_KEY_ED25519 = 1;

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

    public static function decode(XdrBuffer $xdr): XdrContractIDPublicKeyType
    {
        $value = $xdr->readInteger32();
        return new XdrContractIDPublicKeyType($value);
    }
}