<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Responses\LiquidityPools\ReserveResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\ReservesResponse;

/**
 * Represents an effect when assets are withdrawn from a liquidity pool
 *
 * This effect occurs when a liquidity provider redeems their pool shares to withdraw
 * their proportional share of the pool's reserves. Triggered by LiquidityPoolWithdraw
 * operations.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse
 * @see https://developers.stellar.org/docs/encyclopedia/liquidity-on-stellar-sdex-liquidity-pools
 * @see https://developers.stellar.org/api/resources/effects
 */
class LiquidityPoolWithdrewEffectResponse extends EffectResponse
{
    private LiquidityPoolEffectResponse $liquidityPool;
    private ReservesResponse $reservesReceived;
    private string $sharesRedeemed;

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
     * Gets the reserves received from the withdrawal
     *
     * @return ReservesResponse The received reserves
     */
    public function getReservesReceived(): ReservesResponse
    {
        return $this->reservesReceived;
    }

    /**
     * Gets the pool shares redeemed
     *
     * @return string The redeemed shares
     */
    public function getSharesRedeemed(): string
    {
        return $this->sharesRedeemed;
    }

    /**
     * Loads object data from JSON array
     *
     * @param array $json JSON data array
     * @return void
     */
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

    /**
     * Creates an instance from JSON data
     *
     * @param array $jsonData JSON data array
     * @return LiquidityPoolWithdrewEffectResponse
     */
    public static function fromJson(array $jsonData) : LiquidityPoolWithdrewEffectResponse {
        $result = new LiquidityPoolWithdrewEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}
