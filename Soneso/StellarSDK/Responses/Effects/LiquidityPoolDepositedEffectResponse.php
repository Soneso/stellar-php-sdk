<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Responses\LiquidityPools\ReserveResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\ReservesResponse;

class LiquidityPoolDepositedEffectResponse extends EffectResponse
{
    private LiquidityPoolEffectResponse $liquidityPool;
    private ReservesResponse $reservesDeposited;
    private string $sharesReceived;

    /**
     * @return LiquidityPoolEffectResponse
     */
    public function getLiquidityPool(): LiquidityPoolEffectResponse
    {
        return $this->liquidityPool;
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
        if (isset($json['liquidity_pool'])) $this->liquidityPool = LiquidityPoolEffectResponse::fromJson($json['liquidity_pool']);
        if (isset($json['shares_received'])) $this->sharesReceived = $json['shares_received'];
        if (isset($json['reserves_deposited'])) {
            $this->reservesDeposited = new ReservesResponse();
            foreach ($json['reserves_deposited'] as $jsonValue) {
                $value = ReserveResponse::fromJson($jsonValue);
                $this->reservesDeposited->add($value);
            }
        }
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : LiquidityPoolDepositedEffectResponse {
        $result = new LiquidityPoolDepositedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}