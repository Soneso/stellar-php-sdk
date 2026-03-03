<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
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
 * Request builder for GET /deposit-exchange endpoint operations.
 *
 * This builder constructs HTTP requests to initiate deposit operations with cross-asset exchange
 * via SEP-06 and SEP-38. This endpoint combines deposit functionality with on-chain asset exchange,
 * allowing users to deposit one asset and receive a different Stellar asset.
 *
 * For example, a user can deposit USD via bank transfer and receive USDC on Stellar, with the
 * exchange happening as part of the deposit process. This requires the anchor to support SEP-38
 * quote functionality.
 *
 * Example usage:
 * ```php
 * $builder = new DepositExchangeRequestBuilder($httpClient, $serviceAddress, $jwt);
 * $response = $builder->forQueryParameters([
 *     'source_asset' => 'iso4217:USD',
 *     'destination_asset' => 'stellar:USDC:GXXX...',
 *     'amount' => '100',
 *     'account' => 'GXXX...'
 * ])->execute();
 * ```
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#deposit-exchange SEP-06 Deposit Exchange
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md SEP-38 Quotes
 * @see TransferServerService::depositExchange() For the service method using this builder
 * @see DepositResponse For the response structure
 * @since 1.0.0
 */
class DepositExchangeRequestBuilder extends RequestBuilder
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
     * Constructor for building GET /deposit-exchange requests.
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
    public function forQueryParameters(array $queryParameters) : DepositExchangeRequestBuilder {
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
                    throw new AuthenticationRequiredException("The deposit exchange endpoint requires authentication");
                }
            }
        }
        $responseHandler = new ResponseHandler();
        $response = $responseHandler->handleResponse($response, RequestType::ANCHOR_DEPOSIT, $this->httpClient);
        if (!$response instanceof DepositResponse) {
            throw new UnexpectedValueException('Expected DepositResponse, got ' . get_class($response));
        }

        return $response;
    }

    /**
     * Builds the url for the request.
     * @return string the constructed url.
     */
    public function buildUrl() : string {
        $url = $this->serviceAddress . "/deposit-exchange";
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