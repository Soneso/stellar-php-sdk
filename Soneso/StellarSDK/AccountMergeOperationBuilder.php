<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

/**
 * Builder for creating AccountMerge operations.
 *
 * This builder implements the builder pattern to construct AccountMergeOperation
 * instances with a fluent interface. AccountMerge operations merge one account into
 * another, transferring the source account's XLM balance to the destination account
 * and removing the source account from the ledger.
 *
 * @package Soneso\StellarSDK
 * @see AccountMergeOperation
 * @see https://developers.stellar.org/docs/fundamentals-and-concepts/list-of-operations#account-merge
 * @since 1.0.0
 *
 * @example
 * $operation = (new AccountMergeOperationBuilder($destinationId))
 *     ->setSourceAccount($sourceId)
 *     ->build();
 */
class AccountMergeOperationBuilder
{
    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * @var MuxedAccount The destination account that receives the remaining balance
     */
    private MuxedAccount $destination;

    /**
     * Creates a new AccountMerge operation builder.
     *
     * @param string $destinationAccountId The account that receives the remaining XLM balance of the source account
     */
    public function __construct(string $destinationAccountId) {
        $this->destination = MuxedAccount::fromAccountId($destinationAccountId);
    }

    /**
     * Creates a new AccountMerge operation builder for a muxed destination account.
     *
     * @param MuxedAccount $destination The muxed destination account
     * @return AccountMergeOperationBuilder The new builder instance
     */
    public static function forMuxedDestinationAccount(MuxedAccount $destination) : AccountMergeOperationBuilder {
        return new AccountMergeOperationBuilder($destination->getAccountId());
    }

    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : AccountMergeOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : AccountMergeOperationBuilder  {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the AccountMerge operation.
     *
     * @return AccountMergeOperation The constructed operation
     */
    public function build(): AccountMergeOperation {
        $result = new AccountMergeOperation($this->destination);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}