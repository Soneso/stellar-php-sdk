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

class Sep24PostRequestBuilder extends RequestBuilder {

    private string $serviceAddress;
    private string $endpoint;
    private string $jwtToken;
    /**
     * @var array<array-key, mixed> $fields to be added to the request
     */
    private array $fields;

    /**
     * @var array<array-key, mixed> $files to be added to the request.
     */
    private ?array $files = null;

    /**
     * Constructor.
     * @param Client $httpClient the client to be used for the request.
     * @param string $serviceAddress the server address of the sep-24 service (e.g. from sep-01).
     * @param string $endpoint the endpoint to be called. e.g. 'transactions/withdraw/interactive'
     * @param array<array-key, mixed> $fields the data fields to be added to the request.
     * @param array<array-key, mixed>|null $files the files to be added to the request.
     * @param string $jwtToken the jwt token obtained from sep-10 authentication.
     */
    public function __construct(
        Client $httpClient,
        string $serviceAddress,
        string $endpoint,
        string $jwtToken,
        array $fields,
        ?array $files = null
    )
    {
        $this->serviceAddress = $serviceAddress;
        $this->endpoint = $endpoint;
        $this->jwtToken = $jwtToken;
        $this->fields = $fields;
        $this->files = $files;
        parent::__construct($httpClient);
    }

    /**
     * Sends the request to the given url.
     * @param string $url the url to send the request to
     * @return SEP24InteractiveResponse the parsed response.
     * @throws GuzzleException if any request exception occurred.
     */
    public function request(string $url) : SEP24InteractiveResponse {
        /**
         * @var array<array-key, mixed> $headers
         */
        $headers = array_merge( RequestBuilder::HEADERS,  ['Authorization' => "Bearer ".$this->jwtToken]);

        /**
         * @var array<array-key, mixed> $multipartFields
         */
        $multipartFields = $this->fields;

        if ($this->files) {
            $multipartFields = array_merge($multipartFields, $this->files);
        }

        /**
         * @var array<array-key, mixed> $multipart
         */
        $multipart = array();
        foreach(array_keys($multipartFields) as $key) {
            $arr = array();
            $arr += ["name" => $key];
            $arr += ["contents" => $multipartFields[$key]];
            $multipart[] = $arr;
        }

        $response = $this->httpClient->request("POST", $url, [
            "multipart" => $multipart,
            "headers" => $headers
        ]);
        $responseHandler = new ResponseHandler();
        $response = $responseHandler->handleResponse($response, RequestType::SEP24_POST, $this->httpClient);
        assert($response instanceof SEP24InteractiveResponse);

        return $response;
    }

    /**
     * Builds the url for the request.
     * @return string the constructed url.
     */
    public function buildUrl() : string {

        return $this->serviceAddress . '/' . $this->endpoint;
    }

    /**
     * Build and execute request.
     * @return SEP24InteractiveResponse the parsed response.
     * @throws GuzzleException if any request exception occurred.
     */
    public function execute() : SEP24InteractiveResponse {

        return $this->request($this->buildUrl());
    }
}