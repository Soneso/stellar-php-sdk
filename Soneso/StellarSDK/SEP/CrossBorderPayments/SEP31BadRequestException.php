<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\SEP\CrossBorderPayments;

use Exception;

/**
 * Exception thrown when a SEP-31 request returns HTTP 400 Bad Request.
 *
 * This exception indicates that the request was malformed or contained invalid
 * parameters. The error message provides details about what was incorrect in
 * the request.
 *
 * Common causes:
 * - Invalid or missing required fields
 * - Amount outside min/max limits
 * - Unsupported asset or destination asset
 * - Invalid quote_id or expired quote
 * - Missing or invalid sender_id or receiver_id
 *
 * @package Soneso\StellarSDK\SEP\CrossBorderPayments
 * @see https://github.com/stellar/stellar-protocol/blob/v3.1.0/ecosystem/sep-0031.md
 * @see CrossBorderPaymentsService
 */
class SEP31BadRequestException extends Exception
{

}