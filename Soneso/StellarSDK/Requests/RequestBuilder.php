<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use RuntimeException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Response;
use Soneso\StellarSDK\Responses\ResponseHandler;
use Soneso\StellarSDK\StellarSDK;

/**
 * Base class for all Horizon API request builders
 *
 * This abstract class provides the foundation for querying the Stellar Horizon API.
 * It handles URL construction, query parameter management, pagination, streaming,
 * and HTTP request execution. All specific request builders extend this class.
 *
 * Common query methods available on all request builders:
 * - cursor(): Navigate to a specific position in the result set
 * - limit(): Control the number of records returned
 * - order(): Sort results in ascending or descending order
 *
 * @package Soneso\StellarSDK\Requests
 * @see https://developers.stellar.org Stellar developer docs Horizon API documentation
 */
abstract class RequestBuilder
{
    protected Client $httpClient;
    protected array $queryParameters;
    public const HEADERS = ["X-Client-Name" => "stellar_php_sdk", "X-Client-Version" => StellarSDK::VERSION_NR];
    protected array $segments;
    private bool $segmentsAdded = false;
    
    
    /**
     * Constructs a new request builder instance
     *
     * @param Client $httpClient The Guzzle HTTP client for making requests
     * @param string|null $defaultSegment Optional default URL segment to initialize the builder
     */
    public function __construct(Client $httpClient, ?string $defaultSegment = null) {
        $this->httpClient = $httpClient;
        $this->segments = array();
        $this->queryParameters = array();
        if ($defaultSegment !== null) {
            $this->setSegments($defaultSegment);
        }
        $this->segmentsAdded = false; // Allow overwriting segments
    }
    
    /**
     * Sets the URL path segments for this request
     *
     * This method constructs the URL path by combining multiple segments.
     * Can only be called once per request builder instance.
     *
     * @param string ...$segments Variable number of URL path segments
     * @return RequestBuilder This instance for method chaining
     * @throws RuntimeException If segments have already been set
     */
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
     * @see https://developers.stellar.org Stellar developer docs Pagination documentation
     * @param string $cursor
     */
    public function cursor(string $cursor) : RequestBuilder {
        $this->queryParameters['cursor'] = $cursor;
        return $this;
    }
    
    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int $number Maximum number of records to return
     */
    public function limit(int $number) : RequestBuilder {
        $this->queryParameters['limit'] = $number;
        return $this;
    }
    
    /**
     * Sets <code>order</code> parameter on the request.
     * @param string $direction "asc" or "desc"
     */
    public function order(string $direction = "asc") : RequestBuilder {
        $this->queryParameters['order'] = $direction;
        return $this;
    }
    
    /**
     * Builds the complete request URL with all segments and query parameters
     *
     * Combines the URL path segments with query parameters to create the final
     * request URL string that will be sent to Horizon.
     *
     * @return string The constructed URL with query parameters
     */
    public function buildUrl() : string {
        $implodedSegments = implode("/", $this->segments);
        $result = $implodedSegments . "?" . http_build_query($this->queryParameters);
        //print($result . PHP_EOL);
        return $result;
    }

    /**
     * Executes an HTTP request to Horizon and returns a parsed response object
     *
     * This method sends the HTTP request to the Horizon server, handles errors,
     * and parses the JSON response into the appropriate response type.
     *
     * @param string $url The complete request URL to fetch
     * @param string $requestType The expected response type for parsing
     * @param string|null $requestMethod The HTTP method to use (default: "GET")
     * @return Response The parsed response object of the specified type
     * @throws HorizonRequestException If the request fails or response cannot be parsed
     */
    public function executeRequest(string $url, string $requestType, ?string $requestMethod = "GET") : Response {

        $response = null;
        try {
            $request = new Request($requestMethod, $url, RequestBuilder::HEADERS);
            $response = $this->httpClient->send($request);
        }
        catch (GuzzleException $e) {
            throw HorizonRequestException::fromOtherException($url, $requestMethod, $e, $response);
        }
        $responseHandler = new ResponseHandler();
        try {
            return $responseHandler->handleResponse($response, $requestType, $this->httpClient);
        } catch (\Exception $e) {
            throw HorizonRequestException::fromOtherException($url, $requestMethod, $e, $response);
        }
    }

    /**
     * Builds the URL and executes the request, returning the response
     *
     * This abstract method must be implemented by subclasses to define
     * the specific behavior for executing their request type.
     *
     * @return Response The parsed response object
     * @throws HorizonRequestException If the request fails
     */
    public abstract function execute() : Response;

    /**
     * Streams Server-Sent Events from Horizon to a callback function
     *
     * This method establishes a persistent connection to Horizon's streaming endpoints
     * using Server-Sent Events (SSE). It processes each event and passes the parsed
     * data to the provided callback function. The stream automatically reconnects on
     * server exceptions if retryOnServerException is true.
     *
     * Horizon streaming uses SSE to push real-time updates. The stream sends:
     * - "hello" message on connection
     * - "byebye" message on disconnection
     * - JSON data objects for actual events
     *
     * @param string $relativeUrl The relative URL to stream from
     * @param callable $callback Function to receive parsed event data
     * @param bool $retryOnServerException If true, automatically retry on server errors (default: true)
     * @return void This method runs indefinitely until interrupted
     * @throws GuzzleException If a network error occurs and retryOnServerException is false
     */
    public function getAndStream(string $relativeUrl, callable $callback, bool $retryOnServerException = true) : void
    {
        while (true) {
            try {
                $response = $this->httpClient->get($relativeUrl, [
                    'stream' => true,
                    'read_timeout' => null,
                    'headers' => [
                        'Accept' => 'text/event-stream',
                    ]
                ]);

                $body = $response->getBody();

                while (!$body->eof()) {
                    $line = '';

                    $char = null;
                    while ($char != "\n") {
                        $line .= $char;
                        $char = $body->read(1);
                    }

                    // Ignore empty lines
                    if (!$line) continue;

                    // Ignore "data: hello" handshake
                    if (str_starts_with($line, 'data: "hello"')) continue;

                    // "data: byebye" if closed, restart
                    if (str_starts_with($line, 'data: "byebye"')) break;

                    // Ignore lines that don't start with "data: "
                    $sentinel = 'data: ';
                    if (!str_starts_with($line, $sentinel)) continue;

                    // Remove sentinel prefix
                    $json = substr($line, strlen($sentinel));

                    $decoded = json_decode($json, true);
                    if ($decoded) {
                        $callback($decoded);
                    }
                }
            }
            catch (ServerException $e) {
                if (!$retryOnServerException) throw $e;

                // Delay for a bit before trying again
                sleep(10);
            }
        }
    }
}