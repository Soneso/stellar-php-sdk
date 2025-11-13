<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\LiquidityPools;

use Soneso\StellarSDK\Responses\Account\AccountBalanceResponse;
use Soneso\StellarSDK\Responses\Account\AccountBalancesResponse;
use Soneso\StellarSDK\Responses\Response;

/**
 * Represents a liquidity pool on the Stellar network
 *
 * This response contains comprehensive liquidity pool details including the pool ID, pool type,
 * fee structure, asset reserves, total shares, trustline count, and modification history.
 * Liquidity pools are automated market makers (AMMs) that enable decentralized trading by
 * allowing users to deposit assets and earn fees from trades.
 *
 * Key fields:
 * - Unique pool ID for identification
 * - Pool type (currently constant_product)
 * - Fee charged in basis points
 * - Asset reserves held in the pool
 * - Total shares representing pool ownership
 * - Number of trustlines to the pool
 * - Ledger modification history
 *
 * Returned by Horizon endpoints:
 * - GET /liquidity_pools - All liquidity pools
 * - GET /liquidity_pools/{liquidity_pool_id} - Specific pool details
 * - GET /accounts/{account_id}/liquidity_pools - Pools an account participates in
 *
 * @package Soneso\StellarSDK\Responses\LiquidityPools
 * @see ReservesResponse For the pool's asset reserves
 * @see LiquidityPoolLinksResponse For related navigation links
 * @see https://developers.stellar.org Stellar developer docs Horizon Liquidity Pools API
 * @since 1.0.0
 */
class LiquidityPoolResponse extends Response
{
    private string $poolId;
    private string $pagingToken;
    private int $fee; // TODO: Bigint
    private string $type;
    private string $totalTrustlines;
    private string $totalShares;
    private LiquidityPoolLinksResponse $links;
    private ReservesResponse $reserves;
    private int $lastModifiedLedger;
    private string $lastModifiedTime;


    /**
     * Gets the unique identifier for this liquidity pool
     *
     * @return string The liquidity pool ID
     */
    public function getPoolId(): string
    {
        return $this->poolId;
    }

    /**
     * Gets the paging token for this liquidity pool in list results
     *
     * @return string The paging token used for cursor-based pagination
     */
    public function getPagingToken(): string
    {
        return $this->pagingToken;
    }

    /**
     * Gets the fee charged by this pool on trades
     *
     * @return int The fee in basis points (e.g., 30 = 0.3%)
     */
    public function getFee(): int
    {
        return $this->fee;
    }

    /**
     * Gets the liquidity pool type
     *
     * @return string The pool type (e.g., "constant_product")
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Gets the total number of trustlines to this pool
     *
     * @return string The count of accounts with trustlines to this pool
     */
    public function getTotalTrustlines(): string
    {
        return $this->totalTrustlines;
    }

    /**
     * Gets the total pool shares issued
     *
     * @return string The total shares representing ownership in the pool
     */
    public function getTotalShares(): string
    {
        return $this->totalShares;
    }

    /**
     * Gets the links to related resources for this liquidity pool
     *
     * @return LiquidityPoolLinksResponse The navigation links
     */
    public function getLinks(): LiquidityPoolLinksResponse
    {
        return $this->links;
    }

    /**
     * Gets the asset reserves held in this pool
     *
     * @return ReservesResponse The pool's asset reserves
     */
    public function getReserves(): ReservesResponse
    {
        return $this->reserves;
    }

    /**
     * Gets the ledger sequence number when this pool was last modified
     *
     * @return int The last modified ledger sequence
     */
    public function getLastModifiedLedger(): int
    {
        return $this->lastModifiedLedger;
    }

    /**
     * Gets the timestamp when this pool was last modified
     *
     * @return string The last modified time in ISO 8601 format
     */
    public function getLastModifiedTime(): string
    {
        return $this->lastModifiedTime;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['_links'])) $this->links = LiquidityPoolLinksResponse::fromJson($json['_links']);
        if (isset($json['id'])) $this->poolId = $json['id'];
        if (isset($json['paging_token'])) $this->pagingToken = $json['paging_token'];
        if (isset($json['fee_bp'])) $this->fee = $json['fee_bp'];
        if (isset($json['type'])) $this->type = $json['type'];
        if (isset($json['total_trustlines'])) $this->totalTrustlines = $json['total_trustlines'];
        if (isset($json['total_shares'])) $this->totalShares = $json['total_shares'];
        if (isset($json['last_modified_ledger'])) $this->lastModifiedLedger = $json['last_modified_ledger'];
        if (isset($json['last_modified_time'])) $this->lastModifiedTime = $json['last_modified_time'];

        if (isset($json['reserves'])) {
            $this->reserves = new ReservesResponse();
            foreach ($json['reserves'] as $jsonValue) {
                $value = ReserveResponse::fromJson($jsonValue);
                $this->reserves->add($value);
            }
        }
    }

    public static function fromJson(array $json) : LiquidityPoolResponse {
        $result = new LiquidityPoolResponse();
        $result->loadFromJson($json);
        return $result;
    }
}