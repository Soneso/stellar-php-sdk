<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Exceptions;

use Exception;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Soneso\StellarSDK\Responses\Errors\HorizonErrorResponse;
use Throwable;

/**
 * Exception thrown when a Horizon API request fails
 *
 * This exception encapsulates all information about a failed Horizon request,
 * including the HTTP status code, error response details, and rate limiting
 * information. It extends ErrorException to provide detailed error context.
 *
 * Rate limiting (HTTP 429) responses include a retry-after value that indicates
 * when the request can be retried.
 *
 * @package Soneso\StellarSDK\Exceptions
 * @see HorizonErrorResponse For structured error response details
 * @see https://developers.stellar.org Stellar developer docs Documentation on Horizon errors
 */
class HorizonRequestException extends \ErrorException
{
    private string $requestedUrl; // URL that was requested and generated the error
    private string $httpMethod; // HTTP method used to request $requestedUrl
    private ?int $statusCode = null;
    private ?string $retryAfter = null;
    private ?HorizonErrorResponse $horizonErrorResponse = null;
    public ?ResponseInterface $httpResponse = null;

    /**
     * Gets the URL that was requested and generated the error
     *
     * @return string The requested URL
     */
    public function getRequestedUrl(): string
    {
        return $this->requestedUrl;
    }

    /**
     * Gets the HTTP method used to request the URL
     *
     * @return string The HTTP method (GET, POST, etc.)
     */
    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    /**
     * Gets the HTTP status code of the failed request
     *
     * @return int|null The HTTP status code, or null if not available
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * Gets the retry-after value for rate-limited requests
     *
     * When a request is rate-limited (HTTP 429), this indicates how many seconds
     * to wait before retrying the request.
     *
     * @return string|null The retry-after value (in seconds), or null if not rate-limited
     */
    public function getRetryAfter(): ?string
    {
        return $this->retryAfter;
    }

    /**
     * Gets the raw HTTP response
     *
     * @return ResponseInterface|null The PSR-7 HTTP response, or null if not available
     */
    public function getHttpResponse(): ?ResponseInterface
    {
        return $this->httpResponse;
    }

    /**
     * Gets the structured Horizon error response
     *
     * @return HorizonErrorResponse|null The parsed Horizon error response, or null if not available
     */
    public function getHorizonErrorResponse(): ?HorizonErrorResponse
    {
        return $this->horizonErrorResponse;
    }

    /**
     * Creates a HorizonRequestException from another exception
     *
     * This factory method wraps other exceptions (particularly Guzzle exceptions)
     * into a HorizonRequestException with additional context about the request.
     *
     * @param string $requestedUrl The URL that was requested
     * @param string $httpMethod The HTTP method used
     * @param Exception $e The original exception
     * @param ResponseInterface|null $httpResponse The HTTP response, if available
     * @return HorizonRequestException The wrapped exception
     */
    public static function fromOtherException(string $requestedUrl, string $httpMethod, Exception $e, ?ResponseInterface $httpResponse = null) : HorizonRequestException {

        $result =  new HorizonRequestException($e->getMessage(), $e);
        $result->requestedUrl = $requestedUrl;
        $result->httpMethod = $httpMethod;
        $result->httpResponse = $httpResponse;
        if ($httpResponse != null) {
            $result->statusCode = $httpResponse->getStatusCode();
        }

        if ($e instanceof RequestException && $e->getResponse()) {
            // print($e->getResponse()->getBody()->__toString() . PHP_EOL);
            $httpResponse = $e->getResponse();
            $result->httpResponse = $httpResponse;
            $result->statusCode = $httpResponse->getStatusCode();
            $decoded = json_decode($e->getResponse()->getBody()->__toString(), true);
            if ($decoded != null && $e instanceof BadResponseException && isset($decoded['type'])) {
                $errorResponse = HorizonErrorResponse::fromJson($decoded);
                $errorResponse->setHeaders($e->getResponse()->getHeaders());
                $result->horizonErrorResponse = $errorResponse;
                $result->message = $errorResponse->getDetail();
            }
        }
        if ($httpResponse != null && 429 === $httpResponse->getStatusCode()) {
            $headerArr = $httpResponse->getHeader("Retry-After");
            $count = count($headerArr);
            if ($count > 0) {
                $result->retryAfter = $headerArr[0];
            }
        }
        return $result;
    }
    
    /**
     * HorizonRequestException constructor
     *
     * @param string $title The error message title
     * @param Throwable|null $previous The previous exception for exception chaining
     */
    public function __construct(string $title, Throwable $previous = null)
    {
        parent::__construct($title, 0, 1, $previous->getFile(), $previous->getLine(), $previous);
    }

}