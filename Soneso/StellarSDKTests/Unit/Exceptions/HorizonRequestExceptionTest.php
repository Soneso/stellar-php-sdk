<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Exceptions;

use Exception;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Errors\HorizonErrorResponse;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

class HorizonRequestExceptionTest extends TestCase
{
    public function setUp(): void
    {
        error_reporting(E_ALL);
    }

    public function testBasicConstruction()
    {
        $message = "Test error message";
        $originalException = new Exception($message);

        $horizonException = new HorizonRequestException($message, $originalException);

        assertEquals($message, $horizonException->getMessage());
        assertEquals($originalException, $horizonException->getPrevious());
    }

    public function testFromOtherExceptionBasic()
    {
        $url = "https://horizon.stellar.org/accounts/test";
        $method = "GET";
        $originalException = new Exception("Network error");

        $horizonException = HorizonRequestException::fromOtherException($url, $method, $originalException);

        assertEquals($url, $horizonException->getRequestedUrl());
        assertEquals($method, $horizonException->getHttpMethod());
        assertEquals("Network error", $horizonException->getMessage());
        assertNull($horizonException->getStatusCode());
        assertNull($horizonException->getRetryAfter());
        assertNull($horizonException->getHorizonErrorResponse());
    }

    public function testFromOtherExceptionWithHttpResponse()
    {
        $url = "https://horizon.stellar.org/accounts/test";
        $method = "GET";
        $statusCode = 404;
        $response = new Response($statusCode);
        $originalException = new Exception("Not found");

        $horizonException = HorizonRequestException::fromOtherException($url, $method, $originalException, $response);

        assertEquals($url, $horizonException->getRequestedUrl());
        assertEquals($method, $horizonException->getHttpMethod());
        assertEquals($statusCode, $horizonException->getStatusCode());
        assertNotNull($horizonException->getHttpResponse());
    }

    public function testFromOtherExceptionWithRequestException()
    {
        $url = "https://horizon.stellar.org/accounts/test";
        $method = "GET";
        $statusCode = 400;

        // Create a proper Horizon error response
        $errorBody = json_encode([
            'type' => 'https://stellar.org/horizon-errors/bad_request',
            'title' => 'Bad Request',
            'status' => 400,
            'detail' => 'The request you sent was invalid in some way.',
            'extras' => [
                'invalid_field' => 'account_id',
                'reason' => 'Account ID must start with G',
            ]
        ]);

        $response = new Response($statusCode, [], $errorBody);
        $request = new Request($method, $url);
        $requestException = new RequestException("Bad Request", $request, $response);

        $horizonException = HorizonRequestException::fromOtherException($url, $method, $requestException);

        assertEquals($url, $horizonException->getRequestedUrl());
        assertEquals($method, $horizonException->getHttpMethod());
        assertEquals($statusCode, $horizonException->getStatusCode());
        assertNotNull($horizonException->getHttpResponse());
        assertNull($horizonException->getRetryAfter());
    }

    public function testFromOtherExceptionWithBadResponseException()
    {
        $url = "https://horizon.stellar.org/accounts/test";
        $method = "POST";
        $statusCode = 400;

        $errorBody = json_encode([
            'type' => 'https://stellar.org/horizon-errors/transaction_failed',
            'title' => 'Transaction Failed',
            'status' => 400,
            'detail' => 'The transaction failed when submitted to the Stellar network.',
            'extras' => [
                'envelope_xdr' => 'AAAAAA...',
                'result_codes' => [
                    'transaction' => 'tx_bad_seq',
                ]
            ]
        ]);

        $response = new Response($statusCode, [], $errorBody);
        $request = new Request($method, $url);
        $badResponseException = new BadResponseException("Transaction Failed", $request, $response);

        $horizonException = HorizonRequestException::fromOtherException($url, $method, $badResponseException);

        assertEquals($url, $horizonException->getRequestedUrl());
        assertEquals($method, $horizonException->getHttpMethod());
        assertEquals($statusCode, $horizonException->getStatusCode());
        assertNotNull($horizonException->getHorizonErrorResponse());

        $errorResponse = $horizonException->getHorizonErrorResponse();
        assertTrue($errorResponse instanceof HorizonErrorResponse);
        assertEquals('The transaction failed when submitted to the Stellar network.', $errorResponse->getDetail());
    }

