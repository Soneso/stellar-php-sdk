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
     * @var string $total The total amount of fee applied.
     */
    public string $total;
    /**
     * @var string $asset The asset in which the fee is applied, represented through the Asset Identification Format.
     */
    public string $asset;
    /**
     * @var array<SEP31FeeDetailsDetails>|null $details (optional) An array of objects detailing the fees that were used to calculate the
     * conversion price. This can be used to detail the price components for the end-user.
     */
    public ?array $details = null;

    /**
     * @param string $total The total amount of fee applied.
     * @param string $asset The asset in which the fee is applied, represented through the Asset Identification Format.
     * @param array<SEP31FeeDetailsDetails>|null $details
     */
    public function __construct(string $total, string $asset, ?array $details = null)
    {
        $this->total = $total;
        $this->asset = $asset;
        $this->details = $details;
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