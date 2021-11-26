<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\LiquidityPools;

use Soneso\StellarSDK\Asset;

class ReserveResponse
{
    private string $amount;
    private Asset $asset;

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @return Asset
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