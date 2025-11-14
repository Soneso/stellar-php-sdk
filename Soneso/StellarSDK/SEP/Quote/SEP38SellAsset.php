<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

/**
 * Sell asset information with indicative price from SEP-38 prices endpoint.
 *
 * This class represents a single sell asset option with its indicative exchange
 * rate and decimal precision when exchanging for a specified buy asset.
 * Added in SEP-38 v2.3.0 to support reverse price lookups.
 *
 * @package Soneso\StellarSDK\SEP\Quote
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#get-prices
 * @see SEP38PricesResponse
 */
class SEP38SellAsset
{
    /**
     * @var string $asset The asset identifier that can be sold.
     */
    public string $asset;

    /**
     * @var string $price The indicative price of one unit of the sell asset in terms of the buy asset.
     */
    public string $price;

    /**
     * @var int $decimals The number of decimal places precision supported for this asset.
     */
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

    /**
     * Constructs a new instance of SEP38SellAsset by using the given data.
     *
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP38SellAsset the object containing the parsed data.
     */
    public static function fromJson(array $json) : SEP38SellAsset
    {
        return new SEP38SellAsset($json['asset'], $json['price'], $json['decimals']);
    }

}
