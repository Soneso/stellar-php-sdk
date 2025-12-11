<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

/**
 * Exception thrown when the function arguments in a contract challenge are invalid.
 *
 * This exception is thrown when:
 * - The args are not in the expected Map<Symbol, String> format
 * - Required arguments are missing (account, home_domain, web_auth_domain, etc.)
 * - Argument values don't match expected values
 * - The web_auth_domain_account doesn't match the server's signing key
 * - Client domain arguments are present but invalid
 * - Args cannot be parsed or decoded
 *
 * Security Impact:
 * High severity. Invalid arguments could indicate a malformed or malicious challenge.
 * The args contain critical security parameters that must be validated to ensure the
 * challenge is authentic and intended for the correct account and service.
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Challenge Validation
 */
class ContractChallengeValidationErrorInvalidArgs extends ContractChallengeValidationError
{

}
