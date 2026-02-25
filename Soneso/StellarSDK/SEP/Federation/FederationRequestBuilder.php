<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Federation;

use GuzzleHttp\Client;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\Requests\RequestType;
use UnexpectedValueException;

/**
 * Request builder for SEP-0002 federation queries.
 *
 * This class builds and executes federation protocol requests to resolve
 * Stellar addresses, account IDs, transaction IDs, or perform forward lookups.
 *
 * Federation servers must enable CORS by setting the following HTTP header
 * for all responses: Access-Control-Allow-Origin: *
 *
 * @package Soneso\StellarSDK\SEP\Federation
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0002.md
 * @see Federation
 * @see FederationResponse
 */
class FederationRequestBuilder extends RequestBuilder
{

    private string $serviceAddress;

    /**
     * Constructor.
     *
     * @param Client $httpClient HTTP client for making requests.
     * @param string $serviceAddress Base URL of the federation server.
     */
    public function __construct(Client $httpClient, string $serviceAddress)
    {
        $this->serviceAddress = $serviceAddress;
        parent::__construct($httpClient);
    }

    /**
     * Sets the federation query type.
     *
     * @param string $type Query type ("name", "id", "txid", or "forward").
     * @return FederationRequestBuilder This instance for method chaining.
     */
    public function forType(string $type) : FederationRequestBuilder {
        $this->queryParameters["type"] = $type;
        return $this;
    }

    /**
     * Sets the string to look up in the federation query.
     *
     * @param string $stringToLookUp The address, account ID, or transaction ID to query.
     * @return FederationRequestBuilder This instance for method chaining.
     */
    public function forStringToLookUp(string $stringToLookUp) : FederationRequestBuilder {
        $this->queryParameters["q"] = $stringToLookUp;
        return $this;
    }

    /**
     * Adds custom query parameters for forward queries.
     *
     * @param array<array-key, mixed> $queryParameters Additional query parameters.
     * @return FederationRequestBuilder This instance for method chaining.
     */
    public function forQueryParameters(array $queryParameters) : FederationRequestBuilder {
        $this->queryParameters = array_merge($this->queryParameters, $queryParameters);
        return $this;
    }

    /**
     * Builds the complete federation query URL.
     *
     * @return string The constructed URL with query parameters.
     */
    public function buildUrl() : string {
        $url = $this->serviceAddress;
        if (count($this->queryParameters) > 0) {
            $url .= '?' . http_build_query($this->queryParameters);
        }
        return $url;
    }

    /**
     * Executes a federation request to the specified URL.
     *
     * The federation server may return a 3xx redirect status code with a Location
     * header to redirect to the correct URL.
     *
     * @param string $url The complete URL to query.
     * @return FederationResponse The federation response.
     * @throws HorizonRequestException If the request fails.
     */
    public function request(string $url) : FederationResponse {
        $response = parent::executeRequest($url,RequestType::FEDERATION);
        if (!$response instanceof FederationResponse) {
            throw new UnexpectedValueException('Expected FederationResponse, got ' . get_class($response));
        }
        return $response;
    }

    /**
     * Builds and executes the federation request.
     *
     * @return FederationResponse The federation response.
     * @throws HorizonRequestException If the request fails.
     */
    public function execute() : FederationResponse {
        return $this->request($this->buildUrl());
    }
}