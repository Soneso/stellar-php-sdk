<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Responses\LiquidityPools\LiquidityPoolPriceResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\ReserveResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\ReservesResponse;

/**
 * Represents a liquidity pool deposit operation response from Horizon API
 *
 * This operation deposits assets into an automated market maker (AMM) liquidity pool, providing
 * liquidity for trading. The depositor specifies maximum amounts of each reserve asset to deposit
 * and acceptable price bounds. In return, they receive pool shares representing their proportional
 * ownership of the pool. The actual deposited amounts are determined by the pool's current ratio
 * to maintain balance.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org/api/resources/operations/object/liquidity-pool-deposit Horizon Liquidity Pool Deposit Operation
 */
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
     * Gets the unique identifier of the liquidity pool
     *
     * @return string The liquidity pool ID
     */
    public function getLiquidityPoolId(): string
    {
        return $this->liquidityPoolId;
    }

    /**
     * Gets the maximum amounts willing to deposit for each reserve
     *
     * @return ReservesResponse Maximum deposit amounts for pool reserves
     */
    public function getReservesMax(): ReservesResponse
    {
        return $this->reservesMax;
    }

    /**
     * Gets the minimum acceptable exchange rate as a decimal string
     *
     * @return string Minimum price threshold
     */
    public function getMinPrice(): string
    {
        return $this->minPrice;
    }

    /**
     * Gets the minimum acceptable exchange rate as a rational number
     *
     * @return LiquidityPoolPriceResponse Minimum price as numerator/denominator
     */
    public function getMinPriceR(): LiquidityPoolPriceResponse
    {
        return $this->minPriceR;
    }

    /**
     * Gets the maximum acceptable exchange rate as a decimal string
     *
     * @return string Maximum price threshold
     */
    public function getMaxPrice(): string
    {
        return $this->maxPrice;
    }

    /**
     * Gets the maximum acceptable exchange rate as a rational number
     *
     * @return LiquidityPoolPriceResponse Maximum price as numerator/denominator
     */
    public function getMaxPriceR(): LiquidityPoolPriceResponse
    {
        return $this->maxPriceR;
    }

    /**
     * Gets the actual amounts deposited into each reserve
     *
     * @return ReservesResponse Actual deposited amounts for pool reserves
     */
    public function getReservesDeposited(): ReservesResponse
    {
        return $this->reservesDeposited;
    }

    /**
     * Gets the number of pool shares received for this deposit
     *
     * @return string Pool shares amount as a string to preserve precision
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