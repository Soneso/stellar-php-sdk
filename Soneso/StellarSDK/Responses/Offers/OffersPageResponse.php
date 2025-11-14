<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Offers;

use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Page\PageResponse;

/**
 * Paginated collection of offers from Horizon API
 *
 * This response represents a single page of offers returned by Horizon's offer endpoints.
 * Each page contains a collection of offer records from the Stellar distributed exchange (DEX)
 * along with pagination links to navigate forward and backward through the complete result set.
 *
 * Offers represent standing orders in the DEX order book where accounts commit to exchange
 * one asset for another at a specified price ratio. The response follows Horizon's standard
 * pagination pattern with cursor-based navigation.
 *
 * Returned by Horizon endpoints:
 * - GET /offers - All offers on the DEX
 * - GET /offers/{offer_id} - Specific offer details
 * - GET /accounts/{account_id}/offers - Offers created by an account
 *
 * @package Soneso\StellarSDK\Responses\Offers
 * @see PageResponse For pagination functionality
 * @see OffersResponse For the collection of offers in this page
 * @see OfferResponse For individual offer details
 * @see https://developers.stellar.org Stellar developer docs Horizon Offers List API & Pagination
 */
class OffersPageResponse extends PageResponse
{

    private OffersResponse $offers;

    /**
     * Gets the collection of offers in this page
     *
     * @return OffersResponse The iterable collection of offer records
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

    /**
     * Creates an OffersPageResponse from JSON data
     *
     * @param array $json Associative array of parsed JSON response
     * @return OffersPageResponse The populated page response
     */
    public static function fromJson(array $json) : OffersPageResponse {
        $result = new OffersPageResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * Fetches the next page of offers
     *
     * @return OffersPageResponse|null The next page or null if no next page exists
     */
    public function getNextPage(): OffersPageResponse | null {
        return $this->executeRequest(RequestType::OFFERS_PAGE, $this->getNextPageUrl());
    }

    /**
     * Fetches the previous page of offers
     *
     * @return OffersPageResponse|null The previous page or null if no previous page exists
     */
    public function getPreviousPage(): OffersPageResponse | null {
        return $this->executeRequest(RequestType::OFFERS_PAGE, $this->getPrevPageUrl());
    }
}
