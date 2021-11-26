<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Account;

use Soneso\StellarSDK\Responses\Link\LinkResponse;

class AccountLinksResponse
{
    
    private LinkResponse $effects;
    private LinkResponse $offers;
    private LinkResponse $operations;
    private LinkResponse $self;
    private LinkResponse $transactions;
    
    public function getEffects() : LinkResponse {
        return $this->effects;
    }
    
    public function getOffers() : LinkResponse {
        return $this->offers;
    }

    public function getOperations() : LinkResponse {
        return $this->operations;
    }

    public function getSelf() : LinkResponse {
        return $this->self;
    }

    public function getTransactions() : LinkResponse {
        return $this->transactions;
    }

    protected function loadFromJson(array $json) : void {
        
        if (isset($json['effects'])) $this->effects = LinkResponse::fromJson($json['effects']);
        if (isset($json['offers'])) $this->offers = LinkResponse::fromJson($json['offers']);
        if (isset($json['operations'])) $this->operations = LinkResponse::fromJson($json['operations']);
        if (isset($json['self'])) $this->self = LinkResponse::fromJson($json['self']);
        if (isset($json['transactions'])) $this->transactions = LinkResponse::fromJson($json['transactions']);
    }
    
    public static function fromJson(array $json) : AccountLinksResponse {
        $result = new AccountLinksResponse();
        $result->loadFromJson($json);
        return $result;
    }
    
}

