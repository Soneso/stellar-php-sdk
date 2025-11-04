<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents an effect when a liquidity pool is removed from the ledger
 *
 * This effect occurs when a liquidity pool is deleted, typically when all shares
 * have been withdrawn and the pool has no remaining reserves. Triggered when the
 * last liquidity provider withdraws their position.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse
 * @see https://developers.stellar.org/docs/encyclopedia/liquidity-on-stellar-sdex-liquidity-pools
 * @see https://developers.stellar.org/api/resources/effects
 */
class LiquidityPoolRemovedEffectResponse extends EffectResponse
{
    private string $liquidityPoolId;

    /**
     * Gets the unique identifier of the removed liquidity pool
     *
     * @return string The pool ID
     */
    public function getLiquidityPoolId(): string
    {
        return $this->liquidityPoolId;
    }

    /**
     * Loads object data from JSON array
     *
     * @param array $json JSON data array
     * @return void
     */
    protected function loadFromJson(array $json) : void {
        if (isset($json['liquidity_pool_id'])) $this->liquidityPoolId = $json['liquidity_pool_id'];
        parent::loadFromJson($json);
    }

    /**
     * Creates an instance from JSON data
     *
     * @param array $jsonData JSON data array
     * @return LiquidityPoolRemovedEffectResponse
     */
    public static function fromJson(array $jsonData) : LiquidityPoolRemovedEffectResponse {
        $result = new LiquidityPoolRemovedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}
