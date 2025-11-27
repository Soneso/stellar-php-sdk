<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

/**
 * Builder for creating CreatePassiveSellOffer operations.
 *
 * This builder implements the builder pattern to construct CreatePassiveSellOfferOperation
 * instances with a fluent interface. Passive sell offers allow an account to place an offer
 * on the order book that will not be immediately matched with crossing offers.
 *
 * @package Soneso\StellarSDK
 * @see CreatePassiveSellOfferOperation
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 *
 * @example
 * $operation = (new CreatePassiveSellOfferOperationBuilder($sellingAsset, $buyingAsset, '100.00', $price))
 *     ->setSourceAccount($sourceId)
 *     ->build();
 */
class CreatePassiveSellOfferOperationBuilder
{
    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Creates a new CreatePassiveSellOffer operation builder.
     *
     * @param Asset $selling The asset being sold
     * @param Asset $buying The asset being bought
     * @param string $amount The amount of selling asset
     * @param Price $price The price ratio of buying to selling
     */
    public function __construct(
        private Asset $selling,
        private Asset $buying,
        private string $amount,
        private Price $price,
    ) {
    }

    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : CreatePassiveSellOfferOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : CreatePassiveSellOfferOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the CreatePassiveSellOffer operation.
     *
     * @return CreatePassiveSellOfferOperation The constructed operation
     */
    public function build(): CreatePassiveSellOfferOperation {
        $result = new CreatePassiveSellOfferOperation($this->selling, $this->buying, $this->amount, $this->price);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}