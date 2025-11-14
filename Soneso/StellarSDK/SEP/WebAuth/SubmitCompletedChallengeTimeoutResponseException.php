<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

/**
 * Exception thrown when submitting the signed challenge results in a gateway timeout (504).
 *
 * Thrown when the authentication server returns HTTP status code 504 (Gateway Timeout) in
 * response to the signed challenge submission. This indicates the server took too long to
 * process the authentication request, typically due to infrastructure issues, network problems,
 * or server overload.
 *
 * Security Implications:
 * While timeouts are usually infrastructure-related rather than security issues, repeated
 * timeouts could indicate a denial-of-service attack on the authentication server. Clients
 * should implement exponential backoff and retry limits to avoid contributing to server load.
 * Timeouts should be logged and monitored for patterns that might indicate attacks.
 *
 * Common Scenarios:
 * - Authentication server experiencing high load or performance issues
 * - Network connectivity problems between client and server
 * - Server timeout while verifying signatures or loading account data
 * - Database or Horizon API timeouts during account validation
 * - Infrastructure issues with reverse proxy or load balancer
 * - Temporary server maintenance or degraded performance
 *
 * @package Soneso\StellarSDK\SEP\WebAuth
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#token SEP-10 Token Endpoint
 */
class SubmitCompletedChallengeTimeoutResponseException extends \ErrorException
{

}