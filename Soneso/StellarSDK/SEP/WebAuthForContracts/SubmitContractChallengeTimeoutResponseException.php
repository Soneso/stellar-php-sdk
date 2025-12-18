<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

/**
 * Exception thrown when submitting signed authorization entries times out.
 *
 * This exception is thrown when the POST request to the WEB_AUTH_FOR_CONTRACTS_ENDPOINT
 * returns HTTP status code 504 (Gateway Timeout), indicating the server could not complete
 * the request in a reasonable time.
 *
 * Common causes:
 * - Server experiencing high load or performance issues
 * - Transaction simulation taking too long
 * - Database or network connectivity issues on the server
 * - Server-side timeout thresholds exceeded
 *
 * Recommended Actions:
 * - Retry the submission after a brief delay
 * - Check server status or service health endpoints
 * - Contact server operator if timeouts persist
 * - Consider implementing exponential backoff for retries
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Token Endpoint
 */
class SubmitContractChallengeTimeoutResponseException extends \Exception
{
    /**
     * Creates a new timeout exception.
     */
    public function __construct()
    {
        parent::__construct("Request timed out (504)");
    }
}
