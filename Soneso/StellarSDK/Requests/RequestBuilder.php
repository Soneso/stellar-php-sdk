<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use RuntimeException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Response;
use Soneso\StellarSDK\Responses\ResponseHandler;
use Soneso\StellarSDK\StellarSDK;

abstract class RequestBuilder
{
    protected Client $httpClient;
    protected array $queryParameters;
    public const HEADERS = ["X-Client-Name" => "stellar_php_sdk", "X-Client-Version" => StellarSDK::VERSION_NR];
    private array $segments;
    private bool $segmentsAdded = false;
    
    
    public function __construct(Client $httpClient, ?string $defaultSegment = null) {
        $this->httpClient = $httpClient;
        $this->segments = array();
        $this->queryParameters = array();
        if ($defaultSegment != null) {
            $this->setSegments($defaultSegment);
        }
        $this->segmentsAdded = false; // Allow overwriting segments
    }
    
    protected function setSegments(string ...$segments) : RequestBuilder {
        if ($this->segmentsAdded) {
            throw new RuntimeException("URL segments have been already added.");
        }
        
        $this->segmentsAdded = true;
        
        $this->segments = array();
        foreach ($segments as $segment) {
            array_push($this->segments, $segment);
        }
        return $this;
        
    }
    
    /**
     * Sets <code>cursor</code> parameter on the request.
     * A cursor is a value that points to a specific location in a collection of resources.
     * The cursor attribute itself is an opaque value meaning that users should not try to parse it.
     * @see <a href="https://developers.stellar.org/api/introduction/pagination/">Page documentation</a>
     * @param string cursor
     */
    public function cursor(string $cursor) : RequestBuilder {
        $this->queryParameters['cursor'] = $cursor;
        return $this;
    }
    
    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int number maximum number of records to return
     */
    public function limit(int $number) : RequestBuilder {
        $this->queryParameters['limit'] = $number;
        return $this;
    }
    
    /**
     * Sets <code>order</code> parameter on the request.
     * @param string direction "asc" or "desc"
     */
    public function order(string $direction = "asc") : RequestBuilder {
        $this->queryParameters['order'] = $direction;
        return $this;
    }
    
    public function buildUrl() : string {
        $implodedSegments = implode("/", $this->segments);
        $result = "/" . $implodedSegments . "?" . http_build_query($this->queryParameters);
        //print($result . PHP_EOL);
        return $result;
    }

    /**
     * Requests specific <code>url</code> and returns {@link Response} as given by <code>requestType</code>.
     * @throws HorizonRequestException
     */
    public function executeRequest(string $url, string $requestType, ?string $requestMethod = "GET") : Response {

        $response = null;
        try {
            $request = new Request($requestMethod, $url, ['headers' => RequestBuilder::HEADERS]);
            $response = $this->httpClient->send($request);
        }
        catch (GuzzleException $e) {
            throw HorizonRequestException::fromOtherException($url, $requestMethod, $e, $response);
        }
        $responseHandler = new ResponseHandler();
        try {
            return $responseHandler->handleResponse($response, $requestType);
        } catch (\Exception $e) {
            throw HorizonRequestException::fromOtherException($url, $requestMethod, $e, $response);
        }
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException
    */
    public abstract function execute() : Response;
}