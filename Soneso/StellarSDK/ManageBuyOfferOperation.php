<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use InvalidArgumentException;
use Soneso\StellarSDK\Xdr\XdrManageBuyOfferOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

/**
 * Represents <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a> operation.
 *
 * Creates, updates, or deletes an offer to buy one asset for another with a specified buy amount.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a>
 * @see ManageSellOfferOperation For sell-side offers
 * @since 1.0.0
 */
class ManageBuyOfferOperation extends AbstractOperation
{
    /**
     * Creates a new ManageBuyOfferOperation.
     *
     * @param Asset $selling The asset being sold
     * @param Asset $buying The asset being bought
     * @param string $amount The amount of buying asset to purchase (as a decimal string, set to "0" to delete)
     * @param Price $price The price of 1 unit of buying in terms of selling
     * @param int $offerId The offer ID (0 for new offers, or existing offer ID to update/delete)
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
     * Gets the asset being sold.
     *
     * @return Asset The selling asset
     */
    public function getSelling(): Asset {
        return $this->selling;
    }

    /**
     * Gets the asset being bought.
     *
     * @return Asset The buying asset
     */
    public function getBuying(): Asset {
        return $this->buying;
    }

    /**
     * Gets the amount to be bought.
     *
     * @return string The amount as a decimal string
     */
    public function getAmount(): string {
        return $this->amount;
    }

    /**
     * Gets the offer price.
     *
     * @return Price The price
     */
    public function getPrice(): Price {
        return $this->price;
    }

    /**
     * Gets the offer ID.
     *
     * @return int The offer ID
     */
    public function getOfferId(): int {
        return $this->offerId;
    }

    /**
     * Creates a ManageBuyOfferOperation from its XDR representation.
     *
     * @param XdrManageBuyOfferOperation $xdrOp The XDR manage buy offer operation to convert
     * @return ManageBuyOfferOperation The resulting ManageBuyOfferOperation instance
     */
    public static function fromXdrOperation(XdrManageBuyOfferOperation $xdrOp): ManageBuyOfferOperation {
        $selling = Asset::fromXdr($xdrOp->getSelling());
        $buying = Asset::fromXdr($xdrOp->getBuying());
        $amount = AbstractOperation::fromXdrAmount($xdrOp->getAmount());
        $price = Price::fromXdr($xdrOp->getPrice());
        return new ManageBuyOfferOperation($selling, $buying, $amount, $price, $xdrOp->getOfferId());
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
        $op = new XdrManageBuyOfferOperation($xdrSelling, $xdrBuying, $xdrAmount, $xdrPrice, $this->offerId);
        $type = new XdrOperationType(XdrOperationType::MANAGE_BUY_OFFER);
        $result = new XdrOperationBody($type);
        $result->setManageBuyOfferOp($op);
        return $result;
    }
}