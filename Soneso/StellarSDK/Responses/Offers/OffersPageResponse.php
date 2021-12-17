<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Offers;

use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Page\PageResponse;

class OffersPageResponse extends PageResponse
{

    private OffersResponse $offers;

    /**
     * @return OffersResponse
     */
    public function getOffers(): OffersResponse {
        return $this->offers;
    }

    protected function loadFromJson(array $json) : void {
        parent::loadFromJson($json);
        if (isset($json['_embedded']['records'])) {
            $this->offers = new OffersResponse();
            foreach ($json['_embedded']['records'] as $jsonValue) {
                $offer = OfferResponse::fromJson($jsonValue);
                $this->offers->add($offer);
            }
        }
    }

    public static function fromJson(array $json) : OffersPageResponse {
        $result = new OffersPageResponse();
        $result->loadFromJson($json);
        return $result;
    }

    public function getNextPage(): OffersPageResponse | null {
        return $this->executeRequest(RequestType::OFFERS_PAGE, $this->getNextPageUrl());
    }

    public function getPreviousPage(): OffersPageResponse | null {
        return $this->executeRequest(RequestType::OFFERS_PAGE, $this->getPrevPageUrl());
    }
}