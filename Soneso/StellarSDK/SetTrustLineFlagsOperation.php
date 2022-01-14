<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrSetTrustLineFlagsOperation;

class SetTrustLineFlagsOperation extends AbstractOperation
{
    private string $trustorId;
    private Asset $asset;
    private int $clearFlags;
    private int $setFlags;

    /**
     * @param string $trustorId
     * @param Asset $asset
     * @param int $clearFlags
     * @param int $setFlags
     */
    public function __construct(string $trustorId, Asset $asset, int $clearFlags, int $setFlags)
    {
        $this->trustorId = $trustorId;
        $this->asset = $asset;
        $this->clearFlags = $clearFlags;
        $this->setFlags = $setFlags;
    }

    /**
     * @return string
     */
    public function getTrustorId(): string
    {
        return $this->trustorId;
    }

    /**
     * @return Asset
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * @return int
     */
    public function getClearFlags(): int
    {
        return $this->clearFlags;
    }

    /**
     * @return int
     */
    public function getSetFlags(): int
    {
        return $this->setFlags;
    }

    public static function fromXdrOperation(XdrSetTrustLineFlagsOperation $xdrOp): SetTrustLineFlagsOperation {
        $trustorId = $xdrOp->getAccountID()->getAccountId();
        $asset = Asset::fromXdr($xdrOp->getAsset());
        $clearFlags = $xdrOp->getClearFlags();
        $setFlags = $xdrOp->getSetFlags();
        return new SetTrustLineFlagsOperation($trustorId, $asset, $clearFlags, $setFlags);
    }

    public function toOperationBody(): XdrOperationBody
    {
        $trustorId = XdrAccountID::fromAccountId($this->trustorId);
        $asset = $this->asset->toXdr();
        $op = new XdrSetTrustLineFlagsOperation($trustorId, $asset, $this->clearFlags, $this->setFlags);
        $type = new XdrOperationType(XdrOperationType::SET_TRUST_LINE_FLAGS);
        $result = new XdrOperationBody($type);
        $result->setSetTrustLineFlagsOperation($op);
        return $result;
    }
}