<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\ClaimableBalances;

/**
 * Iterable collection of claimable balance responses
 *
 * Provides iterator functionality for traversing multiple ClaimableBalanceResponse objects.
 * Supports adding claimable balances, counting, and converting to array.
 *
 * @package Soneso\StellarSDK\Responses\ClaimableBalances
 * @see ClaimableBalanceResponse For individual claimable balance details
 * @since 1.0.0
 */
class ClaimableBalancesResponse extends \IteratorIterator
{

    /**
     * Constructs a claimable balances collection
     *
     * @param ClaimableBalanceResponse ...$response Variable number of claimable balance responses
     */
    public function __construct(ClaimableBalanceResponse ...$response)
    {
        parent::__construct(new \ArrayIterator($response));
    }

    /**
     * Gets the current claimable balance in the iteration
     *
     * @return ClaimableBalanceResponse The current claimable balance
     */
    public function current(): ClaimableBalanceResponse
    {
        return parent::current();
    }

    /**
     * Adds a claimable balance to the collection
     *
     * @param ClaimableBalanceResponse $response The claimable balance to add
     * @return void
     */
    public function add(ClaimableBalanceResponse $response)
    {
        $this->getInnerIterator()->append($response);
    }

    /**
     * Gets the total number of claimable balances in the collection
     *
     * @return int The count of claimable balances
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * Converts the collection to an array
     *
     * @return array<ClaimableBalanceResponse> Array of claimable balance responses
     */
    public function toArray() : array {
        /**
         * @var array<ClaimableBalanceResponse> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}