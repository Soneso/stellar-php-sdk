<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Responses\LiquidityPools\ReserveResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\ReservesResponse;

/**
 * Represents a liquidity pool withdraw operation response from Horizon API
 *
 * This operation withdraws assets from an automated market maker (AMM) liquidity pool by burning
 * pool shares. The withdrawer specifies the number of shares to burn and minimum acceptable amounts
 * to receive from each reserve. The actual amounts received are determined by the pool's current
 * ratio and the proportion of shares being burned. This reduces the depositor's stake in the pool.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org Stellar developer docs Horizon Liquidity Pool Withdraw Operation
 */
class LiquidityPoolWithdrawOperationResponse extends OperationResponse
{
    private string $liquidityPoolId;
    private ReservesResponse $reservesMin;
    private string $shares;
    private ReservesResponse $reservesReceived;

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
     * Gets the minimum acceptable amounts to receive from each reserve
     *
     * @return ReservesResponse Minimum withdrawal amounts for pool reserves
     */
    public function getReservesMin(): ReservesResponse
    {
        return $this->reservesMin;
    }

    /**
     * Gets the number of pool shares being withdrawn (burned)
     *
     * @return string Pool shares amount as a string to preserve precision
     */
    public function getShares(): string
    {
        return $this->shares;
    }

    /**
     * Gets the actual amounts received from each reserve
     *
     * @return ReservesResponse Actual received amounts for pool reserves
     */
    public function getReservesReceived(): ReservesResponse
    {
        return $this->reservesReceived;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['liquidity_pool_id'])) $this->liquidityPoolId = $json['liquidity_pool_id'];

        if (isset($json['reserves_min'])) {
            $this->reservesMin = new ReservesResponse();
            foreach ($json['reserves_min'] as $jsonValue) {
                $value = ReserveResponse::fromJson($jsonValue);
                $this->reservesMin->add($value);
            }
        }

        if (isset($json['shares'])) $this->shares = $json['shares'];

        if (isset($json['reserves_received'])) {
            $this->reservesReceived = new ReservesResponse();
            foreach ($json['reserves_received'] as $jsonValue) {
                $value = ReserveResponse::fromJson($jsonValue);
                $this->reservesReceived->add($value);
            }
        }
        parent::loadFromJson($json);

    }

    public static function fromJson(array $jsonData) : LiquidityPoolWithdrawOperationResponse {
        $result = new LiquidityPoolWithdrawOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}