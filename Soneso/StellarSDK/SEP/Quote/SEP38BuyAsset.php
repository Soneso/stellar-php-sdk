<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

class SEP38BuyAsset
{
    public string $asset;
    public string $price;
    public int $decimals;

    /**
     * @param string $asset
     * @param string $price
     * @param int $decimals
     */
    public function __construct(string $asset, string $price, int $decimals)
    {
        $this->asset = $asset;
        $this->price = $price;
        $this->decimals = $decimals;
    }

    public static function fromJson(array $json) : SEP38BuyAsset
    {
        return new SEP38BuyAsset($json['asset'], $json['price'], $json['decimals']);
    }

}