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

/**
 * Request builder for PUT /customer endpoint operations.
 *
 * This builder constructs HTTP requests to submit or update customer KYC information to the
 * anchor's SEP-12 endpoint. It supports submitting standard SEP-9 fields (name, address, etc.),
 * custom fields, and binary files (ID documents, proof of address).
 *
 * The request uses multipart/form-data encoding to support both text fields and file uploads
 * in a single request. The anchor returns a customer ID that can be used in subsequent requests.
 *
 * Example usage:
 * ```php
 * $fields = ['first_name' => 'John', 'last_name' => 'Doe'];
 * $files = ['photo_id_front' => $imageBytes];
 * $builder = new PutCustomerInfoRequestBuilder($httpClient, $serviceAddress, $fields, $files, $jwt);
 * $response = $builder->execute();
 * ```
 *
 * @package Soneso\StellarSDK\SEP\KYCService
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#customer-put SEP-12 v1.15.0
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0009.md SEP-9 Standard KYC Fields
 * @see KYCService::putCustomerInfo() For the service method using this builder
 * @see PutCustomerInfoResponse For the response structure
 * @since 1.0.0
 */
class PutCustomerInfoRequestBuilder extends RequestBuilder {
    /**
     * Constructor for building PUT /customer requests.
     *
     * @param Client $httpClient The HTTP client to use for sending requests
     * @param string $serviceAddress The base URL of the SEP-12 KYC service endpoint
     * @param array<array-key, mixed>|null $fields Customer data fields to submit
     * @param array<array-key, string>|null $files Binary file data to upload (field name => file bytes)
     * @param string|null $jwtToken JWT token for authentication obtained via SEP-10
     */
    public function __construct(
        Client $httpClient,
        private string $serviceAddress,
        private ?array $fields = null,
        private ?array $files = null,
        private ?string $jwtToken = null,
    ) {
        parent::__construct($httpClient);
    }

    /**
     * Builds the complete URL for the request.
     *
     * @return string The fully constructed URL for customer information submission
     */
    public function buildUrl() : string {
        return $this->serviceAddress . "/customer";
    }

    /**
     * Executes the HTTP request to submit customer information.
     *
     * @param string $url The fully constructed URL to send the request to
     * @return PutCustomerInfoResponse The parsed response containing customer ID
     * @throws GuzzleException If the HTTP request fails or server returns an error
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
     * Builds and executes the customer information submission request.
     *
     * @return PutCustomerInfoResponse The parsed response containing customer ID
     * @throws GuzzleException If the HTTP request fails or server returns an error
     */
    public function execute() : PutCustomerInfoResponse {
        return $this->request($this->buildUrl());
    }
}