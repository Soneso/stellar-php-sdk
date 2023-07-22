<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractEntryBodyType
{
    public int $value;

    const DATA_ENTRY = 0;
    const EXPIRATION_EXTENSION = 1;

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

    public static function decode(XdrBuffer $xdr): XdrContractEntryBodyType
    {
        $value = $xdr->readInteger32();
        return new XdrContractEntryBodyType($value);
    }

    public static function DATA_ENTRY() : XdrContractEntryBodyType {
        return new XdrContractEntryBodyType(XdrContractEntryBodyType::DATA_ENTRY);
    }

    public static function EXPIRATION_EXTENSION() : XdrContractEntryBodyType {
        return new XdrContractEntryBodyType(XdrContractEntryBodyType::EXPIRATION_EXTENSION);
    }
}