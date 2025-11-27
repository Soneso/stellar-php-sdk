<?php  declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Transaction;

use Soneso\StellarSDK\Responses\Response;

/**
 * Represents the response from an asynchronous transaction submission
 *
 * Async transaction submission allows clients to submit transactions without waiting
 * for full validation and inclusion in the ledger. This is useful for high-throughput
 * scenarios where immediate feedback is not required. The response includes a status
 * indicating whether the transaction was accepted for processing.
 *
 * Status values:
 * - ERROR: Transaction submission failed due to validation errors
 * - PENDING: Transaction accepted and queued for inclusion in a future ledger
 * - DUPLICATE: Transaction already submitted and in processing queue
 * - TRY_AGAIN_LATER: Server temporarily unable to accept the transaction
 *
 * Use the transaction hash to query transaction status later via GET /transactions/{hash}.
 *
 * @package Soneso\StellarSDK\Responses\Transaction
 * @see SubmitTransactionResponse For synchronous transaction submission
 * @see https://developers.stellar.org Stellar developer docs Submit Async Transaction
 * @since 1.0.0
 */
class SubmitAsyncTransactionResponse extends Response
{
    const TX_STATUS_ERROR = 'ERROR';
    const TX_STATUS_PENDING = 'PENDING';
    const TX_STATUS_DUPLICATE = 'DUPLICATE';
    const TX_STATUS_TRY_AGAIN_LATER = 'TRY_AGAIN_LATER';

    /**
     * Creates a new SubmitAsyncTransactionResponse
     *
     * @param string $txStatus Status of the transaction submission (ERROR, PENDING, DUPLICATE, TRY_AGAIN_LATER)
     * @param string $hash The 64-character hexadecimal transaction hash
     * @param int $httpStatusCode The HTTP status code from the Horizon response
     */
    public function __construct(
        public string $txStatus,
        public string $hash,
        public int $httpStatusCode,
    ) {
    }

    /**
     * Creates a SubmitAsyncTransactionResponse instance from JSON data
     *
     * @param array $json The JSON array containing async submission response data from Horizon
     * @param int $httpResponseStatusCode The HTTP status code from the response
     * @return SubmitAsyncTransactionResponse The parsed async transaction submission response
     */
    public static function fromJson(array $json, int $httpResponseStatusCode) : SubmitAsyncTransactionResponse
    {
        $txStatus = $json['tx_status'];
        $hash = $json['hash'];
        return new SubmitAsyncTransactionResponse(
            txStatus: $txStatus,
            hash: $hash,
            httpStatusCode: $httpResponseStatusCode,
        );
    }
}