<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use phpseclib3\Math\BigInteger;

/**
 * Builder for creating BumpSequence operations.
 *
 * This builder implements the builder pattern to construct BumpSequenceOperation
 * instances with a fluent interface. BumpSequence operations bump the sequence number
 * of the source account to a specified value, allowing the account to invalidate any
 * transactions with a lower sequence number.
 *
 * @package Soneso\StellarSDK
 * @see BumpSequenceOperation
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 *
 * @example
 * $operation = (new BumpSequenceOperationBuilder(new BigInteger('12345')))
 *     ->setSourceAccount($sourceId)
 *     ->build();
 */
class BumpSequenceOperationBuilder
{
    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Creates a new BumpSequence operation builder.
     *
     * @param BigInteger $bumpTo The desired value for the source account's sequence number
     */
    public function __construct(
        private BigInteger $bumpTo,
    ) {
    }

    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : BumpSequenceOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : BumpSequenceOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the BumpSequence operation.
     *
     * @return BumpSequenceOperation The constructed operation
     */
    public function build(): BumpSequenceOperation {
        $result = new BumpSequenceOperation($this->bumpTo);
        if ($this->sourceAccount !== null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}