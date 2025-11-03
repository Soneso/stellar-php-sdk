<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\LiquidityPools\LiquidityPoolResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\LiquidityPoolsPageResponse;

/**
 * Builds requests for the liquidity pools endpoint in Horizon
 *
 * This class provides methods to query liquidity pools on the Stellar network. Liquidity
 * pools enable automated market making by allowing users to deposit assets into pools
 * that facilitate trading without traditional order books.
 *
 * Query Methods:
 * - forPoolId(): Fetch a single liquidity pool by its ID
 * - forReserves(): Filter by specific reserve assets in the pool
 * - forAccount(): Get all liquidity pools an account participates in
 *
 * Liquidity pool IDs can be provided in either PoolID format (starts with "L") or
 * as hex strings.
 *
 * Usage Examples:
 *
 * // Get a specific liquidity pool
 * $pool = $sdk->liquidityPools()->forPoolId("LABC123...");
 *
 * // Get all pools for specific reserve assets
 * $pools = $sdk->liquidityPools()
 *     ->forReserves("native", "USD:GBBD...")
 *     ->execute();
 *
 * // Get all pools an account participates in
 * $pools = $sdk->liquidityPools()
 *     ->forAccount("GDAT5...")
 *     ->execute();
 *
 * @package Soneso\StellarSDK\Requests
 * @see LiquidityPoolsPageResponse For the response format
 * @see https://developers.stellar.org/api/resources/liquiditypools Horizon API Liquidity Pools endpoint
 */
class LiquidityPoolsRequestBuilder extends RequestBuilder
{
    private const RESERVES_PARAMETER_NAME = "reserves";
    private const ACCOUNT_PARAMETER_NAME = "account";

    /**
     * Constructor
     *
     * @param Client $httpClient The HTTP client used for making requests to Horizon
     */
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
        $idHex = $liquidityPoolID;
        if (str_starts_with($idHex, "L")) {
            $idHex = StrKey::decodeLiquidityPoolIdHex($idHex);
        }
        $this->setSegments("liquidity_pools", $idHex);
        return parent::executeRequest($this->buildUrl(), RequestType::SINGLE_LIQUIDITY_POOL);
    }

    /**
     * Returns all liquidity pools that contain reserves in all specified assets.
     *
     * @param string ...$reserves
     * @return LiquidityPoolsRequestBuilder current instance
     * @see https://developers.stellar.org/api/resources/liquiditypools/list/ LiquidityPools
     */
    public function forReserves(string ...$reserves) : LiquidityPoolsRequestBuilder {
        $this->queryParameters[LiquidityPoolsRequestBuilder::RESERVES_PARAMETER_NAME] = implode(",", $reserves);
        return $this;
    }

    /**
     * Returns all liquidity pools the specified account is participating in.
     *
     * @param string $accountId Account ID to filter liquidity pools
     * @return LiquidityPoolsRequestBuilder current instance
     * @see https://developers.stellar.org/docs/data/apis/horizon/api-reference/list-liquidity-pools LiquidityPools
     */
    public function forAccount(string $accountId) : LiquidityPoolsRequestBuilder {
        $this->queryParameters[LiquidityPoolsRequestBuilder::ACCOUNT_PARAMETER_NAME] = $accountId;
        return $this;
    }

    /**
     * Sets <code>cursor</code> parameter on the request.
     * A cursor is a value that points to a specific location in a collection of resources.
     * The cursor attribute itself is an opaque value meaning that users should not try to parse it.
     * @see https://developers.stellar.org/api/introduction/pagination/ Page documentation
     * @param string $cursor
     */
    public function cursor(string $cursor) : LiquidityPoolsRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int $number Maximum number of records to return
     */
    public function limit(int $number) : LiquidityPoolsRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string $direction "asc" or "desc"
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