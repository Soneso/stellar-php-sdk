<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrExtendFootprintTTLOp;
use Soneso\StellarSDK\Xdr\XdrExtensionPoint;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

/**
 * Represents <a href="https://developers.stellar.org/docs/start/list-of-operations/#extend-footprint-ttl" target="_blank">ExtendFootprintTTL</a> operation.
 *
 * Extends the time-to-live (TTL) of contract-related ledger entries in the transaction's footprint.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org/docs/start/list-of-operations/" target="_blank">List of Operations</a>
 * @see <a href="https://developers.stellar.org/docs/learn/smart-contracts" target="_blank">Smart Contracts</a>
 * @since 1.0.0
 */
class ExtendFootprintTTLOperation extends AbstractOperation
{
    /**
     * @var int The number of ledgers to extend the entry lifetimes by
     */
    private int $extendTo;

    /**
     * Creates a new ExtendFootprintTTLOperation.
     *
     * @param int $extendTo The number of ledgers to extend the entry lifetimes by
     */
    public function __construct(int $extendTo)
    {
        $this->extendTo = $extendTo;
    }

    /**
     * Creates an ExtendFootprintTTLOperation from its XDR representation.
     *
     * @param XdrExtendFootprintTTLOp $xdrOp The XDR extend footprint TTL operation to convert
     * @return ExtendFootprintTTLOperation The resulting ExtendFootprintTTLOperation instance
     */
    public static function fromXdrOperation(XdrExtendFootprintTTLOp $xdrOp): ExtendFootprintTTLOperation {
        return new ExtendFootprintTTLOperation($xdrOp->extendTo);
    }

    /**
     * Converts this operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body
     */
    public function toOperationBody(): XdrOperationBody
    {
        $op = new XdrExtendFootprintTTLOp(new XdrExtensionPoint(0), $this->extendTo);
        $type = new XdrOperationType(XdrOperationType::EXTEND_FOOTPRINT_TTL);
        $result = new XdrOperationBody($type);
        $result->setExtendFootprintTTLOp($op);
        return $result;
    }

    /**
     * Gets the number of ledgers to extend by.
     *
     * @return int The number of ledgers
     */
    public function getExtendTo(): int
    {
        return $this->extendTo;
    }

    /**
     * Sets the number of ledgers to extend by.
     *
     * @param int $extendTo The number of ledgers
     */
    public function setExtendTo(int $extendTo): void
    {
        $this->extendTo = $extendTo;
    }

}