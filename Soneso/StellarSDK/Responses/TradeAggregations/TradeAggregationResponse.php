<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\TradeAggregations;

use Soneso\StellarSDK\Responses\Response;
use Soneso\StellarSDK\Responses\Trades\TradePriceResponse;

/**
 * Represents aggregated trade statistics for a time period
 *
 * This response contains OHLC (Open-High-Low-Close) candlestick data and volume statistics
 * for trades of a specific asset pair over a defined time period. Trade aggregations enable
 * historical price analysis, charting, and market trend identification by summarizing multiple
 * individual trades into time-based buckets.
 *
 * Key fields:
 * - Timestamp marking the start of the aggregation period
 * - Trade count for the period
 * - Base and counter asset volumes
 * - Average price for the period
 * - OHLC prices (open, high, low, close) in decimal and rational formats
 *
 * Prices are provided as both decimal strings and rational fractions (price_r fields) for
 * precision. The aggregation period (resolution) is specified when querying the endpoint
 * and can range from minutes to days.
 *
 * Returned by Horizon endpoint:
 * - GET /trade_aggregations - Aggregated trade statistics by time period
 *
 * @package Soneso\StellarSDK\Responses\TradeAggregations
 * @see TradePriceResponse For rational price representation
 * @see TradeAggregationsPageResponse For paginated aggregation results
 * @see https://developers.stellar.org/api/aggregations/trade-aggregations Horizon Trade Aggregations API
 */
class TradeAggregationResponse extends Response
{
    private string $timestamp;
    private string $tradeCount;
    private string $baseVolume;
    private string $counterVolume;
    private string $averagePrice;
    private string $highPrice;
    private TradePriceResponse $highPriceR;
    private string $lowPrice;
    private TradePriceResponse $lowPriceR;
    private string $openPrice;
    private TradePriceResponse $openPriceR;
    private string $closePrice;
    private TradePriceResponse $closePriceR;

    /**
     * Gets the timestamp marking the start of this aggregation period
     *
     * @return string The timestamp in milliseconds since epoch
     */
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    /**
     * Gets the number of trades in this aggregation period
     *
     * @return string The total count of executed trades
     */
    public function getTradeCount(): string
    {
        return $this->tradeCount;
    }

    /**
     * Gets the total volume of the base asset traded
     *
     * @return string The base asset volume
     */
    public function getBaseVolume(): string
    {
        return $this->baseVolume;
    }

    /**
     * Gets the total volume of the counter asset traded
     *
     * @return string The counter asset volume
     */
    public function getCounterVolume(): string
    {
        return $this->counterVolume;
    }

    /**
     * Gets the average price during this period
     *
     * @return string The average price as a decimal string
     */
    public function getAveragePrice(): string
    {
        return $this->averagePrice;
    }

    /**
     * Gets the highest price reached during this period
     *
     * @return string The high price as a decimal string
     */
    public function getHighPrice(): string
    {
        return $this->highPrice;
    }

    /**
     * Gets the lowest price reached during this period
     *
     * @return string The low price as a decimal string
     */
    public function getLowPrice(): string
    {
        return $this->lowPrice;
    }

    /**
     * Gets the opening price at the start of this period
     *
     * @return string The open price as a decimal string
     */
    public function getOpenPrice(): string
    {
        return $this->openPrice;
    }

    /**
     * Gets the closing price at the end of this period
     *
     * @return string The close price as a decimal string
     */
    public function getClosePrice(): string
    {
        return $this->closePrice;
    }

    /**
     * Gets the rational representation of the high price
     *
     * @return TradePriceResponse The high price as a fraction
     */
    public function getHighPriceR(): TradePriceResponse
    {
        return $this->highPriceR;
    }

    /**
     * Gets the rational representation of the low price
     *
     * @return TradePriceResponse The low price as a fraction
     */
    public function getLowPriceR(): TradePriceResponse
    {
        return $this->lowPriceR;
    }

    /**
     * Gets the rational representation of the open price
     *
     * @return TradePriceResponse The open price as a fraction
     */
    public function getOpenPriceR(): TradePriceResponse
    {
        return $this->openPriceR;
    }

    /**
     * Gets the rational representation of the close price
     *
     * @return TradePriceResponse The close price as a fraction
     */
    public function getClosePriceR(): TradePriceResponse
    {
        return $this->closePriceR;
    }



    protected function loadFromJson(array $json) : void {

        if (isset($json['timestamp'])) $this->timestamp = $json['timestamp'];
        if (isset($json['trade_count'])) $this->tradeCount = $json['trade_count'];
        if (isset($json['base_volume'])) $this->baseVolume = $json['base_volume'];
        if (isset($json['counter_volume'])) $this->counterVolume = $json['counter_volume'];
        if (isset($json['avg'])) $this->averagePrice = $json['avg'];
        if (isset($json['high'])) $this->highPrice = $json['high'];
        if (isset($json['high_r'])) $this->highPriceR = TradePriceResponse::fromJson($json['high_r']);
        if (isset($json['low'])) $this->lowPrice = $json['low'];
        if (isset($json['low_r'])) $this->lowPriceR = TradePriceResponse::fromJson($json['low_r']);
        if (isset($json['open'])) $this->openPrice = $json['open'];
        if (isset($json['open_r'])) $this->openPriceR = TradePriceResponse::fromJson($json['open_r']);
        if (isset($json['close'])) $this->closePrice = $json['close'];
        if (isset($json['close_r'])) $this->closePriceR = TradePriceResponse::fromJson($json['close_r']);

    }

    /**
     * Creates a TradeAggregationResponse from JSON data
     *
     * @param array $json Associative array of parsed JSON response
     * @return TradeAggregationResponse The populated aggregation response
     */
    public static function fromJson(array $json) : TradeAggregationResponse
    {
        $result = new TradeAggregationResponse();
        $result->loadFromJson($json);
        return $result;
    }
}