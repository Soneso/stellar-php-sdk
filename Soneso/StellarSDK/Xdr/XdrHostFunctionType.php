<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrHostFunctionType
{
    public int $value;

    const HOST_FUNCTION_TYPE_INVOKE_CONTRACT = 0;
    const HOST_FUNCTION_TYPE_CREATE_CONTRACT = 1;
    const HOST_FUNCTION_TYPE_UPLOAD_CONTRACT_WASM = 2;

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

    public static function decode(XdrBuffer $xdr): XdrHostFunctionType
    {
        $value = $xdr->readInteger32();
        return new XdrHostFunctionType($value);
    }

    public static function INVOKE_CONTRACT() :  XdrHostFunctionType {
        return new XdrHostFunctionType(XdrHostFunctionType::HOST_FUNCTION_TYPE_INVOKE_CONTRACT);
    }

    public static function CREATE_CONTRACT() :  XdrHostFunctionType {
        return new XdrHostFunctionType(XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT);
    }

    public static function UPLOAD_CONTRACT_WASM() :  XdrHostFunctionType {
        return new XdrHostFunctionType(XdrHostFunctionType::HOST_FUNCTION_TYPE_UPLOAD_CONTRACT_WASM);
    }
}