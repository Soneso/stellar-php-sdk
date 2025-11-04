<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Transaction;

/**
 * Represents an iterable collection of transaction responses
 *
 * This collection holds multiple TransactionResponse objects and provides convenient
 * iteration, counting, and array conversion capabilities. It extends IteratorIterator
 * to allow direct use in foreach loops and other iteration contexts.
 *
 * Typically populated within a TransactionsPageResponse when querying transaction lists
 * from Horizon. The collection can be dynamically expanded by adding new transaction
 * responses.
 *
 * Usage patterns:
 * - Iterate: foreach ($transactions as $transaction) { ... }
 * - Count: $transactions->count()
 * - Convert: $array = $transactions->toArray()
 * - Add: $transactions->add($newTransaction)
 *
 * @package Soneso\StellarSDK\Responses\Transaction
 * @see TransactionsPageResponse For paginated transaction results
 * @see TransactionResponse For individual transaction details
 * @since 1.0.0
 */
class TransactionsResponse extends \IteratorIterator
{

    /**
     * Creates a new transactions collection
     *
     * @param TransactionResponse ...$transactions Variable number of transaction response objects
     */
    public function __construct(TransactionResponse ...$transactions)
    {
        parent::__construct(new \ArrayIterator($transactions));
    }

    /**
     * Gets the current transaction in the iteration
     *
     * @return TransactionResponse The transaction at the current iterator position
     */
    public function current(): TransactionResponse
    {
        return parent::current();
    }

    /**
     * Adds a transaction to the collection
     *
     * @param TransactionResponse $transaction The transaction response to add
     * @return void
     */
    public function add(TransactionResponse $transaction)
    {
        $this->getInnerIterator()->append($transaction);
    }

    /**
     * Gets the number of transactions in the collection
     *
     * @return int The transaction count
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * Converts the transaction collection to an array
     *
     * @return array<TransactionResponse> Array of transaction response objects
     */
    public function toArray() : array {
        /**
         * @var array<TransactionResponse> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}