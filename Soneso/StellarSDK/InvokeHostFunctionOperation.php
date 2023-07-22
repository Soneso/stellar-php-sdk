<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Exception;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Xdr\XdrContractIDPreimageType;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;
use Soneso\StellarSDK\Xdr\XdrInvokeHostFunctionOp;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrContractExecutableType;

class InvokeHostFunctionOperation extends AbstractOperation
{
    public HostFunction $function;
    public array $auth; // [SorobanAuthorizationEntry]

    /**
     * @param HostFunction $function
     * @param array $auth [XdrSorobanAuthorizationEntry]
     * @param MuxedAccount|null $sourceAccount
     */
    public function __construct(HostFunction $function, array $auth = array(), ?MuxedAccount $sourceAccount = null)
    {
        $this->function = $function;
        $this->auth = $auth;
        $this->setSourceAccount($sourceAccount);
    }


    /**
     * @throws Exception
     */
    public static function fromXdrOperation(XdrInvokeHostFunctionOp $xdrOp): InvokeHostFunctionOperation {
        $auth = array();
        foreach ($xdrOp->auth as $nextXdrAuth) {
            array_push($auth, SorobanAuthorizationEntry::fromXdr($nextXdrAuth));
        }

        $xdrFunction = $xdrOp->hostFunction;
        switch ($xdrFunction->type->value) {
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_INVOKE_CONTRACT:
                return new InvokeHostFunctionOperation(InvokeContractHostFunction::fromXdr($xdrFunction), $auth);
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_UPLOAD_CONTRACT_WASM:
                return new InvokeHostFunctionOperation(UploadContractWasmHostFunction::fromXdr($xdrFunction), $auth);
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT:
                $createContract = $xdrFunction->createContract;
                if ($createContract == null) {
                    throw new Exception("invalid argument");
                }
                $contractIdPreimageTypeVal = $createContract->contractIDPreimage->type->value;
                $executableTypeValue = $createContract->executable->type->value;
                if ($contractIdPreimageTypeVal == XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS) {
                    if ($executableTypeValue == XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM) {
                        return new InvokeHostFunctionOperation(CreateContractHostFunction::fromXdr($xdrFunction), $auth);
                    } else if ($executableTypeValue == XdrContractExecutableType::CONTRACT_EXECUTABLE_TOKEN){
                        return new InvokeHostFunctionOperation(DeploySACWithSourceAccountHostFunction::fromXdr($xdrFunction), $auth);
                    }
                } else if ($executableTypeValue == XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ASSET) {
                    return new InvokeHostFunctionOperation(DeploySACWithAssetHostFunction::fromXdr($xdrFunction), $auth);
                }
                break;
        }
        throw new Exception("invalid argument");
    }

    public function toOperationBody(): XdrOperationBody
    {
        $xdrAuth = array();
        foreach ($this->auth as $nextAuth) {
            array_push($xdrAuth, $nextAuth->toXdr());
        }
        $xdrOp = new XdrInvokeHostFunctionOp($this->function->toXdr(), $xdrAuth);
        $type = new XdrOperationType(XdrOperationType::INVOKE_HOST_FUNCTION);
        $result = new XdrOperationBody($type);
        $result->setInvokeHostFunctionOperation($xdrOp);
        return $result;
    }

    /**
     * @return HostFunction
     */
    public function getFunction(): HostFunction
    {
        return $this->function;
    }

    /**
     * @param HostFunction $function
     */
    public function setFunction(HostFunction $function): void
    {
        $this->function = $function;
    }

    /**
     * @return array
     */
    public function getAuth(): array
    {
        return $this->auth;
    }

    /**
     * @param array $auth
     */
    public function setAuth(array $auth): void
    {
        $this->auth = $auth;
    }
}