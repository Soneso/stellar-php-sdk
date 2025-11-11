<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

/**
 * Builder for creating ClaimClaimableBalance operations.
 *
 * This builder implements the builder pattern to construct ClaimClaimableBalanceOperation
 * instances with a fluent interface. This operation claims a claimable balance entry,
 * transferring its contents to the claiming account.
 *
 * @package Soneso\StellarSDK
 * @see ClaimClaimableBalanceOperation
 * @see https://developers.stellar.org/docs/fundamentals-and-concepts/list-of-operations#claim-claimable-balance
 * @since 1.0.0
 *
 * @example
 * $operation = (new ClaimClaimableBalanceOperationBuilder($balanceId))
 *     ->setSourceAccount($claimantId)
 *     ->build();
 */
class ClaimClaimableBalanceOperationBuilder
{
    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * @var string The claimable balance ID to claim
     */
    private string $balanceId;

    /**
     * Creates a new ClaimClaimableBalance operation builder.
     *
     * @param string $balanceId The claimable balance ID to claim
     */
    public function __construct(string $balanceId) {
        $this->balanceId = $balanceId;
    }

    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : ClaimClaimableBalanceOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : ClaimClaimableBalanceOperationBuilder  {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the ClaimClaimableBalance operation.
     *
     * @return ClaimClaimableBalanceOperation The constructed operation
     */
    public function build(): ClaimClaimableBalanceOperation {
        $result = new ClaimClaimableBalanceOperation($this->balanceId);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}