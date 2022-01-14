<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrClawbackOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

class ClawbackOperation extends AbstractOperation
{
    private Asset $asset;
    private MuxedAccount $from;
    private string $amount;

    public function __construct(Asset $asset, MuxedAccount $from, string $amount) {
        $this->asset = $asset;
        $this->from = $from;
        $this->amount = $amount;
    }

    /**
     * @return Asset
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * @return MuxedAccount
     */
    public function getFrom(): MuxedAccount
    {
        return $this->from;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    public static function fromXdrOperation(XdrClawbackOperation $xdrOp): ClawbackOperation {
        $asset = Asset::fromXdr($xdrOp->getAsset());
        $from = MuxedAccount::fromXdr($xdrOp->getFrom());
        $amount = AbstractOperation::fromXdrAmount($xdrOp->getAmount());
        return new ClawbackOperation($asset, $from, $amount);
    }

    public function toOperationBody(): XdrOperationBody
    {
        $asset = $this->asset->toXdr();
        $from = $this->from->toXdr();
        $amount = AbstractOperation::toXdrAmount($this->amount);
        $op = new XdrClawbackOperation($asset, $from, $amount);
        $type = new XdrOperationType(XdrOperationType::CLAWBACK);
        $result = new XdrOperationBody($type);
        $result->setClawbackOperation($op);
        return $result;
    }
}