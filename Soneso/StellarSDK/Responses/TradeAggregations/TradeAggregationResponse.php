<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\TradeAggregations;

use Soneso\StellarSDK\Responses\Response;
use Soneso\StellarSDK\Responses\Trades\TradePriceResponse;

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
     * @return string
     */
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getTradeCount(): string
    {
        return $this->tradeCount;
    }

    /**
     * @return string
     */
    public function getBaseVolume(): string
    {
        return $this->baseVolume;
    }

    /**
     * @return string
     */
    public function getCounterVolume(): string
    {
        return $this->counterVolume;
    }

    /**
     * @return string
     */
    public function getAveragePrice(): string
    {
        return $this->averagePrice;
    }

    /**
     * @return string
     */
    public function getHighPrice(): string
    {
        return $this->highPrice;
    }

    /**
     * @return string
     */
    public function getLowPrice(): string
    {
        return $this->lowPrice;
    }

    /**
     * @return string
     */
    public function getOpenPrice(): string
    {
        return $this->openPrice;
    }

    /**
     * @return string
     */
    public function getClosePrice(): string
    {
        return $this->closePrice;
    }

    /**
     * @return TradePriceResponse
     */
    public function getHighPriceR(): TradePriceResponse
    {
        return $this->highPriceR;
    }

    /**
     * @return TradePriceResponse
     */
    public function getLowPriceR(): TradePriceResponse
    {
        return $this->lowPriceR;
    }

    /**
     * @return TradePriceResponse
     */
    public function getOpenPriceR(): TradePriceResponse
    {
        return $this->openPriceR;
    }

    /**
     * @return TradePriceResponse
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

    public static function fromJson(array $json) : TradeAggregationResponse
    {
        $result = new TradeAggregationResponse();
        $result->loadFromJson($json);
        return $result;
    }
}