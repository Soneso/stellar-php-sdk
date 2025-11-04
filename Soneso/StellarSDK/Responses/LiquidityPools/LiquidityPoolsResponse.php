<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\LiquidityPools;

/**
 * Iterable collection of liquidity pool responses
 *
 * This class provides an iterable wrapper around a collection of LiquidityPoolResponse objects.
 * It extends IteratorIterator to enable efficient traversal of liquidity pools returned from
 * Horizon API endpoints. The collection supports iteration, counting, array conversion, and
 * dynamic addition of pool records.
 *
 * Used by LiquidityPoolsPageResponse to hold the pools contained in a single page of results.
 * Each item in the collection represents an individual liquidity pool with its reserves, shares,
 * and metadata.
 *
 * @package Soneso\StellarSDK\Responses\LiquidityPools
 * @see LiquidityPoolResponse For individual pool details
 * @see LiquidityPoolsPageResponse For paginated pool results
 */
class LiquidityPoolsResponse extends \IteratorIterator
{

    /**
     * Constructs a new liquidity pools collection
     *
     * @param LiquidityPoolResponse ...$responses Variable number of liquidity pool responses
     */
    public function __construct(LiquidityPoolResponse ...$responses)
    {
        parent::__construct(new \ArrayIterator($responses));
    }

    /**
     * Gets the current liquidity pool in the iteration
     *
     * @return LiquidityPoolResponse The current pool response
     */
    public function current(): LiquidityPoolResponse
    {
        return parent::current();
    }

    /**
     * Adds a liquidity pool response to the collection
     *
     * @param LiquidityPoolResponse $response The pool response to add
     * @return void
     */
    public function add(LiquidityPoolResponse $response)
    {
        $this->getInnerIterator()->append($response);
    }

    /**
     * Gets the total number of liquidity pools in this collection
     *
     * @return int The count of pools
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * @return array<LiquidityPoolResponse>
     */
    public function toArray() : array {
        /**
         * @var array<LiquidityPoolResponse> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}