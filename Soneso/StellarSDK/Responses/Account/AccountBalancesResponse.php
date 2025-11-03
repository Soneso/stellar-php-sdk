<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Account;

/**
 * Represents a collection of account balances
 *
 * This iterable collection holds all asset balances for an account including native XLM
 * and all trustline balances. The collection is included in AccountResponse and provides
 * iterator functionality to traverse all balance entries.
 *
 * @package Soneso\StellarSDK\Responses\Account
 * @see AccountResponse For the parent account details
 * @see AccountBalanceResponse For individual balance entries
 * @since 1.0.0
 */
class AccountBalancesResponse extends \IteratorIterator {

    /**
     * Creates a new collection of account balances
     *
     * @param AccountBalanceResponse ...$balances Variable number of balance entries
     */
    public function __construct(AccountBalanceResponse ...$balances) {
        parent::__construct(new \ArrayIterator($balances));
    }

    /**
     * Gets the current balance in the iteration
     *
     * @return AccountBalanceResponse The current balance entry
     */
    public function current() : AccountBalanceResponse {
        return parent::current();
    }

    /**
     * Adds a balance entry to the collection
     *
     * @param AccountBalanceResponse $balance The balance entry to add
     */
    public function add(AccountBalanceResponse $balance) {
        $this->getInnerIterator()->append($balance);
    }

    /**
     * Gets the number of balance entries in the collection
     *
     * @return int The count of balance entries
     */
    public function count() : int {
        return $this->getInnerIterator()->count();
    }

    /**
     * Converts the collection to an array
     *
     * @return array<AccountBalanceResponse> Array of balance entries
     */
    public function toArray() : array {
        /**
         * @var array<AccountBalanceResponse> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}