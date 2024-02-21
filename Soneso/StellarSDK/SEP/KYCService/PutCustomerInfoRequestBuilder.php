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

class PutCustomerInfoRequestBuilder extends RequestBuilder {
    private string $serviceAddress;
    private ?string $jwtToken = null;
    /**
     * @var array<array-key, mixed>|null $fields
     */
    private ?array $fields = null;

    /**
     * @var array<array-key, string>|null $files
     */
    private ?array $files = null;

    /**
     * @param Client $httpClient
     * @param string $serviceAddress
     * @param array<array-key, mixed>|null $fields
     * @param array<array-key, string>|null $files
     * @param string|null $jwtToken
     */
    public function __construct(Client $httpClient, string $serviceAddress, ?array $fields = null, ?array $files = null, ?string $jwtToken = null)
    {
        $this->serviceAddress = $serviceAddress;
        $this->jwtToken = $jwtToken;
        $this->fields = $fields;
        $this->files = $files;
        parent::__construct($httpClient);
    }

    public function buildUrl() : string {
        return $this->serviceAddress . "/customer";
    }
    /**
     * @param string $url
     * @return PutCustomerInfoResponse
     * @throws GuzzleException
     */
    public function request(string $url) : PutCustomerInfoResponse {
        $headers = array();
        $headers = array_merge($headers, RequestBuilder::HEADERS);
        if ($this->jwtToken) {
            $headers = array_merge($headers, ['Authorization' => "Bearer ".$this->jwtToken]);
        }

        $multipartFields = array();
        if ($this->fields) {
            $multipartFields = array_merge($multipartFields, $this->fields);
        }
        if ($this->files) {
            $multipartFields = array_merge($multipartFields, $this->files);
        }

        $multipart = array();
        foreach(array_keys($multipartFields) as $key) {
            $arr = array();
            $arr += ["name" => $key];
            $arr += ["contents" => $multipartFields[$key]];
            array_push($multipart, $arr);
        }

        $response = $this->httpClient->request("PUT", $url, [
            "multipart" => $multipart,
            "headers" => $headers
        ]);
        $responseHandler = new ResponseHandler();
        return $responseHandler->handleResponse($response, RequestType::PUT_CUSTOMER_INFO, $this->httpClient);
    }

    /**
     * Build and execute request.
     * @return PutCustomerInfoResponse
     * @throws GuzzleException
     */
    public function execute() : PutCustomerInfoResponse {
        return $this->request($this->buildUrl());
    }
}