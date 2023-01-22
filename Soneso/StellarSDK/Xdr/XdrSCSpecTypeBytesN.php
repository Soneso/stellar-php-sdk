<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecTypeBytesN
{

    public int $n;

    /**
     * @param int $n
     */
    public function __construct(int $n)
    {
        $this->n = $n;
    }

    public function encode(): string {
        return XdrEncoder::unsignedInteger32($this->n);
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecTypeBytesN {
        return new XdrSCSpecTypeBytesN($xdr->readUnsignedInteger32());
    }

    /**
     * @return int
     */
    public function getN(): int
    {
        return $this->n;
    }

    /**
     * @param int $n
     */
    public function setN(int $n): void
    {
        $this->n = $n;
    }

}