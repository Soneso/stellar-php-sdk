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

    public static function fromXdr(XdrPrice $xdrPrice) : Price {
        return new Price($xdrPrice->getN(), $xdrPrice->getD());
    }

    public static function fromString(string $price) : Price | false {
        $price = Price::float2fraction($price);
        if ($price) {
            return new Price(intval($price["nominator"]), intval($price["denominator"]));
        }
        return false;
    }

    private static function float2fraction($n, $tolerance = 1.e-9) : array | false {
        $n = (float) $n;
        $h1=1; $h2=0;
        $k1=0; $k2=1;
        $b = 1/$n;
        do {
            $b = 1/$b;
            $a = floor($b);
            $aux = $h1; $h1 = $a*$h1+$h2; $h2 = $aux;
            $aux = $k1; $k1 = $a*$k1+$k2; $k2 = $aux;
            $b = $b-$a;
        } while (abs($n-$h1/$k1) > $n*$tolerance);

        if ( ! empty ( $h1 ) && ! empty ( $k1 ))
            return array ( "nominator" => $h1, "denominator" => $k1, "str_view" => "$h1/$k1" ) ;
        else
            return false;
    }
}