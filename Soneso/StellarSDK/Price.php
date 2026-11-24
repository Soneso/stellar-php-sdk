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
 * @see https://developers.stellar.org Stellar developer docs Documentation on offer operations
 */
class Price
{
    private const MAX_INT32 = 2147483647;
    private const DECIMAL_PATTERN = '/^\+?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?$/D';

    /**
     * Price constructor
     *
     * @param int $n The numerator of the price fraction
     * @param int $d The denominator of the price fraction
     */
    public function __construct(
        private int $n,
        private int $d,
    ) {
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
     * Converts a positive decimal price (e.g., "1.5") into a rational fraction
     * using continued fraction approximation to maintain precision. Leading and
     * trailing whitespace (space, \t, \n, \r, \v, \f) is ignored. The
     * approximation uses a relative tolerance of 1.e-9. Returns false if the
     * string is not a positive finite decimal number or if a continued-fraction
     * convergent exceeds 2147483647 in the numerator or the denominator.
     *
     * @param string $price The decimal price as a string
     * @return Price|false The price object, or false if conversion fails
     */
    public static function fromString(string $price) : Price | false {
        $trimmed = trim($price, " \t\n\r\v\f");
        if (preg_match(self::DECIMAL_PATTERN, $trimmed) !== 1) {
            return false;
        }
        $fraction = self::float2fraction((float) $trimmed);
        if ($fraction !== false) {
            return new Price($fraction["nominator"], $fraction["denominator"]);
        }
        return false;
    }

    /**
     * @return array{nominator: int, denominator: int}|false
     */
    private static function float2fraction(float $n, float $tolerance = 1.e-9) : array | false {
        if (!is_finite($n) || $n <= 0.0) {
            return false;
        }
        $h1 = 1.0;
        $h2 = 0.0;
        $k1 = 0.0;
        $k2 = 1.0;
        $b = 1.0 / $n;
        if (!is_finite($b)) {
            return false;
        }
        do {
            $b = 1.0 / $b;
            if (!is_finite($b)) {
                return false;
            }
            $a = floor($b);
            $aux = $h1;
            $h1 = $a * $h1 + $h2;
            $h2 = $aux;
            $aux = $k1;
            $k1 = $a * $k1 + $k2;
            $k2 = $aux;
            if (!is_finite($h1) || !is_finite($k1) || $h1 > self::MAX_INT32 || $k1 > self::MAX_INT32) {
                return false;
            }
            $b = $b - $a;
        } while ($b != 0.0 && abs($n - $h1 / $k1) > $n * $tolerance);

        return ["nominator" => (int) $h1, "denominator" => (int) $k1];
    }
}