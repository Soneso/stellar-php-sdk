<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrPrice;

/**
 * Represents a price as a rational number (fraction) in the Stellar network
 *
 * Prices in Stellar are represented as fractions with a numerator (n) and
 * denominator (d) to avoid floating-point precision issues. This is commonly
 * used in trading operations to specify exchange rates between assets.
 *
 * For example, a price of 1.5 would be represented as n=3, d=2 (3/2).
 *
 * @package Soneso\StellarSDK
 * @see https://developers.stellar.org/docs/encyclopedia/operations/manage-buy-offer Documentation on offer operations
 */
class Price
{
    private int $n;
    private int $d;

    /**
     * Price constructor
     *
     * @param int $n The numerator of the price fraction
     * @param int $d The denominator of the price fraction
     */
    public function __construct(int $n, int $d) {
        $this->n = $n;
        $this->d = $d;
    }

    /**
     * Gets the numerator of the price fraction
     *
     * @return int The numerator value
     */
    public function getN(): int
    {
        return $this->n;
    }

    /**
     * Gets the denominator of the price fraction
     *
     * @return int The denominator value
     */
    public function getD(): int
    {
        return $this->d;
    }

    /**
     * Converts this price to XDR format
     *
     * @return XdrPrice The XDR representation of this price
     */
    public function toXdr() : XdrPrice {
        return new XdrPrice($this->n, $this->d);
    }

    /**
     * Creates a Price from XDR format
     *
     * @param XdrPrice $xdrPrice The XDR encoded price
     * @return Price The decoded price object
     */
    public static function fromXdr(XdrPrice $xdrPrice) : Price {
        return new Price($xdrPrice->getN(), $xdrPrice->getD());
    }

    /**
     * Creates a Price from a decimal string
     *
     * Converts a decimal price (e.g., "1.5") into a rational fraction using
     * continued fraction approximation to maintain precision. The approximation
     * uses a tolerance of 1.e-9, which may result in precision limitations for
     * certain decimal values. The resulting numerator and denominator are
     * constrained by 32-bit integer limits.
     *
     * @param string $price The decimal price as a string
     * @return Price|false The price object, or false if conversion fails
     */
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