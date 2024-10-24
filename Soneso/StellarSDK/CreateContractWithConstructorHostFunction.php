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

class CreateContractWithConstructorHostFunction extends HostFunction
{
    public Address $address;
    public string $wasmId;
    public string $salt;
    /**
     * @var array<XdrSCVal>
     */
    public array $constructorArgs;


    /**
     * @param Address $address
     * @param string $wasmId
     * @param string|null $salt
     * @throws Exception
     */
    public function __construct(Address $address, string $wasmId, array $constructorArgs, ?string $salt = null)
    {
        $this->address = $address;
        $this->wasmId = $wasmId;
        $this->constructorArgs = $constructorArgs;
        $this->salt = $salt != null ? $salt : random_bytes(32);
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