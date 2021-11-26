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

class StrictReceivePathsRequestBuilder extends RequestBuilder {

    private const DESTINATION_ACCOUNT_PARAMETER_NAME = "destination_account";
    private const DESTINATION_AMOUNT_PARAMETER_NAME = "destination_amount";
    private const DESTINATION_ASSET_TYPE_PARAMETER_NAME = "destination_asset_type";
    private const DESTINATION_ASSET_CODE_PARAMETER_NAME = "destination_asset_code";
    private const DESTINATION_ASSET_ISSUER_PARAMETER_NAME = "destination_asset_issuer";
    private const SOURCE_ACCOUNT_PARAMETER_NAME = "source_account";
    private const SOURCE_ASSETS_PARAMETER_NAME = "source_assets";

    public function __construct(Client $httpClient) {
        parent::__construct($httpClient);
        $this->setSegments("paths", "strict-receive");

    }

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
     * @see <a href="https://developers.stellar.org/api/introduction/pagination/">Page documentation</a>
     * @param string cursor
     */
    public function cursor(string $cursor) : StrictReceivePathsRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int number maximum number of records to return
     */
    public function limit(int $number) : StrictReceivePathsRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string direction "asc" or "desc"
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