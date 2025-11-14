<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use phpseclib3\Math\BigInteger;

/**
 * Interface for accounts used in transaction building.
 *
 * Provides the contract for accessing account information needed to build
 * and submit transactions. Implementations must provide account identification,
 * sequence number management, and support for muxed accounts.
 *
 * The sequence number is critical for transaction ordering and replay prevention.
 * Each transaction must use a sequence number greater than the account's current
 * sequence number on the ledger.
 *
 * @package Soneso\StellarSDK
 * @see Account
 * @see MuxedAccount
 * @see TransactionBuilder
 * @link https://developers.stellar.org Stellar developer docs
 */
interface TransactionBuilderAccount
{
    /**
     * Gets the account ID (public key) as a string.
     *
     * Returns the account's public key in StrKey format (G... address).
     * This is used as the source account for transactions.
     *
     * @return string The account's public key in StrKey format
     */
    public function getAccountId() : string;

    /**
     * Gets the current sequence number for the account.
     *
     * Returns the account's current sequence number, which represents the
     * number of transactions this account has submitted. The next transaction
     * from this account must use sequence number + 1.
     *
     * @return BigInteger The account's current sequence number
     */
    public function getSequenceNumber() : BigInteger;

    /**
     * Gets the sequence number incremented by one.
     *
     * Returns the next sequence number that should be used for a transaction
     * without modifying the account's internal state. This is useful for
     * previewing the next sequence number before building a transaction.
     *
     * @return BigInteger The sequence number plus one
     */
    public function getIncrementedSequenceNumber() : BigInteger;

    /**
     * Increments the account's sequence number by one.
     *
     * Modifies the account's internal sequence number, typically called after
     * a transaction is successfully built. This ensures subsequent transactions
     * use the correct sequence number.
     *
     * Note: This only updates the local state. The on-ledger sequence number
     * is only updated when the transaction is successfully submitted and included.
     *
     * @return void
     */
    public function incrementSequenceNumber() : void;

    /**
     * Gets the muxed account representation.
     *
     * Returns a MuxedAccount object that wraps the account ID. Muxed accounts
     * allow multiple virtual accounts to share the same underlying account ID,
     * useful for payment routing and account management.
     *
     * @return MuxedAccount The muxed account object
     */
    public function getMuxedAccount() : MuxedAccount;
}