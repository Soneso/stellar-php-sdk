<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Responses\LiquidityPools\ReserveResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\ReservesResponse;

class LiquidityPoolWithdrawOperationResponse extends OperationResponse
{
    private string $liquidityPoolId;
    private ReservesResponse $reservesMin;
    private string $shares;
    private ReservesResponse $reservesReceived;

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
    public function getReservesMin(): ReservesResponse
    {
        return $this->reservesMin;
    }

    /**
     * @return string
     */
    public function getShares(): string
    {
        return $this->shares;
    }

    /**
     * @return ReservesResponse
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