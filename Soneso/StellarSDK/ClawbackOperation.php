<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrClawbackOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

/**
 * Represents clawback operation.
 *
 * Burns an amount of an asset from an account, effectively destroying the asset. The asset must have the AUTH_CLAWBACK_ENABLED flag set.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a>
 * @since 1.0.0
 */
class ClawbackOperation extends AbstractOperation
{
    /**
     * @var Asset The asset being clawed back
     */
    private Asset $asset;

    /**
     * @var MuxedAccount The account from which the asset is clawed back
     */
    private MuxedAccount $from;

    /**
     * @var string The amount of the asset to claw back (as a decimal string)
     */
    private string $amount;

    /**
     * Creates a new ClawbackOperation.
     *
     * @param Asset $asset The asset to claw back (must have AUTH_CLAWBACK_ENABLED flag set)
     * @param MuxedAccount $from The account from which to claw back the asset
     * @param string $amount The amount to claw back (as a decimal string)
     */
    public function __construct(Asset $asset, MuxedAccount $from, string $amount) {
        $this->asset = $asset;
        $this->from = $from;
        $this->amount = $amount;
    }

    /**
     * Gets the asset being clawed back.
     *
     * @return Asset The asset
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * Gets the account from which the asset is clawed back.
     *
     * @return MuxedAccount The source account
     */
    public function getFrom(): MuxedAccount
    {
        return $this->from;
    }

    /**
     * Gets the amount being clawed back.
     *
     * @return string The amount as a decimal string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Creates a ClawbackOperation from its XDR representation.
     *
     * @param XdrClawbackOperation $xdrOp The XDR clawback operation to convert
     * @return ClawbackOperation The resulting ClawbackOperation instance
     */
    public static function fromXdrOperation(XdrClawbackOperation $xdrOp): ClawbackOperation {
        $asset = Asset::fromXdr($xdrOp->getAsset());
        $from = MuxedAccount::fromXdr($xdrOp->getFrom());
        $amount = AbstractOperation::fromXdrAmount($xdrOp->getAmount());
        return new ClawbackOperation($asset, $from, $amount);
    }

    /**
     * Converts this operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body
     */
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