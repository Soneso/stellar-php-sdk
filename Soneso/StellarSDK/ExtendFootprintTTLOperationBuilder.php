<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

/**
 * Builder for creating ExtendFootprintTTL operations.
 *
 * This builder implements the builder pattern to construct ExtendFootprintTTLOperation
 * instances with a fluent interface. This operation extends the time-to-live (TTL) of
 * Soroban contract storage entries, preventing them from being archived.
 *
 * @package Soneso\StellarSDK
 * @see ExtendFootprintTTLOperation
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 *
 * @example
 * $operation = (new ExtendFootprintTTLOperationBuilder(100000))
 *     ->setSourceAccount($sourceId)
 *     ->build();
 */
class ExtendFootprintTTLOperationBuilder
{
    /**
     * @var int The number of ledgers to extend the TTL
     */
    private int $extendTo;

    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Creates a new ExtendFootprintTTL operation builder.
     *
     * @param int $extendTo The number of ledgers to extend the TTL
     */
    public function __construct(int $extendTo)
    {
        $this->extendTo = $extendTo;
    }


    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : ExtendFootprintTTLOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : ExtendFootprintTTLOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the ExtendFootprintTTL operation.
     *
     * @return ExtendFootprintTTLOperation The constructed operation
     */
    public function build(): ExtendFootprintTTLOperation {
        $result = new ExtendFootprintTTLOperation($this->extendTo);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}