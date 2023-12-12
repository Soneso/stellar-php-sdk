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
    private string $jwtToken;


    public function __construct(Client $httpClient)
    {
        parent::__construct($httpClient, "info");
    }

    public function forQueryParameters(array $queryParameters) : InfoRequestBuilder {
        $this->queryParameters = array_merge($this->queryParameters, $queryParameters);
        return $this;
    }

    /**
     * @param string $url
     * @return SEP24InfoResponse
     * @throws GuzzleException
     */
    public function request(string $url) : SEP24InfoResponse {
        $headers = array();
        $headers = array_merge($headers, RequestBuilder::HEADERS);
        $request = new Request("GET", $url, $headers);
        $response = $this->httpClient->send($request);
        $responseHandler = new ResponseHandler();
        return $responseHandler->handleResponse($response, RequestType::SEP24_INFO, $this->httpClient);
    }

    /**
     * Build and execute request.
     * @return SEP24InfoResponse
     * @throws GuzzleException
     */
    public function execute() : SEP24InfoResponse {
        return $this->request($this->buildUrl());
    }
}