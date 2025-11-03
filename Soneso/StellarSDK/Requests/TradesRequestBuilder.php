<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Trades\TradeResponse;
use Soneso\StellarSDK\Responses\Trades\TradesPageResponse;

/**
 * Builds requests for the trades endpoint in Horizon
 *
 * This class provides methods to query trades on the Stellar network. Trades represent
 * actual exchanges of assets that occur either through the orderbook or via liquidity
 * pools. Each trade records the assets exchanged, amounts, price, and participating accounts.
 *
 * Query Methods:
 * - forAccount(): Get trades for a specific account
 * - forOffer(): Get trades for a specific offer ID
 * - forLiquidityPool(): Get trades for a specific liquidity pool
 * - forTradeType(): Filter by trade type (all, orderbook, liquidity_pools)
 * - forBaseAsset(): Filter by base asset
 * - forCounterAsset(): Filter by counter asset
 *
 * Trades can be filtered by asset pair to analyze specific trading pairs or by account
 * to track trading activity.
 *
 * Usage Examples:
 *
 * // Get recent trades for an account
 * $trades = $sdk->trades()
 *     ->forAccount("GDAT5...")
 *     ->limit(20)
 *     ->order("desc")
 *     ->execute();
 *
 * // Get trades for a specific asset pair
 * $baseAsset = Asset::createNonNativeAsset("USD", "GBBD...");
 * $counterAsset = Asset::native();
 * $trades = $sdk->trades()
 *     ->forBaseAsset($baseAsset)
 *     ->forCounterAsset($counterAsset)
 *     ->execute();
 *
 * // Stream real-time trades
 * $sdk->trades()
 *     ->cursor("now")
 *     ->stream(function(TradeResponse $trade) {
 *         echo "Trade: " . $trade->getBaseAmount() . " " . $trade->getBaseAssetCode() . PHP_EOL;
 *     });
 *
 * @package Soneso\StellarSDK\Requests
 * @see TradesPageResponse For the response format
 * @see https://developers.stellar.org/api/resources/trades Horizon API Trades endpoint
 */
class TradesRequestBuilder extends RequestBuilder
{
    private const TRADE_TYPE_ALL = "all";
    private const TRADE_TYPE_ORDERBOOK= "orderbook";
    private const TRADE_TYPE_LIQUIDITY_POOLS = "liquidity_pools";

    private const OFFER_ID_PARAMETER_NAME = "offer_id";
    private const TRADE_TYPE_PARAMETER_NAME = "trade_type";
    private const BASE_ASSET_TYPE_PARAMETER_NAME = "base_asset_type";
    private const BASE_ASSET_CODE_PARAMETER_NAME = "base_asset_code";
    private const BASE_ASSET_ISSUER_PARAMETER_NAME = "base_asset_issuer";
    private const COUNTER_ASSET_TYPE_PARAMETER_NAME = "counter_asset_type";
    private const COUNTER_ASSET_CODE_PARAMETER_NAME = "counter_asset_code";
    private const COUNTER_ASSET_ISSUER_PARAMETER_NAME = "counter_asset_issuer";

    /**
     * Constructor
     *
     * @param Client $httpClient The HTTP client used for making requests to Horizon
     */
    public function __construct(Client $httpClient)
    {
        parent::__construct($httpClient, "trades");
    }

    /**
     * Builds request to <code>GET /trades</code> filtered by offer_id query parameter.
     * @param string $offerId Offer ID for which to get trades
     * @return TradesRequestBuilder
     * @see https://developers.stellar.org/api/resources/trades/list/ List All Trades
     */
    public function forOffer(string $offerId) : TradesRequestBuilder {
        $this->queryParameters[TradesRequestBuilder::OFFER_ID_PARAMETER_NAME] = $offerId;
        return $this;
    }

    /**
     * Returns all trades that of a specific type.
     *
     * @param string $tradeType
     * @return TradesRequestBuilder current instance
     * @see https://developers.stellar.org/api/resources/trades/list/ List All Trades
     */
    public function forTradeType(string $tradeType) : TradesRequestBuilder {
        $this->queryParameters[TradesRequestBuilder::TRADE_TYPE_PARAMETER_NAME] = $tradeType;
        return $this;
    }

