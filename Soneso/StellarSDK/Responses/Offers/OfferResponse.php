<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Offers;

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\Response;

/**
 * Represents an offer on the Stellar distributed exchange (DEX)
 *
 * This response contains comprehensive offer details including the seller account, assets being
 * traded, amount available, price information, and optional sponsorship details. Offers represent
 * standing orders in the order book where an account commits to exchange one asset for another
 * at a specified price ratio.
 *
 * Key fields:
 * - Offer ID and seller account
 * - Selling and buying asset details
 * - Amount available for trading
 * - Price (both decimal and rational representation)
 * - Optional sponsor account for reserve requirements
 *
 * Returned by Horizon endpoints:
 * - GET /offers - All offers
 * - GET /offers/{offer_id} - Specific offer details
 * - GET /accounts/{account_id}/offers - Offers created by an account
 *
 * @package Soneso\StellarSDK\Responses\Offers
 * @see OfferPriceResponse For price ratio representation
 * @see OfferLinksResponse For related navigation links
 * @see https://developers.stellar.org/api/resources/offers Horizon Offers API
 * @since 1.0.0
 */
class OfferResponse extends Response
{
    private OfferLinksResponse $links;
    private string $offerId;
    private string $pagingToken;
    private string $seller;
    private Asset $selling;
    private Asset $buying;
    private string $amount;
    private string $price;
    private OfferPriceResponse $priceR;
    private ?string $sponsor = null;

    /**
     * Gets the links to related resources for this offer
     *
     * @return OfferLinksResponse The navigation links
     */
    public function getLinks(): OfferLinksResponse
    {
        return $this->links;
    }

    /**
     * Gets the unique identifier for this offer
     *
     * @return string The offer ID
     */
    public function getOfferId(): string
    {
        return $this->offerId;
    }

    /**
     * Gets the paging token for this offer in list results
     *
     * @return string The paging token used for cursor-based pagination
     */
    public function getPagingToken(): string
    {
        return $this->pagingToken;
    }

    /**
     * Gets the account address of the seller who created this offer
     *
     * @return string The seller account ID
     */
    public function getSeller(): string
    {
        return $this->seller;
    }

    /**
     * Gets the asset being sold in this offer
     *
     * @return Asset The selling asset
     */
    public function getSelling(): Asset
    {
        return $this->selling;
    }

    /**
     * Gets the asset being bought in this offer
     *
     * @return Asset The buying asset
     */
    public function getBuying(): Asset
    {
        return $this->buying;
    }

    /**
     * Gets the amount of the selling asset available in this offer
     *
     * @return string The amount available for trading
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Gets the decimal representation of the offer price
     *
     * @return string The price as a decimal string (buying units per selling unit)
     */
    public function getPrice(): string
    {
        return $this->price;
    }

    /**
     * Gets the rational representation of the offer price
     *
     * @return OfferPriceResponse The price as a fraction (numerator/denominator)
     */
    public function getPriceR(): OfferPriceResponse
    {
        return $this->priceR;
    }

    /**
     * Gets the account sponsoring the reserves for this offer
     *
     * @return string|null The sponsor account ID, or null if not sponsored
     */
    public function getSponsor(): ?string
    {
        return $this->sponsor;
    }


    protected function loadFromJson(array $json) : void {
        if (isset($json['_links'])) $this->links = OfferLinksResponse::fromJson($json['_links']);
        if (isset($json['id'])) $this->offerId = $json['id'];
        if (isset($json['paging_token'])) $this->pagingToken = $json['paging_token'];
        if (isset($json['seller'])) $this->seller = $json['seller'];

        if (isset($json['selling'])) {
            $parsedAsset = Asset::fromJson($json['selling']);
            if ($parsedAsset != null) {
                $this->selling = $parsedAsset;
            }
        }
        if (isset($json['buying'])) {
            $parsedAsset = Asset::fromJson($json['buying']);
            if ($parsedAsset != null) {
                $this->buying = $parsedAsset;
            }
        }

        if (isset($json['amount'])) $this->amount = $json['amount'];
        if (isset($json['price'])) $this->price = $json['price'];
        if (isset($json['price_r'])) $this->priceR = OfferPriceResponse::fromJson($json['price_r']);
        if (isset($json['sponsor'])) $this->sponsor = $json['sponsor'];

    }

    public static function fromJson(array $json) : OfferResponse {
        $result = new OfferResponse();
        $result->loadFromJson($json);
        return $result;
    }

}