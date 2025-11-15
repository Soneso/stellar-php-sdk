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

/**
 * Request builder for GET /customer endpoint operations.
 *
 * This builder constructs HTTP requests to retrieve customer information and KYC status
 * from the anchor's SEP-12 endpoint. It supports querying by customer ID, Stellar account,
 * memo, transaction ID, and customer type.
 *
 * The builder follows the builder pattern, allowing method chaining to configure request
 * parameters before execution.
 *
 * Example usage:
 * ```php
 * $builder = new GetCustomerInfoRequestBuilder($httpClient, $serviceAddress, $jwt);
 * $response = $builder->forQueryParameters(['account' => $accountId])->execute();
 * ```
 *
 * @package Soneso\StellarSDK\SEP\KYCService
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#customer-get SEP-12 v1.15.0
 * @see KYCService::getCustomerInfo() For the service method using this builder
 * @see GetCustomerInfoResponse For the response structure
 * @since 1.0.0
 */
class GetCustomerInfoRequestBuilder extends RequestBuilder
{
    /**
     * Constructor for building GET /customer requests.
     *
     * @param Client $httpClient The HTTP client to use for sending requests
     * @param string $serviceAddress The base URL of the SEP-12 KYC service endpoint
     * @param string|null $jwtToken JWT token for authentication obtained via SEP-10
     */
    public function __construct(
        Client $httpClient,
        private string $serviceAddress,
        private ?string $jwtToken = null,
    ) {
        parent::__construct($httpClient);
    }

    /**
     * Sets the query parameters for the request.
     *
     * Supported parameters: id, account, memo, memo_type, type, transaction_id, lang
     *
     * @param array<string, string> $queryParameters Query parameters to include in the request
     * @return GetCustomerInfoRequestBuilder Returns this builder for method chaining
     */
    public function forQueryParameters(array $queryParameters) : GetCustomerInfoRequestBuilder {
        $this->queryParameters = array_merge($this->queryParameters, $queryParameters);
        return $this;
    }

    /**
     * Executes the HTTP request to the specified URL.
     *
     * @param string $url The fully constructed URL to send the request to
     * @return GetCustomerInfoResponse The parsed response containing customer information
     * @throws GuzzleException If the HTTP request fails or server returns an error
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

    /**
     * Builds the complete URL for the request.
     *
     * @return string The fully constructed URL with query parameters
     */
    public function buildUrl() : string {
        $url = $this->serviceAddress . "/customer";
        if (count($this->queryParameters) > 0) {
            $url .= '?' . http_build_query($this->queryParameters);
        }
        return $url;
    }

    /**
     * Builds and executes the request.
     *
     * @return GetCustomerInfoResponse The parsed response containing customer information and status
     * @throws GuzzleException If the HTTP request fails or server returns an error
     */
    public function execute() : GetCustomerInfoResponse {
        return $this->request($this->buildUrl());
    }
}