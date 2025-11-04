<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\CrossBorderPayments;

use Exception;

/**
 * Exception thrown when a SEP-31 request returns an unexpected HTTP status code.
 *
 * This exception is raised when the server responds with a status code that is
 * not explicitly handled by the SDK. The status code and response body are
 * available in the exception message and code.
 *
 * @package Soneso\StellarSDK\SEP\CrossBorderPayments
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0031.md
 * @see CrossBorderPaymentsService
 */
class SEP31UnknownResponseException extends Exception
{

}