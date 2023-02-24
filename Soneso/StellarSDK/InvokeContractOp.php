<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;
use Soneso\StellarSDK\Xdr\XdrInvokeHostFunctionOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrSCObject;
use Soneso\StellarSDK\Xdr\XdrSCVal;

class InvokeContractOp extends InvokeHostFunctionOperation
{
    public string $contractId;
    public string $functionName;
    public ?array $arguments = null;

    /**
     * @param string $contractId
     * @param string $functionName
     * @param array|null $arguments
     * @param Footprint|null $footprint
     * @param array|null $auth
     * @param MuxedAccount|null $sourceAccount
     */
    public function __construct(string $contractId, string $functionName, ?array $arguments = null, ?Footprint $footprint = null, ?array $auth = array(), ?MuxedAccount $sourceAccount = null)
    {
        $this->contractId = $contractId;
        $this->functionName = $functionName;
        $this->arguments = $arguments;
        parent::__construct(new XdrHostFunctionType(XdrHostFunctionType::HOST_FUNCTION_TYPE_INVOKE_CONTRACT), $footprint, $auth, $sourceAccount);
    }

    public function toOperationBody(): XdrOperationBody
    {
        $invokeArgs = array();

        // contractID

        $contractIDObject = XdrSCObject::fromContractId($this->contractId);
        $contractIDVal = XdrSCVal::fromObject($contractIDObject);
        array_push($invokeArgs, $contractIDVal);

        // function name
        $functionNameVal = XdrSCVal::fromSymbol($this->functionName);
        array_push($invokeArgs, $functionNameVal);

        // arguments if any
        if ($this->arguments != null) {
            $invokeArgs = array_merge($invokeArgs, $this->arguments);
        }

        $hostFunction = XdrHostFunction::forInvokingContractWithArgs($invokeArgs);
        $hostFunctionOp = new XdrInvokeHostFunctionOperation($hostFunction, $this->getXdrFootprint(), $this->auth);
        $type = new XdrOperationType(XdrOperationType::INVOKE_HOST_FUNCTION);
        $result = new XdrOperationBody($type);
        $result->setInvokeHostFunctionOperation($hostFunctionOp);
        return $result;
    }

}