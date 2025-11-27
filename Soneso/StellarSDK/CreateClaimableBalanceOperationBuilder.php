<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

/**
 * Builder for creating CreateClaimableBalance operations.
 *
 * This builder implements the builder pattern to construct CreateClaimableBalanceOperation
 * instances with a fluent interface. This operation creates a claimable balance entry
 * that can be claimed by authorized claimants according to defined predicates.
 *
 * @package Soneso\StellarSDK
 * @see CreateClaimableBalanceOperation
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 *
 * @example
 * $operation = (new CreateClaimableBalanceOperationBuilder($claimants, $asset, '100.00'))
 *     ->setSourceAccount($sourceId)
 *     ->build();
 */
class CreateClaimableBalanceOperationBuilder
{
    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Creates a new CreateClaimableBalance operation builder.
     *
     * @param array<Claimant> $claimants The claimants that can claim the claimable balance
     * @param Asset $asset The asset to be claimed
     * @param string $amount The amount of the asset
     */
    public function __construct(
        private array $claimants,
        private Asset $asset,
        private string $amount,
    ) {
    }

    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : CreateClaimableBalanceOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : CreateClaimableBalanceOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the CreateClaimableBalance operation.
     *
     * @return CreateClaimableBalanceOperation The constructed operation
     */
    public function build(): CreateClaimableBalanceOperation {
        $result = new CreateClaimableBalanceOperation($this->claimants, $this->asset, $this->amount);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}