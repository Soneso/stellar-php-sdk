<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCHostObjErrorCode
{
    public int $value;

    const HOST_OBJECT_UNKNOWN_ERROR = 0;
    const HOST_OBJECT_UNKNOWN_REFERENCE = 1;
    const HOST_OBJECT_UNEXPECTED_TYPE = 2;
    const HOST_OBJECT_OBJECT_COUNT_EXCEEDS_U32_MAX = 3;
    const HOST_OBJECT_OBJECT_NOT_EXIST = 4;
    const HOST_OBJECT_VEC_INDEX_OUT_OF_BOUND = 5;
    const HOST_OBJECT_CONTRACT_HASH_WRONG_LENGTH = 6;

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

    public static function decode(XdrBuffer $xdr): XdrSCHostObjErrorCode
    {
        $value = $xdr->readInteger32();
        return new XdrSCHostObjErrorCode($value);
    }
}