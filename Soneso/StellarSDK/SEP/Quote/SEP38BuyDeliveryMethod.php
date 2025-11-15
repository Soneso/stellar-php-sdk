<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

/**
 * Delivery method for receiving an off-chain asset from an anchor via SEP-38.
 *
 * This class describes a method by which an anchor can deliver an off-chain
 * asset to a user, such as bank transfer, mobile money, or cash pickup.
 *
 * @package Soneso\StellarSDK\SEP\Quote
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#get-info
 * @see SEP38Asset
 */
class SEP38BuyDeliveryMethod
{
    /**
     * @param string $name The identifier for the delivery method.
     * @param string $description Human-readable description of the delivery method.
     */
    public function __construct(
        public string $name,
        public string $description,
    ) {
    }

    /**
     * Constructs a new instance of SEP38BuyDeliveryMethod by using the given data.
     *
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP38BuyDeliveryMethod the object containing the parsed data.
     */
    public static function fromJson(array $json) : SEP38BuyDeliveryMethod
    {
        return new SEP38BuyDeliveryMethod($json['name'], $json['description']);
    }

}