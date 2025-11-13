<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

/**
 * Builder for creating CreateAccount operations.
 *
 * This builder implements the builder pattern to construct CreateAccountOperation
 * instances with a fluent interface. CreateAccount operations create a new account
 * on the Stellar ledger with an initial XLM balance.
 *
 * @package Soneso\StellarSDK
 * @see CreateAccountOperation
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 *
 * @example
 * $operation = (new CreateAccountOperationBuilder($destinationId, '10.00'))
 *     ->setSourceAccount($sourceId)
 *     ->build();
 */
class CreateAccountOperationBuilder
{
    /**
     * @var string The destination account ID to be created
     */
    private string $destination;

    /**
     * @var string The initial balance in lumens (XLM)
     */
    private string $startingBalance;

    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Creates a new CreateAccount operation builder.
     *
     * @param string $destination The destination account ID to be created
     * @param string $startingBalance The initial balance to start with in lumens
     */
    public function __construct(string $destination, string $startingBalance) {
        $this->destination = $destination;
        $this->startingBalance = $startingBalance;
    }

    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : CreateAccountOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : CreateAccountOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the CreateAccount operation.
     *
     * @return CreateAccountOperation The constructed operation
     */
    public function build(): CreateAccountOperation {
        $result = new CreateAccountOperation($this->destination, $this->startingBalance);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}