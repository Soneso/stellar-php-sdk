<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Effects;


class LiquidityPoolRemovedEffectResponse extends EffectResponse
{
    private string $liquidityPoolId;

    /**
     * @return string
     */
    public function getLiquidityPoolId(): string
    {
        return $this->liquidityPoolId;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['liquidity_pool_id'])) $this->liquidityPoolId = $json['liquidity_pool_id'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : LiquidityPoolRemovedEffectResponse {
        $result = new LiquidityPoolRemovedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}