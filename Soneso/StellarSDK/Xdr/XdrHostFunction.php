<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrHostFunction
{

    public XdrHostFunctionType $type;
    public ?array $invokeContract = null; // [XdrSCVal]
    public ?XdrCreateContractArgs $createContract = null;
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
                $bytes .= $this->wasm->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrHostFunction {
        $result = new XdrHostFunction(XdrHostFunctionType::decode($xdr));
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
                $result->wasm = XdrDataValueMandatory::decode($xdr);
                break;
        }
        return $result;
    }

    public static function forInvokingContractWithArgs(array $scValArgs) :  XdrHostFunction {
        $result = new XdrHostFunction(XdrHostFunctionType::INVOKE_CONTRACT());
        $result->invokeContract = $scValArgs;
        return $result;
    }

    public static function forUploadContractWasm(string $contractCodeRawBytes) :  XdrHostFunction {
        $result = new XdrHostFunction(XdrHostFunctionType::UPLOAD_CONTRACT_WASM());
        $result->wasm = new XdrDataValueMandatory($contractCodeRawBytes);
        return $result;
    }

    public static function forCreatingContract(XdrSCAddress $address, string $wasmIdHex, string $salt) :  XdrHostFunction {
        $result = new XdrHostFunction(XdrHostFunctionType::CREATE_CONTRACT());
        $cId = new XdrContractIDPreimage(XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS());
        $cId->address = $address;
        $cId->salt = $salt;
        $cCode = new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM());
        $cCode->wasmIdHex = $wasmIdHex;
        $result->createContract = new XdrCreateContractArgs($cId, $cCode);
        return $result;
    }

    public static function forDeploySACWithSourceAccount(XdrSCAddress $address, string $salt) :  XdrHostFunction {
        $result = new XdrHostFunction(XdrHostFunctionType::CREATE_CONTRACT());
        $cId = new XdrContractIDPreimage(XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS());
        $cId->salt = $salt;
        $cId->address = $address;
        $cCode = new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_TOKEN());
        $result->createContract = new XdrCreateContractArgs($cId, $cCode);
        return $result;
    }

    public static function forDeploySACWithAsset(XdrAsset $asset) :  XdrHostFunction {
        $result = new XdrHostFunction(XdrHostFunctionType::CREATE_CONTRACT());
        $cId = new XdrContractIDPreimage(XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ASSET());
        $cId->asset = $asset;
        $cCode = new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_TOKEN());
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
     * @return array|null
     */
    public function getInvokeContract(): ?array
    {
        return $this->invokeContract;
    }

    /**
     * @param array|null $invokeContract
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
     * @return XdrDataValueMandatory|null
     */
    public function getWasm(): ?XdrDataValueMandatory
    {
        return $this->wasm;
    }

    /**
     * @param XdrDataValueMandatory|null $wasm
     */
    public function setWasm(?XdrDataValueMandatory $wasm): void
    {
        $this->wasm = $wasm;
    }
}