<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Asset\AssetsPageResponse;

/**
 * Builds requests for the assets endpoint in Horizon
 *
 * This class provides methods to query all assets on the Stellar network, with optional
 * filtering by asset code and/or issuer account. Assets represent currencies or tokens
 * that can be traded and held by accounts.
 *
 * Query Methods:
 * - forAssetCode(): Filter assets by their asset code (e.g., "USD", "BTC")
 * - forAssetIssuer(): Filter assets by their issuing account ID
 *
 * Both filters can be combined to retrieve a specific asset. The response includes
 * detailed statistics for each asset including supply, number of accounts, and trustlines.
 *
 * Usage Examples:
 *
 * // Get all assets with pagination
 * $assets = $sdk->assets()
 *     ->limit(50)
 *     ->order("desc")
 *     ->execute();
 *
 * // Get assets by code
 * $usdAssets = $sdk->assets()
 *     ->forAssetCode("USD")
 *     ->execute();
 *
 * // Get a specific asset by code and issuer
 * $asset = $sdk->assets()
 *     ->forAssetCode("USD")
 *     ->forAssetIssuer("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5")
 *     ->execute();
 *
 * @package Soneso\StellarSDK\Requests
 * @see AssetsPageResponse For the response format
 * @see https://developers.stellar.org/api/resources/assets Horizon API Assets endpoint
 */
class AssetsRequestBuilder  extends RequestBuilder
{
    private const ASSET_CODE_PARAMETER_NAME = "asset_code";
    private const ASSET_ISSUER_PARAMETER_NAME = "asset_issuer";

    /**
     * Constructor
     *
     * @param Client $httpClient The HTTP client used for making requests to Horizon
     */
    public function __construct(Client $httpClient) {
        parent::__construct($httpClient, "assets");
    }

    /**
     * Filter assets by asset code
     *
     * Restricts the returned assets to those with the specified asset code.
     * Can be combined with forAssetIssuer() to retrieve a specific asset.
     *
     * @param string $assetCode The asset code to filter by (e.g., "USD", "BTC", "EURT")
     * @return AssetsRequestBuilder This builder for method chaining
     */
    public function forAssetCode(string $assetCode) : AssetsRequestBuilder {
        $this->queryParameters[AssetsRequestBuilder::ASSET_CODE_PARAMETER_NAME] = $assetCode;
        return $this;
    }

    /**
     * Filter assets by issuer account
     *
     * Restricts the returned assets to those issued by the specified account.
     * Can be combined with forAssetCode() to retrieve a specific asset.
     *
     * @param string $assetIssuer The Stellar account ID (public key) of the asset issuer
     * @return AssetsRequestBuilder This builder for method chaining
     */
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