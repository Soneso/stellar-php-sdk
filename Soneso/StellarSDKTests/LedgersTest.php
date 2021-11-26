<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\StellarSDK;

class LedgersTest extends TestCase
{
    public function testExistingLedger(): void
    {
        $ledgerSeq = "459955";
        $sdk = StellarSDK::getTestNetInstance();
        $response = $sdk->requestLedger($ledgerSeq);
        $this->assertEquals($ledgerSeq, $response->getSequence());

    }

    public function testQueryLedgers(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $requestBuilder = $sdk->ledgers()->order("desc")->limit(1);
        $response = $requestBuilder->execute();
        foreach ($response->getLedgers() as $ledger) {
            $this->assertEquals(18, $ledger->getProtocolVersion());
        }
    }
}