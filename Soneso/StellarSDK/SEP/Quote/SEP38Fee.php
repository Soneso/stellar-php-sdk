<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

/**
 * Fee structure for a quote or price in SEP-38.
 *
 * This class represents the total fee charged for an exchange operation,
 * including the fee amount, the asset in which it is charged, and an optional
 * breakdown of individual fee components.
 *
 * @package Soneso\StellarSDK\SEP\Quote
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#fee-object-schema
 * @see SEP38QuoteResponse
 * @see SEP38PriceResponse
 * @see SEP38FeeDetails
 */
class SEP38Fee
{
    /**
     * @var string $total The total fee amount.
     */
    public string $total;

    /**
     * @var string $asset The asset in which the fee is charged.
     */
    public string $asset;

    /**
     * @var array<SEP38FeeDetails>|null $details Optional detailed breakdown of fee components.
     */
    public ?array $details = null;

    /**
     * @param string $total
     * @param string $asset
     * @param array<SEP38FeeDetails>|null $details
     */
    public function __construct(string $total, string $asset, ?array $details = null)
    {
        $this->total = $total;
        $this->asset = $asset;
        $this->details = $details;
    }

    /**
     * Constructs a new instance of SEP38Fee by using the given data.
     *
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP38Fee the object containing the parsed data.
     */
    public static function fromJson(array $json) : SEP38Fee
    {
        $total = $json['total'];
        $asset = $json['asset'];

        /**
         * @var array<SEP38FeeDetails> | null $details
         */
        $details = null;
        if (isset($json['details'])) {
            $details = array();
            foreach ($json['details'] as $detail) {
                $details[] = SEP38FeeDetails::fromJson($detail);
            }
        }

        return new SEP38Fee(
            total: $total,
            asset: $asset,
            details: $details,
        );
    }

}