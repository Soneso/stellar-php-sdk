<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Responses;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Health\HealthResponse;
use Soneso\StellarSDK\Responses\ResponseHandler;
use Soneso\StellarSDK\SEP\KYCService\GetCustomerFilesResponse;

class ResponseHandlerTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function bodiesNotObjectOrArray(): array {
        return [
            'string' => ['"abc"', 'string'],
            'number' => ['42', 'int'],
            'null' => ['null', 'null'],
        ];
    }

    #[DataProvider('bodiesNotObjectOrArray')]
    public function testBodyNotObjectOrArrayRejected(string $body, string $type): void {
        try {
            (new ResponseHandler())->handleResponse(new Response(200, [], $body), RequestType::HEALTH, new Client());
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertSame("Response body must be a JSON object or array, got $type", $e->getMessage());
        }
    }

    public function testInvalidJsonBodyRejectedBeforeTypeCheck(): void {
        try {
            (new ResponseHandler())->handleResponse(new Response(200, [], '{not valid json'), RequestType::HEALTH, new Client());
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Error in json_decode: Syntax error', $e->getMessage());
        }
    }

    public function testArrayBodyParses(): void {
        $response = (new ResponseHandler())->handleResponse(
            new Response(200, [], '[]'), RequestType::GET_CUSTOMER_FILES, new Client());

        $this->assertInstanceOf(GetCustomerFilesResponse::class, $response);
        $this->assertSame([], $response->files);
    }

    public function testObjectBodyParses(): void {
        $httpClient = new Client();
        $body = '{"database_connected": true, "core_up": true, "core_synced": false}';
        $response = (new ResponseHandler())->handleResponse(
            new Response(200, ['X-Ratelimit-Remaining' => '17'], $body), RequestType::HEALTH, $httpClient);

        $this->assertInstanceOf(HealthResponse::class, $response);
        $this->assertTrue($response->getDatabaseConnected());
        $this->assertTrue($response->getCoreUp());
        $this->assertFalse($response->getCoreSynced());
        $this->assertSame(17, $response->getRateLimitRemaining());
        $this->assertSame($httpClient, $response->getHttpClient());
    }
}
