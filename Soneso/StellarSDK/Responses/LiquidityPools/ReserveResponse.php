<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\LiquidityPools;

use Soneso\StellarSDK\Asset;

/**
 * Represents a reserve in a liquidity pool
 *
 * Contains the amount and asset type for one of the reserves in a liquidity pool.
 * Liquidity pools typically have two reserves representing the paired assets.
 *
 * @package Soneso\StellarSDK\Responses\LiquidityPools
 * @see LiquidityPoolResponse For the parent liquidity pool details
 * @since 1.0.0
 */
class ReserveResponse
{
    private string $amount;
    private Asset $asset;

    /**
     * Gets the amount of the asset in this reserve
     *
     * @return string The reserve amount as a string to preserve precision
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Gets the asset type for this reserve
     *
     * @return Asset The asset object
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['amount'])) $this->amount = $json['amount'];
        if (isset($json['asset'])) $this->asset = Asset::createFromCanonicalForm($json['asset']);
    }

    public static function fromJson(array $json) : ReserveResponse {
        $result = new ReserveResponse();
        $result->loadFromJson($json);
        return $result;
    }
}