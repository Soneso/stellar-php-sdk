<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Integration;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Responses\Health\HealthResponse;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;

class HealthTest extends TestCase
{
    private string $testOn = 'testnet'; // 'futurenet' or 'testnet'
    private StellarSDK $sdk;

    public function setUp(): void
    {
        if ($this->testOn === 'testnet') {
            $this->sdk = StellarSDK::getTestNetInstance();
        } elseif ($this->testOn === 'futurenet') {
            $this->sdk = StellarSDK::getFutureNetInstance();
        }
    }

    /**
     * Test the health endpoint against live Horizon testnet
     */
    public function testHealthEndpointLive(): void
    {
        // Test against live testnet
        $healthResponse = $this->sdk->health();

        // Assert that we get a HealthResponse object
        $this->assertInstanceOf(HealthResponse::class, $healthResponse);

        // Check that all three boolean fields are present
        $this->assertIsBool($healthResponse->getDatabaseConnected());
        $this->assertIsBool($healthResponse->getCoreUp());
        $this->assertIsBool($healthResponse->getCoreSynced());

        // In a healthy system, all should typically be true
        // But we don't assert this as the system might be in maintenance
        print("\nHealth Status:");
        print("\n  Database Connected: " . ($healthResponse->getDatabaseConnected() ? 'true' : 'false'));
        print("\n  Core Up: " . ($healthResponse->getCoreUp() ? 'true' : 'false'));
        print("\n  Core Synced: " . ($healthResponse->getCoreSynced() ? 'true' : 'false') . "\n");
    }

    /**
     * Test getting health status from public network
     */
    public function testHealthOnPublicNet(): void
    {
        $sdk = StellarSDK::getPublicNetInstance();

        try {
            $healthResponse = $sdk->health();

            $this->assertInstanceOf(HealthResponse::class, $healthResponse);
            $this->assertIsBool($healthResponse->getDatabaseConnected());
            $this->assertIsBool($healthResponse->getCoreUp());
            $this->assertIsBool($healthResponse->getCoreSynced());

            print("\nPublic Network Health Status:");
            print("\n  Database Connected: " . ($healthResponse->getDatabaseConnected() ? 'true' : 'false'));
            print("\n  Core Up: " . ($healthResponse->getCoreUp() ? 'true' : 'false'));
            print("\n  Core Synced: " . ($healthResponse->getCoreSynced() ? 'true' : 'false') . "\n");
        } catch (HorizonRequestException $e) {
            // If the public network is down or unreachable, we should still pass the test
            $this->markTestSkipped('Public network is not reachable: ' . $e->getMessage());
        }
    }
}
