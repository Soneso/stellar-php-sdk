<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

/**
 * Indicative prices for multiple buy or sell assets via SEP-38.
 *
 * This class represents the response from GET /prices, containing indicative
 * exchange rates. Response contains either buy_assets (for selling a specified
 * asset) or sell_assets (for buying a specified asset). Support for sell_assets
 * was added in SEP-38 v2.3.0.
 *
 * @package Soneso\StellarSDK\SEP\Quote
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#get-prices
 * @see QuoteService::prices()
 * @see SEP38BuyAsset
 * @see SEP38SellAsset
 */
class SEP38PricesResponse
{
    /**
     * @param array<SEP38BuyAsset>|null $buyAssets Array of available buy assets with their prices (when selling a specified asset).
     * @param array<SEP38SellAsset>|null $sellAssets Array of available sell assets with their prices (when buying a specified asset, added in SEP-38 v2.3.0).
     */
    public function __construct(
        public ?array $buyAssets = null,
        public ?array $sellAssets = null,
    ) {
    }

    /**
     * Constructs a new instance of SEP38PricesResponse by using the given data.
     *
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP38PricesResponse the object containing the parsed data.
     */
    public static function fromJson(array $json) : SEP38PricesResponse
    {
        /**
         * @var array<SEP38BuyAsset>|null $buyAssets
         */
        $buyAssets = null;
        if (isset($json['buy_assets'])) {
            $buyAssets = array();
            foreach ($json['buy_assets'] as $asset) {
                $buyAssets[] = SEP38BuyAsset::fromJson($asset);
            }
        }

        /**
         * @var array<SEP38SellAsset>|null $sellAssets
         */
        $sellAssets = null;
        if (isset($json['sell_assets'])) {
            $sellAssets = array();
            foreach ($json['sell_assets'] as $asset) {
                $sellAssets[] = SEP38SellAsset::fromJson($asset);
            }
        }

        return new SEP38PricesResponse($buyAssets, $sellAssets);
    }
}