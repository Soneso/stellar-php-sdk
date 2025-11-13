<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrClaimant;
use Soneso\StellarSDK\Xdr\XdrCreateClaimableBalanceOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

/**
 * Represents a create claimable balance operation.
 *
 * Creates a claimable balance entry with a list of claimants who can later claim the balance.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a>
 * @see ClaimClaimableBalanceOperation For claiming a balance
 * @since 1.0.0
 */
class CreateClaimableBalanceOperation extends AbstractOperation
{
    /**
     * @var array<Claimant> Array of claimants who can claim this balance
     */
    private array $claimants;

    /**
     * @var Asset The asset for the claimable balance
     */
    private Asset $asset;

    /**
     * @var string The amount of the asset (as a decimal string)
     */
    private string $amount;

    /**
     * Creates a new CreateClaimableBalanceOperation.
     *
     * @param array<Claimant> $claimants Array of claimants who can claim the balance
     * @param Asset $asset The asset to make claimable
     * @param string $amount The amount to make claimable (as a decimal string)
     */
    public function __construct(array $claimants, Asset $asset, string $amount) {
        $this->claimants = $claimants;
        $this->asset = $asset;
        $this->amount = $amount;
    }

    /**
     * Gets the array of claimants.
     *
     * @return array<Claimant> The claimants
     */
    public function getClaimants(): array
    {
        return $this->claimants;
    }

    /**
     * Gets the asset.
     *
     * @return Asset The asset
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * Gets the amount.
     *
     * @return string The amount as a decimal string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Creates a CreateClaimableBalanceOperation from its XDR representation.
     *
     * @param XdrCreateClaimableBalanceOperation $xdrOp The XDR create claimable balance operation to convert
     * @return CreateClaimableBalanceOperation The resulting CreateClaimableBalanceOperation instance
     */
    public static function fromXdrOperation(XdrCreateClaimableBalanceOperation $xdrOp): CreateClaimableBalanceOperation {
        $asset = Asset::fromXdr($xdrOp->getAsset());
        $amount = AbstractOperation::fromXdrAmount($xdrOp->getAmount());
        $claimants = array();
        foreach($xdrOp->getClaimants() as $xdrClaimant) {
            if ($xdrClaimant instanceof XdrClaimant) {
                array_push($claimants, Claimant::fromXdr($xdrClaimant));
            }
        }
        return new CreateClaimableBalanceOperation($claimants, $asset, $amount);
    }

    /**
     * Converts this operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body
     */
    public function toOperationBody(): XdrOperationBody
    {
        $xdrAsset = $this->asset->toXdr();
        $xdrAmount = AbstractOperation::toXdrAmount($this->amount);
        $xdrClaimants = array();
        foreach($this->claimants as $claimant) {
            if ($claimant instanceof Claimant) {
                array_push($xdrClaimants, $claimant->toXdr());
            }
        }
        $op = new XdrCreateClaimableBalanceOperation($xdrAsset, $xdrAmount, $xdrClaimants);
        $type = new XdrOperationType(XdrOperationType::CREATE_CLAIMABLE_BALANCE);
        $result = new XdrOperationBody($type);
        $result->setCreateClaimableBalanceOperation($op);
        return $result;
    }
}