    /**
     * Returns all trades for the given base asset.
     *
     * @param Asset $baseAsset
     * @return TradesRequestBuilder current instance
     */
    public function forBaseAsset(Asset $baseAsset) : TradesRequestBuilder {
        $this->queryParameters[TradesRequestBuilder::BASE_ASSET_TYPE_PARAMETER_NAME] = $baseAsset->getType();
        if ($baseAsset instanceof AssetTypeCreditAlphanum) {
            $this->queryParameters[TradesRequestBuilder::BASE_ASSET_CODE_PARAMETER_NAME] = $baseAsset->getCode();
            $this->queryParameters[TradesRequestBuilder::BASE_ASSET_ISSUER_PARAMETER_NAME] = $baseAsset->getIssuer();
        }
        return $this;
    }

    /**
     * Returns all trades for the given counter asset.
     *
     * @param Asset $counterAsset
     * @return TradesRequestBuilder current instance
     */
    public function forCounterAsset(Asset $counterAsset) : TradesRequestBuilder {
        $this->queryParameters[TradesRequestBuilder::COUNTER_ASSET_TYPE_PARAMETER_NAME] = $counterAsset->getType();
        if ($counterAsset instanceof AssetTypeCreditAlphanum) {
            $this->queryParameters[TradesRequestBuilder::COUNTER_ASSET_CODE_PARAMETER_NAME] = $counterAsset->getCode();
            $this->queryParameters[TradesRequestBuilder::COUNTER_ASSET_ISSUER_PARAMETER_NAME] = $counterAsset->getIssuer();
        }
        return $this;
    }

    /**
     * Builds request to <code>GET /liquidity_pools/{poolID}/trades</code>
     * @param string $liquidityPoolId Liquidity pool for which to get trades
     * @return TradesRequestBuilder
     * @see https://developers.stellar.org/api/resources/liquiditypools/trades/ Trades for Liquidity Pool
     */
    public function forLiquidityPool(string $liquidityPoolId) : TradesRequestBuilder {
        $idHex = $liquidityPoolId;
        if (str_starts_with($idHex, "L")) {
            $idHex = StrKey::decodeLiquidityPoolIdHex($idHex);
        }
        $this->setSegments("liquidity_pools", $idHex, "trades");
        return $this;
    }

    /**
     * Builds request to <code>GET /accounts/{accountId}/trades</code>
     * @param string $accountId
     * @return TradesRequestBuilder
     * @see https://developers.stellar.org/api/resources/accounts/trades/ Trades for Account
     */
    public function forAccount(string $accountId) : TradesRequestBuilder {
        $this->setSegments("accounts", $accountId, "trades");
        return $this;
    }

    /**
     * Sets <code>cursor</code> parameter on the request.
     * A cursor is a value that points to a specific location in a collection of resources.
     * The cursor attribute itself is an opaque value meaning that users should not try to parse it.
     * @see https://developers.stellar.org/api/introduction/pagination/ Page documentation
     * @param string $cursor
     */
    public function cursor(string $cursor) : TradesRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int $number Maximum number of records to return
     */
    public function limit(int $number) : TradesRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string $direction "asc" or "desc"
     */
    public function order(string $direction = "asc") : TradesRequestBuilder {
        return parent::order($direction);
    }
    /**
     * Requests specific <code>url</code> and returns {@link TradesPageResponse}.
     * @throws HorizonRequestException
     */
    public function request(string $url): TradesPageResponse {
        return parent::executeRequest($url, RequestType::TRADES_PAGE);
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException
     */
    public function execute() : TradesPageResponse {
        return $this->request($this->buildUrl());
    }

    /**
     * Streams TradeResponse objects to $callback
     *
     * $callback should have arguments:
     *  TradeResponse
     *
     * For example:
     *
     * $sdk = StellarSDK::getTestNetInstance();
     * $sdk->trades()->cursor("now")->stream(function(TradeResponse $trade) {
     *     printf('Trade ID: %s, Amount: %s %s for %s %s' . PHP_EOL,
     *         $trade->getId(),
     *         $trade->getBaseAmount(),
     *         $trade->getBaseAssetCode() ?? 'XLM',
     *         $trade->getCounterAmount(),
     *         $trade->getCounterAssetCode() ?? 'XLM'
     *     );
     * });
     *
     * @param callable|null $callback
     * @throws GuzzleException
     */
    public function stream(?callable $callback = null)
    {
        $this->getAndStream($this->buildUrl(), function($rawData) use ($callback) {
            $parsedObject = TradeResponse::fromJson($rawData);
            $callback($parsedObject);
        });
    }
}