<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrHostFunctionArgs
{

    public XdrHostFunctionType $type;
    public ?array $invokeContract = null; // [XdrSCVal]
    public ?XdrCreateContractArgs $createContract = null;
    public ?XdrUploadContractWasmArgs $uploadContractWasm = null;

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
                $bytes .= XdrEncoder::integer32(count($this->invokeContract));
                foreach($this->invokeContract as $val) {
                    if ($val instanceof XdrSCVal) {
                        $bytes .= $val->encode();
                    }
                }
                break;
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT:
                $bytes .= $this->createContract->encode();
                break;
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_UPLOAD_CONTRACT_WASM:
                $bytes .= $this->uploadContractWasm->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrHostFunctionArgs {
        $result = new XdrHostFunctionArgs(XdrHostFunctionType::decode($xdr));
        switch ($result->type->value) {
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_INVOKE_CONTRACT:
                $valCount = $xdr->readInteger32();
                $arr = array();
                for ($i = 0; $i < $valCount; $i++) {
                    array_push($arr, XdrSCVal::decode($xdr));
                }
                $result->invokeContract = $arr;
                break;
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT:
                $result->createContract = XdrCreateContractArgs::decode($xdr);
                break;
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_UPLOAD_CONTRACT_WASM:
                $result->uploadContractWasm = XdrUploadContractWasmArgs::decode($xdr);
                break;
        }
        return $result;
    }

    public static function forInvokingContractWithArgs(array $scValArgs) :  XdrHostFunctionArgs {
        $result = new XdrHostFunctionArgs(XdrHostFunctionType::INVOKE_CONTRACT());
        $result->invokeContract = $scValArgs;
        return $result;
    }

    public static function forUploadContractWasm(string $contractCodeRawBytes) :  XdrHostFunctionArgs {
        $result = new XdrHostFunctionArgs(XdrHostFunctionType::UPLOAD_CONTRACT_WASM());
        $args = new XdrUploadContractWasmArgs(new XdrDataValueMandatory($contractCodeRawBytes));
        $result->uploadContractWasm = $args;
        return $result;
    }

    public static function forCreatingContract(string $wasmIdHex, string $salt) :  XdrHostFunctionArgs {
        $result = new XdrHostFunctionArgs(XdrHostFunctionType::CREATE_CONTRACT());
        $cId = new XdrContractID(new XdrContractIDType(XdrContractIDType::CONTRACT_ID_FROM_SOURCE_ACCOUNT));
        $cId->salt = $salt;
        $cCode = new XdrSCContractExecutable(XdrSCContractExecutableType::WASM_REF());
        $cCode->wasmIdHex = $wasmIdHex;
        $result->createContract = new XdrCreateContractArgs($cId, $cCode);
        return $result;
    }

    public static function forDeploySACWithSourceAccount(string $salt) :  XdrHostFunctionArgs {
        $result = new XdrHostFunctionArgs(XdrHostFunctionType::CREATE_CONTRACT());
        $cId = new XdrContractID(new XdrContractIDType(XdrContractIDType::CONTRACT_ID_FROM_SOURCE_ACCOUNT));
        $cId->salt = $salt;
        $cCode = new XdrSCContractExecutable(XdrSCContractExecutableType::TOKEN());
        $result->createContract = new XdrCreateContractArgs($cId, $cCode);
        return $result;
    }

    public static function forDeploySACWithAsset(XdrAsset $asset) :  XdrHostFunctionArgs {
        $result = new XdrHostFunctionArgs(XdrHostFunctionType::CREATE_CONTRACT());
        $cId = new XdrContractID(new XdrContractIDType(XdrContractIDType::CONTRACT_ID_FROM_ASSET));
        $cId->asset = $asset;
        $cCode = new XdrSCContractExecutable(XdrSCContractExecutableType::TOKEN());
        $result->createContract = new XdrCreateContractArgs($cId, $cCode);
        return $result;
    }

    /**
     * @return XdrHostFunctionType
     */
    public function getType(): XdrHostFunctionType
    {
        return $this->type;
    }

    /**
     * @param XdrHostFunctionType $type
     */
    public function setType(XdrHostFunctionType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return array|null [XdrSCVal]
     */
    public function getInvokeContract(): ?array
    {
        return $this->invokeContract;
    }

    /**
     * @param array|null $invokeContract [XdrSCVal]
     */
    public function setInvokeContract(?array $invokeContract): void
    {
        $this->invokeContract = $invokeContract;
    }

    /**
     * @return XdrCreateContractArgs|null
     */
    public function getCreateContract(): ?XdrCreateContractArgs
    {
        return $this->createContract;
    }

    /**
     * @param XdrCreateContractArgs|null $createContract
     */
    public function setCreateContract(?XdrCreateContractArgs $createContract): void
    {
        $this->createContract = $createContract;
    }

    /**
     * @return XdrUploadContractWasmArgs|null
     */
    public function getUploadContractWasm(): ?XdrUploadContractWasmArgs
    {
        return $this->uploadContractWasm;
    }

    /**
     * @param XdrUploadContractWasmArgs|null $uploadContractWasm
     */
    public function setUploadContractWasm(?XdrUploadContractWasmArgs $uploadContractWasm): void
    {
        $this->uploadContractWasm = $uploadContractWasm;
    }
}