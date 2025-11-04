<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

use Exception;

/**
 * Exception thrown when a SEP-38 request returns HTTP 403 Forbidden.
 *
 * This exception indicates that the authenticated user does not have permission
 * to access the requested quote or perform the requested operation, typically
 * due to insufficient authentication or authorization.
 *
 * @package Soneso\StellarSDK\SEP\Quote
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md
 * @see QuoteService::postQuote()
 * @see QuoteService::getQuote()
 */
class SEP38PermissionDeniedException extends Exception
{

}