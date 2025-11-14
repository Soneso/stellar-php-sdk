<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Exception;

/**
 * Exception thrown when a requested SEP-24 transaction cannot be found.
 *
 * This exception is raised when the anchor service returns HTTP 404 Not Found for a transaction
 * query, indicating that no transaction exists with the specified identifier. This can occur when:
 * - The transaction ID is invalid or malformed
 * - The transaction does not belong to the authenticated user
 * - The transaction has been purged from the anchor's system
 * - The transaction never existed
 *
 * HTTP Status: 404 Not Found
 *
 * Client Handling:
 * - Verify the transaction ID is correct and not corrupted
 * - Check that the authenticated account has access to this transaction
 * - Do not retry the same request as the result will not change
 * - Consider the transaction ID may be from a different anchor service
 * - Check application logic for transaction ID storage and retrieval errors
 *
 * @package Soneso\StellarSDK\SEP\Interactive
 * @see https://github.com/stellar/stellar-protocol/blob/v3.8.0/ecosystem/sep-0024.md SEP-24 Hosted Deposit and Withdrawal
 */
class SEP24TransactionNotFoundException extends Exception
{

}