<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Transaction;

/**
 * Represents detailed result codes for transaction submission failures
 *
 * This response provides granular error information when a transaction fails, including both
 * the overall transaction-level result code and individual operation-level result codes.
 * This allows developers to identify exactly which operation failed and why, enabling
 * precise error handling and debugging.
 *
 * Transaction result codes indicate general failures (e.g., tx_bad_seq, tx_insufficient_balance),
 * while operation result codes show specific operation failures (e.g., op_underfunded, op_no_trust).
 *
 * Included in SubmitTransactionResponseExtras when a transaction submission fails.
 *
 * @package Soneso\StellarSDK\Responses\Transaction
 * @see SubmitTransactionResponseExtras For the extras object containing result codes
 * @see SubmitTransactionResponse For transaction submission results
 * @see https://developers.stellar.org/docs/fundamentals/transactions/transaction-result-codes Transaction Result Codes
 * @see https://developers.stellar.org/docs/fundamentals/transactions/operation-result-codes Operation Result Codes
 * @since 1.0.0
 */
class ExtrasResultCodes
{
    private string $transactionResultCode;
    private array $operationsResultCodes = array();

    /**
     * Gets the transaction-level result code
     *
     * Returns the overall result code for the transaction, such as tx_failed, tx_bad_seq,
     * tx_insufficient_balance, tx_no_source_account, etc. This indicates why the transaction
     * as a whole failed.
     *
     * @return string The transaction result code
     */
    public function getTransactionResultCode(): string
    {
        return $this->transactionResultCode;
    }

    /**
     * Gets the array of operation-level result codes
     *
     * Returns an array of result codes for each operation in the transaction, in the same
     * order as the operations. Each code indicates the specific result of that operation
     * (e.g., op_success, op_underfunded, op_no_trust, op_not_authorized).
     *
     * @return array<string> Array of operation result codes
     */
    public function getOperationsResultCodes(): array
    {
        return $this->operationsResultCodes;
    }

    /**
     * Loads result codes from JSON response data
     *
     * @param array $json The JSON array containing result codes
     * @return void
     */
    protected function loadFromJson(array $json) : void {
        if (isset($json['transaction'])) $this->transactionResultCode = $json['transaction'];
        if (isset($json['operations'])) {
            foreach ($json['operations'] as $code) {
                array_push($this->operationsResultCodes, $code);
            }
        }
    }

    /**
     * Creates an ExtrasResultCodes instance from JSON data
     *
     * @param array $json The JSON array containing result codes from Horizon
     * @return ExtrasResultCodes The parsed result codes
     */
    public static function fromJson(array $json) : ExtrasResultCodes {
        $result = new ExtrasResultCodes();
        $result->loadFromJson($json);
        return $result;
    }
}