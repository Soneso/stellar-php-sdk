<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrHostFunction
{

    public XdrHostFunctionType $type;
    public ?array $invokeArgs = null; // [XdrSCVal]
    public ?XdrCreateContractArgs $createContractArgs = null;
    public ?XdrInstallContractCodeArgs $installContractCodeArgs = null;

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
                $bytes .= XdrEncoder::integer32(count($this->invokeArgs));
                foreach($this->invokeArgs as $val) {
                    if ($val instanceof XdrSCVal) {
                        $bytes .= $val->encode();
                    }
                }
                break;
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT:
                $bytes .= $this->createContractArgs->encode();
                break;
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_INSTALL_CONTRACT_CODE:
                $bytes .= $this->installContractCodeArgs->encode();
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
                $result->invokeArgs = $arr;
                break;
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT:
                $result->createContractArgs = XdrCreateContractArgs::decode($xdr);
                break;
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_INSTALL_CONTRACT_CODE:
                $result->installContractCodeArgs = XdrInstallContractCodeArgs::decode($xdr);
                break;
        }
        return $result;
    }

    public static function forInvokingContractWithArgs(array $scValArgs) :  XdrHostFunction {
        $result = new XdrHostFunction(XdrHostFunctionType::INVOKE_CONTRACT());
        $result->invokeArgs = $scValArgs;
        return $result;
    }

    public static function forInstallingContract(string $contractCodeRawBytes) :  XdrHostFunction {
        $result = new XdrHostFunction(XdrHostFunctionType::INSTALL_CONTRACT_CODE());
        $args = new XdrInstallContractCodeArgs(new XdrDataValueMandatory($contractCodeRawBytes));
        $result->installContractCodeArgs = $args;
        return $result;
    }

    public static function forCreatingContract(string $wasmIdHex, string $salt) :  XdrHostFunction {
        $result = new XdrHostFunction(XdrHostFunctionType::CREATE_CONTRACT());
        $cId = new XdrContractID(new XdrContractIDType(XdrContractIDType::CONTRACT_ID_FROM_SOURCE_ACCOUNT));
        $cId->salt = $salt;
        $cCode = new XdrSCContractExecutable(XdrSCContractExecutableType::WASM_REF());
        $cCode->wasmIdHex = $wasmIdHex;
        $result->createContractArgs = new XdrCreateContractArgs($cId, $cCode);
        return $result;
    }

    public static function forDeploySACWithSourceAccount(string $salt) :  XdrHostFunction {
        $result = new XdrHostFunction(XdrHostFunctionType::CREATE_CONTRACT());
        $cId = new XdrContractID(new XdrContractIDType(XdrContractIDType::CONTRACT_ID_FROM_SOURCE_ACCOUNT));
        $cId->salt = $salt;
        $cCode = new XdrSCContractExecutable(XdrSCContractExecutableType::TOKEN());
        $result->createContractArgs = new XdrCreateContractArgs($cId, $cCode);
        return $result;
    }

    public static function forDeploySACWithAsset(XdrAsset $asset) :  XdrHostFunction {
        $result = new XdrHostFunction(XdrHostFunctionType::CREATE_CONTRACT());
        $cId = new XdrContractID(new XdrContractIDType(XdrContractIDType::CONTRACT_ID_FROM_ASSET));
        $cId->asset = $asset;
        $cCode = new XdrSCContractExecutable(XdrSCContractExecutableType::TOKEN());
        $result->createContractArgs = new XdrCreateContractArgs($cId, $cCode);
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
    public function getInvokeArgs(): ?array
    {
        return $this->invokeArgs;
    }

    /**
     * @param array|null $invokeArgs [XdrSCVal]
     */
    public function setInvokeArgs(?array $invokeArgs): void
    {
        $this->invokeArgs = $invokeArgs;
    }

    /**
     * @return XdrCreateContractArgs|null
     */
    public function getCreateContractArgs(): ?XdrCreateContractArgs
    {
        return $this->createContractArgs;
    }

    /**
     * @param XdrCreateContractArgs|null $createContractArgs
     */
    public function setCreateContractArgs(?XdrCreateContractArgs $createContractArgs): void
    {
        $this->createContractArgs = $createContractArgs;
    }

    /**
     * @return XdrInstallContractCodeArgs|null
     */
    public function getInstallContractCodeArgs(): ?XdrInstallContractCodeArgs
    {
        return $this->installContractCodeArgs;
    }

    /**
     * @param XdrInstallContractCodeArgs|null $installContractCodeArgs
     */
    public function setInstallContractCodeArgs(?XdrInstallContractCodeArgs $installContractCodeArgs): void
    {
        $this->installContractCodeArgs = $installContractCodeArgs;
    }
}