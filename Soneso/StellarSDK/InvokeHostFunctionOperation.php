<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Exception;
use Soneso\StellarSDK\Xdr\XdrContractIDType;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;
use Soneso\StellarSDK\Xdr\XdrInvokeHostFunctionOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrSCContractExecutableType;

class InvokeHostFunctionOperation extends AbstractOperation
{
    public array $functions;

    /**
     * @param array|null $functions
     * @param MuxedAccount|null $sourceAccount
     */
    public function __construct(?array $functions = array(), ?MuxedAccount $sourceAccount = null)
    {
        $this->functions = $functions;
        $this->setSourceAccount($sourceAccount);
    }

    /*
    public function getXdrFootprint(): XdrLedgerFootprint {
        if($this->footprint != null) {
            return $this->footprint->xdrFootprint;
        }
        return new XdrLedgerFootprint([],[]);
    }
*/

    /**
     * @throws Exception
     */
    public static function fromXdrOperation(XdrInvokeHostFunctionOperation $xdrOp): InvokeHostFunctionOperation {

        $functions = array();
        foreach ($xdrOp->functions as $xdr) {
            switch ($xdr->args->type->value) {
                case XdrHostFunctionType::HOST_FUNCTION_TYPE_INVOKE_CONTRACT:
                    array_push($functions , InvokeContractHostFunction::fromXdr($xdr));
                    break;
                case XdrHostFunctionType::HOST_FUNCTION_TYPE_UPLOAD_CONTRACT_WASM:
                    array_push($functions , UploadContractWasmHostFunction::fromXdr($xdr));
                    break;
                case XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT:
                    $args = $xdr->args;
                    if ($args->createContract == null) {
                        throw new Exception("invalid argument");
                    }
                    $contractIdTypeVal = $args->createContract->contractID->type->value;
                    $sourceTypeValue = $args->createContract->executable->type->value;
                    if ($contractIdTypeVal == XdrContractIDType::CONTRACT_ID_FROM_SOURCE_ACCOUNT) {
                        if ($sourceTypeValue == XdrSCContractExecutableType::SCCONTRACT_EXECUTABLE_WASM_REF) {
                            array_push($functions , CreateContractHostFunction::fromXdr($xdr));
                        } else if ($sourceTypeValue == XdrSCContractExecutableType::SCCONTRACT_EXECUTABLE_TOKEN){
                            array_push($functions , DeploySACWithSourceAccountHostFunction::fromXdr($xdr));
                        }
                    } else if ($contractIdTypeVal == XdrContractIDType::CONTRACT_ID_FROM_ASSET) {
                        array_push($functions , DeploySACWithAssetHostFunction::fromXdr($xdr));
                    }
                    break;
            }
        }
        return new InvokeHostFunctionOperation($functions);
    }

    public function toOperationBody(): XdrOperationBody
    {
        $xdrFunctions = array();
        foreach ($this->functions as $function) {
            array_push($xdrFunctions , $function->toXdr());
        }
        $xdrOp = new XdrInvokeHostFunctionOperation($xdrFunctions);
        $type = new XdrOperationType(XdrOperationType::INVOKE_HOST_FUNCTION);
        $result = new XdrOperationBody($type);
        $result->setInvokeHostFunctionOperation($xdrOp);
        return $result;
    }
}