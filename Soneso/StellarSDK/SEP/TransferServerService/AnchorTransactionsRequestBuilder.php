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
 * Request builder for GET /transactions endpoint operations.
 *
 * This builder constructs HTTP requests to query the transaction history for an authenticated
 * user via SEP-06. The endpoint returns a list of deposit and withdrawal transactions, which
 * can be filtered by asset, time range, and pagination parameters.
 *
 * This is useful for:
 * - Displaying transaction history to users
 * - Reconciling transactions with internal records
 * - Monitoring pending transactions
 * - Auditing completed transactions
 *
 * Authentication via SEP-10 is required to ensure users can only see their own transactions.
 *
 * Example usage:
 * ```php
 * $builder = new AnchorTransactionsRequestBuilder($httpClient, $serviceAddress, $jwt);
 * $response = $builder->forQueryParameters([
 *     'asset_code' => 'USD',
 *     'limit' => '20'
 * ])->execute();
 * ```
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#transactions SEP-06 Transactions Endpoint
 * @see TransferServerService::transactions() For the service method using this builder
 * @see AnchorTransactionsResponse For the response structure
 * @since 1.0.0
 */
class AnchorTransactionsRequestBuilder extends RequestBuilder
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
     * Constructor for building GET /transactions requests.
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
    public function forQueryParameters(array $queryParameters) : AnchorTransactionsRequestBuilder {
        $this->queryParameters = array_merge($this->queryParameters, $queryParameters);
        return $this;
    }

    /**
     * Sends the get request for the given url.
     * Attaches the jwt token to the request if provided by constructor.
     * @param string $url the url to request from
     * @return AnchorTransactionsResponse the parsed response
     * @throws GuzzleException if an exception occurs during the request.
     */
    public function request(string $url) : AnchorTransactionsResponse {
        $headers = array();
        $headers = array_merge($headers, RequestBuilder::HEADERS);

        if ($this->jwtToken !== null) {
            $headers = array_merge($headers, ['Authorization' => "Bearer ".$this->jwtToken]);
        }

        $request = new Request("GET", $url, $headers);
        $response = $this->httpClient->send($request);
        $responseHandler = new ResponseHandler();
        $response = $responseHandler->handleResponse($response, RequestType::ANCHOR_TRANSACTIONS, $this->httpClient);
        if (!$response instanceof AnchorTransactionsResponse) {
            throw new UnexpectedValueException('Expected AnchorTransactionsResponse, got ' . get_class($response));
        }

        return $response;
    }

    /**
     * Builds the url for the request.
     * @return string the constructed url.
     */
    public function buildUrl() : string {
        $url = $this->serviceAddress . "/transactions";
        if (count($this->queryParameters) > 0) {
            $url .= '?' . http_build_query($this->queryParameters);
        }

        return $url;
    }

    /**
     * Build and execute request.
     * @return AnchorTransactionsResponse the parsed response.
     * @throws GuzzleException if any request exception occurs.
     */
    public function execute() : AnchorTransactionsResponse {

        return $this->request($this->buildUrl());
    }
}