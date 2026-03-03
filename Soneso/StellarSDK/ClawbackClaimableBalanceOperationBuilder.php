<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

/**
 * Builder for creating ClawbackClaimableBalance operations.
 *
 * This builder implements the builder pattern to construct ClawbackClaimableBalanceOperation
 * instances with a fluent interface. This operation claws back a claimable balance, burning
 * the assets and removing the balance entry from the ledger.
 *
 * @package Soneso\StellarSDK
 * @see ClawbackClaimableBalanceOperation
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 *
 * @example
 * $operation = (new ClawbackClaimableBalanceOperationBuilder($balanceId))
 *     ->setSourceAccount($issuerId)
 *     ->build();
 */
class ClawbackClaimableBalanceOperationBuilder
{
    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Creates a new ClawbackClaimableBalance operation builder.
     *
     * @param string $balanceId The claimable balance ID to claw back
     */
    public function __construct(
        private string $balanceId,
    ) {
    }

    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : ClawbackClaimableBalanceOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : ClawbackClaimableBalanceOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the ClawbackClaimableBalance operation.
     *
     * @return ClawbackClaimableBalanceOperation The constructed operation
     */
    public function build(): ClawbackClaimableBalanceOperation {
        $result = new ClawbackClaimableBalanceOperation($this->balanceId);
        if ($this->sourceAccount !== null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}