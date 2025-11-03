<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\PaymentPath\PathsPageResponse;

/**
 * Builds requests for the strict receive paths endpoint in Horizon
 *
 * This class provides methods to find payment paths where the destination amount and asset
 * are fixed (strict receive). It returns possible paths showing what source assets and
 * amounts are required to deliver the exact destination amount.
 *
 * Query Methods:
 * - forDestinationAccount(): Set the destination account
 * - forDestinationAsset(): Set the asset to be received (required)
 * - forDestinationAmount(): Set the exact amount to be received (required)
 * - forSourceAccount(): Filter paths by source account
 * - forSourceAssets(): Filter paths by possible source assets
 *
 * This is the recommended method for finding payment paths when you know the exact
 * amount the recipient should receive.
 *
 * Usage Examples:
 *
 * // Find paths to deliver exactly 100 USD
 * $destinationAsset = Asset::createNonNativeAsset("USD", "GBBD...");
 * $paths = $sdk->strictReceivePaths()
 *     ->forDestinationAccount("GDAT5...")
 *     ->forDestinationAsset($destinationAsset)
 *     ->forDestinationAmount("100")
 *     ->forSourceAccount("GBBD...")
 *     ->execute();
 *
 * @package Soneso\StellarSDK\Requests
 * @see PathsPageResponse For the response format
 * @see https://developers.stellar.org/api/aggregations/paths/strict-receive Horizon API Strict Receive Paths
 */
class StrictReceivePathsRequestBuilder extends RequestBuilder {

    private const DESTINATION_ACCOUNT_PARAMETER_NAME = "destination_account";
    private const DESTINATION_AMOUNT_PARAMETER_NAME = "destination_amount";
    private const DESTINATION_ASSET_TYPE_PARAMETER_NAME = "destination_asset_type";
    private const DESTINATION_ASSET_CODE_PARAMETER_NAME = "destination_asset_code";
    private const DESTINATION_ASSET_ISSUER_PARAMETER_NAME = "destination_asset_issuer";
    private const SOURCE_ACCOUNT_PARAMETER_NAME = "source_account";
    private const SOURCE_ASSETS_PARAMETER_NAME = "source_assets";

    /**
     * Constructor
     *
     * @param Client $httpClient The HTTP client used for making requests to Horizon
     */
    public function __construct(Client $httpClient) {
        parent::__construct($httpClient);
        $this->setSegments("paths", "strict-receive");

    }

    /**
     * Set the destination account for the payment path
     *
     * @param string $account The Stellar account ID that will receive the payment
     * @return StrictReceivePathsRequestBuilder This builder for method chaining
     */
    public function forDestinationAccount(string $account) : StrictReceivePathsRequestBuilder {
        $this->queryParameters[StrictReceivePathsRequestBuilder::DESTINATION_ACCOUNT_PARAMETER_NAME] = $account;
        return $this;
    }

    public function forSourceAccount(string $account) : StrictReceivePathsRequestBuilder {
        $this->queryParameters[StrictReceivePathsRequestBuilder::SOURCE_ACCOUNT_PARAMETER_NAME] = $account;
        return $this;
    }

    public function forSourceAssets(array $assets) : StrictReceivePathsRequestBuilder {
        if (array_key_exists(StrictReceivePathsRequestBuilder::SOURCE_ACCOUNT_PARAMETER_NAME, $this->queryParameters)) {
            throw new \RuntimeException("cannot set both source_assets and source_account");
        }
        $canonical = array();
        foreach ($assets as $asset) {
            if (!$asset instanceof Asset) {
                throw new \RuntimeException("invalid parameter, not an asset (see class Asset)");
            }
            array_push($canonical, Asset::canonicalForm($asset));
        }
        $this->queryParameters[StrictReceivePathsRequestBuilder::SOURCE_ASSETS_PARAMETER_NAME] = implode(",", $canonical);
        return $this;
    }

    public function forDestinationAmount(string $amount) : StrictReceivePathsRequestBuilder {
        $this->queryParameters[StrictReceivePathsRequestBuilder::DESTINATION_AMOUNT_PARAMETER_NAME] = $amount;
        return $this;
    }

    public function forDestinationAsset(Asset $asset) : StrictReceivePathsRequestBuilder {
        $this->queryParameters[StrictReceivePathsRequestBuilder::DESTINATION_ASSET_TYPE_PARAMETER_NAME] = $asset->getType();
        if ($asset instanceof AssetTypeCreditAlphanum) {
            $this->queryParameters[StrictReceivePathsRequestBuilder::DESTINATION_ASSET_CODE_PARAMETER_NAME] = $asset->getCode();
            $this->queryParameters[StrictReceivePathsRequestBuilder::DESTINATION_ASSET_ISSUER_PARAMETER_NAME] = $asset->getIssuer();
        }
        return $this;
    }

    /**
     * Sets <code>cursor</code> parameter on the request.
     * A cursor is a value that points to a specific location in a collection of resources.
     * The cursor attribute itself is an opaque value meaning that users should not try to parse it.
     * @see https://developers.stellar.org/api/introduction/pagination/ Page documentation
     * @param string $cursor
     */
    public function cursor(string $cursor) : StrictReceivePathsRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int $number Maximum number of records to return
     */
    public function limit(int $number) : StrictReceivePathsRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string $direction "asc" or "desc"
     */
    public function order(string $direction = "asc") : StrictReceivePathsRequestBuilder {
        return parent::order($direction);
    }
    /**
     * Requests specific <code>url</code> and returns {@link PathsPageResponse}.
     * @throws HorizonRequestException
     */
    public function request(string $url): PathsPageResponse {
        return parent::executeRequest($url, RequestType::PATHS_PAGE);
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException
     */
    public function execute() : PathsPageResponse {
        return $this->request($this->buildUrl());
    }
}