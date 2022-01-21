<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\ResponseHandler;

class InfoRequestBuilder extends RequestBuilder
{
    private string $jwtToken;

    /**
     * @param Client $httpClient
     * @param string $jwtToken
     */
    public function __construct(Client $httpClient, string $jwtToken)
    {
        $this->jwtToken = $jwtToken;
        parent::__construct($httpClient, "info");
    }

    public function forQueryParameters(array $queryParameters) : InfoRequestBuilder {
        $this->queryParameters = array_merge($this->queryParameters, $queryParameters);
        return $this;
    }

    /**
     * @param string $url
     * @return InfoResponse
     * @throws GuzzleException
     */
    public function request(string $url) : InfoResponse {
        $headers = array();
        $headers = array_merge($headers, RequestBuilder::HEADERS);
        $headers = array_merge($headers, ['Authorization' => "Bearer ".$this->jwtToken]);
        $request = new Request("GET", $url, $headers);
        $response = $this->httpClient->send($request);
        $responseHandler = new ResponseHandler();
        return $responseHandler->handleResponse($response, RequestType::ANCHOR_INFO, $this->httpClient);
    }

    /**
     * Build and execute request.
     * @return InfoResponse
     * @throws GuzzleException
     */
    public function execute() : InfoResponse {
        return $this->request($this->buildUrl());
    }
}