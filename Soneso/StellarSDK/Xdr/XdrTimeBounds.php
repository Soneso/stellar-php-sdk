<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use DateTime;

class XdrTimeBounds
{

    /**
     * @var DateTime
     */
    private DateTime $minTime;

    /**
     * @var DateTime
     */
    private DateTime $maxTime;

    public function __construct(DateTime $minTime, DateTime $maxTime)
    {
        $this->minTime = $minTime;
        $this->maxTime = $maxTime;
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

    public function encode(): string
    {
        $bytes = XdrEncoder::unsignedInteger64($this->getMinTimestamp());
        $bytes .= XdrEncoder::unsignedInteger64($this->getMaxTimestamp());
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrTimeBounds
    {
        return new XdrTimeBounds(DateTime::createFromFormat('U', strval($xdr->readUnsignedInteger64())),
            DateTime::createFromFormat('U', strval($xdr->readUnsignedInteger64())));
    }

    /**
     * @return int
     */
    public function getMinTimestamp() : int
    {
        return (int)$this->minTime->format('U');
    }

    /**
     * @return int
     */
    public function getMaxTimestamp() : int
    {
        return (int)$this->maxTime->format('U');
    }
}