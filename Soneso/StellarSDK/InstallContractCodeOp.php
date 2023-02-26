<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Soroban\Footprint;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;
use Soneso\StellarSDK\Xdr\XdrInvokeHostFunctionOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

class InstallContractCodeOp extends InvokeHostFunctionOperation
{
    public string $contractCodeBytes;

    /**
     * @param string $contractCodeBytes
     * @param Footprint|null $footprint
     * @param array|null $auth
     * @param MuxedAccount|null $sourceAccount
     */
    public function __construct(string $contractCodeBytes,?Footprint $footprint = null, ?array $auth = array(), ?MuxedAccount $sourceAccount = null)
    {
        $this->contractCodeBytes = $contractCodeBytes;
        parent::__construct(new XdrHostFunctionType(XdrHostFunctionType::HOST_FUNCTION_TYPE_INSTALL_CONTRACT_CODE), $footprint, $auth, $sourceAccount);
    }

    public function toOperationBody(): XdrOperationBody
    {

        $hostFunction = XdrHostFunction::forInstallingContract($this->contractCodeBytes);
        $hostFunctionOp = new XdrInvokeHostFunctionOperation($hostFunction, $this->getXdrFootprint(), self::convertToXdrAuth($this->auth));
        $type = new XdrOperationType(XdrOperationType::INVOKE_HOST_FUNCTION);
        $result = new XdrOperationBody($type);
        $result->setInvokeHostFunctionOperation($hostFunctionOp);
        return $result;
    }
}