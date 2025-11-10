<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\SEP\RegulatedAssets;

use Exception;

/**
 * Exception thrown when the approval server returns an invalid or malformed response.
 *
 * This exception is thrown by RegulatedAssetsService::postTransaction() when:
 * - The response lacks a required 'status' field
 * - The status field contains an unknown/unsupported value
 * - Required fields for a given status are missing (e.g., 'tx' for success, 'error' for rejected)
 * - HTTP status code is neither 200 (success/revised/pending/action_required) nor 400 (rejected)
 * - Response body is not valid JSON
 *
 * The exception message contains details about what was invalid or missing. The exception
 * code contains the HTTP status code if available.
 *
 * Handling Recommendations:
 * - Log the exception details for debugging approval server issues
 * - Display a generic error to the user about approval server unavailability
 * - Consider retry logic with exponential backoff for transient server errors
 * - Contact the asset issuer if the issue persists
 *
 * @package Soneso\StellarSDK\SEP\RegulatedAssets
 * @see RegulatedAssetsService::postTransaction()
 */
class SEP08InvalidPostTransactionResponse extends Exception
{

}