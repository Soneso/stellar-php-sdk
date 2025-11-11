<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use InvalidArgumentException;

/**
 * Builder for creating ManageSellOffer operations.
 *
 * This builder implements the builder pattern to construct ManageSellOfferOperation
 * instances with a fluent interface. ManageSellOffer operations create, update, or delete
 * offers to sell an asset on the Stellar decentralized exchange.
 *
 * @package Soneso\StellarSDK
 * @see ManageSellOfferOperation
 * @see https://developers.stellar.org/docs/fundamentals-and-concepts/list-of-operations#manage-sell-offer
 * @since 1.0.0
 *
 * @example
 * $operation = (new ManageSellOfferOperationBuilder($selling, $buying, '100', '2.5'))
 *     ->setOfferId($existingOfferId)
 *     ->setSourceAccount($sourceId)
 *     ->build();
 */
class ManageSellOfferOperationBuilder
{
    /**
     * @var Asset The asset being sold
     */
    private Asset $selling;

    /**
     * @var Asset The asset being bought
     */
    private Asset $buying;

    /**
     * @var string The amount of selling asset
     */
    private string $amount;

    /**
     * @var Price The price of 1 unit of selling in terms of buying
     */
    private Price $price;

    /**
     * @var int The offer ID (0 for new offers)
     */
    private int $offerId = 0;

    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Creates a new ManageSellOffer operation builder.
     *
     * @param Asset $selling The asset being sold
     * @param Asset $buying The asset being bought
     * @param string $amount The amount of selling asset
     * @param string $price The price of 1 unit of selling in terms of buying
     */
    public function __construct(Asset $selling, Asset $buying, string $amount, string $price) {
        $this->selling = $selling;
        $this->buying = $buying;
        $this->amount = $amount;
        $this->price = Price::fromString($price);
    }

    /**
     * Sets offer ID. <code>0</code> creates a new offer. Set to existing offer ID to change it.
     * @param int $offerId
     * @return ManageSellOfferOperationBuilder Builder object so you can chain methods.
     */
    public function setOfferId(int $offerId) : ManageSellOfferOperationBuilder {
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
    public function setSourceAccount(string $accountId) : ManageSellOfferOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : ManageSellOfferOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the ManageSellOffer operation.
     *
     * @return ManageSellOfferOperation The constructed operation
     */
    public function build(): ManageSellOfferOperation {
        $result = new ManageSellOfferOperation($this->selling, $this->buying, $this->amount, $this->price, $this->offerId);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}