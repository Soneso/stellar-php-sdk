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
 * Request builder for GET /info endpoint operations.
 *
 * This builder constructs HTTP requests to query anchor capabilities and supported assets via
 * the SEP-06 info endpoint. The info endpoint is the discovery endpoint that clients should query
 * first to understand what deposit and withdrawal operations the anchor supports.
 *
 * The response includes:
 * - Supported assets for deposit and withdrawal
 * - Available deposit/withdrawal methods for each asset
 * - Fee structures and minimum/maximum amounts
 * - Feature flags indicating supported functionality
 * - Required KYC fields
 *
 * Example usage:
 * ```php
 * $builder = new InfoRequestBuilder($httpClient, $serviceAddress, $jwt);
 * $response = $builder->forQueryParameters(['lang' => 'en'])->execute();
 * ```
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#info SEP-06 Info Endpoint
 * @see TransferServerService::info() For the service method using this builder
 * @see InfoResponse For the response structure
 * @since 1.0.0
 */
class InfoRequestBuilder extends RequestBuilder
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
     * Constructor for building GET /info requests.
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
    public function forQueryParameters(array $queryParameters) : InfoRequestBuilder {
        $this->queryParameters = array_merge($this->queryParameters, $queryParameters);
        return $this;
    }

    /**
     * Sends the get request for the given url.
     * Attaches the jwt token to the request if provided by constructor.
     * @param string $url the url to request from
     * @return InfoResponse the parsed response
     * @throws GuzzleException if an exception occurs during the request.
     */
    public function request(string $url) : InfoResponse {
        /**
         * @var array<array-key, mixed> $headers
         */
        $headers = array();
        $headers = array_merge($headers, RequestBuilder::HEADERS);
        if ($this->jwtToken !== null) {
            $headers = array_merge($headers, ['Authorization' => "Bearer ".$this->jwtToken]);
        }
        $request = new Request("GET", $url, $headers);
        $response = $this->httpClient->send($request);
        $responseHandler = new ResponseHandler();
        $response = $responseHandler->handleResponse($response, RequestType::ANCHOR_INFO, $this->httpClient);
        if (!$response instanceof InfoResponse) {
            throw new UnexpectedValueException('Expected InfoResponse, got ' . get_class($response));
        }

        return $response;
    }

    /**
     * Builds the url for the request.
     * @return string the constructed url.
     */
    public function buildUrl() : string {
        $url = $this->serviceAddress . "/info";
        if (count($this->queryParameters) > 0) {
            $url .= '?' . http_build_query($this->queryParameters);
        }

        return $url;
    }

    /**
     * Build and execute request.
     * @return InfoResponse the parsed response.
     * @throws GuzzleException if any request exception occurs.
     */
    public function execute() : InfoResponse {

        return $this->request($this->buildUrl());
    }
}