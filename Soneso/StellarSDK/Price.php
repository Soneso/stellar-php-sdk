<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrPrice;

class Price
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

    public function toXdr() : XdrPrice {
        return new XdrPrice($this->n, $this->d);
    }

    //TODO: public static function fromString(string $price) : Price {}

}