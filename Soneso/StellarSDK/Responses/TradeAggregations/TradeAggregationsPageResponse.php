<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\TradeAggregations;

use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Page\PageResponse;

/**
 * Paginated collection of trade aggregations from Horizon API
 *
 * This response represents a single page of trade aggregations returned by Horizon's trade
 * aggregations endpoint. Each page contains a collection of OHLC (Open-High-Low-Close)
 * candlestick records for a specific asset pair and time resolution, along with pagination
 * links to navigate through the complete historical data.
 *
 * Trade aggregations summarize executed trades into time-based buckets, providing candlestick
 * charts, volume data, and price statistics essential for market analysis and visualization.
 * The response follows Horizon's standard pagination pattern with cursor-based navigation.
 *
 * The aggregation resolution (time period) is specified when querying the endpoint and can
 * range from one minute to one week. Each aggregation record represents one time bucket with
 * OHLC prices, volumes, and trade counts.
 *
 * Returned by Horizon endpoint:
 * - GET /trade_aggregations - Historical trade statistics aggregated by time period
 *
 * @package Soneso\StellarSDK\Responses\TradeAggregations
 * @see PageResponse For pagination functionality
 * @see TradeAggregationsResponse For the collection of aggregations in this page
 * @see TradeAggregationResponse For individual aggregation details
 * @see https://developers.stellar.org/api/aggregations/trade-aggregations Horizon Trade Aggregations API
 * @see https://developers.stellar.org/api/introduction/pagination Horizon Pagination
 */
class TradeAggregationsPageResponse extends PageResponse
{
    private TradeAggregationsResponse $tradeAggregations;

    /**
     * Gets the collection of trade aggregations in this page
     *
     * @return TradeAggregationsResponse The iterable collection of aggregation records
     */
    public function getTradeAggregations(): TradeAggregationsResponse {
        return $this->tradeAggregations;
    }

    protected function loadFromJson(array $json) : void {
        parent::loadFromJson($json);
        if (isset($json['_embedded']['records'])) {
            $this->tradeAggregations = new TradeAggregationsResponse();
            foreach ($json['_embedded']['records'] as $jsonValue) {
                $value = TradeAggregationResponse::fromJson($jsonValue);
                $this->tradeAggregations->add($value);
            }
        }
    }

    /**
     * Creates a TradeAggregationsPageResponse from JSON data
     *
     * @param array $json Associative array of parsed JSON response
     * @return TradeAggregationsPageResponse The populated page response
     */
    public static function fromJson(array $json) : TradeAggregationsPageResponse {
        $result = new TradeAggregationsPageResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * Fetches the next page of trade aggregations
     *
     * @return TradeAggregationsPageResponse|null The next page or null if no next page exists
     */
    public function getNextPage(): TradeAggregationsPageResponse | null {
        return $this->executeRequest(RequestType::TRADE_AGGREGATIONS_PAGE, $this->getNextPageUrl());
    }

    /**
     * Fetches the previous page of trade aggregations
     *
     * @return TradeAggregationsPageResponse|null The previous page or null if no previous page exists
     */
    public function getPreviousPage(): TradeAggregationsPageResponse | null {
        return $this->executeRequest(RequestType::TRADE_AGGREGATIONS_PAGE, $this->getPrevPageUrl());
    }
}