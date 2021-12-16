<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\LiquidityPools\LiquidityPoolResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\LiquidityPoolsPageResponse;

class LiquidityPoolsRequestBuilder extends RequestBuilder
{
    private const RESERVES_PARAMETER_NAME = "reserves";

    public function __construct(Client $httpClient)
    {
        parent::__construct($httpClient, "liquidity_pools");
    }

    /**
     * Requests <code>GET /liquidity_pools/{liquidity_pool_id}</code>
     * @param string $liquidityPoolID (liquidity_pool_id) of the liquidity pool to fetch
     * @throws HorizonRequestException
     */
    public function forPoolId(string $liquidityPoolID) : LiquidityPoolResponse
    {
        $this->setSegments("liquidity_pools", $liquidityPoolID);
        return parent::executeRequest($this->buildUrl(), RequestType::SINGLE_LIQUIDITY_POOL);
    }

    /**
     * Returns all liquidity pools that contain reserves in all specified assets.
     *
     * @param string ...$reserves
     * @return LiquidityPoolsRequestBuilder current instance
     * @see <a href="https://developers.stellar.org/api/resources/liquiditypools/list/">LiquidityPools</a>
     */
    public function forReserves(string ...$reserves) : LiquidityPoolsRequestBuilder {
        $this->queryParameters[LiquidityPoolsRequestBuilder::RESERVES_PARAMETER_NAME] = implode(",", $reserves);
        return $this;
    }

    /**
     * Sets <code>cursor</code> parameter on the request.
     * A cursor is a value that points to a specific location in a collection of resources.
     * The cursor attribute itself is an opaque value meaning that users should not try to parse it.
     * @see <a href="https://developers.stellar.org/api/introduction/pagination/">Page documentation</a>
     * @param string cursor
     */
    public function cursor(string $cursor) : LiquidityPoolsRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int number maximum number of records to return
     */
    public function limit(int $number) : LiquidityPoolsRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string direction "asc" or "desc"
     */
    public function order(string $direction = "asc") : LiquidityPoolsRequestBuilder {
        return parent::order($direction);
    }

    /**
     * Requests specific <code>url</code> and returns {@link LiquidityPoolsPageResponse}.
     * @throws HorizonRequestException
     */
    public function request(string $url): LiquidityPoolsPageResponse {
        return parent::executeRequest($url, RequestType::LIQUIDITY_POOLS_PAGE);
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException
     */
    public function execute() : LiquidityPoolsPageResponse {
        return $this->request($this->buildUrl());
    }
}