<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrExtensionPoint;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrRestoreFootprintOp;


class RestoreFootprintOperation extends AbstractOperation
{


    public function __construct()
    {
    }

    public static function fromXdrOperation(XdrRestoreFootprintOp $xdrOp): RestoreFootprintOperation {
        return new RestoreFootprintOperation();
    }

    public function toOperationBody(): XdrOperationBody
    {
        $op = new XdrRestoreFootprintOp(new XdrExtensionPoint(0));
        $type = new XdrOperationType(XdrOperationType::RESTORE_FOOTPRINT);
        $result = new XdrOperationBody($type);
        $result->setRestoreFootprintOp($op);
        return $result;
    }
}