<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

class SEP38PricesResponse
{
    /**
     * @var array<SEP38BuyAsset> $buyAssets
     */
    public array $buyAssets;

    /**
     * @param array<SEP38BuyAsset> $buyAssets
     */
    public function __construct(array $buyAssets)
    {
        $this->buyAssets = $buyAssets;
    }

    public static function fromJson(array $json) : SEP38PricesResponse
    {
        /**
         * @var array<SEP38BuyAsset> $buyAssets
         */
        $buyAssets = array();
        if (isset($json['buy_assets'])) {
            foreach ($json['buy_assets'] as $asset) {
                $buyAssets[] = SEP38BuyAsset::fromJson($asset);
            }
        }

        return new SEP38PricesResponse($buyAssets);
    }
}