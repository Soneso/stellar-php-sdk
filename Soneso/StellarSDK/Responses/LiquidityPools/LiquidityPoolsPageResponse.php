<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\LiquidityPools;

use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Page\PageResponse;

/**
 * Paginated collection of liquidity pools from Horizon API
 *
 * This response represents a single page of liquidity pools returned by Horizon's liquidity pool
 * endpoints. Each page contains a collection of liquidity pool records along with pagination links
 * to navigate forward and backward through the complete result set. Liquidity pools are automated
 * market makers (AMMs) that enable decentralized trading on the Stellar network.
 *
 * The response follows Horizon's standard pagination pattern with cursor-based navigation. Use the
 * getNextPage() and getPreviousPage() methods to traverse pages, or check hasNextPage() and
 * hasPrevPage() to determine if additional pages exist.
 *
 * Returned by Horizon endpoints:
 * - GET /liquidity_pools - All liquidity pools
 * - GET /accounts/{account_id}/liquidity_pools - Pools an account participates in
 *
 * @package Soneso\StellarSDK\Responses\LiquidityPools
 * @see PageResponse For pagination functionality
 * @see LiquidityPoolsResponse For the collection of liquidity pools in this page
 * @see LiquidityPoolResponse For individual pool details
 * @see https://developers.stellar.org Stellar developer docs Horizon Liquidity Pools List API & Pagination
 */
class LiquidityPoolsPageResponse extends PageResponse
{
    private LiquidityPoolsResponse $liquidityPools;


    /**
     * Gets the collection of liquidity pools in this page
     *
     * @return LiquidityPoolsResponse The iterable collection of liquidity pool records
     */
    public function getLiquidityPools(): LiquidityPoolsResponse {
        return $this->liquidityPools;
    }

    protected function loadFromJson(array $json) : void {
        parent::loadFromJson($json);
        if (isset($json['_embedded']['records'])) {
            $this->liquidityPools = new LiquidityPoolsResponse();
            foreach ($json['_embedded']['records'] as $jsonValue) {
                $value = LiquidityPoolResponse::fromJson($jsonValue);
                $this->liquidityPools->add($value);
            }
        }
    }

    /**
     * Creates a LiquidityPoolsPageResponse from JSON data
     *
     * @param array $json Associative array of parsed JSON response
     * @return LiquidityPoolsPageResponse The populated page response
     */
    public static function fromJson(array $json) : LiquidityPoolsPageResponse {
        $result = new LiquidityPoolsPageResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * Fetches the next page of liquidity pools
     *
     * @return LiquidityPoolsPageResponse|null The next page or null if no next page exists
     */
    public function getNextPage(): LiquidityPoolsPageResponse | null {
        return $this->executeRequest(RequestType::LIQUIDITY_POOLS_PAGE, $this->getNextPageUrl());
    }

    /**
     * Fetches the previous page of liquidity pools
     *
     * @return LiquidityPoolsPageResponse|null The previous page or null if no previous page exists
     */
    public function getPreviousPage(): LiquidityPoolsPageResponse | null {
        return $this->executeRequest(RequestType::LIQUIDITY_POOLS_PAGE, $this->getPrevPageUrl());
    }
}
