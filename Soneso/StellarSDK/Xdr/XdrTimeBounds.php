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

    /**
     * Override fromTxRep to supply DateTime objects as required by this constructor.
     *
     * @param array<string,string> $map
     * @param string               $prefix
     * @return static
     */
    public static function fromTxRep(array $map, string $prefix): static
    {
        $minTimestamp = TxRepHelper::parseInt(TxRepHelper::getValue($map, $prefix . '.minTime') ?? '0');
        $maxTimestamp = TxRepHelper::parseInt(TxRepHelper::getValue($map, $prefix . '.maxTime') ?? '0');
        $minDt = (new DateTime())->setTimestamp($minTimestamp);
        $maxDt = (new DateTime())->setTimestamp($maxTimestamp);
        return new static($minDt, $maxDt);
    }
}
