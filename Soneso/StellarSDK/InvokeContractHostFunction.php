<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Exception;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrHostFunctionArgs;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;
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
     * @param array|null $auth
     */
    public function __construct(string $contractId, string $functionName, ?array $arguments = null, ?array $auth = array())
    {
        $this->contractId = $contractId;
        $this->functionName = $functionName;
        $this->arguments = $arguments;
        parent::__construct($auth);
    }

    public function toXdr() : XdrHostFunction {
        $invokeContract = array();

        // contractID
        $contractIDVal = XdrSCVal::forContractId($this->contractId);
        array_push($invokeContract, $contractIDVal);

        // function name
        $functionNameVal = XdrSCVal::forSymbol($this->functionName);
        array_push($invokeContract, $functionNameVal);

        // arguments if any
        if ($this->arguments != null) {
            $invokeContract = array_merge($invokeContract, $this->arguments);
        }

        $args = XdrHostFunctionArgs::forInvokingContractWithArgs($invokeContract);
        return new XdrHostFunction($args, self::convertToXdrAuth($this->auth));
    }

    /**
     * @throws Exception
     */
    public static function fromXdr(XdrHostFunction $xdr) : InvokeContractHostFunction {
        $args = $xdr->args;
        $type = $args->type;
        if ($type->value != XdrHostFunctionType::HOST_FUNCTION_TYPE_INVOKE_CONTRACT
            || $args->invokeContract == null || count($args->invokeContract) < 2) {
            throw new Exception("Invalid argument");
        }
        $contractId = null;
        $functionName = null;
        $fArgs = null;
        $contractIdVal = $args->invokeContract[0];
        $functionVal = $args->invokeContract[1];
        if ($contractIdVal instanceof XdrSCVal && $contractIdVal->bytes != null ) {
            $contractId = bin2hex($contractIdVal->bytes->getValue());
        }
        if ($functionVal instanceof XdrSCVal && $functionVal->sym != null) {
            $functionName = $functionVal->sym;
        }
        if(count($args->invokeContract) > 2) {
            $fArgs = array_slice($args->invokeContract, 2);
        }
        if($contractId == null | $functionName == null) {
            throw new Exception("invalid argument");
        }
        return new InvokeContractHostFunction($contractId, $functionName, $fArgs, self::convertFromXdrAuth($xdr->auth));
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