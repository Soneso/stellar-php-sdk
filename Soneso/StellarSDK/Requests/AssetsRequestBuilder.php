<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Asset\AssetsPageResponse;

class AssetsRequestBuilder  extends RequestBuilder
{
    private const ASSET_CODE_PARAMETER_NAME = "asset_code";
    private const ASSET_ISSUER_PARAMETER_NAME = "asset_issuer";

    public function __construct(Client $httpClient) {
        parent::__construct($httpClient, "assets");
    }

    public function forAssetCode(string $assetCode) : AssetsRequestBuilder {
        $this->queryParameters[AssetsRequestBuilder::ASSET_CODE_PARAMETER_NAME] = $assetCode;
        return $this;
    }

    public function forAssetIssuer(string $assetIssuer) : AssetsRequestBuilder {
        $this->queryParameters[AssetsRequestBuilder::ASSET_ISSUER_PARAMETER_NAME] = $assetIssuer;
        return $this;
    }
    /**
     * Sets <code>cursor</code> parameter on the request.
     * A cursor is a value that points to a specific location in a collection of resources.
     * The cursor attribute itself is an opaque value meaning that users should not try to parse it.
     * @see <a href="https://developers.stellar.org/api/introduction/pagination/">Page documentation</a>
     * @param string cursor
     */
    public function cursor(string $cursor) : AssetsRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int number maximum number of records to return
     */
    public function limit(int $number) : AssetsRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string direction "asc" or "desc"
     */
    public function order(string $direction = "asc") : AssetsRequestBuilder {
        return parent::order($direction);
    }

    /**
     * Requests specific <code>url</code> and returns {@link AssetsRequestBuilder}.
     * @throws HorizonRequestException
     */
    public function request(string $url) : AssetsPageResponse {
        return parent::executeRequest($url,RequestType::ASSETS_PAGE);
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException
     */
    public function execute() : AssetsPageResponse {
        return $this->request($this->buildUrl());
    }
}