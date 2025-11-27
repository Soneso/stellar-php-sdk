<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use InvalidArgumentException;

/**
 * Builder for creating ManageBuyOffer operations.
 *
 * This builder implements the builder pattern to construct ManageBuyOfferOperation
 * instances with a fluent interface. ManageBuyOffer operations create, update, or delete
 * offers on the Stellar decentralized exchange, specifying the amount to buy rather than
 * the amount to sell.
 *
 * @package Soneso\StellarSDK
 * @see ManageBuyOfferOperation
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 *
 * @example
 * $operation = (new ManageBuyOfferOperationBuilder($selling, $buying, '100.00', '0.85'))
 *     ->setOfferId(12345)
 *     ->setSourceAccount($sourceId)
 *     ->build();
 */
class ManageBuyOfferOperationBuilder
{
    /**
     * @var int The offer ID (0 for new offers, existing ID to modify)
     */
    private int $offerId = 0;

    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * @var Price The price of the asset being bought in terms of the asset being sold
     */
    private Price $price;

    /**
     * Creates a new ManageBuyOffer operation builder.
     *
     * @param Asset $selling The asset being sold in this operation
     * @param Asset $buying The asset being bought in this operation
     * @param string $amount The amount of asset to be bought
     * @param string $price The price of the asset being bought in terms of the asset being sold
     */
    public function __construct(
        private Asset $selling,
        private Asset $buying,
        private string $amount,
        string $price,
    ) {
        $this->price = Price::fromString($price);
    }

    /**
     * Sets the offer ID for this operation.
     *
     * Use 0 to create a new offer, or an existing offer ID to modify it.
     *
     * @param int $offerId The offer ID (must be non-negative)
     * @return $this Returns the builder instance for method chaining
     * @throws InvalidArgumentException If the offer ID is negative
     */
    public function setOfferId(int $offerId) : ManageBuyOfferOperationBuilder {
        if ($offerId < 0) {
            throw new InvalidArgumentException("Invalid offer id: ".$offerId);
        }
        $this->offerId = $offerId;
        return $this;
    }

    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : ManageBuyOfferOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : ManageBuyOfferOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the ManageBuyOffer operation.
     *
     * @return ManageBuyOfferOperation The constructed operation
     */
    public function build(): ManageBuyOfferOperation {
        $result = new ManageBuyOfferOperation($this->selling, $this->buying, $this->amount, $this->price, $this->offerId);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}