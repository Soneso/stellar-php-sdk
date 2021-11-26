<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Trades;

use Soneso\StellarSDK\Responses\Link\LinkResponse;

class TradeLinksResponse
{

    private LinkResponse $base;
    private LinkResponse $counter;
    private LinkResponse $operation;
    private LinkResponse $self;

    /**
     * @return LinkResponse
     */
    public function getBase(): LinkResponse
    {
        return $this->base;
    }

    /**
     * @return LinkResponse
     */
    public function getCounter(): LinkResponse
    {
        return $this->counter;
    }

    /**
     * @return LinkResponse
     */
    public function getOperation(): LinkResponse
    {
        return $this->operation;
    }

    /**
     * @return LinkResponse
     */
    public function getSelf(): LinkResponse
    {
        return $this->self;
    }


    protected function loadFromJson(array $json) : void {

        if (isset($json['base'])) $this->base = LinkResponse::fromJson($json['base']);
        if (isset($json['counter'])) $this->counter = LinkResponse::fromJson($json['counter']);
        if (isset($json['operation'])) $this->operation = LinkResponse::fromJson($json['operation']);
        if (isset($json['self'])) $this->self = LinkResponse::fromJson($json['self']);
    }

    public static function fromJson(array $json) : TradeLinksResponse {
        $result = new TradeLinksResponse();
        $result->loadFromJson($json);
        return $result;
    }

}