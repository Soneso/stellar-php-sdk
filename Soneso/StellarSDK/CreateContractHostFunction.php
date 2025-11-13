<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Exception;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Xdr\XdrContractIDPreimageType;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;
use Soneso\StellarSDK\Xdr\XdrContractExecutableType;

/**
 * Represents a Soroban host function for creating smart contracts
 *
 * This host function deploys a new smart contract instance from previously uploaded
 * WASM code. The contract is identified by the WASM ID (hash of the uploaded code),
 * a deployer address, and a salt value for uniqueness.
 *
 * The contract address is deterministically generated from:
 * - The deployer address (typically the source account or contract)
 * - The WASM ID (hash of the contract code)
 * - A salt value (random or specified for reproducibility)
 *
 * Usage:
 * <code>
 * // Deploy a contract from uploaded WASM
 * $hostFunction = new CreateContractHostFunction(
 *     Address::fromAccountId("GABC..."), // Deployer address
 *     $wasmId,                            // WASM hash from upload
 *     $salt                               // Optional salt (generated if not provided)
 * );
 *
 * // Use in an InvokeHostFunctionOperation
 * $operation = (new InvokeHostFunctionOperationBuilder($hostFunction))->build();
 * </code>
 *
 * @package Soneso\StellarSDK
 * @see HostFunction Base class for all host functions
 * @see UploadContractWasmHostFunction For uploading WASM code first
 * @see Address For address handling
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 */
class CreateContractHostFunction extends HostFunction
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
     * Constructs a new CreateContractHostFunction
     *
     * @param Address $address The deployer address
     * @param string $wasmId The WASM ID (hash of uploaded contract code)
     * @param string|null $salt Optional salt (32 random bytes generated if not provided)
     * @throws Exception If random bytes generation fails
     */
    public function __construct(Address $address, string $wasmId, ?string $salt = null)
    {
        $this->address = $address;
        $this->wasmId = $wasmId;
        $this->salt = $salt != null ? $salt : random_bytes(32);
        parent::__construct();
    }

    /**
     * Converts the create contract host function to XDR format
     *
     * @return XdrHostFunction The XDR host function
     */
    public function toXdr() : XdrHostFunction {
        return XdrHostFunction::forCreatingContract($this->address->toXdr(), $this->wasmId, $this->salt);
    }

    /**
     * Creates a CreateContractHostFunction from XDR format
     *
     * @param XdrHostFunction $xdr The XDR host function
     * @return CreateContractHostFunction The decoded host function
     * @throws Exception If the XDR format is invalid or missing required data
     */
    public static function fromXdr(XdrHostFunction $xdr) : CreateContractHostFunction {
        $type = $xdr->type;
        if ($type->value != XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT || $xdr->createContract == null
            || $xdr->createContract->contractIDPreimage->type->value != XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS
            || $xdr->createContract->executable->type->value != XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM) {
            throw new Exception("Invalid argument");
        }
        $wasmId = $xdr->createContract->executable->wasmIdHex;
        $xdrAddress = $xdr->createContract->contractIDPreimage->address;

        if ($wasmId == null || $xdrAddress == null) {
            throw new Exception("invalid argument");
        }
        return new CreateContractHostFunction(Address::fromXdr($xdrAddress), $wasmId, $xdr->createContract->contractIDPreimage->salt);
    }

    /**
     * Gets the deployer address
     *
     * @return Address The deployer address
     */
    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * Sets the deployer address
     *
     * @param Address $address The deployer address
     * @return void
     */
    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    /**
     * Gets the WASM ID
     *
     * @return string The WASM ID (hash of uploaded contract code)
     */
    public function getWasmId(): string
    {
        return $this->wasmId;
    }

    /**
     * Sets the WASM ID
     *
     * @param string $wasmId The WASM ID (hash of uploaded contract code)
     * @return void
     */
    public function setWasmId(string $wasmId): void
    {
        $this->wasmId = $wasmId;
    }

    /**
     * Gets the salt value
     *
     * @return string The salt value for contract address generation
     */
    public function getSalt(): string
    {
        return $this->salt;
    }

    /**
     * Sets the salt value
     *
     * @param string $salt The salt value for contract address generation
     * @return void
     */
    public function setSalt(string $salt): void
    {
        $this->salt = $salt;
    }

}