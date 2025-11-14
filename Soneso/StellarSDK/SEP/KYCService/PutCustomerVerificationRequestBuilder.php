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
 * Request builder for PUT /customer/verification endpoint operations.
 *
 * This builder constructs HTTP requests to submit verification data values such as confirmation
 * codes that verify previously provided fields (e.g., mobile_number or email_address).
 *
 * When a customer provides contact information, the anchor may send a verification code to
 * that contact method. The customer then submits this code through this endpoint to prove
 * ownership of the contact information.
 *
 * Example usage:
 * ```php
 * $fields = ['mobile_number_verification' => '123456'];
 * $builder = new PutCustomerVerificationRequestBuilder($httpClient, $serviceAddress, $fields, $jwt);
 * $response = $builder->execute();
 * ```
 *
 * @package Soneso\StellarSDK\SEP\KYCService
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#customer-put-verification SEP-12 v1.15.0
 * @see KYCService::putCustomerVerification() For the service method using this builder
 * @see GetCustomerInfoResponse For the response structure
 * @deprecated This endpoint is deprecated per SEP-12 specification
 * @since 1.0.0
 */
class PutCustomerVerificationRequestBuilder extends RequestBuilder
{
    /**
     * @var string The base URL of the SEP-12 KYC service endpoint
     */
    private string $serviceAddress;

    /**
     * @var string|null JWT token for authentication obtained via SEP-10
     */
    private ?string $jwtToken = null;

    /**
     * @var array<array-key, string>|null Verification fields (e.g., mobile_number_verification => code)
     */
    private ?array $fields = null;

    /**
     * Constructor for building PUT /customer/verification requests.
     *
     * @param Client $httpClient The HTTP client to use for sending requests
     * @param string $serviceAddress The base URL of the SEP-12 service
     * @param array<array-key, string>|null $fields Verification fields to submit
     * @param string|null $jwtToken JWT token for authentication obtained via SEP-10
     */
    public function __construct(Client $httpClient, string $serviceAddress, ?array $fields = null, ?string $jwtToken = null)
    {
        $this->serviceAddress = $serviceAddress;
        $this->jwtToken = $jwtToken;
        $this->fields = $fields;
        parent::__construct($httpClient);
    }

    /**
     * Builds the complete URL for the request.
     *
     * @return string The fully constructed URL for verification submission
     */
    public function buildUrl() : string {
        return $this->serviceAddress . "/customer/verification";
    }

    /**
     * Executes the HTTP request to submit verification data.
     *
     * @param string $url The fully constructed URL to send the request to
     * @return GetCustomerInfoResponse The parsed response containing updated customer status
     * @throws GuzzleException If the HTTP request fails or server returns an error
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
     * Builds and executes the verification submission request.
     *
     * @return GetCustomerInfoResponse The parsed response containing updated customer status
     * @throws GuzzleException If the HTTP request fails or server returns an error
     */
    public function execute() : GetCustomerInfoResponse {
        return $this->request($this->buildUrl());
    }
}