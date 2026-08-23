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

/**
 * Represents a Soroban host function for creating contracts from a CAP-85 external
 * executable reference
 *
 * From Protocol 28 on, a contract can be created without naming a wasm hash directly.
 * The executable instead names an owner contract and a tag; the owner holds a
 * persistent contract data entry under that tag whose value is the 32-byte hash of an
 * already uploaded wasm, and the created instance runs that code.
 *
 * The executable owner must be a contract address — only a contract can hold the
 * executable tag entry. The constructor, setExecutableOwner(), and toXdr() reject any
 * other address type.
 *
 * Usage:
 * <code>
 * $hostFunction = new CreateContractFromExternalRefHostFunction(
 *     Address::fromAccountId("GABC..."),   // Deployer address
 *     Address::fromContractId($ownerId),   // Contract holding the executable tag entry
 *     "token-v1",                          // Tag of the entry on the owner
 *     $salt                                // Optional salt
 * );
 * </code>
 *
 * @package Soneso\StellarSDK
 * @see HostFunction Base class for all host functions
 * @see CreateContractFromExternalRefWithConstructorHostFunction For contracts with constructor arguments
 * @see CreateContractHostFunction For creating contracts from a wasm hash
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.13.0
 */
class CreateContractFromExternalRefHostFunction extends HostFunction
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
     * @var string $salt The salt value for contract address generation
     */
    public string $salt;


    /**
     * Constructs a new CreateContractFromExternalRefHostFunction
     *
     * @param Address $address The deployer address
     * @param Address $executableOwner The contract holding the executable tag entry
     * @param string $tag The tag of the executable entry on the owner; matched byte for byte
     * @param string|null $salt Optional salt (32 random bytes generated if not provided)
     * @throws InvalidArgumentException If the executable owner is not a contract address
     * @throws Exception If random bytes generation fails
     */
    public function __construct(Address $address, Address $executableOwner, string $tag, ?string $salt = null)
    {
        self::assertOwnerIsContract($executableOwner);
        $this->address = $address;
        $this->executableOwner = $executableOwner;
        $this->tag = $tag;
        $this->salt = $salt !== null ? $salt : random_bytes(32);
        parent::__construct();
    }

    /**
     * @throws InvalidArgumentException If the executable owner is not a contract address
     */
    public function toXdr() : XdrHostFunction {
        self::assertOwnerIsContract($this->executableOwner);
        return XdrHostFunction::forCreatingContractWithExternalRef($this->address->toXdr(),
            $this->executableOwner->toXdr(), $this->tag, $this->salt);
    }

    /**
     * @throws InvalidArgumentException If the executable owner in the XDR is not a contract address
     * @throws Exception If the XDR does not carry an external-ref CREATE_CONTRACT host function
     */
    public static function fromXdr(XdrHostFunction $xdr) : CreateContractFromExternalRefHostFunction {
        $type = $xdr->type;
        if ($type->value !== XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT || $xdr->createContract === null
            || $xdr->createContract->contractIDPreimage->type->value !== XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS
            || $xdr->createContract->executable->type->value !== XdrContractExecutableType::CONTRACT_EXECUTABLE_EXTERNAL_REF) {
            throw new Exception("Invalid argument");
        }
        $ref = $xdr->createContract->executable->externalRef;
        $xdrAddress = $xdr->createContract->contractIDPreimage->address;

        if ($ref === null || $xdrAddress === null) {
            throw new Exception("invalid argument");
        }
        return new CreateContractFromExternalRefHostFunction(Address::fromXdr($xdrAddress),
            Address::fromXdr($ref->executableOwner), $ref->tag, $xdr->createContract->contractIDPreimage->salt);
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
