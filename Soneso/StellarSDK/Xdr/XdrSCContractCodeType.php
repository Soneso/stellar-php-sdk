<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCContractCodeType
{
    public int $value;

    const SCCONTRACT_CODE_WASM_REF = 0;
    const SCCONTRACT_CODE_TOKEN = 1;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function WASM_REF() :  XdrSCContractCodeType {
        return new XdrSCContractCodeType(XdrSCContractCodeType::SCCONTRACT_CODE_WASM_REF);
    }

    public static function TOKEN() :  XdrSCContractCodeType {
        return new XdrSCContractCodeType(XdrSCContractCodeType::SCCONTRACT_CODE_TOKEN);
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

    public static function decode(XdrBuffer $xdr): XdrSCContractCodeType
    {
        $value = $xdr->readInteger32();
        return new XdrSCContractCodeType($value);
    }
}