<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\ResponseHandler;

class PostRequestBuilder extends RequestBuilder {
    private ?string $jwtToken = null;
    private ?array $fields = null;
    private ?array $files = null;

    /**
     * @param Client $httpClient
     * @param array|null $fields
     * @param array|null $files
     * @param string|null $jwtToken
     */
    public function __construct(Client $httpClient, string $endpoint, ?array $fields = null, ?array $files = null, ?string $jwtToken = null)
    {
        $this->jwtToken = $jwtToken;
        $this->fields = $fields;
        $this->files = $files;
        parent::__construct($httpClient, $endpoint);
    }

    public function buildUrl() : string {
        $implodedSegments = implode("/", $this->segments);
        return "/" . $implodedSegments;
    }
    /**
     * @param string $url
     * @return SEP24InteractiveResponse
     * @throws GuzzleException
     */
    public function request(string $url) : SEP24InteractiveResponse {
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
            print("KEY : " . $key . PHP_EOL);
            $arr += ["contents" => $multipartFields[$key]];
            array_push($multipart, $arr);
        }

        $response = $this->httpClient->request("POST", $url, [
            "multipart" => $multipart,
            "headers" => $headers
        ]);
        $responseHandler = new ResponseHandler();
        return $responseHandler->handleResponse($response, RequestType::SEP24_POST, $this->httpClient);
    }

    /**
     * Build and execute request.
     * @return SEP24InteractiveResponse
     * @throws GuzzleException
     */
    public function execute() : SEP24InteractiveResponse {
        return $this->request($this->buildUrl());
    }
}