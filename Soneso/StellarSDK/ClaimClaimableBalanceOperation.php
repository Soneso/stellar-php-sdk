<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrClaimableBalanceID;
use Soneso\StellarSDK\Xdr\XdrClaimClaimableBalanceOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

/**
 * Represents <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a> operation.
 *
 * Claims a claimable balance entry and adds the amount to the source account's balance.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a>
 * @see CreateClaimableBalanceOperation For creating claimable balances
 * @since 1.0.0
 */
class ClaimClaimableBalanceOperation extends AbstractOperation
{
    /**
     * Creates a new ClaimClaimableBalanceOperation.
     *
     * @param string $balanceId The claimable balance ID (in hex format).
     */
    public function __construct(
        private string $balanceId,
    ) {
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
     * Creates a ClaimClaimableBalanceOperation from its XDR representation.
     *
     * @param XdrClaimClaimableBalanceOperation $xdrOp The XDR claim claimable balance operation to convert
     * @return ClaimClaimableBalanceOperation The resulting ClaimClaimableBalanceOperation instance
     */
    public static function fromXdrOperation(XdrClaimClaimableBalanceOperation $xdrOp): ClaimClaimableBalanceOperation {
        $balanceId = $xdrOp->getBalanceID()->getHash();
        return new ClaimClaimableBalanceOperation($balanceId);
    }

    /**
     * Converts this operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body
     */
    public function toOperationBody(): XdrOperationBody
    {
        $bId = XdrClaimableBalanceID::forClaimableBalanceId($this->balanceId);
        $op = new XdrClaimClaimableBalanceOperation($bId);
        $type = new XdrOperationType(XdrOperationType::CLAIM_CLAIMABLE_BALANCE);
        $result = new XdrOperationBody($type);
        $result->setClaimClaimableBalanceOperation($op);
        return $result;
    }
}