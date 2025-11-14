<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents an effect when a liquidity pool is created
 *
 * This effect occurs when a new liquidity pool is initialized on the ledger.
 * Liquidity pools enable automated market making on the Stellar DEX. Triggered
 * by ChangeTrust operations that create pool trustlines.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse
 * @see https://developers.stellar.org Stellar developer docs
 */
class LiquidityPoolCreatedEffectResponse extends EffectResponse
{
    private LiquidityPoolEffectResponse $liquidityPool;

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
     * Loads object data from JSON array
     *
     * @param array $json JSON data array
     * @return void
     */
    protected function loadFromJson(array $json) : void {
        if (isset($json['liquidity_pool'])) $this->liquidityPool = LiquidityPoolEffectResponse::fromJson($json['liquidity_pool']);
        parent::loadFromJson($json);
    }

    /**
     * Creates an instance from JSON data
     *
     * @param array $jsonData JSON data array
     * @return LiquidityPoolCreatedEffectResponse
     */
    public static function fromJson(array $jsonData) : LiquidityPoolCreatedEffectResponse {
        $result = new LiquidityPoolCreatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}
