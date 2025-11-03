<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\ClaimableBalances;

/**
 * Iterable collection of claimant predicate responses
 *
 * Provides iterator functionality for traversing multiple ClaimantPredicateResponse objects.
 * Used for logical combinations like AND and OR predicates.
 *
 * @package Soneso\StellarSDK\Responses\ClaimableBalances
 * @see ClaimantPredicateResponse For individual predicate details
 * @since 1.0.0
 */
class ClaimantPredicatesResponse extends \IteratorIterator
{

    /**
     * Constructs a claimant predicates collection
     *
     * @param ClaimantPredicateResponse ...$predicates Variable number of predicate responses
     */
    public function __construct(ClaimantPredicateResponse ...$predicates)
    {
        parent::__construct(new \ArrayIterator($predicates));
    }

    /**
     * Gets the current predicate in the iteration
     *
     * @return ClaimantPredicateResponse The current predicate
     */
    public function current(): ClaimantPredicateResponse
    {
        return parent::current();
    }

    /**
     * Adds a predicate to the collection
     *
     * @param ClaimantPredicateResponse $predicates The predicate to add
     * @return void
     */
    public function add(ClaimantPredicateResponse $predicates)
    {
        $this->getInnerIterator()->append($predicates);
    }

    /**
     * Gets the total number of predicates in the collection
     *
     * @return int The count of predicates
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * Converts the collection to an array
     *
     * @return array<ClaimantPredicateResponse> Array of predicate responses
     */
    public function toArray() : array {
        /**
         * @var array<ClaimantPredicateResponse> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}