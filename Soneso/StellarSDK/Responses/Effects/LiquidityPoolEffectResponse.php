<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Responses\LiquidityPools\ReserveResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\ReservesResponse;

class LiquidityPoolEffectResponse extends EffectResponse
{
    private string $poolId;
    private int $fee; // TODO: Bigint
    private string $type;
    private string $totalTrustlines;
    private string $totalShares;
    private ReservesResponse $reserves;

    /**
     * @return string
     */
    public function getPoolId(): string
    {
        return $this->poolId;
    }

    /**
     * @return int
     */
    public function getFee(): int
    {
        return $this->fee;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getTotalTrustlines(): string
    {
        return $this->totalTrustlines;
    }

    /**
     * @return string
     */
    public function getTotalShares(): string
    {
        return $this->totalShares;
    }

    /**
     * @return ReservesResponse
     */
    public function getReserves(): ReservesResponse
    {
        return $this->reserves;
    }

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

    public static function fromJson(array $jsonData) : LiquidityPoolEffectResponse {
        $result = new LiquidityPoolEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}