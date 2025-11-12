<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\ResponseHandler;

/**
 * Request builder for GET /deposit endpoint operations.
 *
 * This builder constructs HTTP requests to initiate deposit operations from external assets
 * (fiat or crypto) to Stellar assets via SEP-06. A deposit moves funds from an off-chain source
 * (bank account, cash, external blockchain) onto the Stellar network.
 *
 * The response provides instructions for completing the deposit, including:
 * - Deposit address or account details
 * - Required fields or interactive URL
 * - Minimum and maximum deposit amounts
 * - Fee information
 * - Estimated completion time
 *
 * Required authentication via SEP-10 is typically required for this endpoint.
 *
 * Example usage:
 * ```php
 * $builder = new DepositRequestBuilder($httpClient, $serviceAddress, $jwt);
 * $response = $builder->forQueryParameters([
 *     'asset_code' => 'USD',
 *     'account' => 'GXXX...',
 *     'type' => 'bank_account'
 * ])->execute();
 * ```
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#deposit SEP-06 Deposit Endpoint
 * @see TransferServerService::deposit() For the service method using this builder
 * @see DepositResponse For the response structure
 * @since 1.0.0
 */
class DepositRequestBuilder extends RequestBuilder
{
    /**
     * @var string|null JWT token for authentication obtained via SEP-10
     */
    private ?string $jwtToken = null;

    /**
     * @var string The base URL of the SEP-06 transfer server service
     */
    private string $serviceAddress;

    /**
     * Constructor for building GET /deposit requests.
     *
     * @param Client $httpClient The HTTP client to use for sending requests
     * @param string $serviceAddress The base URL of the transfer server from stellar.toml
     * @param string|null $jwtToken Optional JWT token obtained from SEP-10 authentication
     */
    public function __construct(Client $httpClient, string $serviceAddress, ?string $jwtToken = null)
    {
        $this->jwtToken = $jwtToken;
        $this->serviceAddress = $serviceAddress;
        parent::__construct($httpClient);
    }

    /**
     * Append query parameters to the request.
     * @param array<array-key, mixed> $queryParameters the query parameters to use for the get request.
     * @return $this returns the builder, so that it can be chained.
     */
    public function forQueryParameters(array $queryParameters) : DepositRequestBuilder {
        $this->queryParameters = array_merge($this->queryParameters, $queryParameters);
        return $this;
    }

    /**
     * Sends the get request for the given url.
     * Attaches the jwt token to the request if provided by constructor.
     * @param string $url the url to request from
     * @return DepositResponse the parsed response
     * @throws CustomerInformationNeededException if the server response status code is 403 and
     * type is customer_info_status
     * @throws CustomerInformationStatusException if the server response status code is 403 and
     * type is non_interactive_customer_info_needed
     * @throws AuthenticationRequiredException if the endpoint requires authentication.
     * @throws GuzzleException if an exception occurs during the request.
    */
    public function request(string $url) : DepositResponse {
        $headers = array();
        $headers = array_merge($headers, RequestBuilder::HEADERS);
        if ($this->jwtToken !== null) {
            $headers = array_merge($headers, ['Authorization' => "Bearer ".$this->jwtToken]);
        }
        $request = new Request("GET", $url, $headers);
        $response = $this->httpClient->send($request);
        if (403 === $response->getStatusCode()) {
            $content = $response->getBody()->__toString();
            $jsonData = @json_decode($content, true);
            if (isset($jsonData['type'])){
                $type = $jsonData['type'];
                if ("non_interactive_customer_info_needed" === $type) {
                    $val = CustomerInformationNeededResponse::fromJson($jsonData);
                    throw new CustomerInformationNeededException($val);
                } else if ("customer_info_status" === $type) {
                    $val = CustomerInformationStatusResponse::fromJson($jsonData);
                    throw new CustomerInformationStatusException($val);
                } else if ("authentication_required" === $type) {
                    throw new AuthenticationRequiredException("The deposit endpoint requires authentication");
                }
            }
        }
        $responseHandler = new ResponseHandler();
        $response = $responseHandler->handleResponse($response, RequestType::ANCHOR_DEPOSIT, $this->httpClient);
        assert($response instanceof DepositResponse);

        return $response;
    }

    /**
     * Builds the url for the request.
     * @return string the constructed url.
     */
    public function buildUrl() : string {
        $url = $this->serviceAddress . "/deposit";
        if (count($this->queryParameters) > 0) {
            $url .= '?' . http_build_query($this->queryParameters);
        }

        return $url;
    }

    /**
     * Build and execute request.
     * @return DepositResponse the parsed response.
     * @throws CustomerInformationNeededException if the server response status code is 403 and
     * type is customer_info_status
     * @throws CustomerInformationStatusException if the server response status code is 403 and
     * type is non_interactive_customer_info_needed
     * @throws AuthenticationRequiredException if the endpoint requires authentication.
     * @throws GuzzleException if any request exception occurs.
     */
    public function execute() : DepositResponse {
        return $this->request($this->buildUrl());
    }
}