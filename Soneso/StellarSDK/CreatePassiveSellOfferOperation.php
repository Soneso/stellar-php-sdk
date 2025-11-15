<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrCreatePassiveSellOfferOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

/**
 * Represents a create passive sell offer operation.
 *
 * Creates an offer to sell one asset for another without taking a reverse offer of equal price.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a>
 * @see ManageSellOfferOperation For active sell offers
 * @since 1.0.0
 */
class CreatePassiveSellOfferOperation extends AbstractOperation
{
    /**
     * Creates a new CreatePassiveSellOfferOperation.
     *
     * @param Asset $selling The asset being sold
     * @param Asset $buying The asset being bought
     * @param string $amount The amount of selling asset to sell (as a decimal string)
     * @param Price $price The price of 1 unit of selling in terms of buying
     */
    public function __construct(
        private Asset $selling,
        private Asset $buying,
        private string $amount,
        private Price $price,
    ) {
    }

    /**
     * Gets the asset being sold.
     *
     * @return Asset The selling asset
     */
    public function getSelling(): Asset
    {
        return $this->selling;
    }

    /**
     * Gets the asset being bought.
     *
     * @return Asset The buying asset
     */
    public function getBuying(): Asset
    {
        return $this->buying;
    }

    /**
     * Gets the amount being sold.
     *
     * @return string The amount as a decimal string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Gets the offer price.
     *
     * @return Price The price
     */
    public function getPrice(): Price
    {
        return $this->price;
    }

    /**
     * Creates a CreatePassiveSellOfferOperation from its XDR representation.
     *
     * @param XdrCreatePassiveSellOfferOperation $xdrOp The XDR create passive sell offer operation to convert
     * @return CreatePassiveSellOfferOperation The resulting CreatePassiveSellOfferOperation instance
     */
    public static function fromXdrOperation(XdrCreatePassiveSellOfferOperation $xdrOp): CreatePassiveSellOfferOperation {
        $selling = Asset::fromXdr($xdrOp->getSelling());
        $buying = Asset::fromXdr($xdrOp->getBuying());
        $amount = AbstractOperation::fromXdrAmount($xdrOp->getAmount());
        $price = Price::fromXdr($xdrOp->getPrice());
        return new CreatePassiveSellOfferOperation($selling, $buying, $amount, $price);
    }

    /**
     * Converts this operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body
     */
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