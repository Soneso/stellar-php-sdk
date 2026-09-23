<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Exceptions;

/**
 * Exception thrown when a destination account requires a memo the transaction does not carry
 *
 * SEP-0029 lets an account declare that incoming payments must carry a memo by storing the
 * data entry "config.memo_required" with the value 1. The check runs on the sending side:
 * the SDK refuses to submit a memo-less transaction that pays, path pays or merges into such
 * an account. This exception names the destination that requires the memo and the zero-based
 * index of the operation that first sends to it, so the caller can rebuild the transaction
 * with a memo or address the operation in question.
 *
 * @package Soneso\StellarSDK\Exceptions
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0029.md
 */
class AccountRequiresMemoException extends \Exception
{
    private string $accountId;
    private int $operationIndex;

    /**
     * Creates a new exception for the given destination account and operation
     *
     * @param string $accountId The account id (G-address) of the destination requiring a memo
     * @param int $operationIndex The zero-based index of the operation sending to that account
     */
    public function __construct(string $accountId, int $operationIndex)
    {
        $this->accountId = $accountId;
        $this->operationIndex = $operationIndex;
        parent::__construct("Destination account " . $accountId . " of operation "
            . $operationIndex . " requires a memo in the transaction.");
    }

    /**
     * Gets the account id of the destination that requires a memo
     *
     * @return string The account id (G-address) of the destination
     */
    public function getAccountId(): string
    {
        return $this->accountId;
    }

    /**
     * Gets the index of the operation that sends to the destination requiring a memo
     *
     * The index is zero-based and counts all operations of the checked transaction,
     * including those that name no destination.
     *
     * @return int The zero-based index of the operation
     */
    public function getOperationIndex(): int
    {
        return $this->operationIndex;
    }
}
