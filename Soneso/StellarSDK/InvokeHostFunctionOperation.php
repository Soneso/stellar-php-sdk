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

/**
 * Represents <a href="https://developers.stellar.org/docs/start/list-of-operations/#invoke-host-function" target="_blank">InvokeHostFunction</a> operation.
 *
 * Invokes a Soroban smart contract function, uploads WASM code, or deploys a contract.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org/docs/start/list-of-operations/" target="_blank">List of Operations</a>
 * @see <a href="https://developers.stellar.org/docs/learn/smart-contracts" target="_blank">Smart Contracts</a>
 * @since 1.0.0
 */
class InvokeHostFunctionOperation extends AbstractOperation
{
    /**
     * @var HostFunction The host function to invoke
     */
    public HostFunction $function;

    /**
     * @var array<SorobanAuthorizationEntry> Authorization entries for the invocation
     */
    public array $auth;

    /**
     * Creates a new InvokeHostFunctionOperation.
     *
     * @param HostFunction $function The host function to invoke
     * @param array<SorobanAuthorizationEntry> $auth Authorization entries
     * @param MuxedAccount|null $sourceAccount Optional source account
     */
    public function __construct(HostFunction $function, array $auth = array(), ?MuxedAccount $sourceAccount = null)
    {
        $this->function = $function;
        $this->auth = $auth;
        $this->setSourceAccount($sourceAccount);
    }

    /**
     * Creates an InvokeHostFunctionOperation from its XDR representation.
     *
     * @param XdrInvokeHostFunctionOp $xdrOp The XDR invoke host function operation to convert
     * @return InvokeHostFunctionOperation The resulting InvokeHostFunctionOperation instance
     * @throws Exception If the XDR operation is invalid
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
                    } else if ($executableTypeValue == XdrContractExecutableType::CONTRACT_EXECUTABLE_STELLAR_ASSET){
                        return new InvokeHostFunctionOperation(DeploySACWithSourceAccountHostFunction::fromXdr($xdrFunction), $auth);
                    }
                } else if ($executableTypeValue == XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ASSET) {
                    return new InvokeHostFunctionOperation(DeploySACWithAssetHostFunction::fromXdr($xdrFunction), $auth);
                }
                break;
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT_V2:
                $createContractV2 = $xdrFunction->createContractV2;
                if ($createContractV2 == null) {
                    throw new Exception("invalid argument");
                }
                $contractIdPreimageTypeVal = $createContractV2->contractIDPreimage->type->value;
                $executableTypeValue = $createContractV2->executable->type->value;
                if ($contractIdPreimageTypeVal == XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS) {
                    if ($executableTypeValue == XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM) {
                        return new InvokeHostFunctionOperation(CreateContractWithConstructorHostFunction::fromXdr($xdrFunction), $auth);
                    } else if ($executableTypeValue == XdrContractExecutableType::CONTRACT_EXECUTABLE_STELLAR_ASSET){
                        return new InvokeHostFunctionOperation(DeploySACWithSourceAccountHostFunction::fromXdr($xdrFunction), $auth);
                    }
                } else if ($executableTypeValue == XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ASSET) {
                    return new InvokeHostFunctionOperation(DeploySACWithAssetHostFunction::fromXdr($xdrFunction), $auth);
                }
                break;
        }
        throw new Exception("invalid argument");
    }

    /**
     * Converts this operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body
     */
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
     * Gets the host function.
     *
     * @return HostFunction The host function
     */
    public function getFunction(): HostFunction
    {
        return $this->function;
    }

    /**
     * Sets the host function.
     *
     * @param HostFunction $function The host function
     */
    public function setFunction(HostFunction $function): void
    {
        $this->function = $function;
    }

    /**
     * Gets the authorization entries.
     *
     * @return array<SorobanAuthorizationEntry> The authorization entries
     */
    public function getAuth(): array
    {
        return $this->auth;
    }

    /**
     * Sets the authorization entries.
     *
     * @param array<SorobanAuthorizationEntry> $auth The authorization entries
     */
    public function setAuth(array $auth): void
    {
        $this->auth = $auth;
    }
}