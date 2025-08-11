<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Exception;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrInvokeContractArgs;
use Soneso\StellarSDK\Xdr\XdrSCVal;

class InvokeContractHostFunction extends HostFunction
{
    /**
     * @var string $contractId can be any type that can be converted to an address.
     * E.g. hex contract id, StrKey contract id, hex claimable balance id, StrKey claimable balance id, etc.
     * Use Address::fromAny($contractId) to get the exact type.
     */
    public string $contractId;
    public string $functionName;
    /**
     * @var array<XdrSCVal>|null
     */
    public ?array $arguments;

    /**
     * @param string $contractId
     * @param string $functionName
     * @param array<XdrSCVal>|null $arguments
     */
    public function __construct(string $contractId, string $functionName, ?array $arguments = null)
    {
        $this->contractId = $contractId;
        $this->functionName = $functionName;
        $this->arguments = $arguments;
        parent::__construct();
    }

    public function toXdr() : XdrHostFunction {
        $args = array();
        if ($this->arguments != null) {
            $args = array_merge($args, $this->arguments);
        }
        // allow all kinds of addresses
        $address = Address::fromAnyId($this->contractId);
        $invokeArgs = new XdrInvokeContractArgs($address->toXdr(),
            $this->functionName,$args);
        return XdrHostFunction::forInvokingContractWithArgs($invokeArgs);
    }

    /**
     * @throws Exception
     */
    public static function fromXdr(XdrHostFunction $xdr) : InvokeContractHostFunction {
        $invokeContract = $xdr->invokeContract;
        if ($invokeContract == null) {
            throw new Exception("Invalid argument");
        }
        // allow all types of addresses
        $contractId = $invokeContract->contractAddress->toStrKey();
        $functionName = $invokeContract->functionName;
        $args= $invokeContract->getArgs();

        return new InvokeContractHostFunction($contractId, $functionName, $args);
    }

    /**
     * @return string
     */
    public function getContractId(): string
    {
        return $this->contractId;
    }

    /**
     * @param string $contractId
     */
    public function setContractId(string $contractId): void
    {
        $this->contractId = $contractId;
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
     * @return array<XdrSCVal>|null
     */
    public function getArguments(): ?array
    {
        return $this->arguments;
    }

    /**
     * @param array<XdrSCVal>|null $arguments
     */
    public function setArguments(?array $arguments): void
    {
        $this->arguments = $arguments;
    }

}