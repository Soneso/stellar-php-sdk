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
 * @package Soneso\StellarSDK\SEP\CrossBorderPayments
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0031.md
 * @see CrossBorderPaymentsService
 */
class SEP31BadRequestException extends Exception
{

}