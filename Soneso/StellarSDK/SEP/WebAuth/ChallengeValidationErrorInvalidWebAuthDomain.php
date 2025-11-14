<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

/**
 * Exception thrown when the web_auth_domain value in the challenge transaction is invalid.
 *
 * Thrown when the challenge contains a ManageData operation with key "web_auth_domain" but
 * the value does not match the domain of the authentication endpoint. This validation prevents
 * domain confusion attacks where a challenge from one domain is used to authenticate with
 * a different domain.
 *
 * Security Implications:
 * The web_auth_domain validation prevents authentication endpoint confusion attacks. Without
 * this check, an attacker could use a challenge generated for one service to authenticate
 * with a different service, potentially bypassing access controls. This validation ensures
 * the challenge was specifically created for the authentication endpoint being used, preventing
 * cross-domain authentication attacks.
 *
 * Common Scenarios:
 * - Challenge from auth.example.com used to authenticate with auth.malicious.com
 * - Server misconfiguration setting wrong web_auth_domain value
 * - Man-in-the-middle attack substituting authentication domain
 * - Challenge reuse across different authentication endpoints
 * - Domain mismatch due to subdomain or protocol differences
 *
 * @package Soneso\StellarSDK\SEP\WebAuth
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#challenge SEP-10 Web Auth Domain Validation
 */
class ChallengeValidationErrorInvalidWebAuthDomain extends \ErrorException
{

}