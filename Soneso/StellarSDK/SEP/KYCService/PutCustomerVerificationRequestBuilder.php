<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\KYCService;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\ResponseHandler;

class PutCustomerVerificationRequestBuilder extends RequestBuilder
{
    private ?string $jwtToken = null;
    private ?array $fields = null;

    /**
     * @param Client $httpClient
     * @param array|null $fields
     * @param string|null $jwtToken
     */
    public function __construct(Client $httpClient, ?array $fields = null, ?string $jwtToken = null)
    {
        $this->jwtToken = $jwtToken;
        $this->fields = $fields;
        parent::__construct($httpClient, "customer/verification");
    }

    public function buildUrl() : string {
        $implodedSegments = implode("/", $this->segments);
        return "/" . $implodedSegments;
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
            $headers = array_merge($headers, ['Authorization' => "Bearer ".$this->jwtToken]);
        }

        $multipart = array();
        foreach(array_keys($this->fields) as $key) {
            $arr = array();
            $arr += ["name" => $key];
            $arr += ["contents" => $this->fields[$key]];
            array_push($multipart, $arr);
        }

        $response = $this->httpClient->request("PUT", $url, [
            "multipart" => $multipart,
            "headers" => $headers
        ]);
        $responseHandler = new ResponseHandler();
        return $responseHandler->handleResponse($response, RequestType::PUT_CUSTOMER_VERIFICATION, $this->httpClient);
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