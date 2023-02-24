<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Exception;
use Soneso\StellarSDK\Xdr\XdrContractIDType;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;
use Soneso\StellarSDK\Xdr\XdrInvokeHostFunctionOperation;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrSCContractCodeType;
use Soneso\StellarSDK\Xdr\XdrSCVal;

abstract class InvokeHostFunctionOperation extends AbstractOperation
{
    public XdrHostFunctionType $functionType;
    public ?Footprint $footprint = null;
    public array $auth;

    /**
     * @param XdrHostFunctionType $functionType
     * @param Footprint|null $footprint
     * @param MuxedAccount|null $sourceAccount
     */
    public function __construct(XdrHostFunctionType $functionType, ?Footprint $footprint = null, ?array $auth = array(), ?MuxedAccount $sourceAccount = null)
    {
        $this->functionType = $functionType;
        $this->footprint = $footprint;
        $this->auth = $auth;
        $this->setSourceAccount($sourceAccount);
    }

    /**
     * @return Footprint|null
     */
    public function getFootprint(): ?Footprint
    {
        return $this->footprint;
    }

    /**
     * @param Footprint|null $footprint
     */
    public function setFootprint(?Footprint $footprint): void
    {
        $this->footprint = $footprint;
    }


    public function getXdrFootprint(): XdrLedgerFootprint {
        if($this->footprint != null) {
            return $this->footprint->xdrFootprint;
        }
        return new XdrLedgerFootprint([],[]);
    }

    /**
     * @return XdrHostFunctionType
     */
    public function getFunctionType(): XdrHostFunctionType
    {
        return $this->functionType;
    }

    /**
     * @return array|null
     */
    public function getAuth(): ?array
    {
        return $this->auth;
    }

    /**
     * @param array|null $auth
     */
    public function setAuth(?array $auth): void
    {
        $this->auth = $auth;
    }



    /**
     * @throws Exception
     */
    public static function fromXdrOperation(XdrInvokeHostFunctionOperation $xdrOp): InvokeHostFunctionOperation {
        $footprint = new Footprint($xdrOp->footprint);
        $hostFunction = $xdrOp->function;
        switch ($hostFunction->type->value) {
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_INVOKE_CONTRACT:
                return InvokeHostFunctionOperation::fromInvokeContractHostFunction($hostFunction, $footprint, $xdrOp->auth);
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_INSTALL_CONTRACT_CODE:
                return InvokeHostFunctionOperation::fromInstallContractCodeHostFunction($hostFunction, $footprint, $xdrOp->auth);
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT:
                if($hostFunction->createContractArgs == null) {
                    throw new Exception("invalid argument");
                }
                $contractIdTypeVal = $hostFunction->createContractArgs->contractID->type->value;
                $sourceTypeValue = $hostFunction->createContractArgs->source->type->value;
                if ($contractIdTypeVal == XdrContractIDType::CONTRACT_ID_FROM_SOURCE_ACCOUNT) {
                    if ($sourceTypeValue == XdrSCContractCodeType::SCCONTRACT_CODE_WASM_REF) {
                        return InvokeHostFunctionOperation::fromCreateContractHostFunction($hostFunction, $footprint, $xdrOp->auth);
                    } else if ($sourceTypeValue == XdrSCContractCodeType::SCCONTRACT_CODE_TOKEN){
                        return InvokeHostFunctionOperation::fromDeployCreateTokenContractWithSourceAccount($hostFunction, $footprint, $xdrOp->auth);
                    }
                } else if ($contractIdTypeVal == XdrContractIDType::CONTRACT_ID_FROM_ASSET) {
                    return InvokeHostFunctionOperation::fromDeployCreateTokenContractWithAsset($hostFunction, $footprint, $xdrOp->auth);
                }
        }
        throw new Exception("unknown function type");
    }

    /**
     * @param XdrHostFunction $hostFunction
     * @param Footprint $footprint
     * @param array|null $auth
     * @return DeploySACWithAssetOp
     * @throws Exception
     */
    private static function fromDeployCreateTokenContractWithAsset(XdrHostFunction $hostFunction, Footprint $footprint, ?array $auth = array()) : DeploySACWithAssetOp {
        if ($hostFunction->type->value != XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT
            || $hostFunction->createContractArgs == null
            || $hostFunction->createContractArgs->contractID->type->value != XdrContractIDType::CONTRACT_ID_FROM_ASSET
            ||$hostFunction->createContractArgs->source->type->value != XdrSCContractCodeType::SCCONTRACT_CODE_TOKEN)
        {
            throw new Exception("invalid argument");
        }
        $asset = Asset::fromXdr($hostFunction->createContractArgs->contractID->asset);
        return new DeploySACWithAssetOp($asset,$footprint, $auth);
    }

