<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;

/**
 * Response from transactions endpoint containing list of deposit and withdrawal transactions.
 *
 * Provides transaction history for the authenticated account, including all deposit
 * and withdrawal transactions processed by the anchor. Each transaction includes
 * status, amounts, timestamps, and other relevant details.
 *
 * Results can be filtered by various criteria and paginated for large datasets.
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/v4.3.0/ecosystem/sep-0006.md SEP-06 Specification
 * @see TransferServerService::transactions()
 * @see AnchorTransactionsRequest
 * @see AnchorTransaction
 */
class AnchorTransactionsResponse extends Response
{
    /**
     * @var array<AnchorTransaction>
     */
    public array $transactions = array();

    /**
     * @param array<AnchorTransaction> $transactions
     */
    public function __construct(array $transactions)
    {
        $this->transactions = $transactions;
    }

    /**
     * Constructs a new instance of AnchorTransactionsResponse by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return AnchorTransactionsResponse the object containing the parsed data.
     */
    public static function fromJson(array $json) : AnchorTransactionsResponse
    {
        /**
         * @var array<AnchorTransaction> $transactions
         */
        $transactions = array();
        if (isset($json['transactions'])) {
            foreach ($json['transactions'] as $transaction) {
                $transactions[] = AnchorTransaction::fromJson($transaction);
            }
        }
        return new AnchorTransactionsResponse($transactions);
    }
}