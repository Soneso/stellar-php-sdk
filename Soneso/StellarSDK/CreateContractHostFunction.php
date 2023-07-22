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

class CreateContractHostFunction extends HostFunction
{
    public Address $address;
    public string $wasmId;
    public string $salt;


    /**
     * @param Address $address
     * @param string $wasmId
     * @param string|null $salt
     * @throws Exception
     */
    public function __construct(Address $address, string $wasmId, ?string $salt = null)
    {
        $this->address = $address;
        $this->wasmId = $wasmId;
        $this->salt = $salt != null ? $salt : random_bytes(32);
        parent::__construct();
    }

    public function toXdr() : XdrHostFunction {
        return XdrHostFunction::forCreatingContract($this->address->toXdr(), $this->wasmId, $this->salt);
    }

    /**
     * @throws Exception
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

}