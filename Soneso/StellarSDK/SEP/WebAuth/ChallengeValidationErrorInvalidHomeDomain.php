<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

/**
 * Exception thrown when the challenge transaction contains an invalid home domain.
 *
 * Thrown when the first operation in the challenge transaction does not have a ManageData
 * operation key matching the expected format "<home_domain> auth". This validation ensures
 * that the challenge was generated for the correct service and prevents domain confusion attacks.
 *
 * Security Implications:
 * The home domain validation is critical for preventing phishing and domain substitution attacks.
 * If a client accepts a challenge with the wrong home domain, it could inadvertently authenticate
 * with a malicious server impersonating the legitimate service. This validation ensures the
 * challenge was issued by the expected authentication server.
 *
 * Common Scenarios:
 * - Challenge generated for wrong domain (e.g., "malicious.com auth" instead of "example.com auth")
 * - Malformed home domain string in the operation key
 * - Server misconfiguration returning challenges for incorrect domain
 * - Man-in-the-middle attack attempting to substitute domains
 *
 * @package Soneso\StellarSDK\SEP\WebAuth
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#challenge SEP-10 Challenge Operations
 */
class ChallengeValidationErrorInvalidHomeDomain extends \ErrorException
{

}