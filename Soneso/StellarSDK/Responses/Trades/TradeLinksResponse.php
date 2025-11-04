<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Trades;

use Soneso\StellarSDK\Responses\Link\LinkResponse;

/**
 * HAL navigation links for trade resources
 *
 * This response contains hypermedia links following the HAL (Hypertext Application Language)
 * specification for navigating between trade-related resources in the Horizon API. Each link
 * provides a URL to fetch additional information about the trade or related entities.
 *
 * Available links:
 * - self: Link to this trade's detail endpoint
 * - base: Link to the base account involved in the trade
 * - counter: Link to the counter account involved in the trade
 * - operation: Link to the operation that created this trade
 *
 * @package Soneso\StellarSDK\Responses\Trades
 * @see LinkResponse For individual link details
 * @see TradeResponse For the parent trade resource
 * @see https://developers.stellar.org/api/introduction/response-format Horizon HAL Response Format
 */
class TradeLinksResponse
{

    private LinkResponse $base;
    private LinkResponse $counter;
    private LinkResponse $operation;
    private LinkResponse $self;

    /**
     * Gets the link to the base account in the trade
     *
     * @return LinkResponse The base account link
     */
    public function getBase(): LinkResponse
    {
        return $this->base;
    }

    /**
     * Gets the link to the counter account in the trade
     *
     * @return LinkResponse The counter account link
     */
    public function getCounter(): LinkResponse
    {
        return $this->counter;
    }

    /**
     * Gets the link to the operation that created this trade
     *
     * @return LinkResponse The operation link
     */
    public function getOperation(): LinkResponse
    {
        return $this->operation;
    }

    /**
     * Gets the link to this trade's detail endpoint
     *
     * @return LinkResponse The self link
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

    /**
     * Creates a TradeLinksResponse from JSON data
     *
     * @param array $json Associative array of parsed JSON response
     * @return TradeLinksResponse The populated links response
     */
    public static function fromJson(array $json) : TradeLinksResponse {
        $result = new TradeLinksResponse();
        $result->loadFromJson($json);
        return $result;
    }

}