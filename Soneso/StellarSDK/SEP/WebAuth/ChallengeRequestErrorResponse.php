<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

/**
 * Exception thrown when requesting a challenge transaction from the server fails.
 *
 * Thrown when the authentication server returns an error in response to a challenge request
 * (GET to the auth endpoint). This can occur due to invalid request parameters, server-side
 * validation failures, rate limiting, or other issues preventing challenge generation. The
 * exception message contains the error details from the server response.
 *
 * Security Implications:
 * Challenge request failures can indicate rate limiting (protection against DoS attacks),
 * invalid account parameters, or server-side security policies. Clients should respect rate
 * limits and not retry excessively. Repeated failures may indicate an attempt to enumerate
 * valid accounts or abuse the authentication system.
 *
 * Common Scenarios:
 * - Invalid account ID format in request parameters
 * - Rate limiting due to too many challenge requests
 * - Server rejecting requests for unauthorized domains or applications
 * - Missing or invalid home_domain parameter when required
 * - Client domain not supported or invalid
 * - Account blocked or restricted by server policy
 * - Server unable to parse request parameters
 *
 * @package Soneso\StellarSDK\SEP\WebAuth
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#challenge SEP-10 Challenge Request
 */
class ChallengeRequestErrorResponse extends \ErrorException
{

}