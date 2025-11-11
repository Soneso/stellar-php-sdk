<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

/**
 * Builder for creating Clawback operations.
 *
 * This builder implements the builder pattern to construct ClawbackOperation
 * instances with a fluent interface. Clawback operations allow an asset issuer
 * to burn assets from a holder's account, effectively reclaiming them.
 *
 * @package Soneso\StellarSDK
 * @see ClawbackOperation
 * @see https://developers.stellar.org/docs/fundamentals-and-concepts/list-of-operations#clawback
 * @since 1.0.0
 *
 * @example
 * $operation = (new ClawbackOperationBuilder($asset, $fromAccount, '100.00'))
 *     ->setSourceAccount($issuerId)
 *     ->build();
 */
class ClawbackOperationBuilder
{
    /**
     * @var Asset The asset to claw back
     */
    private Asset $asset;

    /**
     * @var MuxedAccount The account from which to claw back the asset
     */
    private MuxedAccount $from;

    /**
     * @var string The amount to claw back
     */
    private string $amount;

    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Creates a new Clawback operation builder.
     *
     * @param Asset $asset The asset to claw back
     * @param MuxedAccount $from The account from which to claw back the asset
     * @param string $amount The amount to claw back
     */
    public function __construct(Asset $asset, MuxedAccount $from, string $amount) {
        $this->asset = $asset;
        $this->from = $from;
        $this->amount = $amount;
    }

    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : ClawbackOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : ClawbackOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the Clawback operation.
     *
     * @return ClawbackOperation The constructed operation
     */
    public function build(): ClawbackOperation {
        $result = new ClawbackOperation($this->asset, $this->from, $this->amount);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}