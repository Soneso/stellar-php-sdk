<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Ledger;

use Soneso\StellarSDK\Responses\Link\LinkResponse;

/**
 * Represents HAL links for a ledger response
 *
 * @package Soneso\StellarSDK\Responses\Ledger
 * @see LedgerResponse For the parent ledger details
 * @see LinkResponse For the link structure
 * @since 1.0.0
 */
class LedgerLinksResponse
{

    private LinkResponse $effects;
    private LinkResponse $payments;
    private LinkResponse $operations;
    private LinkResponse $self;
    private LinkResponse $transactions;

    /**
     * Gets the link to effects that occurred in this ledger
     *
     * @return LinkResponse The effects link
     */
    public function getEffects() : LinkResponse {
        return $this->effects;
    }

    /**
     * Gets the link to payments that occurred in this ledger
     *
     * @return LinkResponse The payments link
     */
    public function getPayments() : LinkResponse {
        return $this->payments;
    }

    /**
     * Gets the link to operations that occurred in this ledger
     *
     * @return LinkResponse The operations link
     */
    public function getOperations() : LinkResponse {
        return $this->operations;
    }

    /**
     * Gets the self-referencing link to this ledger
     *
     * @return LinkResponse The self link
     */
    public function getSelf() : LinkResponse {
        return $this->self;
    }

    /**
     * Gets the link to transactions in this ledger
     *
     * @return LinkResponse The transactions link
     */
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