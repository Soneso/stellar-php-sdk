<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\Offers\OfferPriceResponse;

/**
 * Represents a create passive sell offer operation response from Horizon API
 *
 * This operation creates a passive offer to sell an asset on the Stellar decentralized exchange.
 * Unlike active offers, passive offers will not immediately consume existing offers at the same
 * price. They only execute if the market price moves favorably. This is useful for market making
 * without immediately affecting the order book.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org/api/resources/operations/object/create-passive-sell-offer Horizon Create Passive Sell Offer Operation
 */
class CreatePassiveSellOfferResponse extends OperationResponse
{
    private string $amount;
    private string $price;
    private OfferPriceResponse $priceR;
    private Asset $buyingAsset;
    private Asset $sellingAsset;

    /**
     * Gets the amount of the selling asset to offer
     *
     * @return string The offer amount as a string to preserve precision
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Gets the offer price as a decimal string
     *
     * @return string Price of 1 unit of selling asset in terms of buying asset
     */
    public function getPrice(): string
    {
        return $this->price;
    }

    /**
     * Gets the asset to buy
     *
     * @return Asset The asset being purchased
     */
    public function getBuyingAsset(): Asset
    {
        return $this->buyingAsset;
    }

    /**
     * Gets the asset to sell
     *
     * @return Asset The asset being sold
     */
    public function getSellingAsset(): Asset
    {
        return $this->sellingAsset;
    }

    /**
     * Gets the offer price as a rational number
     *
     * @return OfferPriceResponse Price as numerator/denominator
     */
    public function getPriceR(): OfferPriceResponse
    {
        return $this->priceR;
    }

    protected function loadFromJson(array $json) : void {

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

    public static function fromJson(array $jsonData) : CreatePassiveSellOfferResponse {
        $result = new CreatePassiveSellOfferResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}