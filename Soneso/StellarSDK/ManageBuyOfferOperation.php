<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use InvalidArgumentException;
use Soneso\StellarSDK\Xdr\XdrManageBuyOfferOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

class ManageBuyOfferOperation extends AbstractOperation
{
    private Asset $selling;
    private Asset $buying;
    private string $amount;
    private Price $price;
    private int $offerId;

    /// Creates, updates, or deletes an offer to buy one asset for another, otherwise known as a "bid" order on a traditional orderbook:
    /// [selling] is the asset the offer creator is selling.
    /// [buying] is the asset the offer creator is buying.
    /// [amount] is the amount of buying being bought. Set to 0 if you want to delete an existing offer.
    /// [price] is the price of 1 unit of buying in terms of selling.
    /// [offerId] set to "0" for a new offer, otherwise the id of the offer to be changed or removed.
    public function __construct(Asset $selling, Asset $buying, string $amount, Price $price, int $offerId) {
        $this->selling = $selling;
        $this->buying = $buying;
        $this->amount = $amount;
        $this->price = $price;
        $this->offerId = $offerId;
        if ($offerId < 0) {
            throw new InvalidArgumentException("Invalid offer id: ".$offerId);
        }
    }

    /**
     * @return Asset
     */
    public function getSelling(): Asset
    {
        return $this->selling;
    }

    /**
     * @return Asset
     */
    public function getBuying(): Asset
    {
        return $this->buying;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @return Price
     */
    public function getPrice(): Price
    {
        return $this->price;
    }

    /**
     * @return int
     */
    public function getOfferId(): int
    {
        return $this->offerId;
    }

    public function toOperationBody(): XdrOperationBody
    {
        $xdrSelling = $this->selling->toXdr();
        $xdrBuying = $this->buying->toXdr();
        $xdrAmount = AbstractOperation::toXdrAmount($this->amount);
        $xdrPrice = $this->price->toXdr();
        $op = new XdrManageBuyOfferOperation($xdrSelling, $xdrBuying, $xdrAmount, $xdrPrice, $this->offerId);
        $type = new XdrOperationType(XdrOperationType::MANAGE_BUY_OFFER);
        $result = new XdrOperationBody($type);
        $result->setManageBuyOfferOp($op);
        return $result;
    }
}