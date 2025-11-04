<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Trades;

/**
 * Rational representation of trade price as a fraction
 *
 * This response represents a trade price as a rational number using numerator (n) and
 * denominator (d) components. This precise fractional representation avoids floating-point
 * rounding errors and maintains exact price ratios as recorded in executed trades.
 *
 * The price represents the ratio of counter asset units to base asset units. For example,
 * if n=3 and d=2, the price is 1.5 counter units per base unit. This format preserves the
 * exact price at which the trade executed on the Stellar network.
 *
 * Companion to the decimal string price fields in TradeResponse and TradeAggregationResponse
 * for applications requiring exact arithmetic.
 *
 * @package Soneso\StellarSDK\Responses\Trades
 * @see TradeResponse For executed trade details with decimal price
 * @see TradeAggregationResponse For aggregated trade statistics
 * @see https://developers.stellar.org/api/resources/trades Horizon Trades API
 */
class TradePriceResponse
{
    private string $n;
    private string $d;

    /**
     * Gets the numerator of the price fraction
     *
     * @return string The numerator (n) value as string for large number support
     */
    public function getN(): string
    {
        return $this->n;
    }

    /**
     * Gets the denominator of the price fraction
     *
     * @return string The denominator (d) value as string for large number support
     */
    public function getD(): string
    {
        return $this->d;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['n'])) $this->n = $json['n'];
        if (isset($json['d'])) $this->d = $json['d'];
    }

    /**
     * Creates a TradePriceResponse from JSON data
     *
     * @param array $json Associative array of parsed JSON response
     * @return TradePriceResponse The populated price response
     */
    public static function fromJson(array $json) : TradePriceResponse {
        $result = new TradePriceResponse();
        $result->loadFromJson($json);
        return $result;
    }
}