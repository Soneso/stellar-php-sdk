<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\CrossBorderPayments;

use Exception;

/**
 * Exception thrown when a requested transaction cannot be found via SEP-31.
 *
 * This exception is raised when attempting to retrieve a transaction that does
 * not exist or is not accessible. Typically occurs when calling GET /transactions/:id
 * with an invalid or unknown transaction ID. Returns HTTP 404 Not Found.
 *
 * Common causes:
 * - Transaction ID does not exist
 * - Transaction belongs to a different Sending Anchor
 * - Transaction has been purged from the system
 *
 * @package Soneso\StellarSDK\SEP\CrossBorderPayments
 * @see https://github.com/stellar/stellar-protocol/blob/v3.1.0/ecosystem/sep-0031.md#transaction
 * @see CrossBorderPaymentsService::getTransaction()
 */
class SEP31TransactionNotFoundException extends Exception
{

}