    public function testFromOtherExceptionWithRateLimiting()
    {
        $url = "https://horizon.stellar.org/accounts/test";
        $method = "GET";
        $statusCode = 429;
        $retryAfter = "60";

        $errorBody = json_encode([
            'type' => 'https://stellar.org/horizon-errors/rate_limit_exceeded',
            'title' => 'Rate Limit Exceeded',
            'status' => 429,
            'detail' => 'Rate limit of 100 requests per hour exceeded.',
        ]);

        $response = new Response($statusCode, ['Retry-After' => $retryAfter], $errorBody);
        $request = new Request($method, $url);
        $requestException = new RequestException("Rate Limited", $request, $response);

        $horizonException = HorizonRequestException::fromOtherException($url, $method, $requestException);

        assertEquals($url, $horizonException->getRequestedUrl());
        assertEquals($method, $horizonException->getHttpMethod());
        assertEquals($statusCode, $horizonException->getStatusCode());
        assertEquals($retryAfter, $horizonException->getRetryAfter());
        assertNotNull($horizonException->getHttpResponse());
    }

    public function testGetters()
    {
        $url = "https://horizon.stellar.org/transactions";
        $method = "POST";
        $statusCode = 500;

        $errorBody = json_encode([
            'type' => 'https://stellar.org/horizon-errors/server_error',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'An error occurred while processing your request.',
        ]);

        $response = new Response($statusCode, [], $errorBody);
        $request = new Request($method, $url);
        $badResponseException = new BadResponseException("Server Error", $request, $response);

        $horizonException = HorizonRequestException::fromOtherException($url, $method, $badResponseException);

        // Test all getters
        assertEquals($url, $horizonException->getRequestedUrl());
        assertEquals($method, $horizonException->getHttpMethod());
        assertEquals($statusCode, $horizonException->getStatusCode());
        assertNotNull($horizonException->getHttpResponse());
        assertNotNull($horizonException->getHorizonErrorResponse());
        assertEquals('An error occurred while processing your request.', $horizonException->getMessage());
    }

    public function testHttpMethodTypes()
    {
        $url = "https://horizon.stellar.org/accounts/test";
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

        foreach ($methods as $method) {
            $originalException = new Exception("Test error");
            $horizonException = HorizonRequestException::fromOtherException($url, $method, $originalException);

            assertEquals($method, $horizonException->getHttpMethod());
        }
    }

    public function testMultipleRetryAfterHeaders()
    {
        $url = "https://horizon.stellar.org/accounts/test";
        $method = "GET";
        $statusCode = 429;

        $response = new Response($statusCode, ['Retry-After' => ['60', '120']], '');
        $request = new Request($method, $url);
        $requestException = new RequestException("Rate Limited", $request, $response);

        $horizonException = HorizonRequestException::fromOtherException($url, $method, $requestException);

        // Should use the first value
        assertEquals("60", $horizonException->getRetryAfter());
    }

    public function testNoRetryAfterHeader()
    {
        $url = "https://horizon.stellar.org/accounts/test";
        $method = "GET";
        $statusCode = 429;

        $response = new Response($statusCode, [], '');
        $request = new Request($method, $url);
        $requestException = new RequestException("Rate Limited", $request, $response);

        $horizonException = HorizonRequestException::fromOtherException($url, $method, $requestException);

        // Should be null when header is missing
        assertNull($horizonException->getRetryAfter());
    }

    public function testInvalidJsonResponse()
    {
        $url = "https://horizon.stellar.org/accounts/test";
        $method = "GET";
        $statusCode = 400;

        // Invalid JSON
        $errorBody = "This is not JSON";

        $response = new Response($statusCode, [], $errorBody);
        $request = new Request($method, $url);
        $badResponseException = new BadResponseException("Bad Request", $request, $response);

        $horizonException = HorizonRequestException::fromOtherException($url, $method, $badResponseException);

        assertEquals($statusCode, $horizonException->getStatusCode());
        // Should not have a parsed error response for invalid JSON
        assertNull($horizonException->getHorizonErrorResponse());
    }

    public function testJsonResponseWithoutTypeField()
    {
        $url = "https://horizon.stellar.org/accounts/test";
        $method = "GET";
        $statusCode = 400;

        // Valid JSON but missing 'type' field
        $errorBody = json_encode([
            'title' => 'Error',
            'status' => 400,
            'detail' => 'Something went wrong',
        ]);

        $response = new Response($statusCode, [], $errorBody);
        $request = new Request($method, $url);
        $badResponseException = new BadResponseException("Bad Request", $request, $response);

        $horizonException = HorizonRequestException::fromOtherException($url, $method, $badResponseException);

        assertEquals($statusCode, $horizonException->getStatusCode());
        // Should not have a parsed error response without 'type' field
        assertNull($horizonException->getHorizonErrorResponse());
    }
}
