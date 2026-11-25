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
 * - The args differ between authorization entries that have the same nonce
 * - The web_auth_domain_account is missing or doesn't match the server's signing key
 * - Only one of client_domain and client_domain_account is present
 * - Client domain arguments are present but not requested, or don't match the expected values
 * - Args cannot be parsed or decoded
 *
 * Once the args of all entries are equal, a missing or mismatched account, home_domain or
 * web_auth_domain throws its own exception. A nonce that differs between entries, or a missing
 * or empty nonce, throws ContractChallengeValidationErrorInvalidNonce.
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
