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
     * @param string $context The context for the quote: 'sep6', 'sep24', or 'sep31'.
     * @param string $sellAsset The asset the client would like to sell in Stellar Asset Identification Format or an off-chain asset identifier.
     * @param string $buyAsset The asset the client would like to buy in Stellar Asset Identification Format or an off-chain asset identifier.
     * @param string|null $sellAmount The amount of the sell asset the client would like to exchange for buy asset (mutually exclusive with buyAmount).
     * @param string|null $buyAmount The amount of the buy asset the client would like to purchase with sell asset (mutually exclusive with sellAmount).
     * @param DateTime|null $expireAfter The client's desired expiration date and time for the quote.
     * @param string|null $sellDeliveryMethod The method used by the client to deliver the sell asset to the Anchor.
     * @param string|null $buyDeliveryMethod The method used by the Anchor to deliver the buy asset to the client.
     * @param string|null $countryCode The country code of the user's current location in ISO 3166-2 or ISO 3166-1 alpha-2 format.
     */
    public function __construct(
        public string $context,
        public string $sellAsset,
        public string $buyAsset,
        public ?string $sellAmount = null,
        public ?string $buyAmount = null,
        public ?DateTime $expireAfter = null,
        public ?string $sellDeliveryMethod = null,
        public ?string $buyDeliveryMethod = null,
        public ?string $countryCode = null,
    ) {
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