    /**
     * @param XdrHostFunction $hostFunction
     * @param Footprint $footprint
     * @param array|null $auth
     * @return DeploySACWithSourceAccountOp
     * @throws Exception
     */
    private static function fromDeployCreateTokenContractWithSourceAccount(XdrHostFunction $hostFunction, Footprint $footprint, ?array $auth = array()) : DeploySACWithSourceAccountOp {
        if ($hostFunction->type->value != XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT
            || $hostFunction->createContractArgs == null
            || $hostFunction->createContractArgs->contractID->type->value != XdrContractIDType::CONTRACT_ID_FROM_SOURCE_ACCOUNT
            ||$hostFunction->createContractArgs->source->type->value != XdrSCContractCodeType::SCCONTRACT_CODE_TOKEN)
        {
            throw new Exception("invalid argument");
        }
        return new DeploySACWithSourceAccountOp($hostFunction->createContractArgs->contractID->salt,$footprint, $auth);
    }

    /**
     * @param XdrHostFunction $hostFunction
     * @param Footprint $footprint
     * @param array|null $auth
     * @return CreateContractOp
     * @throws Exception
     */
    private static function fromCreateContractHostFunction(XdrHostFunction $hostFunction, Footprint $footprint, ?array $auth = array()) : CreateContractOp {
        if ($hostFunction->type->value != XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT
            || $hostFunction->createContractArgs == null
            || $hostFunction->createContractArgs->contractID->type->value != XdrContractIDType::CONTRACT_ID_FROM_SOURCE_ACCOUNT
            ||$hostFunction->createContractArgs->source->type->value != XdrSCContractCodeType::SCCONTRACT_CODE_WASM_REF)
        {
            throw new Exception("invalid argument");
        }
        $wasmId = $hostFunction->createContractArgs->source->wasmIdHex;

        if($wasmId == null) {
            throw new Exception("invalid argument");
        }
        return new CreateContractOp($wasmId,$hostFunction->createContractArgs->contractID->salt, $footprint, $auth);
    }

    /**
     * @param XdrHostFunction $hostFunction
     * @param Footprint $footprint
     * @param array|null $auth
     * @return InstallContractCodeOp
     * @throws Exception
     */
    private static function fromInstallContractCodeHostFunction(XdrHostFunction $hostFunction, Footprint $footprint, ?array $auth = array()) : InstallContractCodeOp {
        if ($hostFunction->type->value != XdrHostFunctionType::HOST_FUNCTION_TYPE_INSTALL_CONTRACT_CODE
            || $hostFunction->installContractCodeArgs == null) {
            throw new Exception("invalid argument");
        }
        $contractCode = $hostFunction->installContractCodeArgs->code->getValue();

        if($contractCode == null) {
            throw new Exception("invalid argument");
        }
        return new InstallContractCodeOp($contractCode, $footprint, $auth);
    }

    /**
     * @param XdrHostFunction $hostFunction
     * @param Footprint $footprint
     * @param array|null $auth
     * @return InvokeContractOp
     * @throws Exception
     */
    private static function fromInvokeContractHostFunction(XdrHostFunction $hostFunction, Footprint $footprint, ?array $auth = array()) : InvokeContractOp {
        if ($hostFunction->type->value != XdrHostFunctionType::HOST_FUNCTION_TYPE_INVOKE_CONTRACT
            || $hostFunction->invokeArgs == null || count($hostFunction->invokeArgs) < 2) {
            throw new Exception("invalid argument");
        }
        $contractId = null;
        $functionName = null;
        $args = null;
        $contractIdVal = $hostFunction->invokeArgs[0];
        $functionVal = $hostFunction->invokeArgs[1];
        if ($contractIdVal instanceof XdrSCVal && $contractIdVal->obj != null && $contractIdVal->obj->bin != null ) {
            $contractId = bin2hex($contractIdVal->obj->bin->getValue());
        }
        if ($functionVal instanceof XdrSCVal && $functionVal->sym != null) {
            $functionName = $functionVal->sym;
        }
        if(count($hostFunction->invokeArgs) > 2) {
            $args = array_slice($hostFunction->invokeArgs, 2);
        }
        if($contractId == null | $functionName == null) {
            throw new Exception("invalid argument");
        }
        return new InvokeContractOp($contractId,$functionName, $args, $footprint, $auth);
    }
}