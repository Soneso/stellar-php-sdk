<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\KYCService;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\ResponseHandler;

class PostCustomerFileRequestBuilder extends RequestBuilder {
    private string $serviceAddress;
    private string $jwtToken;
    private string $fileBytes;

    /**
     * @param Client $httpClient
     * @param string $serviceAddress
     * @param string $fileBytes
     * @param string $jwtToken
     */
    public function __construct(Client $httpClient, string $serviceAddress, string $fileBytes, string $jwtToken)
    {
        $this->serviceAddress = $serviceAddress;
        $this->jwtToken = $jwtToken;
        $this->fileBytes = $fileBytes;
        parent::__construct($httpClient);
    }

    public function buildUrl() : string {
        return $this->serviceAddress . "/customer/files";
    }
    /**
     * @param string $url
     * @return CustomerFileResponse
     * @throws GuzzleException
     */
    public function request(string $url) : CustomerFileResponse {
        $headers = array();
        $headers = array_merge($headers, RequestBuilder::HEADERS);
        $headers = array_merge($headers, ['Authorization' => "Bearer ".$this->jwtToken]);

        $multipart = array();
        $multipart['name'] = 'file';
        $multipart['contents'] = $this->fileBytes;

        $response = $this->httpClient->request("POST", $url, [
            "multipart" => [$multipart],
            "headers" => $headers
        ]);
        $responseHandler = new ResponseHandler();
        return $responseHandler->handleResponse($response, RequestType::POST_CUSTOMER_FILE, $this->httpClient);
    }

    /**
     * Build and execute request.
     * @return CustomerFileResponse
     * @throws GuzzleException
     */
    public function execute() : CustomerFileResponse {
        return $this->request($this->buildUrl());
    }
}