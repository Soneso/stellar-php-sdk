<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\LiquidityPools;

/**
 * Iterable collection of liquidity pool reserve responses
 *
 * This class provides an iterable wrapper around a collection of ReserveResponse objects
 * representing the asset reserves held in a liquidity pool. Each reserve represents one asset
 * in the pool with its amount. For constant product pools, there are typically two reserves
 * corresponding to the two assets in the trading pair.
 *
 * The collection supports iteration, counting, array conversion, and dynamic addition of reserve
 * records. Used by LiquidityPoolResponse to represent the pool's asset holdings.
 *
 * @package Soneso\StellarSDK\Responses\LiquidityPools
 * @see ReserveResponse For individual reserve details
 * @see LiquidityPoolResponse For the parent liquidity pool
 */
class ReservesResponse extends \IteratorIterator
{

    /**
     * Constructs a new reserves collection
     *
     * @param ReserveResponse ...$responses Variable number of reserve responses
     */
    public function __construct(ReserveResponse ...$responses)
    {
        parent::__construct(new \ArrayIterator($responses));
    }

    /**
     * Gets the current reserve in the iteration
     *
     * @return ReserveResponse The current reserve response
     */
    public function current(): ReserveResponse
    {
        return parent::current();
    }

    /**
     * Adds a reserve response to the collection
     *
     * @param ReserveResponse $response The reserve response to add
     * @return void
     */
    public function add(ReserveResponse $response)
    {
        $this->getInnerIterator()->append($response);
    }

    /**
     * Gets the total number of reserves in this collection
     *
     * @return int The count of reserves
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * @return array<ReserveResponse>
     */
    public function toArray() : array {
        /**
         * @var array<ReserveResponse> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}