<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrInt256Parts
{
    public int $hiHi;
    public int $hiLo;
    public int $loHi;
    public int $loLo;

    /**
     * @param int $hiHi
     * @param int $hiLo
     * @param int $loHi
     * @param int $loLo
     */
    public function __construct(int $hiHi, int $hiLo, int $loHi, int $loLo)
    {
        $this->hiHi = $hiHi;
        $this->hiLo = $hiLo;
        $this->loHi = $loHi;
        $this->loLo = $loLo;
    }


    public function encode(): string {
        $bytes = XdrEncoder::integer64($this->hiHi);
        $bytes .= XdrEncoder::unsignedInteger64($this->hiLo);
        $bytes .= XdrEncoder::unsignedInteger64($this->loHi);
        $bytes .= XdrEncoder::unsignedInteger64($this->loLo);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrInt256Parts {
        return new XdrInt256Parts($xdr->readInteger64(), $xdr->readUnsignedInteger64(),
            $xdr->readUnsignedInteger64(), $xdr->readUnsignedInteger64());
    }

    /**
     * @return int
     */
    public function getHiHi(): int
    {
        return $this->hiHi;
    }

    /**
     * @param int $hiHi
     */
    public function setHiHi(int $hiHi): void
    {
        $this->hiHi = $hiHi;
    }

    /**
     * @return int
     */
    public function getHiLo(): int
    {
        return $this->hiLo;
    }

    /**
     * @param int $hiLo
     */
    public function setHiLo(int $hiLo): void
    {
        $this->hiLo = $hiLo;
    }

    /**
     * @return int
     */
    public function getLoHi(): int
    {
        return $this->loHi;
    }

    /**
     * @param int $loHi
     */
    public function setLoHi(int $loHi): void
    {
        $this->loHi = $loHi;
    }

    /**
     * @return int
     */
    public function getLoLo(): int
    {
        return $this->loLo;
    }

    /**
     * @param int $loLo
     */
    public function setLoLo(int $loLo): void
    {
        $this->loLo = $loLo;
    }

}