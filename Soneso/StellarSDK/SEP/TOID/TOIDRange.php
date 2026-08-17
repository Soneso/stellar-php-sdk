<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TOID;

/**
 * An inclusive-start, exclusive-end range of encoded SEP-35 operation IDs.
 *
 * This value class is returned by {@see TOID::ledgerRangeInclusive()} to describe the
 * span of encoded 64-bit ID values that correspond to a range of ledgers. The start
 * value is inclusive and the end value is exclusive, so callers should filter with
 * `start <= id < end` when using this range to bound a query.
 *
 * @package Soneso\StellarSDK\SEP\TOID
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0035.md
 * @see TOID
 */
class TOIDRange
{
    private int $start;
    private int $end;

    /**
     * @param int $start the inclusive lower bound of the range, as an encoded ID
     * @param int $end the exclusive upper bound of the range, as an encoded ID
     */
    public function __construct(int $start, int $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * Returns the inclusive lower bound of the range.
     *
     * @return int the smallest encoded ID that belongs to the range
     */
    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * Returns the exclusive upper bound of the range.
     *
     * @return int the smallest encoded ID that no longer belongs to the range
     */
    public function getEnd(): int
    {
        return $this->end;
    }
}
