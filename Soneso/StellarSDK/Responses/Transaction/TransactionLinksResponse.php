<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Transaction;

use Soneso\StellarSDK\Responses\Link\LinkResponse;

class TransactionLinksResponse
{

    private LinkResponse $account;
    private LinkResponse $ledger;
    private LinkResponse $operations;
    private LinkResponse $self;
    private LinkResponse $effects;
    private LinkResponse $precedes;
    private LinkResponse $succeeds;
    private LinkResponse $transaction;

    protected function loadFromJson(array $json) : void {


        if (isset($json['account'])) $this->account = LinkResponse::fromJson($json['account']);
        if (isset($json['ledger'])) $this->account = LinkResponse::fromJson($json['ledger']);
        if (isset($json['operations'])) $this->operations = LinkResponse::fromJson($json['operations']);
        if (isset($json['self'])) $this->self = LinkResponse::fromJson($json['self']);
        if (isset($json['effects'])) $this->effects = LinkResponse::fromJson($json['effects']);
        if (isset($json['precedes'])) $this->precedes = LinkResponse::fromJson($json['precedes']);
        if (isset($json['succeeds'])) $this->succeeds = LinkResponse::fromJson($json['succeeds']);
        if (isset($json['transaction'])) $this->transaction = LinkResponse::fromJson($json['transaction']);
    }

    public static function fromJson(array $json) : TransactionLinksResponse {
        $result = new TransactionLinksResponse();
        $result->loadFromJson($json);
        return $result;
    }
}