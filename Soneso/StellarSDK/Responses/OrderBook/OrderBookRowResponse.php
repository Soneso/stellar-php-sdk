<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\OrderBook;

use Soneso\StellarSDK\Responses\Offers\OfferPriceResponse;

class OrderBookRowResponse
{
    private string $price;
    private string $amount;
    private OfferPriceResponse $priceR;

    /**
     * @return string
     */
    public function getPrice(): string
    {
        return $this->price;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @return OfferPriceResponse
     */
    public function getPriceR(): OfferPriceResponse
    {
        return $this->priceR;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['price'])) $this->price = $json['price'];
        if (isset($json['amount'])) $this->amount = $json['amount'];
        if (isset($json['price_r'])) $this->priceR = OfferPriceResponse::fromJson($json['price_r']);
    }

    public static function fromJson(array $json) : OrderBookRowResponse {
        $result = new OrderBookRowResponse();
        $result->loadFromJson($json);
        return $result;
    }
}