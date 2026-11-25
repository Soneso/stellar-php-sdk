<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

/**
 * Exception thrown when submitting signed authorization entries returns an unexpected response.
 *
 * This exception is thrown when the POST request to the WEB_AUTH_FOR_CONTRACTS_ENDPOINT
 * returns an HTTP status code other than 200, 400, or 504, which have their own handling.
 * SEP-45 treats every status other than 200 as an error.
 *
 * Common scenarios:
 * - 401 Unauthorized: Server requires additional authentication
 * - 403 Forbidden: Client not allowed to use the service
 * - 404 Not Found: Endpoint not available
 * - 429 Too Many Requests: Rate limit exceeded
 * - 500 Internal Server Error: Server-side error
 * - 502 Bad Gateway: Proxy or gateway error
 * - 503 Service Unavailable: Server temporarily unavailable
 *
 * The exception message includes only the HTTP status code so an unexpected response body
 * (which may carry a token) does not reach logs through the message. Callers can still
 * inspect the raw response body via getResponseBody().
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Token Endpoint
 */
class SubmitContractChallengeUnknownResponseException extends \Exception
{
    private int $httpStatusCode;
    private string $responseBody;

    /**
     * Creates a new unknown response exception.
     *
     * Sets a status-only exception message while preserving the raw response body on
     * getResponseBody().
     *
     * @param string $responseBody the raw response body from the server
     * @param int $httpStatusCode the HTTP status code from the response
     */
    public function __construct(string $responseBody, int $httpStatusCode)
    {
        $this->responseBody = $responseBody;
        $this->httpStatusCode = $httpStatusCode;
        // Keep the response body out of the exception message: the body of an unexpected
        // response can carry a token and must not reach logs through the message.
        parent::__construct("Unknown response from server (HTTP $httpStatusCode)");
    }

    /**
     * Returns the HTTP status code from the response.
     *
     * @return int the HTTP status code
     */
    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * Returns the raw response body from the server.
     *
     * The response body is kept out of the exception message because an unexpected response
     * may contain sensitive data such as a token.
     *
     * @return string the response body
     */
    public function getResponseBody(): string
    {
        return $this->responseBody;
    }
}
