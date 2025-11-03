<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrLedgerBounds;

/**
 * Represents ledger number bounds as transaction preconditions
 *
 * LedgerBounds specify a range of ledger numbers during which a transaction
 * is valid. Transactions with ledger bounds will only be accepted if the
 * current ledger number falls within the specified range.
 *
 * This feature was introduced in CAP-21 to provide more granular control
 * over transaction validity based on ledger progression rather than time.
 *
 * @package Soneso\StellarSDK
 * @see https://github.com/stellar/stellar-protocol/blob/master/core/cap-0021.md#specification CAP-21 specification
 * @see https://developers.stellar.org/docs/encyclopedia/transactions/transaction-anatomy#preconditions Documentation on preconditions
 */
class LedgerBounds
{
    /**
     * @var int
     */
    private int $minLedger;

    /**
     * @var int
     */
    private int $maxLedger;

    /**
     * LedgerBounds constructor
     *
     * @param int $minLedger The minimum ledger number (inclusive)
     * @param int $maxLedger The maximum ledger number (inclusive)
     */
    public function __construct(int $minLedger, int $maxLedger)
    {
        $this->minLedger = $minLedger;
        $this->maxLedger = $maxLedger;
    }

    /**
     * Gets the minimum ledger number
     *
     * @return int The minimum ledger number
     */
    public function getMinLedger(): int
    {
        return $this->minLedger;
    }

    /**
     * Sets the minimum ledger number
     *
     * @param int $minLedger The minimum ledger number
     * @return void
     */
    public function setMinLedger(int $minLedger): void
    {
        $this->minLedger = $minLedger;
    }

    /**
     * Gets the maximum ledger number
     *
     * @return int The maximum ledger number
     */
    public function getMaxLedger(): int
    {
        return $this->maxLedger;
    }

    /**
     * Sets the maximum ledger number
     *
     * @param int $maxLedger The maximum ledger number
     * @return void
     */
    public function setMaxLedger(int $maxLedger): void
    {
        $this->maxLedger = $maxLedger;
    }

    /**
     * Converts these ledger bounds to XDR format
     *
     * @return XdrLedgerBounds The XDR representation of these ledger bounds
     */
    public function toXdr(): XdrLedgerBounds {
        return new XdrLedgerBounds($this->getMinLedger(), $this->getMaxLedger());
    }


    /**
     * Creates LedgerBounds from XDR format
     *
     * @param XdrLedgerBounds $xdr The XDR encoded ledger bounds
     * @return LedgerBounds The decoded ledger bounds object
     */
    public static function fromXdr(XdrLedgerBounds $xdr) : LedgerBounds
    {
        return new LedgerBounds($xdr->getMinLedger(), $xdr->getMaxLedger());
    }

}