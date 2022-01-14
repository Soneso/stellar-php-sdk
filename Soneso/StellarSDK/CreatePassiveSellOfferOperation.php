<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrCreatePassiveSellOfferOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

class CreatePassiveSellOfferOperation extends AbstractOperation
{
    private Asset $selling;
    private Asset $buying;
    private string $amount;
    private Price $price;

    /// Creates, updates, or deletes an offer to buy one asset for another, otherwise known as a "bid" order on a traditional orderbook:
    /// [selling] is the asset the offer creator is selling.
    /// [buying] is the asset the offer creator is buying.
    /// [amount] is the amount of buying being bought. Set to 0 if you want to delete an existing offer.
    /// [price] is the price of 1 unit of buying in terms of selling.
    public function __construct(Asset $selling, Asset $buying, string $amount, Price $price) {
        $this->selling = $selling;
        $this->buying = $buying;
        $this->amount = $amount;
        $this->price = $price;
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

    public static function fromXdrOperation(XdrCreatePassiveSellOfferOperation $xdrOp): CreatePassiveSellOfferOperation {
        $selling = Asset::fromXdr($xdrOp->getSelling());
        $buying = Asset::fromXdr($xdrOp->getBuying());
        $amount = AbstractOperation::fromXdrAmount($xdrOp->getAmount());
        $price = Price::fromXdr($xdrOp->getPrice());
        return new CreatePassiveSellOfferOperation($selling, $buying, $amount, $price);
    }

    public function toOperationBody(): XdrOperationBody {
        $xdrSelling = $this->selling->toXdr();
        $xdrBuying = $this->buying->toXdr();
        $xdrAmount = AbstractOperation::toXdrAmount($this->amount);
        $xdrPrice = $this->price->toXdr();
        $op = new XdrCreatePassiveSellOfferOperation($xdrSelling, $xdrBuying, $xdrAmount, $xdrPrice);
        $type = new XdrOperationType(XdrOperationType::CREATE_PASSIVE_SELL_OFFER);
        $result = new XdrOperationBody($type);
        $result->setCreatePassiveSellOfferOp($op);
        return $result;
    }
}