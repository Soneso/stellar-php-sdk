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

class InfoRequestBuilder extends RequestBuilder
{
    private ?string $jwtToken = null;
    private string $serviceAddress;

    /**
     * Constructor.
     * @param Client $httpClient the client to be used for the request.
     * @param string $serviceAddress the server address of the sep-24 service (e.g. from sep-01).
     * @param string|null $jwtToken optional jwt token obtained from sep-10 authentication. If provided it will be used in the request header.
     */
    public function __construct(Client $httpClient, string $serviceAddress, ?string $jwtToken = null)
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
    public function forQueryParameters(array $queryParameters) : InfoRequestBuilder {
        $this->queryParameters = array_merge($this->queryParameters, $queryParameters);

        return $this;
    }

    /**
     * Sends the get request for the given url.
     * Attaches the jwt token to the request if provided by constructor.
     * @param string $url the url to request from
     * @return SEP24InfoResponse the parsed response
     * @throws GuzzleException if an exception occurs during the request.
     */
    public function request(string $url) : SEP24InfoResponse {
        /**
         * @var array<array-key, mixed> $headers
         */
        $headers = array();
        $headers = array_merge($headers, RequestBuilder::HEADERS);
        if ($this->jwtToken) {
            $headers = array_merge($headers, ['Authorization' => "Bearer " . $this->jwtToken]);
        }
        $request = new Request("GET", $url, $headers);
        $response = $this->httpClient->send($request);
        $responseHandler = new ResponseHandler();
        $response = $responseHandler->handleResponse($response, RequestType::SEP24_INFO, $this->httpClient);
        assert($response instanceof SEP24InfoResponse);

        return $response;
    }

    /**
     * Builds the url for the request.
     * @return string the constructed url.
     */
    public function buildUrl() : string {
        $url = $this->serviceAddress . "/info";
        if (count($this->queryParameters) > 0) {
            $url .= '?' . http_build_query($this->queryParameters);
        }

        return $url;
    }

    /**
     * Build and execute request.
     * @return SEP24InfoResponse the parsed response.
     * @throws GuzzleException if any request exception occurs.
     */
    public function execute() : SEP24InfoResponse {

        return $this->request($this->buildUrl());
    }
}