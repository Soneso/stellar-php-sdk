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

class DeploySACWithSourceAccountHostFunction extends HostFunction
{
    public Address $address;
    public string $salt;

    /**
     * @param Address $address
     * @param string|null $salt
     * @throws Exception
     */
    public function __construct(Address $address, ?string $salt = null)
    {
        $this->address = $address;
        $this->salt = $salt != null ? $salt : random_bytes(32);
        parent::__construct();
    }

    public function toXdr() : XdrHostFunction {
        return XdrHostFunction::forDeploySACWithSourceAccount($this->address->toXdr(), $this->salt);
    }

    /**
     * @throws Exception
     */
    public static function fromXdr(XdrHostFunction $xdr) : DeploySACWithSourceAccountHostFunction {
        $type = $xdr->type;
        if ($type->value != XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT || $xdr->createContract == null
            || $xdr->createContract->contractIDPreimage->type->value != XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS
            || $xdr->createContract->executable->type->value != XdrContractExecutableType::CONTRACT_EXECUTABLE_TOKEN) {
            throw new Exception("Invalid argument");
        }

        $xdrAddress = $xdr->createContract->contractIDPreimage->address;

        if ($xdrAddress == null) {
            throw new Exception("invalid argument");
        }

        return new DeploySACWithSourceAccountHostFunction(Address::fromXdr($xdrAddress), $xdr->createContract->contractIDPreimage->salt);
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