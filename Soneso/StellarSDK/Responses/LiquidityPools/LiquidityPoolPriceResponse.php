<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\LiquidityPools;

/**
 * Represents a price as a rational number for liquidity pools
 *
 * Stores price as a fraction with numerator (n) and denominator (d).
 * The price value equals n/d.
 *
 * @package Soneso\StellarSDK\Responses\LiquidityPools
 * @see LiquidityPoolResponse For the parent liquidity pool details
 * @since 1.0.0
 */
class LiquidityPoolPriceResponse
{
    private int $n;
    private int $d;

    /**
     * Gets the numerator of the price fraction
     *
     * @return int The numerator
     */
    public function getN(): int
    {
        return $this->n;
    }

    /**
     * Gets the denominator of the price fraction
     *
     * @return int The denominator
     */
    public function getD(): int
    {
        return $this->d;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['n'])) $this->n = $json['n'];
        if (isset($json['d'])) $this->d = $json['d'];
    }

    public static function fromJson(array $json) : LiquidityPoolPriceResponse {
        $result = new LiquidityPoolPriceResponse();
        $result->loadFromJson($json);
        return $result;
    }
}