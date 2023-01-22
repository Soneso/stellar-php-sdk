<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrInt128Parts
{

    // Both signed and unsigned 128-bit ints
    // are transported in a pair of uint64s
    // to reduce the risk of sign-extension.
    public int $lo;
    public int $hi;

    /**
     * @param int $lo
     * @param int $hi
     */
    public function __construct(int $lo, int $hi)
    {
        $this->lo = $lo;
        $this->hi = $hi;
    }

    public function encode(): string {
        $bytes = XdrEncoder::unsignedInteger64($this->lo);
        $bytes .= XdrEncoder::unsignedInteger64($this->hi);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrInt128Parts {
        return new XdrInt128Parts($xdr->readUnsignedInteger64(), $xdr->readUnsignedInteger64());
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

}