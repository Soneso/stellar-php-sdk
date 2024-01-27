<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\KYCService;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\ResponseHandler;

class GetCustomerInfoRequestBuilder extends RequestBuilder
{
    private string $serviceAddress;
    private ?string $jwtToken = null;

    /**
     * @param Client $httpClient
     * @param string|null $jwtToken
     */
    public function __construct(Client $httpClient, string $serviceAddress, ?string $jwtToken = null)
    {
        $this->serviceAddress = $serviceAddress;
        $this->jwtToken = $jwtToken;
        parent::__construct($httpClient);
    }

    public function forQueryParameters(array $queryParameters) : GetCustomerInfoRequestBuilder {
        $this->queryParameters = array_merge($this->queryParameters, $queryParameters);
        return $this;
    }

    /**
     * @param string $url
     * @return GetCustomerInfoResponse
     * @throws GuzzleException
     */
    public function request(string $url) : GetCustomerInfoResponse {
        $headers = array();
        $headers = array_merge($headers, RequestBuilder::HEADERS);
        if ($this->jwtToken) {
            $headers = array_merge($headers, ['Authorization' => "Bearer " . $this->jwtToken]);
        }
        $request = new Request("GET", $url, $headers);
        $response = $this->httpClient->send($request);
        $responseHandler = new ResponseHandler();
        return $responseHandler->handleResponse($response, RequestType::GET_CUSTOMER_INFO, $this->httpClient);
    }

    public function buildUrl() : string {
        $url = $this->serviceAddress . "/customer";
        if (count($this->queryParameters) > 0) {
            $url .= '?' . http_build_query($this->queryParameters);
        }
        return $url;
    }

    /**
     * Build and execute request.
     * @return GetCustomerInfoResponse
     * @throws GuzzleException
     */
    public function execute() : GetCustomerInfoResponse {
        return $this->request($this->buildUrl());
    }
}