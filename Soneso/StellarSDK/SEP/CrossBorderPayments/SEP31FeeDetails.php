<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\CrossBorderPayments;

/**
 * Detailed fee breakdown for a cross-border payment transaction via SEP-31.
 *
 * This class represents the comprehensive fee structure applied to a transaction,
 * including the total fee amount, the asset in which fees are charged, and an
 * optional breakdown of individual fee components.
 *
 * @package Soneso\StellarSDK\SEP\CrossBorderPayments
 * @see https://github.com/stellar/stellar-protocol/blob/v3.1.0/ecosystem/sep-0031.md#fee-details-object-schema
 * @see SEP31TransactionResponse
 * @see SEP31FeeDetailsDetails
 */
class SEP31FeeDetails
{
    /**
     * @param string $total The total amount of fee applied.
     * @param string $asset The asset in which the fee is applied, represented through the Asset Identification Format.
     * @param array<SEP31FeeDetailsDetails>|null $details Optional array of detailed fee components that sum to the total.
     */
    public function __construct(
        public string $total,
        public string $asset,
        public ?array $details = null,
    ) {
    }

    /**
     * Constructs a new instance of SEP31FeeDetails by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP31FeeDetails the object containing the parsed data.
     */
    public static function fromJson(array $json) : SEP31FeeDetails
    {
        $result = new SEP31FeeDetails($json['total'], $json['asset']);

        if (isset($json['details'])){
            /**
             * @var array<SEP31FeeDetailsDetails> $details
             */
            $details = array();
            foreach ($json['details'] as $detail) {
                $details[] = SEP31FeeDetailsDetails::fromJson($detail);
            }
            $result->details = $details;
        }

        return $result;

    }

}