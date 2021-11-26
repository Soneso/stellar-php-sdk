<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Offers;

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\Response;

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
     * @return OfferLinksResponse
     */
    public function getLinks(): OfferLinksResponse
    {
        return $this->links;
    }

    /**
     * @return string
     */
    public function getOfferId(): string
    {
        return $this->offerId;
    }

    /**
     * @return string
     */
    public function getPagingToken(): string
    {
        return $this->pagingToken;
    }

    /**
     * @return string
     */
    public function getSeller(): string
    {
        return $this->seller;
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
     * @return string
     */
    public function getPrice(): string
    {
        return $this->price;
    }

    /**
     * @return OfferPriceResponse
     */
    public function getPriceR(): OfferPriceResponse
    {
        return $this->priceR;
    }

    /**
     * @return string|null
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