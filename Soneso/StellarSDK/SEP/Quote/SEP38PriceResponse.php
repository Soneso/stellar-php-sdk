<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

/**
 * Indicative price response for a single asset pair via SEP-38.
 *
 * This class represents an indicative (non-binding) price quote for exchanging
 * one asset for another. Unlike firm quotes, these prices are not guaranteed
 * and are provided for estimation purposes only.
 *
 * @package Soneso\StellarSDK\SEP\Quote
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#get-price
 * @see QuoteService::price()
 * @see SEP38Fee
 */
class SEP38PriceResponse
{
    /**
     * @var string $totalPrice The total price of the quote including fees.
     */
    public string $totalPrice;

    /**
     * @var string $price The exchange rate without fees.
     */
    public string $price;

    /**
     * @var string $sellAmount The amount of the sell asset.
     */
    public string $sellAmount;

    /**
     * @var string $buyAmount The amount of the buy asset.
     */
    public string $buyAmount;

    /**
     * @var SEP38Fee $fee The fee structure for this price.
     */
    public SEP38Fee $fee;

    /**
     * @param string $totalPrice
     * @param string $price
     * @param string $sellAmount
     * @param string $buyAmount
     * @param SEP38Fee $fee
     */
    public function __construct(string $totalPrice, string $price, string $sellAmount, string $buyAmount, SEP38Fee $fee)
    {
        $this->totalPrice = $totalPrice;
        $this->price = $price;
        $this->sellAmount = $sellAmount;
        $this->buyAmount = $buyAmount;
        $this->fee = $fee;
    }

    /**
     * Constructs a new instance of SEP38PriceResponse by using the given data.
     *
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP38PriceResponse the object containing the parsed data.
     */
    public static function fromJson(array $json) : SEP38PriceResponse
    {
        return new SEP38PriceResponse(
            $json['total_price'],
            $json['price'],
            $json['sell_amount'],
            $json['buy_amount'],
            SEP38Fee::fromJson($json['fee']),
        );
    }

}