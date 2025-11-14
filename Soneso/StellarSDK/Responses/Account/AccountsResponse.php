<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Account;

/**
 * Represents a collection of accounts
 *
 * This iterable collection holds multiple account responses returned by list endpoints.
 * The collection is typically part of AccountsPageResponse for paginated results and
 * provides iterator functionality to traverse all account entries.
 *
 * @package Soneso\StellarSDK\Responses\Account
 * @see AccountsPageResponse For paginated account lists
 * @see AccountResponse For individual account entries
 * @since 1.0.0
 */
class AccountsResponse extends \IteratorIterator
{

    /**
     * Creates a new collection of accounts
     *
     * @param AccountResponse ...$accounts Variable number of account entries
     */
    public function __construct(AccountResponse ...$accounts)
    {
        parent::__construct(new \ArrayIterator($accounts));
    }

    /**
     * Gets the current account in the iteration
     *
     * @return AccountResponse The current account entry
     */
    public function current(): AccountResponse
    {
        return parent::current();
    }

    /**
     * Adds an account entry to the collection
     *
     * @param AccountResponse $account The account entry to add
     */
    public function add(AccountResponse $account)
    {
        $this->getInnerIterator()->append($account);
    }

    /**
     * Gets the number of account entries in the collection
     *
     * @return int The count of account entries
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * Converts the collection to an array
     *
     * @return array<AccountResponse> Array of account entries
     */
    public function toArray() : array {
        /**
         * @var array<AccountResponse> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}