<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Offers;

use Soneso\StellarSDK\Responses\Link\LinkResponse;

class OfferLinksResponse
{
    private LinkResponse $self;
    private LinkResponse $offerMaker;

    /**
     * @return LinkResponse
     */
    public function getSelf() : LinkResponse {
        return $this->self;
    }

    /**
     * @return LinkResponse
     */
    public function getOfferMaker(): LinkResponse
    {
        return $this->offerMaker;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['self'])) $this->self = LinkResponse::fromJson($json['self']);
        if (isset($json['offer_maker'])) $this->offerMaker = LinkResponse::fromJson($json['offer_maker']);
    }

    public static function fromJson(array $json) : OfferLinksResponse {
        $result = new OfferLinksResponse();
        $result->loadFromJson($json);
        return $result;
    }
}