<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

/**
 * Request parameters for querying fee information via SEP-06.
 *
 * Used to query the fee that would be charged for a specific deposit or withdrawal
 * operation. Important when anchors have complex fee schedules that cannot be fully
 * expressed through the simple fee_fixed, fee_percent, and fee_minimum fields in
 * the info endpoint response.
 *
 * Required fields are operation (deposit/withdraw), assetCode, and amount.
 * Optional type field helps specify the deposit/withdrawal method.
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/v4.3.0/ecosystem/sep-0006.md SEP-06 Specification
 * @see TransferServerService::fee()
 * @see FeeResponse
 */
class FeeRequest
{
    /**
     * @param string $operation Kind of operation (deposit or withdraw).
     * @param string $assetCode Stellar asset code.
     * @param float $amount Amount of the asset that will be deposited/withdrawn.
     * @param string|null $type Type of deposit or withdrawal (SEPA, bank_account, cash, etc...).
     * @param string|null $jwt jwt previously received from the anchor via the SEP-10 authentication flow
     */
    public function __construct(
        public string $operation,
        public string $assetCode,
        public float $amount,
        public ?string $type = null,
        public ?string $jwt = null,
    ) {
    }


}