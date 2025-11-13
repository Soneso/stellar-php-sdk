<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrExtensionPoint;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrRestoreFootprintOp;

/**
 * Represents a Restore Footprint operation.
 *
 * Restores archived contract-related ledger entries specified in the transaction's footprint.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a>
 * @since 1.0.0
 */
class RestoreFootprintOperation extends AbstractOperation
{
    /**
     * Creates a new RestoreFootprintOperation.
     */
    public function __construct()
    {
    }

    /**
     * Creates a RestoreFootprintOperation from its XDR representation.
     *
     * @param XdrRestoreFootprintOp $xdrOp The XDR restore footprint operation to convert
     * @return RestoreFootprintOperation The resulting RestoreFootprintOperation instance
     */
    public static function fromXdrOperation(XdrRestoreFootprintOp $xdrOp): RestoreFootprintOperation {
        return new RestoreFootprintOperation();
    }

    /**
     * Converts this operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body
     */
    public function toOperationBody(): XdrOperationBody
    {
        $op = new XdrRestoreFootprintOp(new XdrExtensionPoint(0));
        $type = new XdrOperationType(XdrOperationType::RESTORE_FOOTPRINT);
        $result = new XdrOperationBody($type);
        $result->setRestoreFootprintOp($op);
        return $result;
    }
}