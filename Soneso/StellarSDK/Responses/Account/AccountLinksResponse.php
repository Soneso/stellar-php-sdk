<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Account;

use Soneso\StellarSDK\Responses\Link\LinkResponse;

/**
 * Represents hypermedia links to related account resources
 *
 * This response contains URIs to access related resources for an account such as effects,
 * offers, operations, and transactions. These links follow HAL (Hypertext Application Language)
 * format and enable navigation through the Horizon API.
 *
 * Available links:
 * - effects: Link to effects involving this account
 * - offers: Link to offers created by this account
 * - operations: Link to operations involving this account
 * - transactions: Link to transactions submitted by this account
 * - self: Link to this account resource
 *
 * This response is included in AccountResponse as part of the account details.
 *
 * @package Soneso\StellarSDK\Responses\Account
 * @see AccountResponse For the parent account details
 * @see LinkResponse For individual link details
 * @since 1.0.0
 */
class AccountLinksResponse
{

    private LinkResponse $effects;
    private LinkResponse $offers;
    private LinkResponse $operations;
    private LinkResponse $self;
    private LinkResponse $transactions;

    /**
     * Gets the link to effects involving this account
     *
     * @return LinkResponse Link to the effects endpoint
     */
    public function getEffects() : LinkResponse {
        return $this->effects;
    }

    /**
     * Gets the link to offers created by this account
     *
     * @return LinkResponse Link to the offers endpoint
     */
    public function getOffers() : LinkResponse {
        return $this->offers;
    }

    /**
     * Gets the link to operations involving this account
     *
     * @return LinkResponse Link to the operations endpoint
     */
    public function getOperations() : LinkResponse {
        return $this->operations;
    }

    /**
     * Gets the link to this account resource
     *
     * @return LinkResponse Link to the account itself
     */
    public function getSelf() : LinkResponse {
        return $this->self;
    }

    /**
     * Gets the link to transactions submitted by this account
     *
     * @return LinkResponse Link to the transactions endpoint
     */
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

    /**
     * Creates an AccountLinksResponse instance from JSON data
     *
     * @param array $json The JSON array containing link data from Horizon
     * @return AccountLinksResponse The parsed links response
     */
    public static function fromJson(array $json) : AccountLinksResponse {
        $result = new AccountLinksResponse();
        $result->loadFromJson($json);
        return $result;
    }

}

