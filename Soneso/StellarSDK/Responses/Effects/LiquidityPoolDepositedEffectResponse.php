<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Responses\LiquidityPools\ReserveResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\ReservesResponse;

/**
 * Represents an effect when assets are deposited into a liquidity pool
 *
 * This effect occurs when a liquidity provider deposits assets into a pool in exchange
 * for pool shares. The depositor receives shares proportional to their contribution.
 * Triggered by LiquidityPoolDeposit operations.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse
 * @see https://developers.stellar.org/docs/encyclopedia/liquidity-on-stellar-sdex-liquidity-pools
 * @see https://developers.stellar.org/api/resources/effects
 */
class LiquidityPoolDepositedEffectResponse extends EffectResponse
{
    private LiquidityPoolEffectResponse $liquidityPool;
    private ReservesResponse $reservesDeposited;
    private string $sharesReceived;

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
     * Gets the reserves deposited into the pool
     *
     * @return ReservesResponse The deposited reserves
     */
    public function getReservesDeposited(): ReservesResponse
    {
        return $this->reservesDeposited;
    }

    /**
     * Gets the pool shares received in exchange
     *
     * @return string The shares received
     */
    public function getSharesReceived(): string
    {
        return $this->sharesReceived;
    }

    /**
     * Loads object data from JSON array
     *
     * @param array $json JSON data array
     * @return void
     */
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

    /**
     * Creates an instance from JSON data
     *
     * @param array $jsonData JSON data array
     * @return LiquidityPoolDepositedEffectResponse
     */
    public static function fromJson(array $jsonData) : LiquidityPoolDepositedEffectResponse {
        $result = new LiquidityPoolDepositedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}
