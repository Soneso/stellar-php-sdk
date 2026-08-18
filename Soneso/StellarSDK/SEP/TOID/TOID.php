<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TOID;

use InvalidArgumentException;
use OverflowException;

/**
 * SEP-35 operation ID (also known as Horizon's "TOID": total order ID).
 *
 * SEP-35 defines the ID scheme used for Stellar operations in historical ledger data.
 * An ID is a single signed 64-bit integer that packs three big-endian bit fields:
 * a 32-bit ledger sequence, a 20-bit transaction application order, and a 12-bit
 * operation index, encoded as
 * `id = (ledgerSequence << 32) | (transactionOrder << 12) | operationIndex`.
 * The sign bit of the encoded value is never set for valid field values, which keeps
 * IDs usable directly as SQL bigint primary keys and cursors.
 *
 * IDs are represented with PHP's native int type, which requires a 64-bit PHP build;
 * this SDK assumes a 64-bit build throughout.
 *
 * On the network, transaction application order and operation index are assigned
 * starting at 1. Nonetheless, this class accepts 0 for both fields, since 0 is a
 * valid encoded value and is required to express range boundaries such as
 * `new TOID($ledgerSequence, 0, 0)`. Ledger sequence 0 is accepted for the same
 * reason by the constructor and by {@see TOID::afterLedger()}. {@see
 * TOID::ledgerRangeInclusive()} is stricter: it requires `$from >= 1`, because the
 * network's first ledger is 1 and a smaller range start is a caller error rather than
 * a valid boundary. Separately, that method special-cases `$from === 1` by pulling the
 * computed range start down to 0, so that IDs encoded with ledger field 0 still fall
 * inside the lowest range.
 *
 * @package Soneso\StellarSDK\SEP\TOID
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0035.md
 * @see TOIDRange
 */
class TOID
{
    /**
     * Largest ledger sequence encodable in the 32-bit ledger sequence field.
     */
    public const MAX_LEDGER_SEQUENCE = 2147483647;

    /**
     * Largest transaction application order encodable in the 20-bit field.
     */
    public const MAX_TRANSACTION_ORDER = 1048575;

    /**
     * Largest operation index encodable in the 12-bit field.
     */
    public const MAX_OPERATION_INDEX = 4095;

    private int $ledgerSequence;
    private int $transactionOrder;
    private int $operationIndex;

    /**
     * @param int $ledgerSequence the ledger the operation was validated in, between 0 and 2147483647
     * @param int $transactionOrder the application order of the transaction within the ledger, between 0 and 1048575
     * @param int $operationIndex the index of the operation within the transaction, between 0 and 4095
     *
     * @throws InvalidArgumentException if any field is outside of its valid encoding range
     */
    public function __construct(int $ledgerSequence, int $transactionOrder, int $operationIndex)
    {
        if ($ledgerSequence < 0 || $ledgerSequence > self::MAX_LEDGER_SEQUENCE) {
            throw new InvalidArgumentException(
                'Invalid ledger sequence, it must be between 0 and ' . self::MAX_LEDGER_SEQUENCE . '.'
            );
        }
        if ($transactionOrder < 0 || $transactionOrder > self::MAX_TRANSACTION_ORDER) {
            throw new InvalidArgumentException(
                'Invalid transaction order, it must be between 0 and ' . self::MAX_TRANSACTION_ORDER . '.'
            );
        }
        if ($operationIndex < 0 || $operationIndex > self::MAX_OPERATION_INDEX) {
            throw new InvalidArgumentException(
                'Invalid operation index, it must be between 0 and ' . self::MAX_OPERATION_INDEX . '.'
            );
        }

        $this->ledgerSequence = $ledgerSequence;
        $this->transactionOrder = $transactionOrder;
        $this->operationIndex = $operationIndex;
    }

    /**
     * Returns the ledger sequence field.
     *
     * @return int the ledger the operation was validated in, between 0 and 2147483647
     */
    public function getLedgerSequence(): int
    {
        return $this->ledgerSequence;
    }

    /**
     * Returns the transaction application order field.
     *
     * @return int the order of the transaction within the ledger, between 0 and 1048575
     */
    public function getTransactionOrder(): int
    {
        return $this->transactionOrder;
    }

    /**
     * Returns the operation index field.
     *
     * @return int the index of the operation within the transaction, between 0 and 4095
     */
    public function getOperationIndex(): int
    {
        return $this->operationIndex;
    }

    /**
     * Encodes the three fields into a single signed 64-bit integer, as defined by SEP-35.
     *
     * @return int the encoded ID: `(ledgerSequence << 32) | (transactionOrder << 12) | operationIndex`
     */
    public function toInt64(): int
    {
        return ($this->ledgerSequence << 32) | ($this->transactionOrder << 12) | $this->operationIndex;
    }

