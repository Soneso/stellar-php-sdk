<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLedgerBounds
{

    /**
     * @var int
     */
    private int $minLedger;

    /**
     * @var int
     */
    private int $maxLedger;

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
     * @return int
     */
    public function getMaxLedger(): int
    {
        return $this->maxLedger;
    }

    /**
     * @param int $minLedger
     */
    public function setMinLedger(int $minLedger): void
    {
        $this->minLedger = $minLedger;
    }

    /**
     * @param int $maxLedger
     */
    public function setMaxLedger(int $maxLedger): void
    {
        $this->maxLedger = $maxLedger;
    }

    /**
     * @return string
     */
    public function encode(): string
    {
        $bytes = XdrEncoder::unsignedInteger32($this->getMinLedger());
        $bytes .= XdrEncoder::unsignedInteger32($this->getMaxLedger());
        return $bytes;
    }

    /**
     * @param XdrBuffer $xdr
     * @return XdrLedgerBounds
     */
    public static function decode(XdrBuffer $xdr) : XdrLedgerBounds
    {
        return new XdrLedgerBounds($xdr->readUnsignedInteger32(),$xdr->readUnsignedInteger32());
    }
}