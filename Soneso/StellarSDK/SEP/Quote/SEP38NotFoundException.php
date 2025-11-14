<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

use Exception;

/**
 * Exception thrown when a requested quote cannot be found via SEP-38.
 *
 * This exception is raised when attempting to retrieve a quote by ID that does
 * not exist, has expired, or is not accessible to the authenticated user.
 *
 * @package Soneso\StellarSDK\SEP\Quote
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#get-quote
 * @see QuoteService::getQuote()
 */
class SEP38NotFoundException extends Exception
{

}