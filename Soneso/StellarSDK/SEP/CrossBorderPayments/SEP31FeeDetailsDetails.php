<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\SEP\CrossBorderPayments;

/**
 * Individual fee component within a cross-border payment transaction fee breakdown.
 *
 * This class represents a single fee item within the detailed fee structure,
 * such as ACH fees, conciliation fees, or service fees. Multiple instances of
 * this class combine to form the complete fee details.
 *
 * @package Soneso\StellarSDK\SEP\CrossBorderPayments
 * @see https://github.com/stellar/stellar-protocol/blob/v3.1.0/ecosystem/sep-0031.md#fee-details-object-schema
 * @see SEP31FeeDetails
 */
class SEP31FeeDetailsDetails
{
    /**
     * @var string $name The name of the fee, for example ACH fee, Brazilian conciliation fee, Service fee, etc.
     */
    public string $name;

    /**
     * @var string $amount The amount of asset applied. If fee_details.details is provided,
     * sum(fee_details.details.amount) should be equals fee_details.total.
     */
    public string $amount;

    /**
     * @var string|null $description (optional) A text describing the fee.
     */
    public ?string $description = null;

    /**
     * @param string $name The name of the fee, for example ACH fee, Brazilian conciliation fee, Service fee, etc.
     * @param string $amount The amount of asset applied. If fee_details.details is provided,
     *  sum(fee_details.details.amount) should be equals fee_details.total.
     * @param string|null $description (optional) A text describing the fee.
     */
    public function __construct(string $name, string $amount, ?string $description = null)
    {
        $this->name = $name;
        $this->amount = $amount;
        $this->description = $description;
    }

    /**
     * Constructs a new instance of SEP31FeeDetailsDetails by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP31FeeDetailsDetails the object containing the parsed data.
     */
    public static function fromJson(array $json) : SEP31FeeDetailsDetails
    {
        $result = new SEP31FeeDetailsDetails($json['name'], $json['amount']);
        if (isset($json['description'])) $result->description = $json['description'];

        return $result;

    }

}