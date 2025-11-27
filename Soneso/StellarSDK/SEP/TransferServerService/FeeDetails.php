<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

/**
 * Describes fee information for a transfer operation.
 *
 * Contains the total fee amount, the asset in which the fee is charged,
 * and optional detailed breakdown of fee components.
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/v4.3.0/ecosystem/sep-0006.md SEP-06 Specification
 */
class FeeDetails
{
    /**
     * @param string $total The total amount of fee applied.
     * @param string $asset The asset in which the fee is applied, represented through the Asset Identification Format.
     * @param array<FeeDetailsDetails>|null $details An array of objects detailing the fees that were used to
     * calculate the conversion price. This can be used to detail the price components for the end-user.
     */
    public function __construct(
        public string $total,
        public string $asset,
        public ?array $details = null,
    ) {
    }

    /**
     * Constructs a new instance of FeeDetails by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return FeeDetails the object containing the parsed data.
     */
    public static function fromJson(array $json) : FeeDetails
    {
        $result = new FeeDetails($json['total'], $json['asset']);
        if (isset($json['details'])) {
            $result->details = array();
            foreach ($json['details'] as $detail) {
                $result->details[] = FeeDetailsDetails::fromJson($detail);
            }
        }

        return $result;
    }
}