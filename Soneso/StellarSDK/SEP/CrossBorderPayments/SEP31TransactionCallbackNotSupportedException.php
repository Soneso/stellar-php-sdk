<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\CrossBorderPayments;

use Exception;

/**
 * Exception thrown when transaction callbacks are not supported by the Receiving Anchor.
 *
 * This exception is raised when attempting to register a callback URL via
 * PUT /transactions/:id/callback but the Receiving Anchor does not support
 * this optional feature. Returns HTTP 404 Not Found.
 *
 * Note: Transaction callbacks are an optional SEP-31 feature. Sending Anchors
 * should fall back to polling GET /transactions/:id if callbacks are not supported.
 *
 * @package Soneso\StellarSDK\SEP\CrossBorderPayments
 * @see https://github.com/stellar/stellar-protocol/blob/v3.1.0/ecosystem/sep-0031.md#put-transaction-callback
 * @see CrossBorderPaymentsService::putTransactionCallback()
 */
class SEP31TransactionCallbackNotSupportedException extends Exception
{

}