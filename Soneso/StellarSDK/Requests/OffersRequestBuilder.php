<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Offers\OfferResponse;
use Soneso\StellarSDK\Responses\Offers\OffersPageResponse;

class OffersRequestBuilder  extends RequestBuilder
{
    private const SPONSOR_PARAMETER_NAME = "sponsor";
    private const SELLING_ASSET_PARAMETER_NAME = "selling";
    private const BUYING_ASSET_PARAMETER_NAME = "buying";
    private const SELLER_PARAMETER_NAME = "seller";


    public function __construct(Client $httpClient)
    {
        parent::__construct($httpClient, "offers");
    }

    /**
     * The offer details endpoint provides information on a single offer.
     * @param string $offerId specifies which offer to load.
     * @return OfferResponse The offer details.
     * @throws HorizonRequestException
     */
    public function offer(string $offerId) : OfferResponse {
        $this->setSegments("offers", $offerId);
        return parent::executeRequest($this->buildUrl(),RequestType::SINGLE_OFFER);
    }

    /**
     * Returns all offers sponsored by a given account.
     * @param string $sponsor sponsor Account ID of the sponsor.
     * @return OffersRequestBuilder current instance
     */
    public function forSponsor(string $sponsor) : OffersRequestBuilder {
        $this->queryParameters[OffersRequestBuilder::SPONSOR_PARAMETER_NAME] = $sponsor;
        return $this;
    }

    /**
     * Returns all offers where the given account is the seller.
     * @param string $seller Account ID of the offer creator.
     * @return OffersRequestBuilder current instance
     */
    public function forSeller(string $seller) : OffersRequestBuilder {
        $this->queryParameters[OffersRequestBuilder::SELLER_PARAMETER_NAME] = $seller;
        return $this;
    }

    /**
     * Returns all offers selling an asset.
     *
     * @param Asset $asset The Asset being sold.
     * @return OffersRequestBuilder current instance
     */
    public function forSellingAsset(Asset $asset) : OffersRequestBuilder {
        $this->queryParameters[OffersRequestBuilder::SELLING_ASSET_PARAMETER_NAME] = Asset::canonicalForm($asset);
        return $this;
    }

    /**
     * Returns all offers buying an asset.
     *
     * @param Asset $asset The Asset being bought.
     * @return OffersRequestBuilder current instance
     */
    public function forBuyingAsset(Asset $asset) : OffersRequestBuilder {
        $this->queryParameters[OffersRequestBuilder::BUYING_ASSET_PARAMETER_NAME] = Asset::canonicalForm($asset);
        return $this;
    }
    /**
     * Sets <code>cursor</code> parameter on the request.
     * A cursor is a value that points to a specific location in a collection of resources.
     * The cursor attribute itself is an opaque value meaning that users should not try to parse it.
     * @see <a href="https://developers.stellar.org/api/introduction/pagination/">Page documentation</a>
     * @param string cursor
     */
    public function cursor(string $cursor) : OffersRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int number maximum number of records to return
     */
    public function limit(int $number) : OffersRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string direction "asc" or "desc"
     */
    public function order(string $direction = "asc") : OffersRequestBuilder {
        return parent::order($direction);
    }
    /**
     * Requests specific <code>url</code> and returns {@link OffersPageResponse}.
     * @throws HorizonRequestException
     */
    public function request(string $url): OffersPageResponse {
        return parent::executeRequest($url, RequestType::OFFERS_PAGE);
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException
     */
    public function execute() : OffersPageResponse {
        return $this->request($this->buildUrl());
    }
}