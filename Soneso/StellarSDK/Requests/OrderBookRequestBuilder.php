<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Operations\OperationsPageResponse;
use Soneso\StellarSDK\Responses\OrderBook\OrderBookResponse;

/**
 * Builds requests for the order book endpoint in Horizon
 *
 * This class provides methods to query the order book for a specific asset pair on the
 * Stellar decentralized exchange. The order book displays all buy (bid) and sell (ask)
 * offers for the trading pair, providing insight into market depth and liquidity.
 *
 * Query Methods:
 * - forBuyingAsset(): Set the asset being purchased
 * - forSellingAsset(): Set the asset being sold
 *
 * Both assets must be specified to retrieve an order book. The response includes lists
 * of bids and asks with prices and amounts, enabling price discovery and market analysis.
 *
 * Usage Examples:
 *
 * // Get order book for XLM/USD trading pair
 * $buying = Asset::native();
 * $selling = Asset::createNonNativeAsset("USD", "GBBD...");
 * $orderBook = $sdk->orderBook()
 *     ->forBuyingAsset($buying)
 *     ->forSellingAsset($selling)
 *     ->execute();
 *
 * // Stream real-time order book updates
 * $sdk->orderBook()
 *     ->forBuyingAsset($buying)
 *     ->forSellingAsset($selling)
 *     ->cursor("now")
 *     ->stream(function(OrderBookResponse $orderBook) {
 *         echo "Bids: " . count($orderBook->getBids()) . PHP_EOL;
 *     });
 *
 * @package Soneso\StellarSDK\Requests
 * @see OrderBookResponse For the response format
 * @see https://developers.stellar.org/api/aggregations/order-books Horizon API Order Book endpoint
 */
class OrderBookRequestBuilder extends RequestBuilder
{
    private const BUYING_ASSET_TYPE_PARAMETER_NAME = "buying_asset_type";
    private const BUYING_ASSET_CODE_PARAMETER_NAME = "buying_asset_code";
    private const BUYING_ASSET_ISSUER_PARAMETER_NAME = "buying_asset_issuer";
    private const SELLING_ASSET_TYPE_PARAMETER_NAME = "selling_asset_type";
    private const SELLING_ASSET_CODE_PARAMETER_NAME = "selling_asset_code";
    private const SELLING_ASSET_ISSUER_PARAMETER_NAME = "selling_asset_issuer";

    /**
     * Constructor
     *
     * @param Client $httpClient The HTTP client used for making requests to Horizon
     */
    public function __construct(Client $httpClient) {
        parent::__construct($httpClient, "order_book");
    }

    /**
     * Returns the order book for the given buying asset.
     *
     * @param Asset $buyingAsset the buying asset
     * @return OrderBookRequestBuilder current instance
     */
    public function forBuyingAsset(Asset $buyingAsset) : OrderBookRequestBuilder {
        $this->queryParameters[OrderBookRequestBuilder::BUYING_ASSET_TYPE_PARAMETER_NAME] = $buyingAsset->getType();
        if ($buyingAsset instanceof AssetTypeCreditAlphanum) {
            $this->queryParameters[OrderBookRequestBuilder::BUYING_ASSET_CODE_PARAMETER_NAME] = $buyingAsset->getCode();
            $this->queryParameters[OrderBookRequestBuilder::BUYING_ASSET_ISSUER_PARAMETER_NAME] = $buyingAsset->getIssuer();
        }
        return $this;
    }

    /**
     * Returns the order book for the given selling asset.
     *
     * @param Asset $sellingAsset the selling asset
     * @return OrderBookRequestBuilder current instance
     */
    public function forSellingAsset(Asset $sellingAsset) : OrderBookRequestBuilder {
        $this->queryParameters[OrderBookRequestBuilder::SELLING_ASSET_TYPE_PARAMETER_NAME] = $sellingAsset->getType();
        if ($sellingAsset instanceof AssetTypeCreditAlphanum) {
            $this->queryParameters[OrderBookRequestBuilder::SELLING_ASSET_CODE_PARAMETER_NAME] = $sellingAsset->getCode();
            $this->queryParameters[OrderBookRequestBuilder::SELLING_ASSET_ISSUER_PARAMETER_NAME] = $sellingAsset->getIssuer();
        }
        return $this;
    }

    /**
     * Sets <code>cursor</code> parameter on the request.
     * A cursor is a value that points to a specific location in a collection of resources.
     * The cursor attribute itself is an opaque value meaning that users should not try to parse it.
     * @see https://developers.stellar.org/api/introduction/pagination/ Page documentation
     * @param string $cursor
     */
    public function cursor(string $cursor) : OrderBookRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int $number Maximum number of records to return
     */
    public function limit(int $number) : OrderBookRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string $direction "asc" or "desc"
     */
    public function order(string $direction = "asc") : OrderBookRequestBuilder {
        return parent::order($direction);
    }
    /**
     * Requests specific <code>url</code> and returns {@link OrderBookResponse}.
     * @throws HorizonRequestException
     */
    public function request(string $url): OrderBookResponse {
        return parent::executeRequest($url, RequestType::ORDER_BOOK);
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException
     */
    public function execute() : OrderBookResponse {
        return $this->request($this->buildUrl());
    }

    /**
     * Streams OrderBookResponse objects to $callback
     *
     * $callback should have arguments:
     *  OrderBookResponse
     *
     * For example:
     *
     * $sdk = StellarSDK::getTestNetInstance();
     * $buyingAsset = Asset::native();
     * $sellingAsset = Asset::createNonNativeAsset("USD", "GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX");
     * $sdk->orderBook()
     *     ->forBuyingAsset($buyingAsset)
     *     ->forSellingAsset($sellingAsset)
     *     ->cursor("now")
     *     ->stream(function(OrderBookResponse $orderBook) {
     *         printf('Order Book - Bids: %d, Asks: %d' . PHP_EOL,
     *             $orderBook->getBids()->count(),
     *             $orderBook->getAsks()->count()
     *         );
     *     });
     *
     * @param callable|null $callback
     * @throws GuzzleException
     */
    public function stream(?callable $callback = null)
    {
        $this->getAndStream($this->buildUrl(), function($rawData) use ($callback) {
            $parsedObject = OrderBookResponse::fromJson($rawData);
            $callback($parsedObject);
        });
    }
}