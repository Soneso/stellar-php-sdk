<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\OrderBook;

use Soneso\StellarSDK\Responses\Offers\OfferPriceResponse;

/**
 * Represents a single price level in the order book
 *
 * This response represents one row in the order book, aggregating all offers at a specific
 * price level. Each row shows the total amount available and the price, provided in both
 * decimal string format and rational fraction format for precision.
 *
 * Order book rows are aggregated by price, meaning multiple individual offers at the same
 * price are combined into a single row showing the total available amount. This provides
 * a consolidated view of market depth at each price level.
 *
 * Used within OrderBookResponse to represent individual entries in the bids or asks collections.
 *
 * @package Soneso\StellarSDK\Responses\OrderBook
 * @see OrderBookRowsResponse For collections of order book rows
 * @see OrderBookResponse For the complete order book
 * @see OfferPriceResponse For the rational price representation
 */
class OrderBookRowResponse
{
    private string $price;
    private string $amount;
    private OfferPriceResponse $priceR;

    /**
     * Gets the price for this order book level
     *
     * @return string The price as a decimal string (counter per base)
     */
    public function getPrice(): string
    {
        return $this->price;
    }

    /**
     * Gets the total amount available at this price level
     *
     * @return string The aggregated amount from all offers at this price
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Gets the rational representation of the price
     *
     * @return OfferPriceResponse The price as a fraction (numerator/denominator)
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

    /**
     * Creates an OrderBookRowResponse from JSON data
     *
     * @param array $json Associative array of parsed JSON response
     * @return OrderBookRowResponse The populated order book row
     */
    public static function fromJson(array $json) : OrderBookRowResponse {
        $result = new OrderBookRowResponse();
        $result->loadFromJson($json);
        return $result;
    }
}