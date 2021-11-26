<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

class LiquidityPoolCreatedEffectResponse extends EffectResponse
{
    private LiquidityPoolEffectResponse $liquidityPool;

    /**
     * @return LiquidityPoolEffectResponse
     */
    public function getLiquidityPool(): LiquidityPoolEffectResponse
    {
        return $this->liquidityPool;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['liquidity_pool'])) $this->liquidityPool = LiquidityPoolEffectResponse::fromJson($json['liquidity_pool']);
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : LiquidityPoolCreatedEffectResponse {
        $result = new LiquidityPoolCreatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}