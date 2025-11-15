<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

/**
 * Builder for creating ManageData operations.
 *
 * This builder implements the builder pattern to construct ManageDataOperation
 * instances with a fluent interface. ManageData operations set, modify, or delete
 * key-value pairs stored in an account's data entries.
 *
 * @package Soneso\StellarSDK
 * @see ManageDataOperation
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 *
 * @example
 * $operation = (new ManageDataOperationBuilder('mykey', 'myvalue'))
 *     ->setSourceAccount($sourceId)
 *     ->build();
 */
class ManageDataOperationBuilder
{
    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Creates a new ManageData operation builder.
     *
     * Pass null as the value parameter to delete a data entry.
     *
     * @param string $key The name of the data entry
     * @param string|null $value The value of the data entry (null will delete the entry)
     */
    public function __construct(
        private string $key,
        private ?string $value = null,
    ) {
    }

    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : ManageDataOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : ManageDataOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the ManageData operation.
     *
     * @return ManageDataOperation The constructed operation
     */
    public function build(): ManageDataOperation {
        $result = new ManageDataOperation($this->key, $this->value);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}