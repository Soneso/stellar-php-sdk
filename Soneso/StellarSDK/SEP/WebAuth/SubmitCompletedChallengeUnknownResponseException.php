<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

/**
 * Exception thrown when submitting the signed challenge results in an unexpected HTTP response.
 *
 * Thrown when the authentication server returns an HTTP status code that is not recognized
 * by the SEP-10 protocol (neither 200 OK, 400 Bad Request, nor 504 Gateway Timeout). This
 * indicates an unexpected server response that doesn't follow the SEP-10 specification or
 * represents an unusual error condition.
 *
 * Security Implications:
 * Unexpected responses could indicate server misconfiguration, protocol violations, or
 * potential man-in-the-middle attacks. Clients should treat unknown responses as authentication
 * failures and not proceed with protected operations. Logging these responses is important for
 * detecting anomalies and potential security issues.
 *
 * Common Scenarios:
 * - Server returns 500 Internal Server Error during authentication processing
 * - Authentication endpoint returns 401/403 (non-standard for SEP-10)
 * - Server misconfiguration returning unexpected status codes
 * - Proxy or intermediary returning non-SEP-10 compliant responses
 * - Server implementation not following SEP-10 specification
 * - Network issues causing malformed HTTP responses
 *
 * @package Soneso\StellarSDK\SEP\WebAuth
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#token SEP-10 Token Endpoint Response Codes
 */
class SubmitCompletedChallengeUnknownResponseException extends \ErrorException
{

}