<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Offers;

use Soneso\StellarSDK\Responses\Link\LinkResponse;

/**
 * HAL navigation links for offer resources
 *
 * This response contains hypermedia links following the HAL (Hypertext Application Language)
 * specification for navigating between offer-related resources in the Horizon API. Each link
 * provides a URL to fetch additional information about the offer or related entities.
 *
 * Available links:
 * - self: Link to this offer's detail endpoint
 * - offer_maker: Link to the account that created the offer
 *
 * @package Soneso\StellarSDK\Responses\Offers
 * @see LinkResponse For individual link details
 * @see OfferResponse For the parent offer resource
 * @see https://developers.stellar.org/api/introduction/response-format Horizon HAL Response Format
 */
class OfferLinksResponse
{
    private LinkResponse $self;
    private LinkResponse $offerMaker;

    /**
     * Gets the link to this offer's detail endpoint
     *
     * @return LinkResponse The self link
     */
    public function getSelf() : LinkResponse {
        return $this->self;
    }

    /**
     * Gets the link to the account that created this offer
     *
     * @return LinkResponse The offer maker account link
     */
    public function getOfferMaker(): LinkResponse
    {
        return $this->offerMaker;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['self'])) $this->self = LinkResponse::fromJson($json['self']);
        if (isset($json['offer_maker'])) $this->offerMaker = LinkResponse::fromJson($json['offer_maker']);
    }

    /**
     * Creates an OfferLinksResponse from JSON data
     *
     * @param array $json Associative array of parsed JSON response
     * @return OfferLinksResponse The populated links response
     */
    public static function fromJson(array $json) : OfferLinksResponse {
        $result = new OfferLinksResponse();
        $result->loadFromJson($json);
        return $result;
    }
}