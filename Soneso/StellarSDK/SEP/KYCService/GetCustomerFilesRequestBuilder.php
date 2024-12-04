<?php

namespace Soneso\StellarSDK\SEP\KYCService;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\ResponseHandler;

class GetCustomerFilesRequestBuilder extends RequestBuilder
{
    private string $serviceAddress;
    private ?string $jwtToken = null;

    /**
     * @param Client $httpClient
     * @param string $jwtToken
     */
    public function __construct(Client $httpClient, string $serviceAddress, string $jwtToken)
    {
        $this->serviceAddress = $serviceAddress;
        $this->jwtToken = $jwtToken;
        parent::__construct($httpClient);
    }

    public function forQueryParameters(array $queryParameters) : GetCustomerFilesRequestBuilder {
        $this->queryParameters = array_merge($this->queryParameters, $queryParameters);
        return $this;
    }

    /**
     * @param string $url
     * @return GetCustomerFilesResponse
     * @throws GuzzleException
     */
    public function request(string $url) : GetCustomerFilesResponse {
        $headers = array();
        $headers = array_merge($headers, RequestBuilder::HEADERS);
        $headers = array_merge($headers, ['Authorization' => "Bearer " . $this->jwtToken]);
        $request = new Request("GET", $url, $headers);
        $response = $this->httpClient->send($request);
        $responseHandler = new ResponseHandler();
        return $responseHandler->handleResponse($response, RequestType::GET_CUSTOMER_FILES, $this->httpClient);
    }

    public function buildUrl() : string {
        $url = $this->serviceAddress . "/customer/files";
        if (count($this->queryParameters) > 0) {
            $url .= '?' . http_build_query($this->queryParameters);
        }
        return $url;
    }

    /**
     * Build and execute request.
     * @return GetCustomerFilesResponse
     * @throws GuzzleException
     */
    public function execute() : GetCustomerFilesResponse {
        return $this->request($this->buildUrl());
    }
}