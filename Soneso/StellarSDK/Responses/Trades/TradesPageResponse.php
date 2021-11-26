<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Trades;

use Soneso\StellarSDK\Responses\Page\PageResponse;
use Soneso\StellarSDK\Responses\Page\PagingLinksResponse;

class TradesPageResponse extends PageResponse
{
    private PagingLinksResponse $links;
    private TradesResponse $trades;

    /**
     * @return PagingLinksResponse
     */
    public function getLinks(): PagingLinksResponse
    {
        return $this->links;
    }

    /**
     * @return TradesResponse
     */
    public function getTrades(): TradesResponse
    {
        return $this->trades;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['_links'])) $this->links = PagingLinksResponse::fromJson($json['_links']);
        if (isset($json['_embedded']['records'])) {
            $this->trades = new TradesResponse();
            foreach ($json['_embedded']['records'] as $jsonTrade) {
                $account = TradeResponse::fromJson($jsonTrade);
                $this->trades->add($account);
            }
        }
    }

    public static function fromJson(array $json) : TradesPageResponse
    {
        $result = new TradesPageResponse();
        $result->loadFromJson($json);
        return $result;
    }
}