<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

class SEP38PriceResponse
{
    public string $totalPrice;
    public string $price;
    public string $sellAmount;
    public string $buyAmount;
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