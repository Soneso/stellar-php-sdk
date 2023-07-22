<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrBumpFootprintExpirationOp;
use Soneso\StellarSDK\Xdr\XdrExtensionPoint;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;


class BumpFootprintExpirationOperation extends AbstractOperation
{
    private int $ledgersToExpire;

    /**
     * @param int $ledgersToExpire
     */
    public function __construct(int $ledgersToExpire)
    {
        $this->ledgersToExpire = $ledgersToExpire;
    }

    public static function fromXdrOperation(XdrBumpFootprintExpirationOp $xdrOp): BumpFootprintExpirationOperation {
        return new BumpFootprintExpirationOperation($xdrOp->ledgersToExpire);
    }

    public function toOperationBody(): XdrOperationBody
    {
        $op = new XdrBumpFootprintExpirationOp(new XdrExtensionPoint(0), $this->ledgersToExpire);
        $type = new XdrOperationType(XdrOperationType::BUMP_FOOTPRINT_EXPIRATION);
        $result = new XdrOperationBody($type);
        $result->setBumpFootprintExpirationOp($op);
        return $result;
    }

    /**
     * @return int
     */
    public function getLedgersToExpire(): int
    {
        return $this->ledgersToExpire;
    }

    /**
     * @param int $ledgersToExpire
     */
    public function setLedgersToExpire(int $ledgersToExpire): void
    {
        $this->ledgersToExpire = $ledgersToExpire;
    }

}