<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Ledger;

use Soneso\StellarSDK\Responses\Link\LinkResponse;

class LedgerLinksResponse
{

    private LinkResponse $effects;
    private LinkResponse $payments;
    private LinkResponse $operations;
    private LinkResponse $self;
    private LinkResponse $transactions;

    public function getEffects() : LinkResponse {
        return $this->effects;
    }

    public function getPayments() : LinkResponse {
        return $this->payments;
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
        if (isset($json['payments'])) $this->payments = LinkResponse::fromJson($json['payments']);
        if (isset($json['operations'])) $this->operations = LinkResponse::fromJson($json['operations']);
        if (isset($json['self'])) $this->self = LinkResponse::fromJson($json['self']);
        if (isset($json['transactions'])) $this->transactions = LinkResponse::fromJson($json['transactions']);
    }

    public static function fromJson(array $json) : LedgerLinksResponse {
        $result = new LedgerLinksResponse();
        $result->loadFromJson($json);
        return $result;
    }

}