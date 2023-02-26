<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Exception;
use Soneso\StellarSDK\Soroban\Footprint;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;
use Soneso\StellarSDK\Xdr\XdrInvokeHostFunctionOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

class CreateContractOp extends InvokeHostFunctionOperation
{
    public string $wasmId;
    public string $salt;

    /**
     * @param string $wasmId
     * @param string $salt
     * @param Footprint|null $footprint
     * @param array|null $auth
     * @param MuxedAccount|null $sourceAccount
     */
    public function __construct(string $wasmId, string $salt, ?Footprint $footprint = null, ?array $auth = array(), ?MuxedAccount $sourceAccount = null)
    {
        $this->wasmId = $wasmId;
        $this->salt = $salt;
        $this->footprint = $footprint;
        parent::__construct(new XdrHostFunctionType(XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT), $footprint, $auth, $sourceAccount);
    }

    /**
     * @throws Exception
     */
    public function toOperationBody(): XdrOperationBody
    {
        $hostFunction = XdrHostFunction::forCreatingContract($this->wasmId, $this->salt);
        $hostFunctionOp = new XdrInvokeHostFunctionOperation($hostFunction, $this->getXdrFootprint(), self::convertToXdrAuth($this->auth));
        $type = new XdrOperationType(XdrOperationType::INVOKE_HOST_FUNCTION);
        $result = new XdrOperationBody($type);
        $result->setInvokeHostFunctionOperation($hostFunctionOp);
        return $result;
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