<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use InvalidArgumentException;

/**
 * Builds ManageSellOffer operation.
 * If you want to update existing offer use setOfferId()
 * @see ManageSellOfferOperation
 */
class ManageSellOfferOperationBuilder
{
    private Asset $selling;
    private Asset $buying;
    private string $amount;
    private Price $price;
    private int $offerId = 0;
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Creates a new ManageSellOffer builder. If you want to update existing offer use setOfferId().
     * @param Asset $selling The asset being sold in this operation.
     * @param Asset $buying The asset being bought in this operation.
     * @param string $amount  Amount of selling being sold.
     * @param string $price Price of 1 unit of selling in terms of buying.
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
     * Sets the source account for this operation. G...
     * @param string $accountId The operation's source account.
     * @return ManageSellOfferOperationBuilder Builder object so you can chain methods
     */
    public function setSourceAccount(string $accountId) : ManageSellOfferOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     * @param MuxedAccount $sourceAccount The operation's source account.
     * @return ManageSellOfferOperationBuilder Builder object so you can chain methods
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : ManageSellOfferOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds an operation
     * @return ManageSellOfferOperation
     */
    public function build(): ManageSellOfferOperation {
        $result = new ManageSellOfferOperation($this->selling, $this->buying, $this->amount, $this->price, $this->offerId);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}