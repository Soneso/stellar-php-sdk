<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

use DateTime;
use DateTimeInterface;

/**
 * Request body for requesting a firm quote via SEP-38.
 *
 * This class represents the data required to submit a POST /quote request to
 * obtain a firm, time-limited quote for exchanging assets. Either sellAmount
 * or buyAmount must be provided, but not both.
 *
 * @package Soneso\StellarSDK\SEP\Quote
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#post-quote
 * @see QuoteService::postQuote()
 * @see SEP38QuoteResponse
 */
class SEP38PostQuoteRequest
{
    /**
     * @var string $context The context for the quote: 'sep6', 'sep24', or 'sep31'.
     */
    public string $context;

    /**
     * @var string $sellAsset The asset the client would like to sell in Stellar Asset Identification Format or an off-chain asset identifier.
     */
    public string $sellAsset;

    /**
     * @var string $buyAsset The asset the client would like to buy in Stellar Asset Identification Format or an off-chain asset identifier.
     */
    public string $buyAsset;

    /**
     * @var string|null $sellAmount The amount of the sell asset the client would like to exchange for buy asset.
     */
    public ?string $sellAmount = null;

    /**
     * @var string|null $buyAmount The amount of the buy asset the client would like to purchase with sell asset.
     */
    public ?string $buyAmount = null;

    /**
     * @var DateTime|null $expireAfter The client's desired expiration date and time for the quote.
     */
    public ?DateTime $expireAfter = null;

    /**
     * @var string|null $sellDeliveryMethod The method used by the client to deliver the sell asset to the Anchor.
     */
    public ?string $sellDeliveryMethod = null;

    /**
     * @var string|null $buyDeliveryMethod The method used by the Anchor to deliver the buy asset to the client.
     */
    public ?string $buyDeliveryMethod = null;

    /**
     * @var string|null $countryCode The country code of the user's current location in ISO 3166-2 or ISO 3166-1 alpha-2 format.
     */
    public ?string $countryCode = null;

    /**
     * @param string $context
     * @param string $sellAsset
     * @param string $buyAsset
     * @param string|null $sellAmount
     * @param string|null $buyAmount
     * @param DateTime|null $expireAfter
     * @param string|null $sellDeliveryMethod
     * @param string|null $buyDeliveryMethod
     * @param string|null $countryCode
     */
    public function __construct(
        string $context,
        string $sellAsset,
        string $buyAsset,
        ?string $sellAmount = null,
        ?string $buyAmount = null,
        ?DateTime $expireAfter = null,
        ?string $sellDeliveryMethod = null,
        ?string $buyDeliveryMethod = null,
        ?string $countryCode = null)
    {
        $this->context = $context;
        $this->sellAsset = $sellAsset;
        $this->buyAsset = $buyAsset;
        $this->sellAmount = $sellAmount;
        $this->buyAmount = $buyAmount;
        $this->expireAfter = $expireAfter;
        $this->sellDeliveryMethod = $sellDeliveryMethod;
        $this->buyDeliveryMethod = $buyDeliveryMethod;
        $this->countryCode = $countryCode;
    }

    /**
     * @return array<array-key, mixed> json data
     */
    public function toJson() : array {

        /**
         * @var array<array-key, mixed> $result
         */
        $result = [
            'sell_asset' => $this->sellAsset,
            'buy_asset' => $this->buyAsset,
            'context' => $this->context,
            ];

        if ($this->sellAmount !== null) {
            $result['sell_amount'] = $this->sellAmount;
        }
        if ($this->buyAmount !== null) {
            $result['buy_amount'] = $this->buyAmount;
        }
        if ($this->expireAfter !== null) {
            $result['expire_after'] = $this->expireAfter->format(DateTimeInterface::ATOM) ;
        }
        if ($this->sellDeliveryMethod !== null) {
            $result['sell_delivery_method'] = $this->sellDeliveryMethod;
        }
        if ($this->buyDeliveryMethod !== null) {
            $result['buy_delivery_method'] = $this->buyDeliveryMethod;
        }
        if ($this->countryCode !== null) {
            $result['country_code'] = $this->countryCode;
        }

        return $result;
    }

}