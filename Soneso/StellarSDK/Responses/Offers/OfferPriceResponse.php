<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Offers;

/**
 * Rational representation of offer price as a fraction
 *
 * This response represents an offer price as a rational number using numerator (n) and
 * denominator (d) components. This precise fractional representation avoids floating-point
 * rounding errors and maintains exact price ratios as stored on the Stellar ledger.
 *
 * The price represents the ratio of buying asset units to selling asset units. For example,
 * if n=3 and d=2, the price is 1.5 buying units per selling unit. This format matches the
 * Price XDR structure used in Stellar protocol operations.
 *
 * Companion to the decimal string price field in OfferResponse for applications requiring
 * exact arithmetic.
 *
 * @package Soneso\StellarSDK\Responses\Offers
 * @see OfferResponse For the parent offer with decimal price
 * @see https://developers.stellar.org Stellar developer docs Horizon Offers API
 */
class OfferPriceResponse
{
    private int $n;
    private int $d;

    /**
     * Gets the numerator of the price fraction
     *
     * @return int The numerator (n) value
     */
    public function getN(): int
    {
        return $this->n;
    }

    /**
     * Gets the denominator of the price fraction
     *
     * @return int The denominator (d) value
     */
    public function getD(): int
    {
        return $this->d;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['n'])) $this->n = $json['n'];
        if (isset($json['d'])) $this->d = $json['d'];
    }

    /**
     * Creates an OfferPriceResponse from JSON data
     *
     * @param array $json Associative array of parsed JSON response
     * @return OfferPriceResponse The populated price response
     */
    public static function fromJson(array $json) : OfferPriceResponse {
        $result = new OfferPriceResponse();
        $result->loadFromJson($json);
        return $result;
    }
}