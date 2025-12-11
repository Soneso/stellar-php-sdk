<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

/**
 * Exception thrown when a contract challenge has an invalid web_auth_domain argument.
 *
 * This exception is thrown when the web_auth_domain argument in the web_auth_verify function
 * does not match the server's authentication domain (extracted from the auth endpoint URL).
 * This validation ensures the challenge was issued by the expected authentication server.
 *
 * Security Impact:
 * High security check. An incorrect web_auth_domain could indicate the challenge was
 * issued by a different server than expected, potentially part of a phishing or
 * man-in-the-middle attack. Always verify the web_auth_domain matches the server.
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Challenge Validation
 */
class ContractChallengeValidationErrorInvalidWebAuthDomain extends ContractChallengeValidationError
{

}
