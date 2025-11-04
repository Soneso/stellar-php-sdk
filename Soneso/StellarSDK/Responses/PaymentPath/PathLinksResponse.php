<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.



namespace Soneso\StellarSDK\Responses\PaymentPath;

use Soneso\StellarSDK\Responses\Link\LinkResponse;

/**
 * HAL navigation links for payment path resources
 *
 * This response contains hypermedia links following the HAL (Hypertext Application Language)
 * specification for navigating payment path resources in the Horizon API. Path payment
 * endpoints return possible routes for converting one asset to another through the network.
 *
 * Available links:
 * - self: Link to this payment path endpoint
 *
 * @package Soneso\StellarSDK\Responses\PaymentPath
 * @see LinkResponse For individual link details
 * @see PathResponse For the parent payment path resource
 * @see https://developers.stellar.org/api/introduction/response-format Horizon HAL Response Format
 */
class PathLinksResponse
{
    private LinkResponse $self;

    /**
     * Gets the link to this payment path endpoint
     *
     * @return LinkResponse The self link
     */
    public function getSelf() : LinkResponse {
        return $this->self;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['self'])) $this->self = LinkResponse::fromJson($json['self']);
    }

    /**
     * Creates a PathLinksResponse from JSON data
     *
     * @param array $json Associative array of parsed JSON response
     * @return PathLinksResponse The populated links response
     */
    public static function fromJson(array $json) : PathLinksResponse {
        $result = new PathLinksResponse();
        $result->loadFromJson($json);
        return $result;
    }
}