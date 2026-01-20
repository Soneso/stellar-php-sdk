<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Responses\Health\HealthResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class HealthTest extends TestCase
{
    /**
     * Test the health endpoint with mocked response for all systems operational
     */
    public function testHealthEndpointMockedAllHealthy(): void
    {
        $mockResponse = [
            'database_connected' => true,
            'core_up' => true,
            'core_synced' => true
        ];

        $mock = new MockHandler([
            new Response(200, ['X-Ratelimit-Limit' => ['100']], json_encode($mockResponse))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $sdk->setHttpClient($client);

        $healthResponse = $sdk->health();

        $this->assertInstanceOf(HealthResponse::class, $healthResponse);
        $this->assertTrue($healthResponse->getDatabaseConnected());
        $this->assertTrue($healthResponse->getCoreUp());
        $this->assertTrue($healthResponse->getCoreSynced());
    }

    /**
     * Test the health endpoint with mocked response for partial system failure
     */
    public function testHealthEndpointMockedPartialFailure(): void
    {
        $mockResponse = [
            'database_connected' => true,
            'core_up' => false,
            'core_synced' => false
        ];

        $mock = new MockHandler([
            new Response(200, ['X-Ratelimit-Limit' => ['100']], json_encode($mockResponse))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $sdk->setHttpClient($client);

        $healthResponse = $sdk->health();

        $this->assertInstanceOf(HealthResponse::class, $healthResponse);
        $this->assertTrue($healthResponse->getDatabaseConnected());
        $this->assertFalse($healthResponse->getCoreUp());
        $this->assertFalse($healthResponse->getCoreSynced());
    }

    /**
     * Test the health endpoint with mocked response for database failure
     */
    public function testHealthEndpointMockedDatabaseFailure(): void
    {
        $mockResponse = [
            'database_connected' => false,
            'core_up' => true,
            'core_synced' => true
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $sdk->setHttpClient($client);

        $healthResponse = $sdk->health();

        $this->assertInstanceOf(HealthResponse::class, $healthResponse);
        $this->assertFalse($healthResponse->getDatabaseConnected());
        $this->assertTrue($healthResponse->getCoreUp());
        $this->assertTrue($healthResponse->getCoreSynced());
    }

    /**
     * Test the health endpoint response parsing
     */
    public function testHealthResponseParsing(): void
    {
        // Test with various response formats
        $testCases = [
            [
                'input' => ['database_connected' => true, 'core_up' => true, 'core_synced' => true],
                'expected' => [true, true, true]
            ],
            [
                'input' => ['database_connected' => false, 'core_up' => false, 'core_synced' => false],
                'expected' => [false, false, false]
            ],
            [
                'input' => ['database_connected' => true, 'core_up' => false, 'core_synced' => true],
                'expected' => [true, false, true]
            ]
        ];

        foreach ($testCases as $testCase) {
            $response = HealthResponse::fromJson($testCase['input']);
            $this->assertEquals($testCase['expected'][0], $response->getDatabaseConnected());
            $this->assertEquals($testCase['expected'][1], $response->getCoreUp());
            $this->assertEquals($testCase['expected'][2], $response->getCoreSynced());
        }
    }

    /**
     * Test rate limit headers are properly parsed
     */
    public function testRateLimitHeaders(): void
    {
        $mockResponse = [
            'database_connected' => true,
            'core_up' => true,
            'core_synced' => true
        ];

        $mock = new MockHandler([
            new Response(200, [
                'X-Ratelimit-Limit' => ['100'],
                'X-Ratelimit-Remaining' => ['99'],
                'X-Ratelimit-Reset' => ['3600']
            ], json_encode($mockResponse))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $sdk->setHttpClient($client);

        $healthResponse = $sdk->health();

        $this->assertEquals(100, $healthResponse->getRateLimitLimit());
        $this->assertEquals(99, $healthResponse->getRateLimitRemaining());
        $this->assertEquals(3600, $healthResponse->getRateLimitReset());
    }
}
