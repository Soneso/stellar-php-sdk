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
 * Builds requests for the strict send paths endpoint in Horizon
 *
 * This class provides methods to find payment paths where the source amount and asset
 * are fixed (strict send). It returns possible paths showing what destination assets and
 * amounts will be received when sending the exact source amount.
 *
 * Query Methods:
 * - forSourceAccount(): Filter paths by source account
 * - forSourceAsset(): Set the asset to be sent (required)
 * - forSourceAmount(): Set the exact amount to be sent (required)
 * - forDestinationAccount(): Filter paths by destination account
 * - forDestinationAssets(): Filter paths by possible destination assets
 *
 * This is the recommended method for finding payment paths when you know the exact
 * amount to send and want to discover what the recipient will receive.
 *
 * Usage Examples:
 *
 * // Find paths when sending exactly 50 XLM
 * $sourceAsset = Asset::native();
 * $paths = $sdk->strictSendPaths()
 *     ->forSourceAccount("GDAT5...")
 *     ->forSourceAsset($sourceAsset)
 *     ->forSourceAmount("50")
 *     ->forDestinationAccount("GBBD...")
 *     ->execute();
 *
 * @package Soneso\StellarSDK\Requests
 * @see PathsPageResponse For the response format
 * @see https://developers.stellar.org Stellar developer docs Horizon API Strict Send Paths
 */
class StrictSendPathsRequestBuilder extends RequestBuilder {

    private const DESTINATION_ACCOUNT_PARAMETER_NAME = "destination_account";
    private const DESTINATION_ASSETS_PARAMETER_NAME = "destination_assets";
    private const SOURCE_ACCOUNT_PARAMETER_NAME = "source_account";
    private const SOURCE_AMOUNT_PARAMETER_NAME = "source_amount";
    private const SOURCE_ASSET_TYPE_PARAMETER_NAME = "source_asset_type";
    private const SOURCE_ASSET_CODE_PARAMETER_NAME = "source_asset_code";
    private const SOURCE_ASSET_ISSUER_PARAMETER_NAME = "source_asset_issuer";

    /**
     * Constructor
     *
     * @param Client $httpClient The HTTP client used for making requests to Horizon
     */
    public function __construct(Client $httpClient) {
        parent::__construct($httpClient);
        $this->setSegments("paths", "strict-send");
    }

    /**
     * Set the destination account for the payment path
     *
     * @param string $account The Stellar account ID that will receive the payment
     * @return StrictSendPathsRequestBuilder This builder for method chaining
     */
    public function forDestinationAccount(string $account) : StrictSendPathsRequestBuilder {
        $this->queryParameters[StrictSendPathsRequestBuilder::DESTINATION_ACCOUNT_PARAMETER_NAME] = $account;
        return $this;
    }

    public function forSourceAccount(string $account) : StrictSendPathsRequestBuilder {
        $this->queryParameters[StrictSendPathsRequestBuilder::SOURCE_ACCOUNT_PARAMETER_NAME] = $account;
        return $this;
    }

    public function forDestinationAssets(array $assets) : StrictSendPathsRequestBuilder {
        if (array_key_exists(StrictSendPathsRequestBuilder::DESTINATION_ACCOUNT_PARAMETER_NAME, $this->queryParameters)) {
            throw new \RuntimeException("cannot set both destination_assets and destination_account");
        }
        $canonical = array();
        foreach ($assets as $asset) {
            if (!$asset instanceof Asset) {
                throw new \RuntimeException("invalid parameter, not an asset (see class Asset)");
            }
            array_push($canonical, Asset::canonicalForm($asset));
        }
        $this->queryParameters[StrictSendPathsRequestBuilder::DESTINATION_ASSETS_PARAMETER_NAME] = implode(",", $canonical);
        return $this;
    }

    public function forSourceAmount(string $amount) : StrictSendPathsRequestBuilder {
        $this->queryParameters[StrictSendPathsRequestBuilder::SOURCE_AMOUNT_PARAMETER_NAME] = $amount;
        return $this;
    }

    public function forSourceAsset(Asset $asset) : StrictSendPathsRequestBuilder {
        $this->queryParameters[StrictSendPathsRequestBuilder::SOURCE_ASSET_TYPE_PARAMETER_NAME] = $asset->getType();
        if ($asset instanceof AssetTypeCreditAlphanum) {
            $this->queryParameters[StrictSendPathsRequestBuilder::SOURCE_ASSET_CODE_PARAMETER_NAME] = $asset->getCode();
            $this->queryParameters[StrictSendPathsRequestBuilder::SOURCE_ASSET_ISSUER_PARAMETER_NAME] = $asset->getIssuer();
        }
        return $this;
    }

    /**
     * Sets <code>cursor</code> parameter on the request.
     * A cursor is a value that points to a specific location in a collection of resources.
     * The cursor attribute itself is an opaque value meaning that users should not try to parse it.
     * @see https://developers.stellar.org Stellar developer docs Page documentation
     * @param string $cursor
     */
    public function cursor(string $cursor) : StrictSendPathsRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int $number Maximum number of records to return
     */
    public function limit(int $number) : StrictSendPathsRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string $direction "asc" or "desc"
     */
    public function order(string $direction = "asc") : StrictSendPathsRequestBuilder {
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