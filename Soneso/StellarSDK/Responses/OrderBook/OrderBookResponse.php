<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\OrderBook;

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\Response;

class OrderBookResponse extends Response
{
    private Asset $base;
    private Asset $counter;
    private OrderBookRowsResponse $asks;
    private OrderBookRowsResponse $bids;

    /**
     * @return Asset
     */
    public function getBase(): Asset
    {
        return $this->base;
    }

    /**
     * @return Asset
     */
    public function getCounter(): Asset
    {
        return $this->counter;
    }

    /**
     * @return OrderBookRowsResponse
     */
    public function getAsks(): OrderBookRowsResponse
    {
        return $this->asks;
    }

    /**
     * @return OrderBookRowsResponse
     */
    public function getBids(): OrderBookRowsResponse
    {
        return $this->bids;
    }

    protected function loadFromJson(array $json) : void {


        if (isset($json['base'])) {
            $parsedAsset = Asset::fromJson($json['base']);
            if ($parsedAsset != null) {
                $this->base = $parsedAsset;
            }
        }
        if (isset($json['counter'])) {
            $parsedAsset = Asset::fromJson($json['counter']);
            if ($parsedAsset != null) {
                $this->counter = $parsedAsset;
            }
        }
        if (isset($json['asks'])) {
            $this->asks = new OrderBookRowsResponse();
            foreach ($json['asks'] as $jsonValue) {
                $val = OrderBookRowResponse::fromJson($jsonValue);
                $this->asks->add($val);
            }
        }
        if (isset($json['bids'])) {
            $this->bids = new OrderBookRowsResponse();
            foreach ($json['bids'] as $jsonValue) {
                $val = OrderBookRowResponse::fromJson($jsonValue);
                $this->bids->add($val);
            }
        }
    }

    public static function fromJson(array $json) : OrderBookResponse {
        $result = new OrderBookResponse();
        $result->loadFromJson($json);
        return $result;
    }
}