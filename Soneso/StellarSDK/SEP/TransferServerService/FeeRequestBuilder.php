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
use UnexpectedValueException;

/**
 * Request builder for GET /fee endpoint operations.
 *
 * This builder constructs HTTP requests to query fee information for deposit and withdrawal
 * operations via SEP-06. The endpoint returns the fee that would be charged for a specific
 * operation, allowing clients to display accurate fee information to users before initiating
 * a transaction.
 *
 * Fees may vary based on:
 * - Asset being deposited or withdrawn
 * - Operation type (deposit vs withdrawal)
 * - Transfer method (bank transfer, cash, crypto)
 * - Amount being transferred
 *
 * Example usage:
 * ```php
 * $builder = new FeeRequestBuilder($httpClient, $serviceAddress, $jwt);
 * $response = $builder->forQueryParameters([
 *     'operation' => 'deposit',
 *     'asset_code' => 'USD',
 *     'amount' => '100'
 * ])->execute();
 * ```
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#fee SEP-06 Fee Endpoint
 * @see TransferServerService::fee() For the service method using this builder
 * @see FeeResponse For the response structure
 * @since 1.0.0
 */
class FeeRequestBuilder extends RequestBuilder
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
     * Constructor for building GET /fee requests.
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
    public function forQueryParameters(array $queryParameters) : FeeRequestBuilder {
        $this->queryParameters = array_merge($this->queryParameters, $queryParameters);
        return $this;
    }

    /**
     * Sends the get request for the given url.
     * Attaches the jwt token to the request if provided by constructor.
     * @param string $url the url to request from
     * @return FeeResponse the parsed response
     * @throws GuzzleException if an exception occurs during the request.
     */
    public function request(string $url) : FeeResponse {
        $headers = array();
        $headers = array_merge($headers, RequestBuilder::HEADERS);

        if ($this->jwtToken !== null) {
            $headers = array_merge($headers, ['Authorization' => "Bearer ".$this->jwtToken]);
        }

        $request = new Request("GET", $url, $headers);
        $response = $this->httpClient->send($request);
        $responseHandler = new ResponseHandler();
        $response = $responseHandler->handleResponse($response, RequestType::ANCHOR_FEE, $this->httpClient);
        if (!$response instanceof FeeResponse) {
            throw new UnexpectedValueException('Expected FeeResponse, got ' . get_class($response));
        }

        return $response;
    }

    /**
     * Builds the url for the request.
     * @return string the constructed url.
     */
    public function buildUrl() : string {
        $url = $this->serviceAddress . "/fee";
        if (count($this->queryParameters) > 0) {
            $url .= '?' . http_build_query($this->queryParameters);
        }

        return $url;
    }

    /**
     * Build and execute request.
     * @return FeeResponse the parsed response.
     * @throws GuzzleException if any request exception occurs.
     */
    public function execute() : FeeResponse {

        return $this->request($this->buildUrl());
    }
}