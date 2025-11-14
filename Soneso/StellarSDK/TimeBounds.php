<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use DateTime;
use Soneso\StellarSDK\Xdr\XdrTimeBounds;

/**
 * Represents the time validity bounds for a transaction
 *
 * TimeBounds specify the time window during which a transaction is valid.
 * Transactions with time bounds will only be accepted by the network if
 * the ledger close time falls within the specified range.
 *
 * Using time bounds is recommended for security purposes to prevent
 * transaction replay attacks and to ensure transactions expire if not
 * processed within an expected timeframe.
 *
 * @package Soneso\StellarSDK
 * @see https://developers.stellar.org Stellar developer docs Documentation on time bounds
 */
class TimeBounds
{
    /**
     * @var DateTime
     */
    private DateTime $minTime;

    /**
     * @var DateTime
     */
    private DateTime $maxTime;

    /**
     * TimeBounds constructor
     *
     * @param DateTime $minTime The earliest time the transaction is valid (inclusive)
     * @param DateTime $maxTime The latest time the transaction is valid (inclusive)
     */
    public function __construct(DateTime $minTime, DateTime $maxTime)
    {
        $this->minTime = $minTime;
        $this->maxTime = $maxTime;
    }

    /**
     * Converts these time bounds to XDR format
     *
     * @return XdrTimeBounds The XDR representation of these time bounds
     */
    public function toXdr(): XdrTimeBounds {
        return new XdrTimeBounds($this->getMinTime(), $this->getMaxTime());
    }

    /**
     * Creates TimeBounds from XDR format
     *
     * @param XdrTimeBounds $xdrTimeBounds The XDR encoded time bounds
     * @return TimeBounds The decoded time bounds object
     */
    public static function fromXdr(XdrTimeBounds $xdrTimeBounds) : TimeBounds
    {
        return new TimeBounds($xdrTimeBounds->getMinTime(), $xdrTimeBounds->getMaxTime());
    }

    /**
     * Gets the minimum time (earliest validity)
     *
     * @return DateTime The earliest time the transaction is valid
     */
    public function getMinTime(): DateTime
    {
        return $this->minTime;
    }

    /**
     * Gets the maximum time (latest validity)
     *
     * @return DateTime The latest time the transaction is valid
     */
    public function getMaxTime(): DateTime
    {
        return $this->maxTime;
    }
}