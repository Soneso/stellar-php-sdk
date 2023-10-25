<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrExtendFootprintTTLOp;
use Soneso\StellarSDK\Xdr\XdrExtensionPoint;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;


class ExtendFootprintTTLOperation extends AbstractOperation
{
    private int $extendTo;

    /**
     * @param int $extendTo
     */
    public function __construct(int $extendTo)
    {
        $this->extendTo = $extendTo;
    }

    public static function fromXdrOperation(XdrExtendFootprintTTLOp $xdrOp): ExtendFootprintTTLOperation {
        return new ExtendFootprintTTLOperation($xdrOp->extendTo);
    }

    public function toOperationBody(): XdrOperationBody
    {
        $op = new XdrExtendFootprintTTLOp(new XdrExtensionPoint(0), $this->extendTo);
        $type = new XdrOperationType(XdrOperationType::EXTEND_FOOTPRINT_TTL);
        $result = new XdrOperationBody($type);
        $result->setExtendFootprintTTLOp($op);
        return $result;
    }

    /**
     * @return int
     */
    public function getExtendTo(): int
    {
        return $this->extendTo;
    }

    /**
     * @param int $extendTo
     */
    public function setExtendTo(int $extendTo): void
    {
        $this->extendTo = $extendTo;
    }

}