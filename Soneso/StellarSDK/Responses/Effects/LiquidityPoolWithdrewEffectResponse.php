<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Responses\LiquidityPools\ReserveResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\ReservesResponse;

class LiquidityPoolWithdrewEffectResponse extends EffectResponse
{
    private LiquidityPoolEffectResponse $liquidityPool;
    private ReservesResponse $reservesReceived;
    private string $sharesRedeemed;

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
    public function getReservesReceived(): ReservesResponse
    {
        return $this->reservesReceived;
    }

    /**
     * @return string
     */
    public function getSharesRedeemed(): string
    {
        return $this->sharesRedeemed;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['liquidity_pool'])) $this->liquidityPool = LiquidityPoolEffectResponse::fromJson($json['liquidity_pool']);
        if (isset($json['shares_redeemed'])) $this->sharesRedeemed = $json['shares_redeemed'];
        if (isset($json['reserves_received'])) {
            $this->reservesReceived = new ReservesResponse();
            foreach ($json['reserves_received'] as $jsonValue) {
                $value = ReserveResponse::fromJson($jsonValue);
                $this->reservesReceived->add($value);
            }
        }
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : LiquidityPoolWithdrewEffectResponse {
        $result = new LiquidityPoolWithdrewEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}