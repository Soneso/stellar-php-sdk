<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\LiquidityPools;

use Soneso\StellarSDK\Responses\Link\LinkResponse;

class LiquidityPoolLinksResponse
{

    private LinkResponse $self;
    private LinkResponse $operations;
    private LinkResponse $transactions;

    public function getSelf() : LinkResponse {
        return $this->self;
    }

    public function getOperations() : LinkResponse {
        return $this->operations;
    }

    public function getTransactions() : LinkResponse {
        return $this->transactions;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['self'])) $this->self = LinkResponse::fromJson($json['self']);
        if (isset($json['operations'])) $this->operations = LinkResponse::fromJson($json['operations']);
        if (isset($json['transactions'])) $this->transactions = LinkResponse::fromJson($json['transactions']);
    }

    public static function fromJson(array $json) : LiquidityPoolLinksResponse {
        $result = new LiquidityPoolLinksResponse();
        $result->loadFromJson($json);
        return $result;
    }
}
