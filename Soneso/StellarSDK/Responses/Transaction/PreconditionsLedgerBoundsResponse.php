<?php  declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Transaction;

/**
 * Represents ledger-based validity bounds for transaction preconditions
 *
 * Ledger bounds specify a range of ledger sequence numbers within which a transaction
 * is valid. The transaction can only be included in ledgers with sequence numbers
 * between minLedger and maxLedger (inclusive). This provides deterministic transaction
 * lifetime control based on network state rather than time.
 *
 * Ledger bounds are useful when you need precise control over transaction execution
 * windows and want to avoid time-based uncertainties. A value of 0 for either bound
 * indicates no constraint in that direction.
 *
 * Introduced in Protocol 19 as part of CAP-21 transaction preconditions.
 *
 * @package Soneso\StellarSDK\Responses\Transaction
 * @see TransactionPreconditionsResponse For the parent preconditions container
 * @see PreconditionsTimeBoundsResponse For time-based validity bounds
 * @see https://developers.stellar.org/docs/encyclopedia/transactions-specialized/transaction-preconditions Transaction Preconditions
 * @see https://stellar.org/protocol/cap-21 CAP-21: Generalized Transaction Preconditions
 * @since 1.0.0
 */
class PreconditionsLedgerBoundsResponse
{
    private int $minLedger;
    private int $maxLedger;

    /**
     * Gets the minimum ledger sequence number for transaction validity
     *
     * Returns the minimum ledger sequence in which this transaction can be included.
     * If 0, there is no lower bound and the transaction can be included in any ledger
     * (subject to the maxLedger constraint).
     *
     * @return int The minimum ledger sequence, or 0 for no constraint
     */
    public function getMinLedger(): int
    {
        return $this->minLedger;
    }

    /**
     * Gets the maximum ledger sequence number for transaction validity
     *
     * Returns the maximum ledger sequence in which this transaction can be included.
     * If 0, there is no upper bound and the transaction remains valid indefinitely
     * (subject to the minLedger constraint).
     *
     * @return int The maximum ledger sequence, or 0 for no constraint
     */
    public function getMaxLedger(): int
    {
        return $this->maxLedger;
    }

    /**
     * Loads ledger bounds data from JSON response
     *
     * @param array $json The JSON array containing ledger bounds data
     * @return void
     */
    protected function loadFromJson(array $json): void
    {
        if (isset($json['min_ledger'])) {
            $this->minLedger = $json['min_ledger'];
        } else {
            $this->minLedger = 0;
        }
        if (isset($json['max_ledger'])) {
            $this->maxLedger = $json['max_ledger'];
        } else {
            $this->maxLedger = 0;
        }
    }

    /**
     * Creates a PreconditionsLedgerBoundsResponse instance from JSON data
     *
     * @param array $json The JSON array containing ledger bounds data from Horizon
     * @return PreconditionsLedgerBoundsResponse The parsed ledger bounds response
     */
    public static function fromJson(array $json): PreconditionsLedgerBoundsResponse
    {
        $result = new PreconditionsLedgerBoundsResponse();
        $result->loadFromJson($json);
        return $result;
    }
}