<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\TradeAggregations\TradeAggregationsPageResponse;
use Soneso\StellarSDK\Responses\Trades\TradesPageResponse;

/**
 * @see https://developers.stellar.org/api/aggregations/trade-aggregations/
 */
class TradeAggregationsRequestBuilder extends RequestBuilder
{

    private const BASE_ASSET_TYPE_PARAMETER_NAME = "base_asset_type";
    private const BASE_ASSET_CODE_PARAMETER_NAME = "base_asset_code";
    private const BASE_ASSET_ISSUER_PARAMETER_NAME = "base_asset_issuer";
    private const COUNTER_ASSET_TYPE_PARAMETER_NAME = "counter_asset_type";
    private const COUNTER_ASSET_CODE_PARAMETER_NAME = "counter_asset_code";
    private const COUNTER_ASSET_ISSUER_PARAMETER_NAME = "counter_asset_issuer";
    private const START_TIME_PARAMETER_NAME = "start_time";
    private const END_TIME_PARAMETER_NAME = "end_time";
    private const RESOLUTION_PARAMETER_NAME = "resolution";
    private const OFFSET_PARAMETER_NAME = "offset";

    public function __construct(Client $httpClient)
    {
        parent::__construct($httpClient, "trade_aggregations");
    }


    /**
     * Returns all trades for the given base asset.
     *
     * @param Asset $baseAsset
     * @return TradeAggregationsRequestBuilder current instance
     */
    public function forBaseAsset(Asset $baseAsset) : TradeAggregationsRequestBuilder {
        $this->queryParameters[TradeAggregationsRequestBuilder::BASE_ASSET_TYPE_PARAMETER_NAME] = $baseAsset->getType();
        if ($baseAsset instanceof AssetTypeCreditAlphanum) {
            $this->queryParameters[TradeAggregationsRequestBuilder::BASE_ASSET_CODE_PARAMETER_NAME] = $baseAsset->getCode();
            $this->queryParameters[TradeAggregationsRequestBuilder::BASE_ASSET_ISSUER_PARAMETER_NAME] = $baseAsset->getIssuer();
        }
        return $this;
    }

    /**
     * Returns all trades for the given counter asset.
     *
     * @param Asset $counterAsset
     * @return TradeAggregationsRequestBuilder current instance
     */
    public function forCounterAsset(Asset $counterAsset) : TradeAggregationsRequestBuilder {
        $this->queryParameters[TradeAggregationsRequestBuilder::COUNTER_ASSET_TYPE_PARAMETER_NAME] = $counterAsset->getType();
        if ($counterAsset instanceof AssetTypeCreditAlphanum) {
            $this->queryParameters[TradeAggregationsRequestBuilder::COUNTER_ASSET_CODE_PARAMETER_NAME] = $counterAsset->getCode();
            $this->queryParameters[TradeAggregationsRequestBuilder::COUNTER_ASSET_ISSUER_PARAMETER_NAME] = $counterAsset->getIssuer();
        }
        return $this;
    }

    public function forStartTime(string $startTime) : TradeAggregationsRequestBuilder {
        $this->queryParameters[TradeAggregationsRequestBuilder::START_TIME_PARAMETER_NAME] = $startTime;
        return $this;
    }

    public function forEndTime(string $endTime) : TradeAggregationsRequestBuilder {
        $this->queryParameters[TradeAggregationsRequestBuilder::END_TIME_PARAMETER_NAME] = $endTime;
        return $this;
    }

    public function forResolution(string $resolution) : TradeAggregationsRequestBuilder {
        $this->queryParameters[TradeAggregationsRequestBuilder::RESOLUTION_PARAMETER_NAME] = $resolution;
        return $this;
    }

    public function forOffset(string $offset) : TradeAggregationsRequestBuilder {
        $this->queryParameters[TradeAggregationsRequestBuilder::OFFSET_PARAMETER_NAME] = $offset;
        return $this;
    }

    /**
     * Sets <code>cursor</code> parameter on the request.
     * A cursor is a value that points to a specific location in a collection of resources.
     * The cursor attribute itself is an opaque value meaning that users should not try to parse it.
     * @see <a href="https://developers.stellar.org/api/introduction/pagination/">Page documentation</a>
     * @param string cursor
     */
    public function cursor(string $cursor) : TradeAggregationsRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int number maximum number of records to return
     */
    public function limit(int $number) : TradeAggregationsRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string direction "asc" or "desc"
     */
    public function order(string $direction = "asc") : TradeAggregationsRequestBuilder {
        return parent::order($direction);
    }
    /**
     * Requests specific <code>url</code> and returns {@link TradeAggregationsPageResponse}.
     * @throws HorizonRequestException
     */
    public function request(string $url): TradeAggregationsPageResponse {
        return parent::executeRequest($url, RequestType::TRADE_AGGREGATIONS_PAGE);
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException
     */
    public function execute() : TradeAggregationsPageResponse {
        return $this->request($this->buildUrl());
    }
}