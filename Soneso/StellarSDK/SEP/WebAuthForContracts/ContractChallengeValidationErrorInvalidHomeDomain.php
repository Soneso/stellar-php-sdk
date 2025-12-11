<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

/**
 * Exception thrown when a contract challenge has an invalid home_domain argument.
 *
 * This exception is thrown when the home_domain argument in the web_auth_verify function
 * does not match the expected home domain. This validation prevents domain confusion attacks
 * where a challenge for one service could be used to authenticate with another service.
 *
 * Security Impact:
 * High security check. An incorrect home_domain could indicate a man-in-the-middle attack
 * or attempt to use credentials across different services. Always verify the home_domain
 * matches the expected service domain.
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Challenge Validation
 */
class ContractChallengeValidationErrorInvalidHomeDomain extends ContractChallengeValidationError
{

}
