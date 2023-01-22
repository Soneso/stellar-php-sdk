<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Exception;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;
use Soneso\StellarSDK\Xdr\XdrInvokeHostFunctionOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

class DeploySACWithSourceAccountOp extends InvokeHostFunctionOperation
{
    public string $salt;

    /**
     * @param string $salt
     * @param Footprint|null $footprint
     * @param MuxedAccount|null $sourceAccount
     */
    public function __construct(string $salt, ?Footprint $footprint = null, ?MuxedAccount $sourceAccount = null)
    {
        $this->salt = $salt;
        $this->footprint = $footprint;
        parent::__construct(new XdrHostFunctionType(XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT), $footprint, $sourceAccount);
    }


    /**
     * @throws Exception
     */
    public function toOperationBody(): XdrOperationBody
    {
        $hostFunction = XdrHostFunction::forDeploySACWithSourceAccount($this->salt);
        $hostFunctionOp = new XdrInvokeHostFunctionOperation($hostFunction, $this->getXdrFootprint());
        $type = new XdrOperationType(XdrOperationType::INVOKE_HOST_FUNCTION);
        $result = new XdrOperationBody($type);
        $result->setInvokeHostFunctionOperation($hostFunctionOp);
        return $result;
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