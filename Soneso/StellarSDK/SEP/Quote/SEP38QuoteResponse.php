<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

use DateTime;
use DateTimeInterface;

/**
 * Firm quote response containing guaranteed exchange rates and expiration via SEP-38.
 *
 * This class represents a firm quote with a guaranteed price that expires at a
 * specific time. It includes the exchange rate, amounts, fees, and a unique quote
 * ID that can be used to reference this quote in subsequent transactions.
 *
 * @package Soneso\StellarSDK\SEP\Quote
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#post-quote
 * @see QuoteService::postQuote()
 * @see QuoteService::getQuote()
 * @see SEP38Fee
 */
class SEP38QuoteResponse
{
    /**
     * @var string $id The unique identifier for this quote.
     */
    public string $id;

    /**
     * @var DateTime $expiresAt The date and time when the quote will expire.
     */
    public DateTime $expiresAt;

    /**
     * @var string $totalPrice The total price of the quote including fees.
     */
    public string $totalPrice;

    /**
     * @var string $price The exchange rate without fees.
     */
    public string $price;

    /**
     * @var string $sellAsset The asset being sold.
     */
    public string $sellAsset;

    /**
     * @var string $sellAmount The amount of the sell asset.
     */
    public string $sellAmount;

    /**
     * @var string $buyAsset The asset being purchased.
     */
    public string $buyAsset;

    /**
     * @var string $buyAmount The amount of the buy asset.
     */
    public string $buyAmount;

    /**
     * @var SEP38Fee $fee The fee structure for this quote.
     */
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

    /**
     * Constructs a new instance of SEP38QuoteResponse by using the given data.
     *
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP38QuoteResponse the object containing the parsed data.
     */
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