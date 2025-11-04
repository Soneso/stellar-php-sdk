<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Trades;

/**
 * Iterable collection of trade responses
 *
 * This class provides an iterable wrapper around a collection of TradeResponse objects
 * representing executed DEX trades. It extends IteratorIterator to enable efficient traversal
 * of trades returned from Horizon API endpoints. The collection supports iteration, counting,
 * array conversion, and dynamic addition of trade records.
 *
 * Used by TradesPageResponse to hold the trades contained in a single page of results.
 * Each item in the collection represents an executed exchange between two assets with
 * details about the participants, amounts, price, and timing.
 *
 * @package Soneso\StellarSDK\Responses\Trades
 * @see TradeResponse For individual trade details
 * @see TradesPageResponse For paginated trade results
 */
class TradesResponse extends \IteratorIterator
{

    /**
     * Constructs a new trades collection
     *
     * @param TradeResponse ...$trades Variable number of trade responses
     */
    public function __construct(TradeResponse ...$trades)
    {
        parent::__construct(new \ArrayIterator($trades));
    }

    /**
     * Gets the current trade in the iteration
     *
     * @return TradeResponse The current trade response
     */
    public function current(): TradeResponse
    {
        return parent::current();
    }

    /**
     * Adds a trade response to the collection
     *
     * @param TradeResponse $trade The trade response to add
     * @return void
     */
    public function add(TradeResponse $trade)
    {
        $this->getInnerIterator()->append($trade);
    }

    /**
     * Gets the total number of trades in this collection
     *
     * @return int The count of trades
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * @return array<TradeResponse>
     */
    public function toArray() : array {
        /**
         * @var array<TradeResponse> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}