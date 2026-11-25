<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

/**
 * Exception thrown when submitting the signed challenge results in an unexpected HTTP response.
 *
 * Thrown when the authentication server returns an HTTP status code other than 200 OK,
 * 400 Bad Request and 504 Gateway Timeout, which have their own handling. SEP-10 treats
 * every status other than 200 as an error.
 *
 * Security Implications:
 * Unexpected responses could indicate server misconfiguration, protocol violations, or
 * potential man-in-the-middle attacks. Clients should treat unknown responses as authentication
 * failures and not proceed with protected operations. Logging these responses is important for
 * detecting anomalies and potential security issues.
 *
 * Common Scenarios:
 * - Server returns 500 Internal Server Error during authentication processing
 * - Authentication endpoint returns 401 or 403
 * - Server misconfiguration returning unexpected status codes
 * - Proxy or intermediary returning its own error status
 * - Server implementation not following SEP-10 specification
 * - Network issues causing malformed HTTP responses
 *
 * @package Soneso\StellarSDK\SEP\WebAuth
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#token SEP-10 Token Endpoint Response Codes
 */
class SubmitCompletedChallengeUnknownResponseException extends \ErrorException
{

}