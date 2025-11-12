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

/**
 * Request builder for POST /customer/files endpoint operations.
 *
 * This builder constructs HTTP requests to upload customer files such as ID documents, proof of
 * address, or other supporting documentation to the anchor's SEP-12 endpoint. The endpoint returns
 * a file_id that can be used in subsequent PUT /customer requests.
 *
 * This endpoint is particularly useful when submitting binary files separately from structured
 * customer data, allowing the use of content types like application/json in PUT /customer requests
 * while still supporting file uploads.
 *
 * Example usage:
 * ```php
 * $fileBytes = file_get_contents('/path/to/document.pdf');
 * $builder = new PostCustomerFileRequestBuilder($httpClient, $serviceAddress, $fileBytes, $jwt);
 * $response = $builder->execute();
 * $fileId = $response->fileId;
 * ```
 *
 * @package Soneso\StellarSDK\SEP\KYCService
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#customer-files SEP-12 v1.15.0
 * @see KYCService::postCustomerFile() For the service method using this builder
 * @see CustomerFileResponse For the response structure
 * @since 1.0.0
 */
class PostCustomerFileRequestBuilder extends RequestBuilder {
    /**
     * @var string The base URL of the SEP-12 KYC service endpoint
     */
    private string $serviceAddress;

    /**
     * @var string JWT token for authentication obtained via SEP-10
     */
    private string $jwtToken;

    /**
     * @var string The raw bytes of the file to upload
     */
    private string $fileBytes;

    /**
     * Constructor for building POST /customer/files requests.
     *
     * @param Client $httpClient The HTTP client to use for sending requests
     * @param string $serviceAddress The base URL of the SEP-12 service
     * @param string $fileBytes The raw bytes of the file to upload
     * @param string $jwtToken JWT token for authentication obtained via SEP-10
     */
    public function __construct(Client $httpClient, string $serviceAddress, string $fileBytes, string $jwtToken)
    {
        $this->serviceAddress = $serviceAddress;
        $this->jwtToken = $jwtToken;
        $this->fileBytes = $fileBytes;
        parent::__construct($httpClient);
    }

    /**
     * Builds the complete URL for the request.
     *
     * @return string The fully constructed URL for file upload
     */
    public function buildUrl() : string {
        return $this->serviceAddress . "/customer/files";
    }

    /**
     * Executes the HTTP request to upload the file.
     *
     * @param string $url The fully constructed URL to send the request to
     * @return CustomerFileResponse The parsed response containing file ID and metadata
     * @throws GuzzleException If the HTTP request fails or server returns an error
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
     * Builds and executes the file upload request.
     *
     * @return CustomerFileResponse The parsed response containing file ID and metadata
     * @throws GuzzleException If the HTTP request fails or server returns an error
     */
    public function execute() : CustomerFileResponse {
        return $this->request($this->buildUrl());
    }
}