<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\SEP\RegulatedAssets;

/**
 * Response indicating the transaction is not compliant and cannot be revised.
 *
 * This response means the transaction violates the issuer's compliance criteria in a way
 * that cannot be corrected by adding additional operations or modifications. The wallet
 * should display the error message to the user explaining why the transaction was rejected.
 *
 * Common rejection reasons:
 * - Destination account is blocked or sanctioned
 * - Transaction amount exceeds velocity limits that cannot be split
 * - Source or destination is in a restricted jurisdiction
 * - Asset holder has incomplete or expired KYC information
 * - Transaction violates regulatory constraints
 *
 * The wallet should not retry rejected transactions without addressing the underlying
 * compliance issue described in the error message.
 *
 * HTTP Status Code: 400
 *
 * @package Soneso\StellarSDK\SEP\RegulatedAssets
 * @see https://github.com/stellar/stellar-protocol/blob/v1.7.4/ecosystem/sep-0008.md#rejected SEP-0008 v1.7.4
 */
class SEP08PostTransactionRejected extends SEP08PostTransactionResponse
{

    /**
     * @var string $error A human-readable string explaining why the transaction is not
     * compliant and could not be made compliant.
     */
    public string $error;

    /**
     * @param string $error
     */
    public function __construct(string $error)
    {
        $this->error = $error;
    }

}