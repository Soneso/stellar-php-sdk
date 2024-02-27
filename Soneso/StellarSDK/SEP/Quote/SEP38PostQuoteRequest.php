<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

use DateTime;
use DateTimeInterface;

class SEP38PostQuoteRequest
{
    public string $context;
    public string $sellAsset;
    public string $buyAsset;
    public ?string $sellAmount = null;
    public ?string $buyAmount = null;
    public ?DateTime $expireAfter = null;
    public ?string $sellDeliveryMethod = null;
    public ?string $buyDeliveryMethod = null;
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