<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;

/**
 * Response from transaction endpoint containing details of a single transaction.
 *
 * Provides comprehensive information about a specific deposit or withdrawal transaction,
 * including current status, amounts, fees, timestamps, account details, and any
 * additional information needed for the user to complete or track the transaction.
 *
 * Used for validating transactions and polling for status updates.
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/v4.3.0/ecosystem/sep-0006.md SEP-06 Specification
 * @see TransferServerService::transaction()
 * @see AnchorTransactionRequest
 * @see AnchorTransaction
 */
class AnchorTransactionResponse extends Response {

    /**
     * @param AnchorTransaction $transaction
     */
    public function __construct(
        public AnchorTransaction $transaction,
    ) {
    }

    /**
     * Constructs a new instance of AnchorTransactionResponse by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return AnchorTransactionResponse the object containing the parsed data.
     * @throws \InvalidArgumentException If the data has no 'transaction' object, which SEP-06 requires.
     */
    public static function fromJson(array $json) : AnchorTransactionResponse
    {
        if (!isset($json['transaction']) || !is_array($json['transaction'])) {
            throw new \InvalidArgumentException(
                "Anchor transaction response has no 'transaction' object, received keys: ["
                . implode(', ', array_keys($json)) . "]"
            );
        }
        return new AnchorTransactionResponse(AnchorTransaction::fromJson($json['transaction']));
    }
}