<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Ledger;

/**
 * Iterable collection of ledger responses
 *
 * Provides iterator functionality for traversing multiple LedgerResponse objects.
 * Supports adding ledgers, counting, and converting to array.
 *
 * @package Soneso\StellarSDK\Responses\Ledger
 * @see LedgerResponse For individual ledger details
 * @since 1.0.0
 */
class LedgersResponse extends \IteratorIterator {

    /**
     * Constructs a ledgers collection
     *
     * @param LedgerResponse ...$ledgers Variable number of ledger responses
     */
    public function __construct(LedgerResponse ...$ledgers)
    {
        parent::__construct(new \ArrayIterator($ledgers));
    }

    /**
     * Gets the current ledger in the iteration
     *
     * @return LedgerResponse The current ledger
     */
    public function current(): LedgerResponse
    {
        return parent::current();
    }

    /**
     * Adds a ledger to the collection
     *
     * @param LedgerResponse $ledger The ledger to add
     * @return void
     */
    public function add(LedgerResponse $ledger)
    {
        $this->getInnerIterator()->append($ledger);
    }

    /**
     * Gets the total number of ledgers in the collection
     *
     * @return int The count of ledgers
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * Converts the collection to an array
     *
     * @return array<LedgerResponse> Array of ledger responses
     */
    public function toArray() : array {
        /**
         * @var array<LedgerResponse> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}