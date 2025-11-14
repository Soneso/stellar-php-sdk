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
 * Represents a Soroban host function for deploying a Stellar Asset Contract from a source account
 *
 * This host function deploys a Stellar Asset Contract (SAC) for the native asset (XLM)
 * controlled by a specific source account. Unlike DeploySACWithAssetHostFunction which deploys
 * for existing Stellar assets, this variant creates a SAC tied to an account's identity.
 *
 * The deployment uses:
 * - A source account address (the account that controls the asset)
 * - A salt value for deterministic address generation
 *
 * This is useful for creating wrapped native asset contracts where the issuing
 * account wants control over the SAC deployment.
 *
 * Usage:
 * <code>
 * // Deploy SAC with source account
 * $address = Address::fromAccountId("GABC...");
 * $hostFunction = new DeploySACWithSourceAccountHostFunction($address);
 *
 * // Deploy with specific salt for reproducibility
 * $hostFunction = new DeploySACWithSourceAccountHostFunction($address, $salt);
 *
 * // Use in an InvokeHostFunctionOperation
 * $operation = (new InvokeHostFunctionOperationBuilder($hostFunction))->build();
 * </code>
 *
 * @package Soneso\StellarSDK
 * @see HostFunction Base class for all host functions
 * @see DeploySACWithAssetHostFunction For deploying SACs for existing assets
 * @see Address For address handling
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 */
class DeploySACWithSourceAccountHostFunction extends HostFunction
{
    /**
     * @var Address $address The source account address
     */
    public Address $address;

    /**
     * @var string $salt The salt value for contract address generation
     */
    public string $salt;

    /**
     * Constructs a new DeploySACWithSourceAccountHostFunction
     *
     * @param Address $address The source account address
     * @param string|null $salt Optional salt (32 random bytes generated if not provided)
     * @throws Exception If random bytes generation fails
     */
    public function __construct(Address $address, ?string $salt = null)
    {
        $this->address = $address;
        $this->salt = $salt != null ? $salt : random_bytes(32);
        parent::__construct();
    }

    /**
     * Converts the deploy SAC host function to XDR format
     *
     * @return XdrHostFunction The XDR host function
     */
    public function toXdr() : XdrHostFunction {
        return XdrHostFunction::forDeploySACWithSourceAccount($this->address->toXdr(), $this->salt);
    }

    /**
     * Creates a DeploySACWithSourceAccountHostFunction from XDR format
     *
     * @param XdrHostFunction $xdr The XDR host function
     * @return DeploySACWithSourceAccountHostFunction The decoded host function
     * @throws Exception If the XDR format is invalid or missing required data
     */
    public static function fromXdr(XdrHostFunction $xdr) : DeploySACWithSourceAccountHostFunction {
        $type = $xdr->type;
        if ($type->value !== XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT &&
            $type->value !== XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT_V2) {
            throw new Exception("Invalid argument");
        }

        $preimage = $xdr->createContract !== null ? $xdr->createContract->contractIDPreimage :
            ($xdr->createContractV2 !== null ? $xdr->createContractV2->contractIDPreimage : null);
        $executableTypeValue = $xdr->createContract != null ? $xdr->createContract->executable->type->value :
            ($xdr->createContractV2 !== null ? $xdr->createContractV2->executable->type->value : null);
        $xdrAddress = $xdr->createContract !== null ? $xdr->createContract->contractIDPreimage->address :
            ($xdr->createContractV2 !== null ? $xdr->createContractV2->contractIDPreimage->address : null);

        if($preimage === null || $executableTypeValue === null || $xdrAddress === null) {
            throw new Exception("Invalid argument");
        }

        if ($preimage->type->value !== XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS ||
            $executableTypeValue !== XdrContractExecutableType::CONTRACT_EXECUTABLE_STELLAR_ASSET) {
            throw new Exception("Invalid argument");
        }

        return new DeploySACWithSourceAccountHostFunction(Address::fromXdr($xdrAddress), $preimage->salt);
    }

    /**
     * Gets the source account address
     *
     * @return Address The source account address
     */
    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * Sets the source account address
     *
     * @param Address $address The source account address
     * @return void
     */
    public function setAddress(Address $address): void
    {
        $this->address = $address;
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