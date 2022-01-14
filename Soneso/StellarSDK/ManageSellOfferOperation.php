<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use InvalidArgumentException;
use Soneso\StellarSDK\Xdr\XdrManageSellOfferOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

/**
 * Represents <a href="https://developers.stellar.org/docs/start/list-of-operations/#manage-sell-offer" target="_blank">ManageSellOffer</a> operation.
 * @see <a href="https://developers.stellar.org/docs/start/list-of-operations/" target="_blank">List of Operations</a>
 */
class ManageSellOfferOperation extends AbstractOperation
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
     * The asset being sold in this operation.
     * @return Asset
     */
    public function getSelling(): Asset
    {
        return $this->selling;
    }

    /**
     * The asset being bought in this operation.
     * @return Asset
     */
    public function getBuying(): Asset
    {
        return $this->buying;
    }

    /**
     * Amount of selling being sold.
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Price of 1 unit of selling in terms of buying.
     * @return Price
     */
    public function getPrice(): Price
    {
        return $this->price;
    }

    /**
     *  The ID of the offer.
     * @return int
     */
    public function getOfferId(): int {
        return $this->offerId;
    }

    public static function fromXdrOperation(XdrManageSellOfferOperation $xdrOp): ManageSellOfferOperation {
        $selling = Asset::fromXdr($xdrOp->getSelling());
        $buying = Asset::fromXdr($xdrOp->getBuying());
        $amount = AbstractOperation::fromXdrAmount($xdrOp->getAmount());
        $price = Price::fromXdr($xdrOp->getPrice());
        return new ManageSellOfferOperation($selling, $buying, $amount, $price, $xdrOp->getOfferId());
    }

    public function toOperationBody(): XdrOperationBody {
        $xdrSelling = $this->selling->toXdr();
        $xdrBuying = $this->buying->toXdr();
        $xdrAmount = AbstractOperation::toXdrAmount($this->amount);
        $xdrPrice = $this->price->toXdr();
        $op = new XdrManageSellOfferOperation($xdrSelling, $xdrBuying, $xdrAmount, $xdrPrice, $this->offerId);
        $type = new XdrOperationType(XdrOperationType::MANAGE_SELL_OFFER);
        $result = new XdrOperationBody($type);
        $result->setManageSellOfferOp($op);
        return $result;
    }
}