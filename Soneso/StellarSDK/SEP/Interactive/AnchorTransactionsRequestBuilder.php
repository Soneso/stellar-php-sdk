<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\ResponseHandler;

class AnchorTransactionsRequestBuilder extends RequestBuilder
{
    private string $serviceAddress;
    private string $jwtToken;

    /**
     * Constructor.
     * @param Client $httpClient client to use for the request.
     * @param string $serviceAddress the server address of the sep-24 service (e.g. from sep-01).
     * @param string $jwtToken the jwt token obtained by sep-10 authentication.
     */
    public function __construct(Client $httpClient, string $serviceAddress, string $jwtToken)
    {
        $this->serviceAddress = $serviceAddress;
        $this->jwtToken = $jwtToken;
        parent::__construct($httpClient);
    }

    /**
     * Append query parameters to the request.
     * @param array<array-key, mixed> $queryParameters the query parameters to use for the get request.
     * @return $this returns the builder, so that it can be chained.
     */
    public function forQueryParameters(array $queryParameters) : AnchorTransactionsRequestBuilder {
        $this->queryParameters = array_merge($this->queryParameters, $queryParameters);

        return $this;
    }

    /**
     * Sends the get request for the given url. attaches the jwt token to the request.
     * @param string $url the url to request from
     * @return SEP24TransactionsResponse the parsed response
     * @throws GuzzleException if an exception occurs during the request.
     */
    private function request(string $url) : SEP24TransactionsResponse {
        /**
         * @var array<array-key, mixed> $headers
         */
        $headers = array_merge(RequestBuilder::HEADERS, ['Authorization' => "Bearer ".$this->jwtToken]);
        $request = new Request("GET", $url, $headers);
        $response = $this->httpClient->send($request);
        $responseHandler = new ResponseHandler();
        $response = $responseHandler->handleResponse($response, RequestType::SEP24_TRANSACTIONS, $this->httpClient);
        assert($response instanceof SEP24TransactionsResponse);

        return $response;
    }

    /**
     * Builds the url for the request.
     * @return string the constructed url.
     */
    public function buildUrl() : string {
        $url = $this->serviceAddress . "/transactions";
        if (count($this->queryParameters) > 0) {
            $url .= '?' . http_build_query($this->queryParameters);
        }

        return $url;
    }

    /**
     * Build and execute request.
     * @return SEP24TransactionsResponse the parsed response.
     * @throws GuzzleException if any request exception occurs.
     */
    public function execute() : SEP24TransactionsResponse {

        return $this->request($this->buildUrl());
    }
}