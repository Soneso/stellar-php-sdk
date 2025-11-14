<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Exception;

/**
 * Exception thrown when a SEP-24 request fails due to client or server errors.
 *
 * This exception is raised when the anchor service returns an error response indicating that
 * the request could not be processed. Common scenarios include:
 * - HTTP 400 Bad Request: Invalid parameters, missing required fields, or malformed data
 * - HTTP 500 Internal Server Error: Anchor service encountered an unexpected error
 * - HTTP 503 Service Unavailable: Anchor service is temporarily unavailable
 * - Other HTTP error codes returned by the anchor
 *
 * HTTP Status: 400, 500, 503, or other error codes
 *
 * Client Handling:
 * - For 400 errors: Review request parameters and correct invalid or missing fields
 * - For 500 errors: Log the error and contact anchor support if persistent
 * - For 503 errors: Implement exponential backoff and retry logic
 * - Parse the error response body for detailed error messages from the anchor
 * - Check the 'error' field in the JSON response for specific error descriptions
 * - Do not retry 400 errors without fixing the request parameters
 *
 * Error Response Format (SEP-24):
 * {
 *   "error": "descriptive error message from anchor"
 * }
 *
 * @package Soneso\StellarSDK\SEP\Interactive
 * @see https://github.com/stellar/stellar-protocol/blob/v3.8.0/ecosystem/sep-0024.md SEP-24 Hosted Deposit and Withdrawal
 * @see https://github.com/stellar/stellar-protocol/blob/v3.8.0/ecosystem/sep-0024.md#error-handling SEP-24 Error Handling
 */
class RequestErrorException extends Exception
{

}