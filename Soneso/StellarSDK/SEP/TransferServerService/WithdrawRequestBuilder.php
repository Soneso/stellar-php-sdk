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

class WithdrawRequestBuilder extends RequestBuilder
{
    private ?string $jwtToken = null;
    private string $serviceAddress;

    /**
     * Constructor.
     * @param Client $httpClient the client to be used for the request.
     * @param string $serviceAddress the server address of the sep-06 service (e.g. from sep-01).
     * @param string|null $jwtToken optional jwt token obtained from sep-10 authentication. If provided it will be used in the request header.
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
    public function forQueryParameters(array $queryParameters) : WithdrawRequestBuilder {
        $this->queryParameters = array_merge($this->queryParameters, $queryParameters);
        return $this;
    }

    /**
     * Sends the get request for the given url.
     * Attaches the jwt token to the request if provided by constructor.
     * @param string $url the url to request from
     * @return WithdrawResponse the parsed response
     * @throws CustomerInformationNeededException if the server response status code is 403 and
     * type is customer_info_status
     * @throws CustomerInformationStatusException if the server response status code is 403 and
     * type is non_interactive_customer_info_needed
     * @throws AuthenticationRequiredException if the endpoint requires authentication.
     * @throws GuzzleException if an exception occurs during the request.
     */
    public function request(string $url) : WithdrawResponse {
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
                }  else if ("authentication_required" === $type) {
                    throw new AuthenticationRequiredException("The withdraw endpoint requires authentication");
                }
            }
        }
        $responseHandler = new ResponseHandler();
        $response = $responseHandler->handleResponse($response, RequestType::ANCHOR_WITHDRAW, $this->httpClient);
        assert($response instanceof WithdrawResponse);

        return $response;
    }

    /**
     * Builds the url for the request.
     * @return string the constructed url.
     */
    public function buildUrl() : string {
        $url = $this->serviceAddress . "/withdraw";
        if (count($this->queryParameters) > 0) {
            $url .= '?' . http_build_query($this->queryParameters);
        }

        return $url;
    }

    /**
     * Build and execute request.
     * @return WithdrawResponse the parsed response.
     * @throws CustomerInformationNeededException if the server response status code is 403 and
     * type is customer_info_status
     * @throws CustomerInformationStatusException if the server response status code is 403 and
     * type is non_interactive_customer_info_needed
     * @throws AuthenticationRequiredException if the endpoint requires authentication.
     * @throws GuzzleException if any request exception occurs.
     */
    public function execute() : WithdrawResponse {
        return $this->request($this->buildUrl());
    }
}