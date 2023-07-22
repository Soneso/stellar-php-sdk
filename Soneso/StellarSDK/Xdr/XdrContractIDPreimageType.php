<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractIDPreimageType
{
    public int $value;

    const CONTRACT_ID_PREIMAGE_FROM_ADDRESS = 0;
    const CONTRACT_ID_PREIMAGE_FROM_ASSET = 1;

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

    public static function decode(XdrBuffer $xdr): XdrContractIDPreimageType
    {
        $value = $xdr->readInteger32();
        return new XdrContractIDPreimageType($value);
    }

    public static function CONTRACT_ID_PREIMAGE_FROM_ADDRESS() : XdrContractIDPreimageType {
        return new XdrContractIDPreimageType(XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS);
    }

    public static function CONTRACT_ID_PREIMAGE_FROM_ASSET() : XdrContractIDPreimageType {
        return new XdrContractIDPreimageType(XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ASSET);
    }
}