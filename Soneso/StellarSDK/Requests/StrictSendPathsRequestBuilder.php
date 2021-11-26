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

class StrictSendPathsRequestBuilder extends RequestBuilder {

    private const DESTINATION_ACCOUNT_PARAMETER_NAME = "destination_account";
    private const DESTINATION_ASSETS_PARAMETER_NAME = "destination_assets";
    private const SOURCE_ACCOUNT_PARAMETER_NAME = "source_account";
    private const SOURCE_AMOUNT_PARAMETER_NAME = "source_amount";
    private const SOURCE_ASSET_TYPE_PARAMETER_NAME = "source_asset_type";
    private const SOURCE_ASSET_CODE_PARAMETER_NAME = "source_asset_code";
    private const SOURCE_ASSET_ISSUER_PARAMETER_NAME = "source_asset_issuer";

    public function __construct(Client $httpClient) {
        parent::__construct($httpClient);
        $this->setSegments("paths", "strict-send");
    }

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
     * @see <a href="https://developers.stellar.org/api/introduction/pagination/">Page documentation</a>
     * @param string cursor
     */
    public function cursor(string $cursor) : StrictSendPathsRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int number maximum number of records to return
     */
    public function limit(int $number) : StrictSendPathsRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string direction "asc" or "desc"
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