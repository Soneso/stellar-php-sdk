<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\TradeAggregations;

use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Page\PageResponse;

class TradeAggregationsPageResponse extends PageResponse
{
    private TradeAggregationsResponse $tradeAggregations;

    /**
     * @return TradeAggregationsResponse
     */
    public function getTradeAggregations(): TradeAggregationsResponse {
        return $this->tradeAggregations;
    }

    protected function loadFromJson(array $json) : void {
        parent::loadFromJson($json);
        if (isset($json['_embedded']['records'])) {
            $this->tradeAggregations = new TradeAggregationsResponse();
            foreach ($json['_embedded']['records'] as $jsonValue) {
                $value = TradeAggregationResponse::fromJson($jsonValue);
                $this->tradeAggregations->add($value);
            }
        }
    }

    public static function fromJson(array $json) : TradeAggregationsPageResponse {
        $result = new TradeAggregationsPageResponse();
        $result->loadFromJson($json);
        return $result;
    }

    public function getNextPage(): TradeAggregationsPageResponse | null {
        return $this->executeRequest(RequestType::TRADE_AGGREGATIONS_PAGE, $this->getNextPageUrl());
    }

    public function getPreviousPage(): TradeAggregationsPageResponse | null {
        return $this->executeRequest(RequestType::TRADE_AGGREGATIONS_PAGE, $this->getPrevPageUrl());
    }
}