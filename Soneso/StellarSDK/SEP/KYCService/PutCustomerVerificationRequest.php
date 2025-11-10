<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\KYCService;

/**
 * Request object for PUT /customer/verification endpoint.
 *
 * This endpoint allows servers to accept verification data values such as confirmation codes
 * that verify previously provided fields (e.g., mobile_number or email_address).
 *
 * @package Soneso\StellarSDK\SEP\KYCService
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#customer-put-verification SEP-12 v1.15.0
 * @deprecated This endpoint is deprecated per SEP-12 specification. Anchors should use alternative verification methods.
 */
class PutCustomerVerificationRequest
{
    /**
     * @var string|null $id The ID of the customer as returned in the response of a previous PUT request.
     */
    public ?string $id = null;

    /**
     * One or more SEP-9 fields appended with _verification ( *_verification)
     * See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#customer-put-verification
     * @var array<array-key, string>|null
     */
    public ?array $verificationFields = null;

    /**
     * @var string|null $jwt jwt token previously received from the anchor via the SEP-10 authentication flow
     */
    public ?string $jwt = null;
}