<?php

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\PaymentPath\PathsPageResponse;

class FindPathsRequestBuilder extends RequestBuilder {

    private const DESTINATION_ACCOUNT_PARAMETER_NAME = "destination_account";
    private const SOURCE_ACCOUNT_PARAMETER_NAME = "source_account";
    private const DESTINATION_AMOUNT_PARAMETER_NAME = "destination_amount";
    private const DESTINATION_ASSET_TYPE_PARAMETER_NAME = "destination_asset_type";
    private const DESTINATION_ASSET_CODE_PARAMETER_NAME = "destination_asset_code";
    private const DESTINATION_ASSET_ISSUER_PARAMETER_NAME = "destination_asset_issuer";

    public function __construct(Client $httpClient) {
        parent::__construct($httpClient, "paths");
    }

    public function forDestinationAccount(string $account) : FindPathsRequestBuilder {
        $this->queryParameters[FindPathsRequestBuilder::DESTINATION_ACCOUNT_PARAMETER_NAME] = $account;
        return $this;
    }

    public function forSourceAccount(string $account) : FindPathsRequestBuilder {
        $this->queryParameters[FindPathsRequestBuilder::SOURCE_ACCOUNT_PARAMETER_NAME] = $account;
        return $this;
    }

    public function forDestinationAmount(string $amount) : FindPathsRequestBuilder {
        $this->queryParameters[FindPathsRequestBuilder::DESTINATION_AMOUNT_PARAMETER_NAME] = $amount;
        return $this;
    }

    public function forDestinationAsset(Asset $asset) : FindPathsRequestBuilder {
        $this->queryParameters[FindPathsRequestBuilder::DESTINATION_ASSET_TYPE_PARAMETER_NAME] = $asset->getType();
        if ($asset instanceof AssetTypeCreditAlphanum) {
            $this->queryParameters[FindPathsRequestBuilder::DESTINATION_ASSET_CODE_PARAMETER_NAME] = $asset->getCode();
            $this->queryParameters[FindPathsRequestBuilder::DESTINATION_ASSET_ISSUER_PARAMETER_NAME] = $asset->getIssuer();
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
    public function cursor(string $cursor) : FindPathsRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int number maximum number of records to return
     */
    public function limit(int $number) : FindPathsRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string direction "asc" or "desc"
     */
    public function order(string $direction = "asc") : FindPathsRequestBuilder {
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