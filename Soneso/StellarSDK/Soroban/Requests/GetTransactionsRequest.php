<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Soroban\Requests;

/**
 * Used for getTransactions()
 * See: https://developers.stellar.org/docs/data/rpc/api-reference/methods/getTransactions
 */
class GetTransactionsRequest
{
    /**
     * @var int | null $startLedger Ledger sequence number to fetch events after (inclusive).
     * The getTransactions method will return an error if startLedger is less than the oldest ledger stored in this node,
     * or greater than the latest ledger seen by this node. If a cursor is included in the request,
     * startLedger must be omitted.
     */
    public ?int $startLedger = null;

    /**
     * @var PaginationOptions|null for pagination.
     */
    public ?PaginationOptions $paginationOptions = null;

    /**
     * Constructor.
     * @param int|null $startLedger Ledger sequence number to fetch events after (inclusive).
     * The getTransactions method will return an error if startLedger is less than the oldest ledger stored in this node,
     * or greater than the latest ledger seen by this node. If a cursor is included in the request,
     * startLedger must be omitted.
     * @param PaginationOptions|null $paginationOptions for pagination.
     */
    public function __construct(?int $startLedger = null, ?PaginationOptions $paginationOptions = null)
    {
        $this->startLedger = $startLedger;
        $this->paginationOptions = $paginationOptions;
    }

    /**
     * Returns the request parameters for the rpc request.
     * @return array<string,mixed> the request parameters for the rpc request.
     */
    public function getRequestParams() : array {
        /**
         * @var array<string,mixed> $params
         */
        $params = array();
        if ($this->startLedger != null) {
            $params['startLedger'] = $this->startLedger;
        }
        if ($this->paginationOptions != null) {
            $params['pagination'] = $this->paginationOptions->getRequestParams();
        }
        return $params;
    }
}