<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Responses\LiquidityPools\ReserveResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\ReservesResponse;

/**
 * Represents an effect when a trade is executed against a liquidity pool
 *
 * This effect occurs when a trader swaps assets through a liquidity pool on the DEX.
 * The pool automatically prices the trade based on its constant product formula.
 * Triggered by path payment or manage buy/sell offer operations that interact with pools.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse
 * @see https://developers.stellar.org Stellar developer docs
 */
class LiquidityPoolTradeEffectResponse extends EffectResponse
{
    private LiquidityPoolEffectResponse $liquidityPool;
    private ReserveResponse $sold;
    private ReserveResponse $bought;

    /**
     * Gets the liquidity pool details
     *
     * @return LiquidityPoolEffectResponse The pool information
     */
    public function getLiquidityPool(): LiquidityPoolEffectResponse
    {
        return $this->liquidityPool;
    }

    /**
     * Gets the reserve that was sold to the pool
     *
     * @return ReserveResponse The sold reserve
     */
    public function getSold(): ReserveResponse
    {
        return $this->sold;
    }

    /**
     * Gets the reserve that was bought from the pool
     *
     * @return ReserveResponse The bought reserve
     */
    public function getBought(): ReserveResponse
    {
        return $this->bought;
    }

    /**
     * Loads object data from JSON array
     *
     * @param array $json JSON data array
     * @return void
     */
    protected function loadFromJson(array $json) : void {
        if (isset($json['liquidity_pool'])) $this->liquidityPool = LiquidityPoolEffectResponse::fromJson($json['liquidity_pool']);
        if (isset($json['sold'])) $this->sold = ReserveResponse::fromJson($json['sold']);
        if (isset($json['bought'])) $this->bought = ReserveResponse::fromJson($json['bought']);
        parent::loadFromJson($json);
    }

    /**
     * Creates an instance from JSON data
     *
     * @param array $jsonData JSON data array
     * @return LiquidityPoolTradeEffectResponse
     */
    public static function fromJson(array $jsonData) : LiquidityPoolTradeEffectResponse {
        $result = new LiquidityPoolTradeEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}
