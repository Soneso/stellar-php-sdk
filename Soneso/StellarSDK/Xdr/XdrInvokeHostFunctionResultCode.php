<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrInvokeHostFunctionResultCode
{
    public int $value;

    const INVOKE_HOST_FUNCTION_SUCCESS = 0;
    const INVOKE_HOST_FUNCTION_MALFORMED = -1;
    const INVOKE_HOST_FUNCTION_TRAPPED = -2;
    const INVOKE_HOST_FUNCTION_RESOURCE_LIMIT_EXCEEDED = -3;

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

    public static function decode(XdrBuffer $xdr): XdrInvokeHostFunctionResultCode
    {
        $value = $xdr->readInteger32();
        return new XdrInvokeHostFunctionResultCode($value);
    }
}