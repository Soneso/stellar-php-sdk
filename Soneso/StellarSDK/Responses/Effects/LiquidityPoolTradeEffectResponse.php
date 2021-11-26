<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Responses\LiquidityPools\ReserveResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\ReservesResponse;

class LiquidityPoolTradeEffectResponse extends EffectResponse
{
    private LiquidityPoolEffectResponse $liquidityPool;
    private ReserveResponse $sold;
    private ReserveResponse $bought;
    private ReservesResponse $reserves;

    protected function loadFromJson(array $json) : void {
        if (isset($json['liquidity_pool'])) $this->liquidityPool = LiquidityPoolEffectResponse::fromJson($json['liquidity_pool']);
        if (isset($json['sold'])) $this->sold = ReserveResponse::fromJson($json['sold']);
        if (isset($json['bought'])) $this->sold = ReserveResponse::fromJson($json['bought']);
        if (isset($json['reserves'])) {
            $this->reserves = new ReservesResponse();
            foreach ($json['reserves'] as $jsonValue) {
                $value = ReserveResponse::fromJson($jsonValue);
                $this->reserves->add($value);
            }
        }
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : LiquidityPoolTradeEffectResponse {
        $result = new LiquidityPoolTradeEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}