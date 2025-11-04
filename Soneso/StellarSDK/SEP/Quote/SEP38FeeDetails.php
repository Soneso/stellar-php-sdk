<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

/**
 * Individual fee component within a quote or price fee breakdown.
 *
 * This class represents a single itemized fee component, such as network fees,
 * service fees, or processing fees. Multiple instances combine to form the
 * complete fee structure.
 *
 * @package Soneso\StellarSDK\SEP\Quote
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#fee-details-schema
 * @see SEP38Fee
 */
class SEP38FeeDetails
{
    /**
     * @var string $name The name of the fee component.
     */
    public string $name;

    /**
     * @var string $amount The amount of this fee component.
     */
    public string $amount;

    /**
     * @var string|null $description Optional description of what this fee component covers.
     */
    public ?string $description = null;

    /**
     * @param string $name
     * @param string $amount
     * @param string|null $description
     */
    public function __construct(string $name, string $amount, ?string $description = null)
    {
        $this->name = $name;
        $this->amount = $amount;
        $this->description = $description;
    }

    /**
     * Constructs a new instance of SEP38FeeDetails by using the given data.
     *
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP38FeeDetails the object containing the parsed data.
     */
    public static function fromJson(array $json) : SEP38FeeDetails
    {
        $description = null;
        if (isset($json['description'])) {
            $description = $json['description'];
        }
        return new SEP38FeeDetails($json['name'], $json['amount'], $description);
    }

}