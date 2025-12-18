<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

/**
 * Exception thrown when requesting a contract challenge from the server fails.
 *
 * This exception is thrown when the GET request to the WEB_AUTH_FOR_CONTRACTS_ENDPOINT
 * fails or returns an error response. This can occur due to:
 * - Network connectivity issues
 * - Server returning an error (4xx or 5xx HTTP status codes)
 * - Invalid request parameters
 * - Server rate limiting
 * - Malformed response that cannot be parsed
 *
 * The exception may contain the HTTP status code and error message from the server
 * if available.
 *
 * Common Scenarios:
 * - 400 Bad Request: Invalid account format, missing required parameters
 * - 404 Not Found: Endpoint not available or service not configured
 * - 429 Too Many Requests: Rate limit exceeded
 * - 500 Internal Server Error: Server-side error
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Challenge Endpoint
 */
class ContractChallengeRequestErrorResponse extends \Exception
{
    private ?int $httpStatusCode = null;

    /**
     * Creates a new challenge request error exception.
     *
     * @param string $message error message describing the failure
     * @param int|null $httpStatusCode HTTP status code from the failed request, if available
     */
    public function __construct(string $message, ?int $httpStatusCode = null)
    {
        $this->httpStatusCode = $httpStatusCode;
        parent::__construct($message);
    }

    /**
     * Returns the HTTP status code from the failed request.
     *
     * @return int|null the HTTP status code, or null if not available
     */
    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }
}
