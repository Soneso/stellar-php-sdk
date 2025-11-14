<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrClaimableBalanceID;
use Soneso\StellarSDK\Xdr\XdrClawbackClaimableBalanceOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

/**
 * Represents clawback claimable balance operation.
 *
 * Claws back a claimable balance, returning the funds to the asset issuer and removing the claimable balance from the ledger.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a>
 * @see CreateClaimableBalanceOperation For creating claimable balances
 * @since 1.0.0
 */
class ClawbackClaimableBalanceOperation extends AbstractOperation
{
    /**
     * @var string The claimable balance ID to claw back (in hex format)
     */
    private string $balanceId;

    /**
     * Creates a new ClawbackClaimableBalanceOperation.
     *
     * @param string $balanceId The claimable balance ID to claw back (in hex format)
     */
    public function __construct(string $balanceId) {
        $this->balanceId = $balanceId;
    }

    /**
     * Gets the claimable balance ID.
     *
     * @return string The claimable balance ID in hex format
     */
    public function getBalanceId(): string
    {
        return $this->balanceId;
    }

    /**
     * Creates a ClawbackClaimableBalanceOperation from its XDR representation.
     *
     * @param XdrClawbackClaimableBalanceOperation $xdrOp The XDR clawback claimable balance operation to convert
     * @return ClawbackClaimableBalanceOperation The resulting ClawbackClaimableBalanceOperation instance
     */
    public static function fromXdrOperation(XdrClawbackClaimableBalanceOperation $xdrOp): ClawbackClaimableBalanceOperation {
        $bId = $xdrOp->getBalanceID()->getHash();
        return new ClawbackClaimableBalanceOperation($bId);
    }

    /**
     * Converts this operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body
     */
    public function toOperationBody(): XdrOperationBody
    {
        $bId = XdrClaimableBalanceID::forClaimableBalanceId($this->balanceId);
        $op = new XdrClawbackClaimableBalanceOperation($bId);
        $type = new XdrOperationType(XdrOperationType::CLAWBACK_CLAIMABLE_BALANCE);
        $result = new XdrOperationBody($type);
        $result->setClawbackClaimableBalanceOperation($op);
        return $result;
    }
}