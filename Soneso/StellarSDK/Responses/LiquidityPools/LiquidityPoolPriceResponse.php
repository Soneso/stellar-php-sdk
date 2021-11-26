<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\LiquidityPools;

class LiquidityPoolPriceResponse
{
    private int $n;
    private int $d;

    /**
     * @return int
     */
    public function getN(): int
    {
        return $this->n;
    }

    /**
     * @return int
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