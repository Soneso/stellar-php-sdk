<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrPrice
{
    private int $n;
    private int $d;

    public function __construct(int $n, int $d) {
        $this->n = $n;
        $this->d = $d;
    }

    /**
     * @return int
     */
    public function getN(): int
    {
        return $this->n;
    }

    /**
     * @return int
     */
    public function getD(): int
    {
        return $this->d;
    }

    public function encode(): string {
        $bytes = XdrEncoder::integer32($this->n);
        $bytes .= XdrEncoder::integer32($this->d);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrPrice {
        $n = $xdr->readInteger32();
        $d = $xdr->readInteger32();
        return new XdrPrice($n,$d);
    }
}