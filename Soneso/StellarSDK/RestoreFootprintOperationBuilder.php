<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

/**
 * Builder for creating RestoreFootprint operations.
 *
 * This builder implements the builder pattern to construct RestoreFootprintOperation
 * instances with a fluent interface. This operation restores archived Soroban contract
 * storage entries, making them active and accessible again.
 *
 * @package Soneso\StellarSDK
 * @see RestoreFootprintOperation
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 *
 * @example
 * $operation = (new RestoreFootprintOperationBuilder())
 *     ->setSourceAccount($sourceId)
 *     ->build();
 */
class RestoreFootprintOperationBuilder
{
    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Creates a new RestoreFootprint operation builder.
     */
    public function __construct()
    {
    }

    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : RestoreFootprintOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : RestoreFootprintOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the RestoreFootprint operation.
     *
     * @return RestoreFootprintOperation The constructed operation
     */
    public function build(): RestoreFootprintOperation {
        $result = new RestoreFootprintOperation();
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}