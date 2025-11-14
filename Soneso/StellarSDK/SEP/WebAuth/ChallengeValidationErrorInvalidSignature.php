<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

/**
 * Exception thrown when the challenge transaction signature validation fails.
 *
 * Thrown when the challenge transaction does not have exactly one signature, the signature
 * is not of the expected type, or the signature verification fails for the server's signing
 * key. This validates that the challenge was genuinely created and signed by the authentication
 * server.
 *
 * Security Implications:
 * Server signature verification prevents man-in-the-middle attacks and ensures the challenge
 * originated from the legitimate authentication server. Without verifying the server's signature,
 * a malicious actor could generate fake challenges to capture client signatures. This validation
 * confirms the server controls the private key corresponding to the SIGNING_KEY published in
 * stellar.toml, proving server authenticity.
 *
 * Common Scenarios:
 * - Challenge signed by wrong server or malicious actor
 * - Signature corrupted during transmission
 * - Server using incorrect signing key (not matching stellar.toml)
 * - Challenge has multiple signatures (should have exactly one from server)
 * - Invalid signature type or malformed signature structure
 *
 * @package Soneso\StellarSDK\SEP\WebAuth
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#challenge SEP-10 Server Signature Verification
 */
class ChallengeValidationErrorInvalidSignature extends \ErrorException
{

}