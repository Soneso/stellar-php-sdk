<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\ClaimableBalances;

/**
 * Iterable collection of claimant responses
 *
 * Provides iterator functionality for traversing multiple ClaimantResponse objects.
 * Represents all eligible claimants for a claimable balance.
 *
 * @package Soneso\StellarSDK\Responses\ClaimableBalances
 * @see ClaimantResponse For individual claimant details
 * @since 1.0.0
 */
class ClaimantsResponse extends \IteratorIterator
{

    /**
     * Constructs a claimants collection
     *
     * @param ClaimantResponse ...$claimants Variable number of claimant responses
     */
    public function __construct(ClaimantResponse ...$claimants)
    {
        parent::__construct(new \ArrayIterator($claimants));
    }

    /**
     * Gets the current claimant in the iteration
     *
     * @return ClaimantResponse The current claimant
     */
    public function current(): ClaimantResponse
    {
        return parent::current();
    }

    /**
     * Adds a claimant to the collection
     *
     * @param ClaimantResponse $claimant The claimant to add
     * @return void
     */
    public function add(ClaimantResponse $claimant)
    {
        $this->getInnerIterator()->append($claimant);
    }

    /**
     * Gets the total number of claimants in the collection
     *
     * @return int The count of claimants
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * Converts the collection to an array
     *
     * @return array<ClaimantResponse> Array of claimant responses
     */
    public function toArray() : array {
        /**
         * @var array<ClaimantResponse> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}