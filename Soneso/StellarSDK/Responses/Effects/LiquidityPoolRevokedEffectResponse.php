<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Responses\LiquidityPools\ReserveResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\ReservesResponse;

class LiquidityPoolRevokedEffectResponse extends EffectResponse
{
    private LiquidityPoolEffectResponse $liquidityPool;
    private ReservesResponse $reservesRevoked;
    private string $sharesRevoked;

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
    public function getReservesRevoked(): ReservesResponse
    {
        return $this->reservesRevoked;
    }

    /**
     * @return string
     */
    public function getSharesRevoked(): string
    {
        return $this->sharesRevoked;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['liquidity_pool'])) $this->liquidityPool = LiquidityPoolEffectResponse::fromJson($json['liquidity_pool']);
        if (isset($json['shares_revoked'])) $this->sharesRevoked = $json['shares_revoked'];
        if (isset($json['reserves_revoked'])) {
            $this->reservesRevoked = new ReservesResponse();
            foreach ($json['reserves_revoked'] as $jsonValue) {
                $value = ReserveResponse::fromJson($jsonValue);
                $this->reservesRevoked->add($value);
            }
        }
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : LiquidityPoolRevokedEffectResponse {
        $result = new LiquidityPoolRevokedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}