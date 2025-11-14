<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Transaction;

use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Page\PageResponse;

/**
 * Represents a paginated collection of transactions from Horizon
 *
 * This response extends PageResponse to provide cursor-based pagination for transaction
 * lists. Transactions are returned in a TransactionsResponse collection along with
 * navigation links to fetch additional pages of results.
 *
 * Pagination is controlled by limit and cursor parameters:
 * - limit: Maximum number of transactions per page (default 10, max 200)
 * - cursor: Paging token to resume from a specific position
 * - order: Sort order (asc or desc, default desc for newest first)
 *
 * Navigation methods allow fetching next and previous pages while maintaining the same
 * query parameters. The page includes HAL links for programmatic navigation.
 *
 * Returned by Horizon endpoints that provide transaction lists:
 * - GET /transactions - All network transactions
 * - GET /accounts/{account_id}/transactions - Account transactions
 * - GET /ledgers/{sequence}/transactions - Ledger transactions
 * - GET /liquidity_pools/{pool_id}/transactions - Liquidity pool transactions
 *
 * @package Soneso\StellarSDK\Responses\Transaction
 * @see PageResponse For base pagination functionality
 * @see TransactionsResponse For the transaction collection
 * @see TransactionResponse For individual transaction details
 * @see https://developers.stellar.org Stellar developer docs Horizon Transactions API
 * @since 1.0.0
 */
class TransactionsPageResponse  extends PageResponse
{
    private TransactionsResponse $transactions;

    /**
     * Gets the collection of transactions in this page
     *
     * @return TransactionsResponse The iterable collection of transaction responses
     */
    public function getTransactions(): TransactionsResponse {
        return $this->transactions;
    }

    /**
     * Loads page data from JSON response
     *
     * @param array $json The JSON array containing page data from Horizon
     * @return void
     */
    protected function loadFromJson(array $json) : void {
        parent::loadFromJson($json);
        if (isset($json['_embedded']['records'])) {
            $this->transactions = new TransactionsResponse();
            foreach ($json['_embedded']['records'] as $jsonTransaction) {
                $transaction = TransactionResponse::fromJson($jsonTransaction);
                $this->transactions->add($transaction);
            }
        }
    }

    /**
     * Creates a TransactionsPageResponse instance from JSON data
     *
     * @param array $json The JSON array containing page data from Horizon
     * @return TransactionsPageResponse The parsed transactions page response
     */
    public static function fromJson(array $json) : TransactionsPageResponse {
        $result = new TransactionsPageResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * Fetches the next page of transactions
     *
     * Uses the next page URL from HAL links to retrieve the subsequent page of results
     * with the same query parameters.
     *
     * @return TransactionsPageResponse|null The next page, or null if no next page exists
     */
    public function getNextPage(): TransactionsPageResponse | null {
        return $this->executeRequest(RequestType::TRANSACTIONS_PAGE, $this->getNextPageUrl());
    }

    /**
     * Fetches the previous page of transactions
     *
     * Uses the previous page URL from HAL links to retrieve the prior page of results
     * with the same query parameters.
     *
     * @return TransactionsPageResponse|null The previous page, or null if no previous page exists
     */
    public function getPreviousPage(): TransactionsPageResponse | null {
        return $this->executeRequest(RequestType::TRANSACTIONS_PAGE, $this->getPrevPageUrl());
    }
}