<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

/**
 * Builder for creating Payment operations.
 *
 * This builder implements the builder pattern to construct PaymentOperation
 * instances with a fluent interface. Payment operations send a specified amount
 * of an asset from the source account to a destination account.
 *
 * @package Soneso\StellarSDK
 * @see PaymentOperation
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 *
 * @example
 * $operation = (new PaymentOperationBuilder($destinationId, $asset, '100.00'))
 *     ->setSourceAccount($sourceId)
 *     ->build();
 */
class PaymentOperationBuilder
{
    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * @var MuxedAccount The destination account receiving the payment
     */
    private MuxedAccount $destination;

    /**
     * Creates a new Payment operation builder.
     *
     * @param string $destinationAccountId The destination account ID
     * @param Asset $asset The asset to be sent
     * @param string $amount The amount of asset to send
     */
    public function __construct(
        string $destinationAccountId,
        private Asset $asset,
        private string $amount,
    ) {
        $this->destination = MuxedAccount::fromAccountId($destinationAccountId);
    }

    /**
     * Creates a new Payment operation builder for a muxed destination account.
     *
     * @param MuxedAccount $destination The muxed destination account
     * @param Asset $asset The asset to send
     * @param string $amount The amount to send
     * @return PaymentOperationBuilder The new builder instance
     */
    public static function forMuxedDestinationAccount(MuxedAccount $destination, Asset $asset, string $amount) : PaymentOperationBuilder {
        return  new PaymentOperationBuilder($destination->getAccountId(), $asset, $amount);
    }

    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : PaymentOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : PaymentOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the Payment operation.
     *
     * @return PaymentOperation The constructed operation
     */
    public function build(): PaymentOperation {
        $result = new PaymentOperation($this->destination, $this->asset, $this->amount);
        if ($this->sourceAccount !== null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}