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
 * Represents a Manage Sell Offer operation.
 *
 * Creates, updates, or deletes an offer to sell one asset for another, setting the amount of asset to sell.
 * This operation creates a sell offer on the Stellar decentralized exchange (DEX).
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a>
 * @see ManageSellOfferOperationBuilder For building this operation
 * @since 1.0.0
 */
class ManageSellOfferOperation extends AbstractOperation
{
    /**
     * Constructs a new ManageSellOfferOperation object.
     *
     * Creates, updates, or deletes an offer to sell one asset for another on the DEX.
     *
     * @param Asset $selling The asset being sold
     * @param Asset $buying The asset being bought
     * @param string $amount The amount of the selling asset to sell (as a decimal string, set to "0" to delete)
     * @param Price $price The price of 1 unit of selling in terms of buying
     * @param int $offerId The offer ID (0 for new offers, or existing offer ID to update/delete)
     * @throws InvalidArgumentException If the offer ID is negative
     */
    public function __construct(
        private Asset $selling,
        private Asset $buying,
        private string $amount,
        private Price $price,
        private int $offerId,
    ) {
        if ($offerId < 0) {
            throw new InvalidArgumentException("Invalid offer id: ".$offerId);
        }
    }

    /**
     * Returns the asset being sold in this offer.
     *
     * @return Asset The selling asset.
     */
    public function getSelling(): Asset
    {
        return $this->selling;
    }

    /**
     * Returns the asset being bought in this offer.
     *
     * @return Asset The buying asset.
     */
    public function getBuying(): Asset
    {
        return $this->buying;
    }

    /**
     * Returns the amount of selling asset being sold.
     *
     * @return string The amount of the selling asset.
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Returns the price of 1 unit of selling in terms of buying.
     *
     * @return Price The offer price.
     */
    public function getPrice(): Price
    {
        return $this->price;
    }

    /**
     * Returns the ID of the offer.
     *
     * @return int The offer ID (0 for new offers).
     */
    public function getOfferId(): int {
        return $this->offerId;
    }

    /**
     * Creates a ManageSellOfferOperation from XDR operation object.
     *
     * @param XdrManageSellOfferOperation $xdrOp The XDR operation object to convert.
     * @return ManageSellOfferOperation The created operation instance.
     */
    public static function fromXdrOperation(XdrManageSellOfferOperation $xdrOp): ManageSellOfferOperation {
        $selling = Asset::fromXdr($xdrOp->getSelling());
        $buying = Asset::fromXdr($xdrOp->getBuying());
        $amount = AbstractOperation::fromXdrAmount($xdrOp->getAmount());
        $price = Price::fromXdr($xdrOp->getPrice());
        return new ManageSellOfferOperation($selling, $buying, $amount, $price, $xdrOp->getOfferId());
    }

    /**
     * Converts the operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body.
     */
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