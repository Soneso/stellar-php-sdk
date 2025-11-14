<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\TradeAggregations;

/**
 * Iterable collection of trade aggregation responses
 *
 * This class provides an iterable wrapper around a collection of TradeAggregationResponse objects
 * representing OHLC candlestick data. It extends IteratorIterator to enable efficient traversal
 * of trade aggregations returned from Horizon API endpoints. The collection supports iteration,
 * counting, array conversion, and dynamic addition of aggregation records.
 *
 * Used by TradeAggregationsPageResponse to hold the aggregations contained in a single page of
 * results. Each item represents a time bucket with open, high, low, close prices, volumes, and
 * trade counts for a specific asset pair during that period.
 *
 * @package Soneso\StellarSDK\Responses\TradeAggregations
 * @see TradeAggregationResponse For individual aggregation details
 * @see TradeAggregationsPageResponse For paginated aggregation results
 */
class TradeAggregationsResponse extends \IteratorIterator
{

    /**
     * Constructs a new trade aggregations collection
     *
     * @param TradeAggregationResponse ...$response Variable number of aggregation responses
     */
    public function __construct(TradeAggregationResponse ...$response)
    {
        parent::__construct(new \ArrayIterator($response));
    }

    /**
     * Gets the current trade aggregation in the iteration
     *
     * @return TradeAggregationResponse The current aggregation response
     */
    public function current(): TradeAggregationResponse
    {
        return parent::current();
    }

    /**
     * Adds a trade aggregation response to the collection
     *
     * @param TradeAggregationResponse $response The aggregation response to add
     * @return void
     */
    public function add(TradeAggregationResponse $response)
    {
        $this->getInnerIterator()->append($response);
    }

    /**
     * Gets the total number of aggregations in this collection
     *
     * @return int The count of trade aggregation records
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * @return array<TradeAggregationResponse>
     */
    public function toArray() : array {
        /**
         * @var array<TradeAggregationResponse> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}