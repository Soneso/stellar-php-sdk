<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\RegulatedAssets;

use Exception;

/**
 * Exception thrown when the action URL returns an invalid or malformed response.
 *
 * This exception is thrown by RegulatedAssetsService::postAction() when:
 * - The response lacks a required 'result' field
 * - The result field contains an unknown/unsupported value
 * - Required fields for a given result are missing (e.g., 'next_url' for follow_next_url)
 * - HTTP status code is not 200
 * - Response body is not valid JSON
 *
 * The exception message contains details about what was invalid or missing. The exception
 * code contains the HTTP status code if available.
 *
 * Handling Recommendations:
 * - Log the exception details for debugging action server issues
 * - Fall back to opening the action_url in a browser (GET method)
 * - Display an error message to the user about completing the action manually
 * - Consider retry logic for transient errors
 *
 * @package Soneso\StellarSDK\SEP\RegulatedAssets
 * @see RegulatedAssetsService::postAction()
 */
class SEP08InvalidPostActionResponse extends Exception
{

}