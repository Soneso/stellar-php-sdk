<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use InvalidArgumentException;
use Soneso\StellarSDK\Constants\StellarConstants;

/**
 * Builder for creating fee bump transactions.
 *
 * Fee bump transactions allow a third party to pay the fee for an existing
 * transaction, enabling sponsored transactions and fee abstraction patterns.
 * The fee bump wraps an inner transaction and increases its fee to ensure
 * it gets included in the ledger when the original fee is too low.
 *
 * The new fee must be higher than the inner transaction's fee. The fee bump
 * transaction replaces the inner transaction in the transaction queue.
 *
 * Example usage:
 * ```php
 * $innerTx = ...; // Existing transaction
 * $feeBump = (new FeeBumpTransactionBuilder($innerTx))
 *     ->setFeeAccount("GABC...")
 *     ->setBaseFee(200)
 *     ->build();
 * ```
 *
 * @package Soneso\StellarSDK
 * @see FeeBumpTransaction
 * @see Transaction
 * @link https://developers.stellar.org/docs/encyclopedia/fee-bump-transactions
 */
class FeeBumpTransactionBuilder
{
    /**
     * The inner transaction to be fee-bumped.
     *
     * @var Transaction
     */
    private Transaction $inner;

    /**
     * The maximum fee willing to pay (calculated from base fee).
     *
     * This is the total fee for the fee bump transaction, which includes
     * the inner transaction's operations plus the fee bump operation itself.
     *
     * @var int|null
     */
    private ?int $baseFee = null;

    /**
     * The account that will pay the fee for the fee bump transaction.
     *
     * @var MuxedAccount|null
     */
    private ?MuxedAccount $feeAccount = null;

    /**
     * Constructs a new fee bump transaction builder.
     *
     * @param Transaction $inner The inner transaction to be fee-bumped
     */
    public function __construct(Transaction $inner) {
        $this->inner = $inner;
    }

    /**
     * Sets the account that will pay the fee for the fee bump transaction.
     *
     * The fee account is the source account for the fee bump transaction and
     * must have sufficient balance to cover the new fee. This can be different
     * from the inner transaction's source account.
     *
     * @param string $feeAccountId The account ID (public key) in StrKey format
     *
     * @return FeeBumpTransactionBuilder This builder instance for method chaining
     */
    public function setFeeAccount(string $feeAccountId) : FeeBumpTransactionBuilder {
        $this->feeAccount = MuxedAccount::fromAccountId($feeAccountId);
        return $this;
    }

    /**
     * Sets the muxed account that will pay the fee for the fee bump transaction.
     *
     * Similar to setFeeAccount but accepts a MuxedAccount directly, allowing
     * for multiplexed accounts that share the same underlying account ID.
     *
     * @param MuxedAccount $feeAccount The muxed account that will pay the fee
     *
     * @return FeeBumpTransactionBuilder This builder instance for method chaining
     */
    public function setMuxedFeeAccount(MuxedAccount $feeAccount) : FeeBumpTransactionBuilder {
        $this->feeAccount = $feeAccount;
        return $this;
    }

    /**
     * Sets the base fee per operation for the fee bump transaction.
     *
     * The base fee is the amount willing to pay per operation, in stroops.
     * The total fee is calculated as: baseFee * (inner operations + 1).
     * This fee must be higher than the inner transaction's base fee.
     *
     * @param int $baseFee The base fee per operation in stroops (minimum 100)
     *
     * @return FeeBumpTransactionBuilder This builder instance for method chaining
     *
     * @throws InvalidArgumentException If base fee is less than minimum (100 stroops)
     * @throws InvalidArgumentException If base fee is lower than inner transaction's base fee
     * @throws InvalidArgumentException If calculated max fee overflows 64-bit integer
     */
    public function setBaseFee(int $baseFee) : FeeBumpTransactionBuilder {
        if ($baseFee < StellarConstants::MIN_BASE_FEE_STROOPS) {
            throw new InvalidArgumentException("base fee can not be smaller than ".StellarConstants::MIN_BASE_FEE_STROOPS);
        }
        $innerBaseFee = $this->inner->getFee();
        $nrOfOperations = count($this->inner->getOperations());
        if ($nrOfOperations > 0) {
            $innerBaseFee = round($innerBaseFee / $nrOfOperations);
        }
        if ($baseFee < $innerBaseFee) {
            throw new InvalidArgumentException("base fee cannot be lower than provided inner transaction base fee");
        }
        $maxFee = $baseFee * ($nrOfOperations + 1);
        if ($maxFee < 0) {
            throw new InvalidArgumentException("fee overflows 64 bit int");
        }
        $this->baseFee = $maxFee;
        return $this;
    }

    /**
     * Builds and returns the fee bump transaction.
     *
     * Creates a FeeBumpTransaction with the configured fee account and base fee
     * wrapping the inner transaction. Both fee account and base fee must be set
     * before calling this method.
     *
     * @return FeeBumpTransaction The constructed fee bump transaction
     *
     * @throws \RuntimeException If fee account has not been set
     * @throws \RuntimeException If base fee has not been set
     */
    public function build() : FeeBumpTransaction
    {
        if (!$this->feeAccount) {
            throw new \RuntimeException("fee account has to be set. you must call setFeeAccount().");
        }
        if (!$this->baseFee) {
            throw new \RuntimeException("base fee has to be set. you must call setBaseFee().");
        }
        return new FeeBumpTransaction($this->feeAccount, $this->baseFee, $this->inner);
    }
}