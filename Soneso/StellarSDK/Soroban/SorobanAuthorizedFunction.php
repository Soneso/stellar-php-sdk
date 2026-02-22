<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use InvalidArgumentException;
use Soneso\StellarSDK\Xdr\XdrCreateContractArgs;
use Soneso\StellarSDK\Xdr\XdrCreateContractArgsV2;
use Soneso\StellarSDK\Xdr\XdrInvokeContractArgs;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedFunction;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedFunctionType;


/**
 * Authorized function for Soroban invocations
 *
 * This class represents one of three types of authorized operations in Soroban:
 * contract function invocation, contract creation, or contract creation with constructor.
 * Exactly one of the three function types must be set.
 *
 * @package Soneso\StellarSDK\Soroban
 * @see SorobanAuthorizedInvocation
 * @see https://developers.stellar.org/docs/learn/smart-contract-internals/authorization Soroban Authorization
 * @since 1.0.0
 */
class SorobanAuthorizedFunction
{
    /**
     * @var XdrInvokeContractArgs|null contract function invocation arguments if this is a contract call
     */
    public ?XdrInvokeContractArgs $contractFn = null;

    /**
     * @var XdrCreateContractArgs|null contract creation arguments if this is a contract deployment
     */
    public ?XdrCreateContractArgs $createContractHostFn = null;

    /**
     * @var XdrCreateContractArgsV2|null contract creation with constructor arguments
     */
    public ?XdrCreateContractArgsV2 $createContractV2HostFn = null;

    /**
     * Creates a new authorized function.
     *
     * @param XdrInvokeContractArgs|null $contractFn contract invocation arguments (mutually exclusive with other params)
     * @param XdrCreateContractArgs|null $createContractHostFn contract creation arguments (mutually exclusive with other params)
     * @param XdrCreateContractArgsV2|null $createContractV2HostFn contract creation with constructor (mutually exclusive with other params)
     * @throws InvalidArgumentException if all parameters are null (at least one must be provided)
     */
    public function __construct(
        ?XdrInvokeContractArgs $contractFn = null,
        ?XdrCreateContractArgs $createContractHostFn = null,
        ?XdrCreateContractArgsV2 $createContractV2HostFn = null)
    {
        if ($contractFn === null && $createContractHostFn === null && $createContractV2HostFn === null) {
            throw new InvalidArgumentException("Invalid arguments");
        }

        $this->contractFn = $contractFn;
        $this->createContractHostFn = $createContractHostFn;
        $this->createContractV2HostFn = $createContractV2HostFn;
    }

    /**
     * Creates an authorized function for a contract invocation.
     *
     * @param Address $contractAddress the address of the contract to invoke
     * @param string $functionName the name of the function to call
     * @param array<XdrSCVal> $args the function arguments
     * @return SorobanAuthorizedFunction the authorized function for contract invocation
     */
    public static function forContractFunction(Address $contractAddress, string $functionName, array $args = array()) : SorobanAuthorizedFunction {
        $cfn = new XdrInvokeContractArgs($contractAddress->toXdr(), $functionName, $args);
        return new SorobanAuthorizedFunction($cfn);
    }

    /**
     * Creates an authorized function for contract creation.
     *
     * @param XdrCreateContractArgs $createContractHostFn the contract creation arguments
     * @return SorobanAuthorizedFunction the authorized function for contract creation
     */
    public static function forCreateContractFunction(XdrCreateContractArgs $createContractHostFn) : SorobanAuthorizedFunction {
        return new SorobanAuthorizedFunction(null, $createContractHostFn);
    }

    /**
     * Creates an authorized function for contract creation with constructor.
     *
     * @param XdrCreateContractArgsV2 $createContractV2HostFn the contract creation with constructor arguments
     * @return SorobanAuthorizedFunction the authorized function for contract creation with constructor
     */
    public static function forCreateContractWithConstructorFunction(XdrCreateContractArgsV2 $createContractV2HostFn) : SorobanAuthorizedFunction {
        return new SorobanAuthorizedFunction(null, null, $createContractV2HostFn);
    }

    /**
     * Creates SorobanAuthorizedFunction from its XDR representation.
     *
     * @param XdrSorobanAuthorizedFunction $xdr the XDR object to decode
     * @return SorobanAuthorizedFunction the decoded authorized function
     */
    public static function fromXdr(XdrSorobanAuthorizedFunction $xdr) : SorobanAuthorizedFunction {
        if ($xdr->type->value == XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CONTRACT_FN && $xdr->contractFn !== null) {
            return new SorobanAuthorizedFunction($xdr->contractFn);
        } else if ($xdr->type->value == XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_HOST_FN && $xdr->createContractHostFn !== null) {
            return new SorobanAuthorizedFunction(null, $xdr->createContractHostFn);
        }
        return new SorobanAuthorizedFunction(null, null, $xdr->createContractV2HostFn);
    }

    /**
     * Converts this object to its XDR representation.
     *
     * @return XdrSorobanAuthorizedFunction the XDR encoded authorized function
     */
    public function toXdr(): XdrSorobanAuthorizedFunction {
        if ($this->contractFn !== null) {
            $af = new XdrSorobanAuthorizedFunction(XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CONTRACT_FN());
            $af->contractFn = $this->contractFn;
            return $af;
        } else if ($this->createContractHostFn !== null) {
            $af = new XdrSorobanAuthorizedFunction(XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_HOST_FN());
            $af->createContractHostFn = $this->createContractHostFn;
            return $af;
        }
        $af = new XdrSorobanAuthorizedFunction(XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_V2_HOST_FN());
        $af->createContractV2HostFn = $this->createContractV2HostFn;
        return $af;
    }

    /**
     * Returns the contract invocation arguments if this is a contract call.
     *
     * @return XdrInvokeContractArgs|null the invocation arguments or null if not a contract call
     */
    public function getContractFn(): ?XdrInvokeContractArgs
    {
        return $this->contractFn;
    }

    /**
     * Sets the contract invocation arguments.
     *
     * @param XdrInvokeContractArgs|null $contractFn the invocation arguments
     */
    public function setContractFn(?XdrInvokeContractArgs $contractFn): void
    {
        $this->contractFn = $contractFn;
    }

    /**
     * Returns the contract creation arguments if this is a contract deployment.
     *
     * @return XdrCreateContractArgs|null the creation arguments or null if not a deployment
     */
    public function getCreateContractHostFn(): ?XdrCreateContractArgs
    {
        return $this->createContractHostFn;
    }

    /**
     * Sets the contract creation arguments.
     *
     * @param XdrCreateContractArgs|null $createContractHostFn the creation arguments
     */
    public function setCreateContractHostFn(?XdrCreateContractArgs $createContractHostFn): void
    {
        $this->createContractHostFn = $createContractHostFn;
    }

    /**
     * Returns the contract creation with constructor arguments.
     *
     * @return XdrCreateContractArgsV2|null the creation arguments or null if not a V2 deployment
     */
    public function getCreateContractV2HostFn(): ?XdrCreateContractArgsV2
    {
        return $this->createContractV2HostFn;
    }

    /**
     * Sets the contract creation with constructor arguments.
     *
     * @param XdrCreateContractArgsV2|null $createContractV2HostFn the creation arguments
     */
    public function setCreateContractV2HostFn(?XdrCreateContractArgsV2 $createContractV2HostFn): void
    {
        $this->createContractV2HostFn = $createContractV2HostFn;
    }

}