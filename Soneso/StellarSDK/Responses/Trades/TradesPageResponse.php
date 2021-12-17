<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Trades;

use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Page\PageResponse;

class TradesPageResponse extends PageResponse
{
    private TradesResponse $trades;

    /**
     * @return TradesResponse
     */
    public function getTrades(): TradesResponse {
        return $this->trades;
    }

    protected function loadFromJson(array $json) : void {
        parent::loadFromJson($json);
        if (isset($json['_embedded']['records'])) {
            $this->trades = new TradesResponse();
            foreach ($json['_embedded']['records'] as $jsonTrade) {
                $account = TradeResponse::fromJson($jsonTrade);
                $this->trades->add($account);
            }
        }
    }

    public static function fromJson(array $json) : TradesPageResponse {
        $result = new TradesPageResponse();
        $result->loadFromJson($json);
        return $result;
    }

    public function getNextPage(): TradesPageResponse | null {
        return $this->executeRequest(RequestType::TRADES_PAGE, $this->getNextPageUrl());
    }

    public function getPreviousPage(): TradesPageResponse | null {
        return $this->executeRequest(RequestType::TRADES_PAGE, $this->getPrevPageUrl());
    }
}