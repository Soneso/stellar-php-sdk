<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrHostFunctionBase
{

    public XdrHostFunctionType $type;
    public ?XdrInvokeContractArgs $invokeContract = null;
    public ?XdrCreateContractArgs $createContract = null;
    public ?XdrCreateContractArgsV2 $createContractV2 = null;
    public ?XdrDataValueMandatory $wasm = null;

    /**
     * @param XdrHostFunctionType $type
     */
    public function __construct(XdrHostFunctionType $type)
    {
        $this->type = $type;
    }


    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_INVOKE_CONTRACT:
                $bytes .= $this->invokeContract->encode();
                break;
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT:
                $bytes .= $this->createContract->encode();
                break;
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_UPLOAD_CONTRACT_WASM:
                $bytes .= $this->wasm->encode();
                break;
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT_V2:
                $bytes .= $this->createContractV2->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr): static {
        $result = new static(XdrHostFunctionType::decode($xdr));
        switch ($result->type->value) {
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_INVOKE_CONTRACT:
                $result->invokeContract = XdrInvokeContractArgs::decode($xdr);
                break;
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT:
                $result->createContract = XdrCreateContractArgs::decode($xdr);
                break;
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_UPLOAD_CONTRACT_WASM:
                $result->wasm = XdrDataValueMandatory::decode($xdr);
                break;
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT_V2:
                $result->createContractV2 = XdrCreateContractArgsV2::decode($xdr);
                break;
        }
        return $result;
    }
}
