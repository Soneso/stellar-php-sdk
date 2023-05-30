<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrUInt128Parts
{
    public int $hi;
    public int $lo;

    /**
     * @param int $hi
     * @param int $lo
     */
    public function __construct(int $hi, int $lo)
    {
        $this->hi = $hi;
        $this->lo = $lo;
    }


    public function encode(): string {
        $bytes = XdrEncoder::unsignedInteger64($this->hi);
        $bytes .= XdrEncoder::unsignedInteger64($this->lo);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrUInt128Parts {
        return new XdrUInt128Parts($xdr->readUnsignedInteger64(), $xdr->readUnsignedInteger64());
    }

    /**
     * @return int
     */
    public function getHi(): int
    {
        return $this->hi;
    }

    /**
     * @param int $hi
     */
    public function setHi(int $hi): void
    {
        $this->hi = $hi;
    }

    /**
     * @return int
     */
    public function getLo(): int
    {
        return $this->lo;
    }

    /**
     * @param int $lo
     */
    public function setLo(int $lo): void
    {
        $this->lo = $lo;
    }

}