<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

/**
 * Exception thrown when a contract challenge has an invalid account argument.
 *
 * This exception is thrown when the account argument in the web_auth_verify function
 * does not match the expected client account ID. This validation ensures the challenge
 * is authenticating the correct contract account and prevents account substitution attacks.
 *
 * Security Impact:
 * Critical security check. If the account doesn't match, an attacker may be trying to
 * authenticate as a different account. Always verify the account matches the client
 * account being authenticated.
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Challenge Validation
 */
class ContractChallengeValidationErrorInvalidAccount extends ContractChallengeValidationError
{

}
