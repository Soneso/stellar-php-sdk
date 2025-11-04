<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Transaction;

/**
 * Represents a collection of transaction signatures
 *
 * This iterable collection holds the base64-encoded signatures attached to a transaction.
 * Each signature is produced by a private key signing the transaction hash, proving
 * authorization from the corresponding account or signer.
 *
 * Transactions require sufficient signature weight to meet the threshold requirements of
 * the source account and any operation source accounts. Multiple signatures may be needed
 * for multi-signature accounts or when extra signers are specified in preconditions.
 *
 * The collection implements IteratorIterator, allowing direct iteration over signature strings.
 * Signatures can be added dynamically and the collection can be converted to an array.
 *
 * @package Soneso\StellarSDK\Responses\Transaction
 * @see TransactionResponse For transactions containing signatures
 * @see FeeBumpTransactionResponse For fee-bump transaction signatures
 * @see InnerTransactionResponse For inner transaction signatures
 * @since 1.0.0
 */
class TransactionSignaturesResponse extends \IteratorIterator
{

    /**
     * Creates a new transaction signatures collection
     *
     * @param string ...$signatures Variable number of base64-encoded signature strings
     */
    public function __construct(string ...$signatures)
    {
        parent::__construct(new \ArrayIterator($signatures));
    }

    /**
     * Gets the current signature in the iteration
     *
     * @return string The base64-encoded signature string at the current iterator position
     */
    public function current(): string
    {
        return parent::current();
    }

    /**
     * Adds a signature to the collection
     *
     * @param string $signature The base64-encoded signature string to add
     * @return void
     */
    public function add(string $signature)
    {
        $this->getInnerIterator()->append($signature);
    }

    /**
     * Gets the number of signatures in the collection
     *
     * @return int The signature count
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * Converts the signature collection to an array
     *
     * @return array<string> Array of base64-encoded signature strings
     */
    public function toArray() : array {
        /**
         * @var array<string> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}