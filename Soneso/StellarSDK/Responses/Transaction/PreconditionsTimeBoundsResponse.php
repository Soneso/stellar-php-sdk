<?php  declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Transaction;

/**
 * Represents time-based validity bounds for transaction preconditions
 *
 * Time bounds specify a time range during which a transaction is valid for submission
 * to the network. The transaction can only be included in ledgers closed between
 * minTime and maxTime (inclusive). Times are represented as Unix timestamps in seconds.
 *
 * Time bounds are the most common form of transaction precondition and help prevent
 * transaction replay attacks and ensure transactions execute within expected timeframes.
 * Null values indicate no constraint in that direction (no minimum or maximum).
 *
 * Available since the early Stellar protocol versions.
 *
 * @package Soneso\StellarSDK\Responses\Transaction
 * @see TransactionPreconditionsResponse For the parent preconditions container
 * @see PreconditionsLedgerBoundsResponse For ledger-based validity bounds
 * @see https://developers.stellar.org/docs/encyclopedia/transactions-specialized/transaction-preconditions Transaction Preconditions
 * @since 1.0.0
 */
class PreconditionsTimeBoundsResponse
{
    private ?string $minTime = null;
    private ?string $maxTime = null;

    /**
     * Gets the minimum time for transaction validity
     *
     * Returns the minimum Unix timestamp (in seconds) after which this transaction
     * becomes valid. If null, the transaction has no minimum time constraint and
     * is valid from creation.
     *
     * @return string|null The minimum Unix timestamp as a string, or null for no constraint
     */
    public function getMinTime(): ?string
    {
        return $this->minTime;
    }

    /**
     * Gets the maximum time for transaction validity
     *
     * Returns the maximum Unix timestamp (in seconds) before which this transaction
     * must be included in a ledger. After this time, the transaction becomes invalid.
     * If null, the transaction has no maximum time constraint.
     *
     * @return string|null The maximum Unix timestamp as a string, or null for no constraint
     */
    public function getMaxTime(): ?string
    {
        return $this->maxTime;
    }

    /**
     * Loads time bounds data from JSON response
     *
     * @param array $json The JSON array containing time bounds data
     * @return void
     */
    protected function loadFromJson(array $json): void
    {
        if (isset($json['min_time'])) $this->minTime = $json['min_time'];
        if (isset($json['max_time'])) $this->maxTime = $json['max_time'];
    }

    /**
     * Creates a PreconditionsTimeBoundsResponse instance from JSON data
     *
     * @param array $json The JSON array containing time bounds data from Horizon
     * @return PreconditionsTimeBoundsResponse The parsed time bounds response
     */
    public static function fromJson(array $json): PreconditionsTimeBoundsResponse
    {
        $result = new PreconditionsTimeBoundsResponse();
        $result->loadFromJson($json);
        return $result;
    }
}