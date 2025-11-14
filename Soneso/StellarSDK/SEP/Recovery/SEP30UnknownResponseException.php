<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

use Exception;

/**
 * Exception thrown when the SEP-30 recovery server returns an unexpected or unknown response.
 *
 * This indicates an unhandled error condition, such as:
 * - HTTP status codes not explicitly handled (e.g., 5xx server errors other than 500)
 * - Malformed or unexpected response format from the server
 * - Network-level errors that don't fit other exception categories
 * - Server implementation errors or unexpected behavior
 *
 * This is a catch-all exception for error conditions that don't map to the standard
 * SEP-30 error responses (400, 401, 404, 409, 500). Clients should log the full
 * exception details for debugging and may want to implement retry logic with exponential
 * backoff for transient server issues.
 *
 * @package Soneso\StellarSDK\SEP\Recovery
 * @see https://github.com/stellar/stellar-protocol/blob/v0.8.1/ecosystem/sep-0030.md#errors
 * @see RecoveryService
 */
class SEP30UnknownResponseException extends Exception
{

}