<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\KYCService;

class PutCustomerVerificationRequest
{
    /// The ID of the customer as returned in the response of a previous PUT request.
    public ?string $id = null;

    /// One or more SEP-9 fields appended with _verification ( *_verification)
    /// See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#customer-put-verification
    public ?array $verificationFields = null; // [string => string]

    /// jwt previously received from the anchor via the SEP-10 authentication flow
    public ?string $jwt = null;
}