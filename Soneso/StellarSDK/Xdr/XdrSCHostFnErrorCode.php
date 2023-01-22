<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCHostFnErrorCode
{
    public int $value;

    const HOST_FN_UNKNOWN_ERROR = 0;
    const HOST_FN_UNEXPECTED_HOST_FUNCTION_ACTION = 1;
    const HOST_FN_INPUT_ARGS_WRONG_LENGTH = 2;
    const HOST_FN_INPUT_ARGS_WRONG_TYPE = 3;
    const HOST_FN_INPUT_ARGS_INVALID = 4;

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

    public static function decode(XdrBuffer $xdr): XdrSCHostFnErrorCode
    {
        $value = $xdr->readInteger32();
        return new XdrSCHostFnErrorCode($value);
    }
}