<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedContractFunction;


class SorobanAuthorizedContractFunction
{
    public Address $contractAddress;
    public string $functionName;
    public array $args; // [XdrSCVal]

    /**
     * @param Address $contractAddress
     * @param string $functionName
     * @param array $args
     */
    public function __construct(Address $contractAddress, string $functionName, array $args = array())
    {
        $this->contractAddress = $contractAddress;
        $this->functionName = $functionName;
        $this->args = $args;
    }

    /**
     * @param XdrSorobanAuthorizedContractFunction $xdr
     * @return SorobanAuthorizedContractFunction
     */
    public static function fromXdr(XdrSorobanAuthorizedContractFunction $xdr) : SorobanAuthorizedContractFunction {
        return new SorobanAuthorizedContractFunction(Address::fromXdr($xdr->contractAddress), $xdr->functionName, $xdr->args);
    }

    /**
     * @return XdrSorobanAuthorizedContractFunction
     */
    public function toXdr(): XdrSorobanAuthorizedContractFunction {
        return new XdrSorobanAuthorizedContractFunction($this->contractAddress->toXdr(),$this->functionName, $this->args);
    }

    /**
     * @return Address
     */
    public function getContractAddress(): Address
    {
        return $this->contractAddress;
    }

    /**
     * @param Address $contractAddress
     */
    public function setContractAddress(Address $contractAddress): void
    {
        $this->contractAddress = $contractAddress;
    }

    /**
     * @return string
     */
    public function getFunctionName(): string
    {
        return $this->functionName;
    }

    /**
     * @param string $functionName
     */
    public function setFunctionName(string $functionName): void
    {
        $this->functionName = $functionName;
    }

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @param array $args
     */
    public function setArgs(array $args): void
    {
        $this->args = $args;
    }
}