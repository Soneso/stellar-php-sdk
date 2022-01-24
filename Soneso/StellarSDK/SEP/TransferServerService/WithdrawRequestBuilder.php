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
    private string $jwtToken;

    /**
     * @param Client $httpClient
     * @param string $jwtToken
     */
    public function __construct(Client $httpClient, string $jwtToken)
    {
        $this->jwtToken = $jwtToken;
        parent::__construct($httpClient, "withdraw");
    }

    public function forQueryParameters(array $queryParameters) : WithdrawRequestBuilder {
        $this->queryParameters = array_merge($this->queryParameters, $queryParameters);
        return $this;
    }

    /**
     * @param string $url
     * @return WithdrawResponse
     * @throws CustomerInformationNeededException
     * @throws CustomerInformationStatusException
     * @throws GuzzleException
     */
    public function request(string $url) : WithdrawResponse {
        $headers = array();
        $headers = array_merge($headers, RequestBuilder::HEADERS);
        $headers = array_merge($headers, ['Authorization' => "Bearer ".$this->jwtToken]);
        $request = new Request("GET", $url, $headers);
        $response = $this->httpClient->send($request);
        if (403 == $response->getStatusCode()) {
            $content = $response->getBody()->__toString();
            $jsonData = @json_decode($content, true);
            if (isset($jsonData['type'])){
                $type = $jsonData['type'];
                if ("non_interactive_customer_info_needed" == $type) {
                    $val = CustomerInformationNeededResponse::fromJson($jsonData);
                    throw new CustomerInformationNeededException($val);
                } else if ("customer_info_status" == $type) {
                    $val = CustomerInformationStatusResponse::fromJson($jsonData);
                    throw new CustomerInformationStatusException($val);
                }
            }
        }
        $responseHandler = new ResponseHandler();
        return $responseHandler->handleResponse($response, RequestType::ANCHOR_WITHDRAW, $this->httpClient);
    }

    /**
     * @return WithdrawResponse
     * @throws CustomerInformationNeededException
     * @throws CustomerInformationStatusException
     * @throws GuzzleException
     */
    public function execute() : WithdrawResponse {
        return $this->request($this->buildUrl());
    }
}