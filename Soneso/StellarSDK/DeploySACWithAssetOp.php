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

class DeploySACWithAssetOp extends InvokeHostFunctionOperation
{
    public Asset $asset;


    /**
     * @param Asset $asset
     * @param Footprint|null $footprint
     * @param array|null $auth
     * @param MuxedAccount|null $sourceAccount
     */
    public function __construct(Asset $asset, ?Footprint $footprint = null, ?array $auth = array(), ?MuxedAccount $sourceAccount = null)
    {
        $this->asset = $asset;
        parent::__construct(new XdrHostFunctionType(XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT), $footprint, $auth, $sourceAccount);
    }

    /**
     * @throws Exception
     */
    public function toOperationBody(): XdrOperationBody
    {
        $hostFunction = XdrHostFunction::forDeploySACWithAsset($this->asset->toXdr());
        $hostFunctionOp = new XdrInvokeHostFunctionOperation($hostFunction, $this->getXdrFootprint(), $this->auth);
        $type = new XdrOperationType(XdrOperationType::INVOKE_HOST_FUNCTION);
        $result = new XdrOperationBody($type);
        $result->setInvokeHostFunctionOperation($hostFunctionOp);
        return $result;
    }
}