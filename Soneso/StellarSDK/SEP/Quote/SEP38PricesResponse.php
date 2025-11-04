<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

/**
 * Indicative prices for multiple buy assets in exchange for a sell asset via SEP-38.
 *
 * This class represents the response from GET /prices, containing indicative
 * exchange rates for all available buy assets that can be obtained in exchange
 * for a specified sell asset amount.
 *
 * @package Soneso\StellarSDK\SEP\Quote
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#get-prices
 * @see QuoteService::prices()
 * @see SEP38BuyAsset
 */
class SEP38PricesResponse
{
    /**
     * @var array<SEP38BuyAsset> $buyAssets
     */
    public array $buyAssets;

    /**
     * @param array<SEP38BuyAsset> $buyAssets Array of available buy assets with their prices.
     */
    public function __construct(array $buyAssets)
    {
        $this->buyAssets = $buyAssets;
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