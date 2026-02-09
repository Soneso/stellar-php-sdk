<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrHostFunction
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

    public static function decode(XdrBuffer $xdr):  XdrHostFunction {
        $result = new XdrHostFunction(XdrHostFunctionType::decode($xdr));
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

    public static function forInvokingContractWithArgs(XdrInvokeContractArgs $args) :  XdrHostFunction {
        $result = new XdrHostFunction(XdrHostFunctionType::INVOKE_CONTRACT());
        $result->invokeContract = $args;
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

    /**
     * @param XdrSCAddress $address
     * @param string $wasmIdHex
     * @param string $salt
     * @param array<XdrSCVal> $constructorArgs
     * @return XdrHostFunction
     */
    public static function forCreatingContractV2(XdrSCAddress $address, string $wasmIdHex, string $salt, array $constructorArgs) :  XdrHostFunction {
        $result = new XdrHostFunction(XdrHostFunctionType::CREATE_CONTRACT_V2());
        $cId = new XdrContractIDPreimage(XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS());
        $cId->address = $address;
        $cId->salt = $salt;
        $cCode = new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM());
        $cCode->wasmIdHex = $wasmIdHex;
        $result->createContractV2 = new XdrCreateContractArgsV2($cId, $cCode, $constructorArgs);
        return $result;
    }

    public static function forDeploySACWithAsset(XdrAsset $asset) :  XdrHostFunction {
        $result = new XdrHostFunction(XdrHostFunctionType::CREATE_CONTRACT());
        $cId = new XdrContractIDPreimage(XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ASSET());
        $cId->asset = $asset;
        $cCode = new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_STELLAR_ASSET());
        $result->createContract = new XdrCreateContractArgs($cId, $cCode);
        return $result;
    }

    public static function forCreatingContractWithArgs(XdrCreateContractArgs $args) :  XdrHostFunction {
        $result = new XdrHostFunction(XdrHostFunctionType::CREATE_CONTRACT());
        $result->createContract = $args;
        return $result;
    }

    public static function forCreatingContractV2WithArgs(XdrCreateContractArgsV2 $args) :  XdrHostFunction {
        $result = new XdrHostFunction(XdrHostFunctionType::CREATE_CONTRACT_V2());
        $result->createContractV2 = $args;
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
     * @return XdrInvokeContractArgs|null
     */
    public function getInvokeContract(): ?XdrInvokeContractArgs
    {
        return $this->invokeContract;
    }

    /**
     * @param XdrInvokeContractArgs|null $invokeContract
     */
    public function setInvokeContract(?XdrInvokeContractArgs $invokeContract): void
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

    /**
     * @return XdrCreateContractArgsV2|null
     */
    public function getCreateContractV2(): ?XdrCreateContractArgsV2
    {
        return $this->createContractV2;
    }

    /**
     * @param XdrCreateContractArgsV2|null $createContractV2
     */
    public function setCreateContractV2(?XdrCreateContractArgsV2 $createContractV2): void
    {
        $this->createContractV2 = $createContractV2;
    }
}