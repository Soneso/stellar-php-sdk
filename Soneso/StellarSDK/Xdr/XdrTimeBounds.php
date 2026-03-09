<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use DateTime;

class XdrTimeBounds extends XdrTimeBoundsBase
{

    private DateTime $minTime;
    private DateTime $maxTime;

    public function __construct(DateTime $minTime, DateTime $maxTime)
    {
        $this->minTime = $minTime;
        $this->maxTime = $maxTime;
        parent::__construct(
            (int)$minTime->format('U'),
            (int)$maxTime->format('U')
        );
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

    public static function decode(XdrBuffer $xdr): static
    {
        return new static(
            DateTime::createFromFormat('U', strval($xdr->readUnsignedInteger64())),
            DateTime::createFromFormat('U', strval($xdr->readUnsignedInteger64()))
        );
    }
}
