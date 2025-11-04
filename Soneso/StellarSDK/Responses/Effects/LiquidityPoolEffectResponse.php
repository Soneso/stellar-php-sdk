<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Responses\LiquidityPools\ReserveResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\ReservesResponse;

/**
 * Base class for liquidity pool details in effect responses
 *
 * This response provides comprehensive information about a liquidity pool's state
 * including its reserves, shares, and fee structure. Used as a nested object in
 * various liquidity pool effect responses.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse
 * @see https://developers.stellar.org/docs/encyclopedia/liquidity-on-stellar-sdex-liquidity-pools
 * @see https://developers.stellar.org/api/resources/effects
 */
class LiquidityPoolEffectResponse extends EffectResponse
{
    private string $poolId;
    private int $fee; // TODO: Bigint
    private string $type;
    private string $totalTrustlines;
    private string $totalShares;
    private ReservesResponse $reserves;

    /**
     * Gets the unique identifier of the liquidity pool
     *
     * @return string The pool ID
     */
    public function getPoolId(): string
    {
        return $this->poolId;
    }

    /**
     * Gets the pool fee in basis points
     *
     * @return int The fee in basis points
     */
    public function getFee(): int
    {
        return $this->fee;
    }

    /**
     * Gets the pool type
     *
     * @return string The pool type (e.g., constant_product)
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Gets the total number of trustlines to this pool
     *
     * @return string The total trustlines count
     */
    public function getTotalTrustlines(): string
    {
        return $this->totalTrustlines;
    }

    /**
     * Gets the total shares issued by this pool
     *
     * @return string The total pool shares
     */
    public function getTotalShares(): string
    {
        return $this->totalShares;
    }

    /**
     * Gets the pool reserves
     *
     * @return ReservesResponse The pool reserves
     */
    public function getReserves(): ReservesResponse
    {
        return $this->reserves;
    }

    /**
     * Loads object data from JSON array
     *
     * @param array $json JSON data array
     * @return void
     */
    protected function loadFromJson(array $json) : void {
        if (isset($json['id'])) $this->poolId = $json['id'];
        if (isset($json['fee_bp'])) $this->fee = $json['fee_bp'];
        if (isset($json['type'])) $this->type = $json['type'];
        if (isset($json['total_trustlines'])) $this->totalTrustlines = $json['total_trustlines'];
        if (isset($json['total_shares'])) $this->totalShares = $json['total_shares'];
        if (isset($json['reserves'])) {
            $this->reserves = new ReservesResponse();
            foreach ($json['reserves'] as $jsonValue) {
                $value = ReserveResponse::fromJson($jsonValue);
                $this->reserves->add($value);
            }
        }
        parent::loadFromJson($json);
    }

    /**
     * Creates an instance from JSON data
     *
     * @param array $jsonData JSON data array
     * @return LiquidityPoolEffectResponse
     */
    public static function fromJson(array $jsonData) : LiquidityPoolEffectResponse {
        $result = new LiquidityPoolEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}
