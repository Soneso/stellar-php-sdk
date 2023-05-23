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

class HorizonRequestException extends \ErrorException
{
    private string $requestedUrl; // URL that was requested and generated the error
    private string $httpMethod; // HTTP method used to request $requestedUrl
    private ?int $statusCode = null;
    private ?string $retryAfter = null;
    private ?HorizonErrorResponse $horizonErrorResponse = null;

    /**
     * @return string
     */
    public function getRequestedUrl(): string
    {
        return $this->requestedUrl;
    }

    /**
     * @return string
     */
    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    /**
     * @return ?int
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * @return int|null
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * @return HorizonErrorResponse|null
     */
    public function getHorizonErrorResponse(): ?HorizonErrorResponse
    {
        return $this->horizonErrorResponse;
    }

    public static function fromOtherException(string $requestedUrl, string $httpMethod, Exception $e, ?ResponseInterface $httpResponse = null) : HorizonRequestException {

        $result =  new HorizonRequestException($e->getMessage(), $e);
        $result->requestedUrl = $requestedUrl;
        $result->httpMethod = $httpMethod;
        if ($httpResponse != null) {
            $result->statusCode = $httpResponse->getStatusCode();
        }

        if ($e instanceof RequestException && $e->getResponse()) {
            // print($e->getResponse()->getBody()->__toString() . PHP_EOL);
            $httpResponse = $e->getResponse();
            $result->statusCode = $httpResponse->getStatusCode();
            $decoded = null;
            $decoded = json_decode($e->getResponse()->getBody()->__toString(), true);
            if ($decoded != null && $e instanceof BadResponseException) {
                $errorResponse = HorizonErrorResponse::fromJson($decoded);
                $errorResponse->setHeaders($e->getResponse()->getHeaders());
                $result->horizonErrorResponse = $errorResponse;
                $result->message = $errorResponse->getDetail();
            }
        }
        if ($httpResponse != null && 429 == $result->statusCode) {
            $headerArr = $httpResponse->getHeader("Retry-After");
            $count = count($headerArr);
            if ($count > 0) {
                $result->retryAfter = $headerArr[0];
            }
        }
        return $result;
    }
    
    /**
     * @param string         $title
     * @param Throwable|null $previous
     */
    public function __construct(string $title, Throwable $previous = null)
    {
        parent::__construct($title, 0, 1, $previous->getFile(), $previous->getLine(), $previous);
    }

}