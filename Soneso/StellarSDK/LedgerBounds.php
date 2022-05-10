<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrLedgerBounds;

/**
 * LedgerBounds are Preconditions of a transaction per <a href="https://github.com/stellar/stellar-protocol/blob/master/core/cap-0021.md#specification">CAP-21<a/>
 *
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
     * @param int $minLedger
     * @param int $maxLedger
     */
    public function __construct(int $minLedger, int $maxLedger)
    {
        $this->minLedger = $minLedger;
        $this->maxLedger = $maxLedger;
    }

    /**
     * @return int
     */
    public function getMinLedger(): int
    {
        return $this->minLedger;
    }

    /**
     * @param int $minLedger
     */
    public function setMinLedger(int $minLedger): void
    {
        $this->minLedger = $minLedger;
    }

    /**
     * @return int
     */
    public function getMaxLedger(): int
    {
        return $this->maxLedger;
    }

    /**
     * @param int $maxLedger
     */
    public function setMaxLedger(int $maxLedger): void
    {
        $this->maxLedger = $maxLedger;
    }

    /**
     * @return XdrLedgerBounds
     */
    public function toXdr(): XdrLedgerBounds {
        return new XdrLedgerBounds($this->getMinLedger(), $this->getMaxLedger());
    }


    /**
     * @param XdrLedgerBounds $xdr
     * @return LedgerBounds
     */
    public static function fromXdr(XdrLedgerBounds $xdr) : LedgerBounds
    {
        return new LedgerBounds($xdr->getMinLedger(), $xdr->getMaxLedger());
    }

}