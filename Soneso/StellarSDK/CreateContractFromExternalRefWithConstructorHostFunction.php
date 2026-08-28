<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Exception;
use InvalidArgumentException;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Xdr\XdrContractIDPreimageType;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;
use Soneso\StellarSDK\Xdr\XdrContractExecutableType;
use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * Represents a Soroban host function for creating contracts from a CAP-85 external
 * executable reference, passing constructor arguments
 *
 * Combines CreateContractFromExternalRefHostFunction with constructor initialization:
 * the executable names an owner contract and a tag whose persistent entry holds the
 * wasm hash, and the constructor arguments are passed to the created instance during
 * deployment.
 *
 * The executable owner must be a contract address — only a contract can hold the
 * executable tag entry. The constructor, setExecutableOwner(), and toXdr() reject any
 * other address type.
 *
 * Usage:
 * <code>
 * $args = [XdrSCVal::forSymbol("admin"), XdrSCVal::forAddress($adminAddress)];
 *
 * $hostFunction = new CreateContractFromExternalRefWithConstructorHostFunction(
 *     Address::fromAccountId("GABC..."),   // Deployer address
 *     Address::fromContractId($ownerId),   // Contract holding the executable tag entry
 *     "token-v1",                          // Tag of the entry on the owner
 *     $args,                               // Constructor arguments
 *     $salt                                // Optional salt
 * );
 * </code>
 *
 * @package Soneso\StellarSDK
 * @see HostFunction Base class for all host functions
 * @see CreateContractFromExternalRefHostFunction For contracts without constructor arguments
 * @see CreateContractWithConstructorHostFunction For the wasm hash form
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.13.0
 */
class CreateContractFromExternalRefWithConstructorHostFunction extends HostFunction
{
    /**
     * @var Address $address The deployer address
     */
    public Address $address;

    /**
     * @var Address $executableOwner The contract holding the executable tag entry the instance runs
     */
    public Address $executableOwner;

    /**
     * @var string $tag The tag the owner holds the executable entry under
     */
    public string $tag;

    /**
     * @var array<XdrSCVal> $constructorArgs The constructor arguments
     */
    public array $constructorArgs;

    /**
     * @var string $salt The salt value for contract address generation
     */
    public string $salt;


    /**
     * Constructs a new CreateContractFromExternalRefWithConstructorHostFunction
     *
     * @param Address $address The deployer address
     * @param Address $executableOwner The contract holding the executable tag entry
     * @param string $tag The tag of the executable entry on the owner; matched byte for byte
     * @param array<XdrSCVal> $constructorArgs The constructor arguments
     * @param string|null $salt Optional salt (32 random bytes generated if not provided)
     * @throws InvalidArgumentException If the executable owner is not a contract address
     * @throws Exception If random bytes generation fails
     */
    public function __construct(Address $address, Address $executableOwner, string $tag, array $constructorArgs, ?string $salt = null)
    {
        self::assertOwnerIsContract($executableOwner);
        $this->address = $address;
        $this->executableOwner = $executableOwner;
        $this->tag = $tag;
        $this->constructorArgs = $constructorArgs;
        $this->salt = $salt !== null ? $salt : random_bytes(32);
        parent::__construct();
    }

    /**
     * @throws InvalidArgumentException If the executable owner is not a contract address
     */
    public function toXdr() : XdrHostFunction {
        self::assertOwnerIsContract($this->executableOwner);
        return XdrHostFunction::forCreatingContractV2WithExternalRef($this->address->toXdr(),
            $this->executableOwner->toXdr(), $this->tag, $this->salt, $this->constructorArgs);
    }

    /**
     * @throws InvalidArgumentException If the executable owner in the XDR is not a contract address
     * @throws Exception If the XDR does not carry an external-ref CREATE_CONTRACT_V2 host function
     */
    public static function fromXdr(XdrHostFunction $xdr) : CreateContractFromExternalRefWithConstructorHostFunction {
        $type = $xdr->type;
        if ($type->value !== XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT_V2 || $xdr->createContractV2 === null
            || $xdr->createContractV2->contractIDPreimage->type->value !== XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS
            || $xdr->createContractV2->executable->type->value !== XdrContractExecutableType::CONTRACT_EXECUTABLE_EXTERNAL_REF) {
            throw new Exception("Invalid argument");
        }
        $ref = $xdr->createContractV2->executable->externalRef;
        $xdrAddress = $xdr->createContractV2->contractIDPreimage->address;

        if ($ref === null || $xdrAddress === null) {
            throw new Exception("invalid argument");
        }
        return new CreateContractFromExternalRefWithConstructorHostFunction(Address::fromXdr($xdrAddress),
            Address::fromXdr($ref->executableOwner), $ref->tag, $xdr->createContractV2->constructorArgs,
            $xdr->createContractV2->contractIDPreimage->salt);
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
     * @return Address
     */
    public function getExecutableOwner(): Address
    {
        return $this->executableOwner;
    }

    /**
     * @param Address $executableOwner
     * @throws InvalidArgumentException If the executable owner is not a contract address
     */
    public function setExecutableOwner(Address $executableOwner): void
    {
        self::assertOwnerIsContract($executableOwner);
        $this->executableOwner = $executableOwner;
    }

    /**
     * @return string
     */
    public function getTag(): string
    {
        return $this->tag;
    }

    /**
     * @param string $tag
     */
    public function setTag(string $tag): void
    {
        $this->tag = $tag;
    }

    /**
     * @return array<XdrSCVal>
     */
    public function getConstructorArgs(): array
    {
        return $this->constructorArgs;
    }

    /**
     * @param array<XdrSCVal> $constructorArgs
     */
    public function setConstructorArgs(array $constructorArgs): void
    {
        $this->constructorArgs = $constructorArgs;
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
     * @throws InvalidArgumentException If the executable owner is not a contract address
     */
    private static function assertOwnerIsContract(Address $executableOwner): void
    {
        if ($executableOwner->type !== Address::TYPE_CONTRACT) {
            throw new InvalidArgumentException(
                "External reference owner is not a contract address; only a contract can hold the executable tag entry");
        }
    }

}
