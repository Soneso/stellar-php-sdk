<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

/**
 * Exception thrown when a contract challenge has an invalid function name.
 *
 * This exception is thrown when the function_name in an authorization entry is not
 * "web_auth_verify". Per SEP-45, all authorization entries must invoke the web_auth_verify
 * function on the web authentication contract. Any other function name indicates a malformed
 * or malicious challenge.
 *
 * Security Impact:
 * Critical security check. If the function name is not "web_auth_verify", the challenge
 * may attempt to authorize unintended contract operations. Always reject such challenges.
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Challenge Validation
 */
class ContractChallengeValidationErrorInvalidFunctionName extends ContractChallengeValidationError
{

}
