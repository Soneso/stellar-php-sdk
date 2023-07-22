<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Exception;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCVal;

class InvokeContractHostFunction extends HostFunction
{
    public string $contractId;
    public string $functionName;
    public ?array $arguments; // [XdrSCVal]

    /**
     * @param string $contractId
     * @param string $functionName
     * @param array|null $arguments
     */
    public function __construct(string $contractId, string $functionName, ?array $arguments = null)
    {
        $this->contractId = $contractId;
        $this->functionName = $functionName;
        $this->arguments = $arguments;
        parent::__construct();
    }

    public function toXdr() : XdrHostFunction {
        $invokeContract = array();

        // contractID
        $contractIDVal = Address::fromContractId($this->contractId)->toXdrSCVal();
        array_push($invokeContract, $contractIDVal);

        // function name
        $functionNameVal = XdrSCVal::forSymbol($this->functionName);
        array_push($invokeContract, $functionNameVal);

        // arguments if any
        if ($this->arguments != null) {
            $invokeContract = array_merge($invokeContract, $this->arguments);
        }

        return XdrHostFunction::forInvokingContractWithArgs($invokeContract);
    }

    /**
     * @throws Exception
     */
    public static function fromXdr(XdrHostFunction $xdr) : InvokeContractHostFunction {
        $invokeContract = $xdr->invokeContract;
        if ($invokeContract == null || count($invokeContract) < 2) {
            throw new Exception("Invalid argument");
        }

        $contractId = null;
        $functionName = null;
        $fArgs = null;
        $contractIdVal = $invokeContract[0];
        $functionVal = $invokeContract[1];

        if ($contractIdVal instanceof XdrSCVal && $contractIdVal->address != null) {
            $contractId = Address::fromXdr($contractIdVal->address)->contractId;
        }

        if ($functionVal instanceof XdrSCVal && $functionVal->sym != null) {
            $functionName = $functionVal->sym;
        }
        if(count($invokeContract) > 2) {
            $fArgs = array_slice($invokeContract, 2);
        }
        if ($contractId == null || $functionName == null) {
            throw new Exception("invalid argument");
        }

        return new InvokeContractHostFunction($contractId, $functionName, $fArgs);
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
     * @return array|null
     */
    public function getArguments(): ?array
    {
        return $this->arguments;
    }

    /**
     * @param array|null $arguments
     */
    public function setArguments(?array $arguments): void
    {
        $this->arguments = $arguments;
    }

}