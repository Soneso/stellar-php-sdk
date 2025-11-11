<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Requests;

/**
 * Pagination options for Soroban RPC requests.
 * Used by getEvents(), getLedgers(), and getTransactions() requests.
 *
 * @see https://developers.stellar.org/network/soroban-rpc/pagination
 * @see https://developers.stellar.org/network/soroban-rpc/api-reference/methods/getEvents
 * @see GetEventsRequest
 * @see GetLedgersRequest
 * @see GetTransactionsRequest
 * @package Soneso\StellarSDK\Soroban\Requests
 */
class PaginationOptions
{
    /**
     * @var string|null $cursor A string ID that points to a specific location in a collection of responses and
     * is pulled from the paging_token value of a record. When a cursor is provided Soroban-RPC will not include
     * the element whose id matches the cursor in the response. Only elements which appear after the cursor
     * are included.
     */
    public ?string $cursor = null;

    /**
     * @var int|null $limit The maximum number of records returned. The limit for getEvents can range from
     * 1 to 10000 - an upper limit that is hardcoded in Soroban-RPC for performance reasons.
     * If this argument isn't designated, it defaults to 100.
     */
    public ?int $limit = null;

    /**
     * Constructor.
     *
     * @param string|null $cursor A string ID that points to a specific location in a collection of responses and
     *  is pulled from the paging_token value of a record. When a cursor is provided Soroban-RPC will not include
     *  the element whose id matches the cursor in the response. Only elements which appear after the cursor
     *  are included.
     * @param int|null $limit The maximum number of records returned. The limit for getEvents can range from
     *  1 to 10000 - an upper limit that is hardcoded in Soroban-RPC for performance reasons.
     *  If this argument isn't designated, it defaults to 100.
     */
    public function __construct(?string $cursor = null, ?int $limit = null)
    {
        $this->cursor = $cursor;
        $this->limit = $limit;
    }

    public function getRequestParams() : array {
        $params = array();
        if ($this->cursor != null) {
            $params['cursor'] = $this->cursor;
        }
        if ($this->limit != null) {
            $params['limit'] = $this->limit;
        }
        return $params;
    }

    /**
     * @return string|null A string ID that points to a specific location in a collection of responses and
     *   is pulled from the paging_token value of a record. When a cursor is provided Soroban-RPC will not include
     *   the element whose id matches the cursor in the response. Only elements which appear after the cursor
     *   are included.
     */
    public function getCursor(): ?string
    {
        return $this->cursor;
    }

    /**
     * @param string|null $cursor A string ID that points to a specific location in a collection of responses and
     *   is pulled from the paging_token value of a record. When a cursor is provided Soroban-RPC will not include
     *   the element whose id matches the cursor in the response. Only elements which appear after the cursor
     *   are included.
     */
    public function setCursor(?string $cursor): void
    {
        $this->cursor = $cursor;
    }

    /**
     * @return int|null The maximum number of records returned. The limit for getEvents can range from
     *   1 to 10000 - an upper limit that is hardcoded in Soroban-RPC for performance reasons.
     *   If this argument isn't designated, it defaults to 100.
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @param int|null $limit The maximum number of records returned. The limit for getEvents can range from
     *   1 to 10000 - an upper limit that is hardcoded in Soroban-RPC for performance reasons.
     *   If this argument isn't designated, it defaults to 100.
     */
    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }

}