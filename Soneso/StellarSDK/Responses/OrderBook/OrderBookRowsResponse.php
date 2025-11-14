<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\OrderBook;

/**
 * Iterable collection of order book row responses
 *
 * This class provides an iterable wrapper around a collection of OrderBookRowResponse objects
 * representing price levels in an order book. It extends IteratorIterator to enable efficient
 * traversal of order book entries (bids or asks) returned from Horizon. The collection supports
 * iteration, counting, array conversion, and dynamic addition of row records.
 *
 * Used by OrderBookResponse to hold the bids or asks for a trading pair. Each item represents
 * an aggregated price level showing the total amount available at a specific price. Rows are
 * sorted by price with best prices first (ascending for asks, descending for bids).
 *
 * @package Soneso\StellarSDK\Responses\OrderBook
 * @see OrderBookRowResponse For individual price level details
 * @see OrderBookResponse For the complete order book
 */
class OrderBookRowsResponse extends \IteratorIterator
{

    /**
     * Constructs a new order book rows collection
     *
     * @param OrderBookRowResponse ...$rows Variable number of order book row responses
     */
    public function __construct(OrderBookRowResponse ...$rows)
    {
        parent::__construct(new \ArrayIterator($rows));
    }

    /**
     * Gets the current order book row in the iteration
     *
     * @return OrderBookRowResponse The current row response
     */
    public function current(): OrderBookRowResponse
    {
        return parent::current();
    }

    /**
     * Adds an order book row to the collection
     *
     * @param OrderBookRowResponse $row The order book row to add
     * @return void
     */
    public function add(OrderBookRowResponse $row)
    {
        $this->getInnerIterator()->append($row);
    }

    /**
     * Gets the total number of price levels in this collection
     *
     * @return int The count of order book rows
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * @return array<OrderBookRowResponse>
     */
    public function toArray() : array {
        /**
         * @var array<OrderBookRowResponse> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}