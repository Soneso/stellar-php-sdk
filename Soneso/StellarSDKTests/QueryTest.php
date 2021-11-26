<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\StellarSDK;

class QueryTest extends TestCase
{

    public function testRoot(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $response = $sdk->root();
        $this->assertEquals("18", $response->getCurrentProtocolVersion());
        $this->assertEquals("18", $response->getCoreSupportedProtocolVersion());
        $this->assertEquals("2.9.0~rc1-f8f9a3ef2fb2b7d4ce258e1b459c0bfac1fe2093", $response->getHorizonVersion());
        $this->assertEquals("stellar-core 18.0.1 (5bec96c4c9d7080802e80a2e93ddc0bd6bd8a98d)", $response->getCoreVersion());
        $this->assertGreaterThan(427672, $response->getIngestLatestLedger());
        $this->assertGreaterThan(427672, $response->getHistoryLatestLedger());
        $this->assertNotNull($response->getHistoryLatestLedgerClosedAt());
        $this->assertNotNull($response->getHistoryElderLedger());
        $this->assertNotNull($response->getCoreLatestLedger());
        $this->assertEquals("Test SDF Network ; September 2015", $response->getNetworkPassphrase());
    }
}

