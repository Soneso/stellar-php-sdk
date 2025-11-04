<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Responses\LiquidityPools\ReserveResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\ReservesResponse;

/**
 * Represents an effect when liquidity pool shares are revoked by an asset issuer
 *
 * This effect occurs when an asset issuer claws back pool shares from a liquidity provider,
 * typically due to regulatory requirements. The issuer must have the AUTH_CLAWBACK_ENABLED
 * flag set. Triggered by ClawbackClaimableBalance or similar clawback operations.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse
 * @see https://developers.stellar.org/docs/encyclopedia/liquidity-on-stellar-sdex-liquidity-pools
 * @see https://developers.stellar.org/api/resources/effects
 */
class LiquidityPoolRevokedEffectResponse extends EffectResponse
{
    private LiquidityPoolEffectResponse $liquidityPool;
    private ReservesResponse $reservesRevoked;
    private string $sharesRevoked;

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
     * Gets the reserves that were revoked
     *
     * @return ReservesResponse The revoked reserves
     */
    public function getReservesRevoked(): ReservesResponse
    {
        return $this->reservesRevoked;
    }

    /**
     * Gets the pool shares that were revoked
     *
     * @return string The revoked shares
     */
    public function getSharesRevoked(): string
    {
        return $this->sharesRevoked;
    }

    /**
     * Loads object data from JSON array
     *
     * @param array $json JSON data array
     * @return void
     */
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

    /**
     * Creates an instance from JSON data
     *
     * @param array $jsonData JSON data array
     * @return LiquidityPoolRevokedEffectResponse
     */
    public static function fromJson(array $jsonData) : LiquidityPoolRevokedEffectResponse {
        $result = new LiquidityPoolRevokedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}
