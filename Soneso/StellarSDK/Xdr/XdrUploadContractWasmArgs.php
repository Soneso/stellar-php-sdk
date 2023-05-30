<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrUploadContractWasmArgs
{

    public XdrDataValueMandatory $code;

    /**
     * @param XdrDataValueMandatory $code
     */
    public function __construct(XdrDataValueMandatory $code)
    {
        $this->code = $code;
    }


    public function encode(): string {
        return $this->code->encode();
    }

    public static function decode(XdrBuffer $xdr):  XdrUploadContractWasmArgs {
        return new XdrUploadContractWasmArgs(XdrDataValueMandatory::decode($xdr));
    }

    /**
     * @return XdrDataValueMandatory
     */
    public function getCode(): XdrDataValueMandatory
    {
        return $this->code;
    }

    /**
     * @param XdrDataValueMandatory $code
     */
    public function setCode(XdrDataValueMandatory $code): void
    {
        $this->code = $code;
    }

}