<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\SEP\Quote;

use Exception;

/**
 * Exception thrown when a SEP-38 request returns HTTP 400 Bad Request.
 *
 * This exception indicates that the request was malformed or contained invalid
 * parameters such as unsupported assets, missing required fields, or invalid
 * amount specifications.
 *
 * @package Soneso\StellarSDK\SEP\Quote
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md
 * @see QuoteService
 */
class SEP38BadRequestException extends Exception
{

}