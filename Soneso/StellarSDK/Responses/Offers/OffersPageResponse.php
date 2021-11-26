<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Offers;

use Soneso\StellarSDK\Responses\Page\PageResponse;
use Soneso\StellarSDK\Responses\Page\PagingLinksResponse;

class OffersPageResponse extends PageResponse
{
    private PagingLinksResponse $links;
    private OffersResponse $offers;

    /**
     * @return PagingLinksResponse
     */
    public function getLinks(): PagingLinksResponse
    {
        return $this->links;
    }

    /**
     * @return OffersResponse
     */
    public function getOffers(): OffersResponse
    {
        return $this->offers;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['_links'])) $this->links = PagingLinksResponse::fromJson($json['_links']);
        if (isset($json['_embedded']['records'])) {
            $this->offers = new OffersResponse();
            foreach ($json['_embedded']['records'] as $jsonValue) {
                $offer = OfferResponse::fromJson($jsonValue);
                $this->offers->add($offer);
            }
        }
    }

    public static function fromJson(array $json) : OffersPageResponse
    {
        $result = new OffersPageResponse();
        $result->loadFromJson($json);
        return $result;
    }
}