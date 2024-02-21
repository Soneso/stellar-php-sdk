<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

use DateTime;
use DateTimeInterface;

class SEP38QuoteResponse
{
    public string $id;
    public DateTime $expiresAt;
    public string $totalPrice;
    public string $price;
    public string $sellAsset;
    public string $sellAmount;
    public string $buyAsset;
    public string $buyAmount;
    public SEP38Fee $fee;

    /**
     * @param string $id
     * @param DateTime $expiresAt
     * @param string $totalPrice
     * @param string $price
     * @param string $sellAsset
     * @param string $sellAmount
     * @param string $buyAsset
     * @param string $buyAmount
     * @param SEP38Fee $fee
     */
    public function __construct(
        string $id,
        DateTime $expiresAt,
        string $totalPrice,
        string $price,
        string $sellAsset,
        string $sellAmount,
        string $buyAsset,
        string $buyAmount,
        SEP38Fee $fee)
    {
        $this->id = $id;
        $this->expiresAt = $expiresAt;
        $this->totalPrice = $totalPrice;
        $this->price = $price;
        $this->sellAsset = $sellAsset;
        $this->sellAmount = $sellAmount;
        $this->buyAsset = $buyAsset;
        $this->buyAmount = $buyAmount;
        $this->fee = $fee;
    }

    public static function fromJson(array $json) : SEP38QuoteResponse
    {
        return new SEP38QuoteResponse(
            $json['id'],
            DateTime::createFromFormat(DateTimeInterface::ATOM, $json['expires_at']),
            $json['total_price'],
            $json['price'],
            $json['sell_asset'],
            $json['sell_amount'],
            $json['buy_asset'],
            $json['buy_amount'],
            SEP38Fee::fromJson($json['fee']),
        );
    }
}