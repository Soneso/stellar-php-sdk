<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\TradeAggregations;

use Soneso\StellarSDK\Responses\Page\PageResponse;
use Soneso\StellarSDK\Responses\Page\PagingLinksResponse;

class TradeAggregationsPageResponse extends PageResponse
{
    private PagingLinksResponse $links;
    private TradeAggregationsResponse $tradeAggregations;

    /**
     * @return PagingLinksResponse
     */
    public function getLinks(): PagingLinksResponse
    {
        return $this->links;
    }

    /**
     * @return TradeAggregationsResponse
     */
    public function getTradeAggregations(): TradeAggregationsResponse
    {
        return $this->tradeAggregations;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['_links'])) $this->links = PagingLinksResponse::fromJson($json['_links']);
        if (isset($json['_embedded']['records'])) {
            $this->tradeAggregations = new TradeAggregationsResponse();
            foreach ($json['_embedded']['records'] as $jsonValue) {
                $value = TradeAggregationResponse::fromJson($jsonValue);
                $this->tradeAggregations->add($value);
            }
        }
    }

    public static function fromJson(array $json) : TradeAggregationsPageResponse
    {
        $result = new TradeAggregationsPageResponse();
        $result->loadFromJson($json);
        return $result;
    }
}