<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\Offers\OfferPriceResponse;

/**
 * Represents a manage sell offer operation response from Horizon API
 *
 * This operation creates, updates, or deletes a sell offer in the Stellar DEX.
 * Contains offer details including ID, amount, price, and the assets being bought and sold.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org/api/resources/operations/object/manage-sell-offer Horizon Manage Sell Offer
 * @since 1.0.0
 */
class ManageSellOfferOperationResponse extends OperationResponse
{
    private string $offerId;
    private string $amount;
    private string $price;
    private OfferPriceResponse $priceR;
    private Asset $buyingAsset;
    private Asset $sellingAsset;

    /**
     * Gets the offer ID
     *
     * @return string The unique offer identifier, 0 if offer was deleted
     */
    public function getOfferId(): string
    {
        return $this->offerId;
    }

    /**
     * Gets the amount of selling asset offered
     *
     * @return string The offer amount
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Gets the offer price as a decimal string
     *
     * @return string The price of 1 unit of selling asset in terms of buying asset
     */
    public function getPrice(): string
    {
        return $this->price;
    }

    /**
     * Gets the asset being bought in this offer
     *
     * @return Asset The buying asset details
     */
    public function getBuyingAsset(): Asset
    {
        return $this->buyingAsset;
    }

    /**
     * Gets the asset being sold in this offer
     *
     * @return Asset The selling asset details
     */
    public function getSellingAsset(): Asset
    {
        return $this->sellingAsset;
    }

    /**
     * Gets the offer price as a rational number
     *
     * @return OfferPriceResponse The price as numerator and denominator
     */
    public function getPriceR(): OfferPriceResponse
    {
        return $this->priceR;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['offer_id'])) $this->offerId = $json['offer_id'];
        if (isset($json['amount'])) $this->amount = $json['amount'];
        if (isset($json['price'])) $this->price = $json['price'];
        if (isset($json['price_r'])) $this->priceR = OfferPriceResponse::fromJson($json['price_r']);
        if (isset($json['buying_asset_type'])) {
            $assetCode = $json['buying_asset_code'] ?? null;
            $assetIssuer = $json['buying_asset_issuer'] ?? null;
            $this->buyingAsset = Asset::create($json['buying_asset_type'], $assetCode, $assetIssuer);
        }
        if (isset($json['selling_asset_type'])) {
            $assetCode = $json['selling_asset_code'] ?? null;
            $assetIssuer = $json['selling_asset_issuer'] ?? null;
            $this->sellingAsset = Asset::create($json['selling_asset_type'], $assetCode, $assetIssuer);
        }
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : ManageSellOfferOperationResponse {
        $result = new ManageSellOfferOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}