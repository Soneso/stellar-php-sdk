<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

/**
 * Asset information including delivery methods and country availability for SEP-38.
 *
 * This class represents a single asset supported by an anchor's quote service,
 * including optional delivery methods for buying and selling the asset, and
 * country code restrictions.
 *
 * @package Soneso\StellarSDK\SEP\Quote
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#get-info
 * @see SEP38InfoResponse
 * @see SEP38SellDeliveryMethod
 * @see SEP38BuyDeliveryMethod
 */
class SEP38Asset
{
    /**
     * @var string $asset The asset identifier in Stellar Asset Identification Format or one of the allowed off-chain assets.
     */
    public string $asset;

    /**
     * @var array<SEP38SellDeliveryMethod>|null $sellDeliveryMethods
     */
    public ?array $sellDeliveryMethods = null;

    /**
     * @var array<SEP38SellDeliveryMethod>|null $buyDeliveryMethods
     */
    public ?array $buyDeliveryMethods = null;

    /**
     * @var array<string>|null $countryCodes
     */
    public ?array $countryCodes = null;

    /**
     * @param string $asset
     * @param array<SEP38SellDeliveryMethod>|null $sellDeliveryMethods
     * @param array<SEP38SellDeliveryMethod>|null $buyDeliveryMethods
     * @param array<string>|null $countryCodes
     */
    public function __construct(
        string $asset,
        ?array $sellDeliveryMethods = null,
        ?array $buyDeliveryMethods = null,
        ?array $countryCodes = null,
    )
    {
        $this->asset = $asset;
        $this->sellDeliveryMethods = $sellDeliveryMethods;
        $this->buyDeliveryMethods = $buyDeliveryMethods;
        $this->countryCodes = $countryCodes;
    }

    /**
     * Constructs a new instance of SEP38Asset by using the given data.
     *
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP38Asset the object containing the parsed data.
     */
    public static function fromJson(array $json) : SEP38Asset
    {
        $asset = $json['asset'];

        /**
         * @var array<SEP38SellDeliveryMethod> | null $sellMethods
         */
        $sellMethods = null;
        if (isset($json['sell_delivery_methods'])) {
            $sellMethods = array();
            foreach ($json['sell_delivery_methods'] as $method) {
                $sellMethods[] = SEP38SellDeliveryMethod::fromJson($method);
            }
        }


        /**
         * @var array<SEP38BuyDeliveryMethod> | null $buyMethods
         */
        $buyMethods = null;
        if (isset($json['buy_delivery_methods'])) {
            $buyMethods = array();
            foreach ($json['buy_delivery_methods'] as $method) {
                $buyMethods[] = SEP38BuyDeliveryMethod::fromJson($method);
            }
        }

        /**
         * @var array<string> | null $countryCodes
         */
        $countryCodes = null;
        if (isset($json['country_codes'])) {
            $countryCodes = array();
            foreach ($json['country_codes'] as $countryCode) {
                $countryCodes[] = $countryCode;
            }
        }

        return new SEP38Asset(
            asset: $asset,
            sellDeliveryMethods: $sellMethods,
            buyDeliveryMethods: $buyMethods,
            countryCodes: $countryCodes,
        );
    }
}