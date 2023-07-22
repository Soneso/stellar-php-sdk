<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Responses\LiquidityPools\LiquidityPoolPriceResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\ReserveResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\ReservesResponse;

class LiquidityPoolDepositOperationResponse extends OperationResponse
{

    private string $liquidityPoolId;
    private ReservesResponse $reservesMax;
    private string $minPrice;
    private LiquidityPoolPriceResponse $minPriceR;
    private string $maxPrice;
    private LiquidityPoolPriceResponse $maxPriceR;
    private ReservesResponse $reservesDeposited;
    private string $sharesReceived;

    /**
     * @return string
     */
    public function getLiquidityPoolId(): string
    {
        return $this->liquidityPoolId;
    }

    /**
     * @return ReservesResponse
     */
    public function getReservesMax(): ReservesResponse
    {
        return $this->reservesMax;
    }

    /**
     * @return string
     */
    public function getMinPrice(): string
    {
        return $this->minPrice;
    }

    /**
     * @return LiquidityPoolPriceResponse
     */
    public function getMinPriceR(): LiquidityPoolPriceResponse
    {
        return $this->minPriceR;
    }

    /**
     * @return string
     */
    public function getMaxPrice(): string
    {
        return $this->maxPrice;
    }

    /**
     * @return LiquidityPoolPriceResponse
     */
    public function getMaxPriceR(): LiquidityPoolPriceResponse
    {
        return $this->maxPriceR;
    }

    /**
     * @return ReservesResponse
     */
    public function getReservesDeposited(): ReservesResponse
    {
        return $this->reservesDeposited;
    }

    /**
     * @return string
     */
    public function getSharesReceived(): string
    {
        return $this->sharesReceived;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['liquidity_pool_id'])) $this->liquidityPoolId = $json['liquidity_pool_id'];

        if (isset($json['reserves_max'])) {
            $this->reservesMax = new ReservesResponse();
            foreach ($json['reserves_max'] as $jsonValue) {
                $value = ReserveResponse::fromJson($jsonValue);
                $this->reservesMax->add($value);
            }
        }

        if (isset($json['min_price'])) $this->minPrice = $json['min_price'];
        if (isset($json['min_price_r'])) $this->minPriceR = LiquidityPoolPriceResponse::fromJson($json['min_price_r']);
        if (isset($json['max_price'])) $this->maxPrice = $json['max_price'];
        if (isset($json['max_price_r'])) $this->maxPriceR = LiquidityPoolPriceResponse::fromJson($json['max_price_r']);

        if (isset($json['reserves_deposited'])) {
            $this->reservesDeposited = new ReservesResponse();
            foreach ($json['reserves_deposited'] as $jsonValue) {
                $value = ReserveResponse::fromJson($jsonValue);
                $this->reservesDeposited->add($value);
            }
        }
        if (isset($json['shares_received'])) $this->sharesReceived = $json['shares_received'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : LiquidityPoolDepositOperationResponse {
        $result = new LiquidityPoolDepositOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}