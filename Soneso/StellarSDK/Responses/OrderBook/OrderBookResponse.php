<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\OrderBook;

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\Response;

/**
 * Represents the order book for a trading pair on Stellar DEX
 *
 * This response contains the current state of the order book (bids and asks) for a specific
 * asset pair on the Stellar distributed exchange. The order book displays all outstanding offers
 * to buy (bids) and sell (asks) the counter asset in terms of the base asset, organized by
 * price levels with aggregated amounts.
 *
 * Key fields:
 * - Base asset (the asset being priced)
 * - Counter asset (the asset used for pricing)
 * - Bids (offers to buy counter asset with base asset)
 * - Asks (offers to sell counter asset for base asset)
 *
 * The bids and asks are sorted by price with best prices first. Each entry shows the price
 * and the total amount available at that price level. This snapshot enables market analysis,
 * order placement, and price discovery for trading pairs.
 *
 * Returned by Horizon endpoint:
 * - GET /order_book - Order book for a specific trading pair
 *
 * @package Soneso\StellarSDK\Responses\OrderBook
 * @see OrderBookRowsResponse For the collection of bids or asks
 * @see OrderBookRowResponse For individual price level details
 * @see https://developers.stellar.org/api/aggregations/order-books Horizon Order Book API
 */
class OrderBookResponse extends Response
{
    private Asset $base;
    private Asset $counter;
    private OrderBookRowsResponse $asks;
    private OrderBookRowsResponse $bids;

    /**
     * Gets the base asset in this trading pair
     *
     * @return Asset The base asset being priced
     */
    public function getBase(): Asset
    {
        return $this->base;
    }

    /**
     * Gets the counter asset in this trading pair
     *
     * @return Asset The counter asset used for pricing
     */
    public function getCounter(): Asset
    {
        return $this->counter;
    }

    /**
     * Gets the sell offers (asks) in the order book
     *
     * @return OrderBookRowsResponse Collection of sell orders sorted by ascending price
     */
    public function getAsks(): OrderBookRowsResponse
    {
        return $this->asks;
    }

    /**
     * Gets the buy offers (bids) in the order book
     *
     * @return OrderBookRowsResponse Collection of buy orders sorted by descending price
     */
    public function getBids(): OrderBookRowsResponse
    {
        return $this->bids;
    }

    protected function loadFromJson(array $json) : void {


        if (isset($json['base'])) {
            $parsedAsset = Asset::fromJson($json['base']);
            if ($parsedAsset != null) {
                $this->base = $parsedAsset;
            }
        }
        if (isset($json['counter'])) {
            $parsedAsset = Asset::fromJson($json['counter']);
            if ($parsedAsset != null) {
                $this->counter = $parsedAsset;
            }
        }
        if (isset($json['asks'])) {
            $this->asks = new OrderBookRowsResponse();
            foreach ($json['asks'] as $jsonValue) {
                $val = OrderBookRowResponse::fromJson($jsonValue);
                $this->asks->add($val);
            }
        }
        if (isset($json['bids'])) {
            $this->bids = new OrderBookRowsResponse();
            foreach ($json['bids'] as $jsonValue) {
                $val = OrderBookRowResponse::fromJson($jsonValue);
                $this->bids->add($val);
            }
        }
    }

    /**
     * Creates an OrderBookResponse from JSON data
     *
     * @param array $json Associative array of parsed JSON response
     * @return OrderBookResponse The populated order book response
     */
    public static function fromJson(array $json) : OrderBookResponse {
        $result = new OrderBookResponse();
        $result->loadFromJson($json);
        return $result;
    }
}