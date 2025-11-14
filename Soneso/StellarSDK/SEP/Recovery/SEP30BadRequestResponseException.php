<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

use Exception;

/**
 * Exception thrown when the SEP-30 recovery server returns HTTP 400 Bad Request.
 *
 * This indicates that the request is invalid, such as:
 * - Missing required fields in the request body
 * - Malformed JSON data
 * - Invalid authentication method types or values
 * - Invalid identity roles or authentication methods
 * - Malformed transaction XDR (for signing operations)
 * - Transaction contains unauthorized operations
 *
 * Clients should validate request data before submission to avoid this error.
 * Check the exception message for specific validation failure details.
 *
 * @package Soneso\StellarSDK\SEP\Recovery
 * @see https://github.com/stellar/stellar-protocol/blob/v0.8.1/ecosystem/sep-0030.md#errors
 * @see RecoveryService
 */
class SEP30BadRequestResponseException extends Exception
{

}