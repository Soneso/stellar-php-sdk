<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrClaimant;
use Soneso\StellarSDK\Xdr\XdrCreateClaimableBalanceOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

class CreateClaimableBalanceOperation extends AbstractOperation
{

    private array $claimants; //[Claimant]
    private Asset $asset;
    private string $amount;

    public function __construct(array $claimants, Asset $asset, string $amount) {
        $this->claimants = $claimants;
        $this->asset = $asset;
        $this->amount = $amount;
    }

    /**
     * @return array
     */
    public function getClaimants(): array
    {
        return $this->claimants;
    }

    /**
     * @return Asset
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

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