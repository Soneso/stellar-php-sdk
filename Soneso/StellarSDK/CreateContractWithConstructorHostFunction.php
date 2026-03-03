<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Exception;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Xdr\XdrContractIDPreimageType;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;
use Soneso\StellarSDK\Xdr\XdrContractExecutableType;
use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * Represents a Soroban host function for creating contracts with constructor arguments
 *
 * This host function deploys a new smart contract instance from previously uploaded WASM code,
 * similar to CreateContractHostFunction, but with support for passing constructor arguments.
 * Constructor arguments are passed to the contract's initialization function during deployment.
 *
 * This is useful for contracts that require initialization parameters, such as:
 * - Token contracts needing initial supply or admin addresses
 * - Contracts with configurable parameters
 * - Contracts requiring setup data at deployment time
 *
 * Usage:
 * <code>
 * // Deploy contract with constructor arguments
 * $args = [
 *     XdrSCVal::forSymbol("admin"),
 *     XdrSCVal::forAddress($adminAddress),
 *     XdrSCVal::forU64(1000000)
 * ];
 *
 * $hostFunction = new CreateContractWithConstructorHostFunction(
 *     Address::fromAccountId("GABC..."), // Deployer address
 *     $wasmId,                            // WASM hash from upload
 *     $args,                              // Constructor arguments
 *     $salt                               // Optional salt
 * );
 * </code>
 *
 * @package Soneso\StellarSDK
 * @see HostFunction Base class for all host functions
 * @see CreateContractHostFunction For contracts without constructor arguments
 * @see UploadContractWasmHostFunction For uploading WASM code first
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 */
class CreateContractWithConstructorHostFunction extends HostFunction
{
    /**
     * @var Address $address The deployer address
     */
    public Address $address;

    /**
     * @var string $wasmId The WASM ID (hash of the uploaded contract code)
     */
    public string $wasmId;

    /**
     * @var string $salt The salt value for contract address generation
     */
    public string $salt;

    /**
     * @var array<XdrSCVal> $constructorArgs The constructor arguments
     */
    public array $constructorArgs;


    /**
     * Constructs a new CreateContractWithConstructorHostFunction
     *
     * @param Address $address The deployer address
     * @param string $wasmId The WASM ID (hash of uploaded contract code)
     * @param array<XdrSCVal> $constructorArgs The constructor arguments
     * @param string|null $salt Optional salt (32 random bytes generated if not provided)
     * @throws Exception If random bytes generation fails
     */
    public function __construct(Address $address, string $wasmId, array $constructorArgs, ?string $salt = null)
    {
        $this->address = $address;
        $this->wasmId = $wasmId;
        $this->constructorArgs = $constructorArgs;
        $this->salt = $salt !== null ? $salt : random_bytes(32);
        parent::__construct();
    }

    public function toXdr() : XdrHostFunction {
        return XdrHostFunction::forCreatingContractV2($this->address->toXdr(),
            $this->wasmId, $this->salt, $this->constructorArgs);
    }

    /**
     * @throws Exception
     */
    public static function fromXdr(XdrHostFunction $xdr) : CreateContractWithConstructorHostFunction {
        $type = $xdr->type;
        if ($type->value !== XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT_V2 || $xdr->createContractV2 === null
            || $xdr->createContractV2->contractIDPreimage->type->value !== XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS
            || $xdr->createContractV2->executable->type->value !== XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM) {
            throw new Exception("Invalid argument");
        }
        $wasmId = $xdr->createContractV2->executable->wasmIdHex;
        $xdrAddress = $xdr->createContractV2->contractIDPreimage->address;

        if ($wasmId === null || $xdrAddress === null) {
            throw new Exception("invalid argument");
        }
        return new CreateContractWithConstructorHostFunction(Address::fromXdr($xdrAddress), $wasmId,
            $xdr->createContractV2->constructorArgs, $xdr->createContract->contractIDPreimage->salt);
    }

    /**
     * @return Address
     */
    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * @param Address $address
     */
    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getWasmId(): string
    {
        return $this->wasmId;
    }

    /**
     * @param string $wasmId
     */
    public function setWasmId(string $wasmId): void
    {
        $this->wasmId = $wasmId;
    }

    /**
     * @return string
     */
    public function getSalt(): string
    {
        return $this->salt;
    }

    /**
     * @param string $salt
     */
    public function setSalt(string $salt): void
    {
        $this->salt = $salt;
    }

    /**
     * @return array
     */
    public function getConstructorArgs(): array
    {
        return $this->constructorArgs;
    }

    /**
     * @param array $constructorArgs
     */
    public function setConstructorArgs(array $constructorArgs): void
    {
        $this->constructorArgs = $constructorArgs;
    }

}