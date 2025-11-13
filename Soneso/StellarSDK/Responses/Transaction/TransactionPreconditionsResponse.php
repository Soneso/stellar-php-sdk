<?php  declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Transaction;

/**
 * Represents transaction preconditions for advanced transaction control
 *
 * Preconditions define constraints that must be satisfied for a transaction to be valid and
 * included in a ledger. These constraints provide fine-grained control over transaction execution
 * timing, sequencing, and authorization beyond basic sequence number requirements.
 *
 * Available precondition types:
 * - Time bounds: Valid time range for transaction inclusion (minTime/maxTime)
 * - Ledger bounds: Valid ledger range for transaction inclusion (minLedger/maxLedger)
 * - Minimum account sequence: Minimum source account sequence number
 * - Minimum account sequence age: Minimum age of source account sequence in seconds
 * - Minimum account sequence ledger gap: Minimum ledger gap since sequence number update
 * - Extra signers: Additional required signers beyond source account
 *
 * Preconditions enable use cases like time-locked transactions, coordinated multi-sig operations,
 * and protection against premature transaction execution.
 *
 * Introduced in Protocol 19 (CAP-21).
 *
 * @package Soneso\StellarSDK\Responses\Transaction
 * @see TransactionResponse For the parent transaction response
 * @see PreconditionsTimeBoundsResponse For time-based validity bounds
 * @see PreconditionsLedgerBoundsResponse For ledger-based validity bounds
 * @see https://developers.stellar.org Stellar developer docs Transaction Preconditions
 * @see https://stellar.org/protocol/cap-21 CAP-21: Generalized Transaction Preconditions
 * @since 1.0.0
 */
class TransactionPreconditionsResponse
{
    private ?PreconditionsTimeBoundsResponse $timeBounds = null;
    private ?PreconditionsLedgerBoundsResponse $ledgerBounds = null;
    private ?string $minAccountSequence = null;
    private ?string $minAccountSequenceAge = null;
    private ?int $minAccountSequenceLedgerGap = null;
    private ?array $extraSigners = null;

    /**
     * Gets the time bounds precondition
     *
     * Returns the time-based validity window for this transaction. If null, the transaction
     * has no time-based constraints.
     *
     * @return PreconditionsTimeBoundsResponse|null The time bounds, or null if not set
     */
    public function getTimeBounds(): ?PreconditionsTimeBoundsResponse
    {
        return $this->timeBounds;
    }

    /**
     * Gets the ledger bounds precondition
     *
     * Returns the ledger-based validity window for this transaction. If null, the transaction
     * has no ledger-based constraints.
     *
     * @return PreconditionsLedgerBoundsResponse|null The ledger bounds, or null if not set
     */
    public function getLedgerBounds(): ?PreconditionsLedgerBoundsResponse
    {
        return $this->ledgerBounds;
    }

    /**
     * Gets the minimum account sequence precondition
     *
     * Returns the minimum sequence number that the source account must have for this
     * transaction to be valid. If null, no minimum sequence constraint is applied.
     *
     * @return string|null The minimum account sequence as a string, or null if not set
     */
    public function getMinAccountSequence(): ?string
    {
        return $this->minAccountSequence;
    }

    /**
     * Gets the minimum account sequence age precondition
     *
     * Returns the minimum age (in seconds) that the source account's sequence number must
     * have been set for this transaction to be valid. If null, no age constraint is applied.
     *
     * @return string|null The minimum sequence age in seconds as a string, or null if not set
     */
    public function getMinAccountSequenceAge(): ?string
    {
        return $this->minAccountSequenceAge;
    }

    /**
     * Gets the minimum account sequence ledger gap precondition
     *
     * Returns the minimum number of ledgers that must have closed since the source account's
     * sequence number was last updated for this transaction to be valid. If null, no ledger
     * gap constraint is applied.
     *
     * @return int|null The minimum ledger gap, or null if not set
     */
    public function getMinAccountSequenceLedgerGap(): ?int
    {
        return $this->minAccountSequenceLedgerGap;
    }

    /**
     * Gets the extra signers precondition
     *
     * Returns the array of additional signer addresses (Ed25519 public keys or pre-authorized
     * transaction hashes) that must sign this transaction beyond the source account signers.
     * If null or empty, no extra signers are required.
     *
     * @return array<string>|null Array of extra signer addresses, or null if not set
     */
    public function getExtraSigners(): ?array
    {
        return $this->extraSigners;
    }

    /**
     * Loads preconditions data from JSON response
     *
     * @param array $json The JSON array containing preconditions data
     * @return void
     */
    protected function loadFromJson(array $json): void
    {
        if (isset($json['timebounds'])) $this->timeBounds = PreconditionsTimeBoundsResponse::fromJson($json['timebounds']);
        if (isset($json['ledgerbounds'])) $this->ledgerBounds = PreconditionsLedgerBoundsResponse::fromJson($json['ledgerbounds']);

        if (isset($json['min_account_sequence'])) $this->minAccountSequence = $json['min_account_sequence'];
        if (isset($json['min_account_sequence_age'])) $this->minAccountSequenceAge = $json['min_account_sequence_age'];
        if (isset($json['min_account_sequence_ledger_gap'])) $this->minAccountSequenceLedgerGap = $json['min_account_sequence_ledger_gap'];

        if (isset($json['extra_signers'])) {
            $this->extraSigners = array();
            foreach ($json['extra_signers'] as $signer) {
                $this->extraSigners[] = $signer;
            }
         }
    }

    /**
     * Creates a TransactionPreconditionsResponse instance from JSON data
     *
     * @param array $json The JSON array containing preconditions data from Horizon
     * @return TransactionPreconditionsResponse The parsed transaction preconditions response
     */
    public static function fromJson(array $json): TransactionPreconditionsResponse
    {
        $result = new TransactionPreconditionsResponse();
        $result->loadFromJson($json);
        return $result;
    }
}