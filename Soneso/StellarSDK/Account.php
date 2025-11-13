<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use phpseclib3\Math\BigInteger;

/**
 * Represents a Stellar account with sequence number management
 *
 * This class manages account state for transaction building, tracking the sequence
 * number to ensure transactions are properly ordered. Each transaction from an account
 * must use a sequence number exactly one greater than the last used sequence number.
 *
 * The account can be a regular Ed25519 account or a multiplexed (muxed) account that
 * includes an additional ID for virtual account separation.
 *
 * Usage:
 * <code>
 * // Create account with current sequence number
 * $account = new Account("GABC...", new BigInteger("12345678"));
 *
 * // Build transaction (sequence number auto-increments)
 * $transaction = (new TransactionBuilder($account))
 *     ->addOperation($operation)
 *     ->build();
 *
 * // Create from muxed account ID
 * $account = Account::fromAccountId("MABC...", new BigInteger("12345678"));
 * </code>
 *
 * @package Soneso\StellarSDK
 * @see TransactionBuilderAccount Interface this class implements
 * @see MuxedAccount For multiplexed account support
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 */
class Account implements TransactionBuilderAccount
{

    protected string $accountId;
    private BigInteger $sequenceNumber;
    private MuxedAccount $muxedAccount;

    /**
     * Constructs a new Account instance
     *
     * @param string $accountId The Ed25519 public key (G-address) of the account
     * @param BigInteger $sequenceNumber The current sequence number of the account
     * @param int|null $muxedAccountMed25519Id Optional muxed account ID for multiplexed accounts
     */
    public function __construct(string $accountId, BigInteger $sequenceNumber, ?int $muxedAccountMed25519Id = null) {
        $this->accountId = $accountId;
        $this->sequenceNumber = $sequenceNumber;
        $this->muxedAccount = new MuxedAccount($accountId,$muxedAccountMed25519Id);
    }

    /**
     * Creates an Account from an account ID (G-address or M-address)
     *
     * This factory method accepts both regular Ed25519 account IDs (G-addresses)
     * and multiplexed account IDs (M-addresses), automatically parsing the muxed
     * account ID if present.
     *
     * @param string $accountId The account ID (G-address or M-address)
     * @param BigInteger $sequenceNumber The current sequence number of the account
     * @return Account The created Account instance
     */
    public static function fromAccountId(string $accountId, BigInteger $sequenceNumber) : Account {
        $mux = MuxedAccount::fromAccountId($accountId);
        return new Account($mux->getEd25519AccountId(), $sequenceNumber, $mux->getId());
    }

    /**
     * Gets the Ed25519 account ID (G-address)
     *
     * @return string The account ID
     */
    public function getAccountId(): string
    {
        return $this->accountId;
    }

    /**
     * Gets the current sequence number
     *
     * @return BigInteger The current sequence number
     */
    public function getSequenceNumber(): BigInteger
    {
        return $this->sequenceNumber;
    }

    /**
     * Gets the next sequence number without modifying the account state
     *
     * This method calculates what the next sequence number would be without
     * actually incrementing the stored sequence number.
     *
     * @return BigInteger The next sequence number
     * @see incrementSequenceNumber() To actually increment the sequence number
     */
    public function getIncrementedSequenceNumber(): BigInteger
    {
        return $this->sequenceNumber->add(new BigInteger(1));
    }

    /**
     * Increments the account's sequence number
     *
     * This method should be called after successfully submitting a transaction
     * to keep the local sequence number synchronized with the network state.
     *
     * @return void
     * @see getIncrementedSequenceNumber() To preview the next sequence number
     */
    public function incrementSequenceNumber(): void
    {
        $this->sequenceNumber = $this->getIncrementedSequenceNumber();
    }

    /**
     * Gets the muxed account representation
     *
     * @return MuxedAccount The muxed account instance
     */
    public function getMuxedAccount(): MuxedAccount
    {
        return $this->muxedAccount;
    }
}