    /**
     * Decodes a signed 64-bit encoded ID into its three fields.
     *
     * The ledger sequence is the top 32 bits, `$value >> 32`; the transaction order is
     * the next 20 bits, `($value >> 12) & 0xFFFFF`; and the operation index is the
     * bottom 12 bits, `$value & 0xFFF`. PHP's native int cannot exceed 2^63 - 1, so
     * there is no separate upper bound check: any non-negative int decodes into field
     * values that already satisfy the constructor's valid ranges.
     *
     * @param int $value the encoded ID to decode
     * @return TOID the decoded ID
     *
     * @throws InvalidArgumentException if $value is negative. A negative value has its
     *     sign bit set, which is never valid for an encoded ID, and right-shifting it
     *     would otherwise produce a ledger sequence outside of the encoding domain.
     */
    public static function fromInt64(int $value): TOID
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Invalid encoded ID, it must not be negative.');
        }

        $ledgerSequence = $value >> 32;
        $transactionOrder = ($value >> 12) & 0xFFFFF;
        $operationIndex = $value & 0xFFF;

        return new TOID($ledgerSequence, $transactionOrder, $operationIndex);
    }

    /**
     * Advances this ID to the next operation slot, for use as a cursor while iterating.
     *
     * The operation index is incremented. When it is already at its maximum value of
     * 4095, it is reset to 0 and the ledger sequence is incremented instead; the
     * transaction application order is left unchanged in both cases, since the next
     * operation slot after the last operation index of a transaction is not known to
     * belong to any particular later transaction.
     *
     * @throws OverflowException if the operation index is already 4095 and the ledger
     *     sequence is already 2147483647. Incrementing further would require a state
     *     that the constructor would reject, and encoding it would set the sign bit.
     */
    public function incrementOperationIndex(): void
    {
        if ($this->operationIndex === self::MAX_OPERATION_INDEX) {
            if ($this->ledgerSequence === self::MAX_LEDGER_SEQUENCE) {
                throw new OverflowException(
                    'Cannot increment operation index, the largest encodable ID has already been reached.'
                );
            }
            $this->operationIndex = 0;
            $this->ledgerSequence++;
        } else {
            $this->operationIndex++;
        }
    }

    /**
     * Returns the largest encodable ID within the given ledger.
     *
     * This is useful as an inclusive upper query bound: compare candidate IDs with
     * `<=` against the result. This is the opposite convention from {@see
     * TOID::ledgerRangeInclusive()}, whose end value is exclusive and must be compared
     * with `<`.
     *
     * @param int $ledgerSequence the ledger sequence, between 0 and 2147483647
     * @return TOID the ID `($ledgerSequence, 1048575, 4095)`
     *
     * @throws InvalidArgumentException if $ledgerSequence is outside of its valid range
     */
    public static function afterLedger(int $ledgerSequence): TOID
    {
        return new TOID($ledgerSequence, self::MAX_TRANSACTION_ORDER, self::MAX_OPERATION_INDEX);
    }

    /**
     * Returns the range of encoded IDs covering ledgers $from through $to, inclusive.
     *
     * The range start is `(new TOID($from, 0, 0))->toInt64()`, except when `$from`
     * is 1, in which case the start is 0 instead, so that the range also covers any
     * ID encoded with ledger field 0. The range end is
     * `(new TOID($to + 1, 0, 0))->toInt64()` and is exclusive: callers must compare
     * candidate IDs with `<` against it, not `<=`.
     *
     * @param int $from the first ledger sequence to include, at least 1
     * @param int $to the last ledger sequence to include
     * @return TOIDRange the ID range covering ledgers $from through $to
     *
     * @throws InvalidArgumentException if $from is greater than $to, if $from is less
     *     than 1, or if $to is not strictly less than 2147483647 (the range end is
     *     computed from `$to + 1`, which must still fit within the ledger sequence field)
     */
    public static function ledgerRangeInclusive(int $from, int $to): TOIDRange
    {
        if ($from > $to) {
            throw new InvalidArgumentException('Invalid range, from must not be greater than to.');
        }
        if ($from < 1) {
            throw new InvalidArgumentException('Invalid range start, it must be at least 1.');
        }
        if ($to >= self::MAX_LEDGER_SEQUENCE) {
            throw new InvalidArgumentException(
                'Invalid range end, it must be less than ' . self::MAX_LEDGER_SEQUENCE . '.'
            );
        }

        $start = $from === 1 ? 0 : (new TOID($from, 0, 0))->toInt64();
        $end = (new TOID($to + 1, 0, 0))->toInt64();

        return new TOIDRange($start, $end);
    }
}
