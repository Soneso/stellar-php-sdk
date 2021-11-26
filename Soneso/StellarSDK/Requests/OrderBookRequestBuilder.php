<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Operations\OperationsPageResponse;
use Soneso\StellarSDK\Responses\OrderBook\OrderBookResponse;

class OrderBookRequestBuilder extends RequestBuilder
{
    private const BUYING_ASSET_TYPE_PARAMETER_NAME = "buying_asset_type";
    private const BUYING_ASSET_CODE_PARAMETER_NAME = "buying_asset_code";
    private const BUYING_ASSET_ISSUER_PARAMETER_NAME = "buying_asset_issuer";
    private const SELLING_ASSET_TYPE_PARAMETER_NAME = "selling_asset_type";
    private const SELLING_ASSET_CODE_PARAMETER_NAME = "selling_asset_code";
    private const SELLING_ASSET_ISSUER_PARAMETER_NAME = "selling_asset_issuer";


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
     * @see <a href="https://developers.stellar.org/api/introduction/pagination/">Page documentation</a>
     * @param string cursor
     */
    public function cursor(string $cursor) : OrderBookRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int number maximum number of records to return
     */
    public function limit(int $number) : OrderBookRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string direction "asc" or "desc"
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
}