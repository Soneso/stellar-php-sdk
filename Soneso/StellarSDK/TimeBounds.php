<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use DateTime;
use Soneso\StellarSDK\Xdr\XdrTimeBounds;

/**
 * Represents a time range
 *
 * Optional Struct with fields:
 *  minTime Uint64
 *  maxTime Uint64
 *
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
     * @param DateTime $minTime
     * @param DateTime $maxTime
     */
    public function __construct(DateTime $minTime, DateTime $maxTime)
    {
        $this->minTime = $minTime;
        $this->maxTime = $maxTime;
    }

    /**
     * @return XdrTimeBounds
     */
    public function toXdr(): XdrTimeBounds {
        return new XdrTimeBounds($this->getMinTime(), $this->getMaxTime());
    }

    /**
     * @param XdrTimeBounds $xdrTimeBounds
     * @return TimeBounds
     */
    public static function fromXdr(XdrTimeBounds $xdrTimeBounds) : TimeBounds
    {
        return new TimeBounds($xdrTimeBounds->getMinTime(), $xdrTimeBounds->getMaxTime());
    }

    /**
     * @return DateTime
     */
    public function getMinTime(): DateTime
    {
        return $this->minTime;
    }

    /**
     * @return DateTime
     */
    public function getMaxTime(): DateTime
    {
        return $this->maxTime;
    }
}