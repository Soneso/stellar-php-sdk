<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Account;

/**
 * Represents a collection of account signers
 *
 * This iterable collection holds all signers configured for an account. Signers enable
 * multi-signature functionality by allowing multiple keys to authorize transactions.
 * The collection is included in AccountResponse and provides iterator functionality.
 *
 * @package Soneso\StellarSDK\Responses\Account
 * @see AccountResponse For the parent account details
 * @see AccountSignerResponse For individual signer entries
 * @see AccountThresholdsResponse For threshold requirements
 * @since 1.0.0
 */
class AccountSignersResponse extends \IteratorIterator
{

    /**
     * Creates a new collection of account signers
     *
     * @param AccountSignerResponse ...$signers Variable number of signer entries
     */
    public function __construct(AccountSignerResponse ...$signers)
    {
        parent::__construct(new \ArrayIterator($signers));
    }

    /**
     * Gets the current signer in the iteration
     *
     * @return AccountSignerResponse The current signer entry
     */
    public function current(): AccountSignerResponse
    {
        return parent::current();
    }

    /**
     * Adds a signer entry to the collection
     *
     * @param AccountSignerResponse $signer The signer entry to add
     */
    public function add(AccountSignerResponse $signer)
    {
        $this->getInnerIterator()->append($signer);
    }

    /**
     * Gets the number of signer entries in the collection
     *
     * @return int The count of signer entries
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * Converts the collection to an array
     *
     * @return array<AccountSignerResponse> Array of signer entries
     */
    public function toArray() : array {
        /**
         * @var array<AccountSignerResponse> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}