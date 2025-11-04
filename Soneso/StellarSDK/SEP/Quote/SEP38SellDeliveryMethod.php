<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

/**
 * Delivery method for selling an off-chain asset to an anchor via SEP-38.
 *
 * This class describes a method by which a user can deliver an off-chain asset
 * to the anchor, such as bank transfer, card payment, or cash deposit.
 *
 * @package Soneso\StellarSDK\SEP\Quote
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#get-info
 * @see SEP38Asset
 */
class SEP38SellDeliveryMethod
{
    /**
     * @var string $name The identifier for the delivery method.
     */
    public string $name;

    /**
     * @var string $description Human-readable description of the delivery method.
     */
    public string $description;

    /**
     * @param string $name
     * @param string $description
     */
    public function __construct(string $name, string $description)
    {
        $this->name = $name;
        $this->description = $description;
    }

    /**
     * Constructs a new instance of SEP38SellDeliveryMethod by using the given data.
     *
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP38SellDeliveryMethod the object containing the parsed data.
     */
    public static function fromJson(array $json) : SEP38SellDeliveryMethod
    {
        return new SEP38SellDeliveryMethod($json['name'], $json['description']);
    }

}