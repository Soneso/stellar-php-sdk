<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\StellarSDK;

class FeeStatsTest extends TestCase
{

    public function testFeeStats(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $response = $sdk->requestFeeStats();
        $this->assertGreaterThan(0,strlen($response->getLastLedger()));
        $this->assertGreaterThan(0,strlen($response->getLastLedgerBaseFee()));
        $this->assertGreaterThan(0,strlen($response->getLedgerCapacityUsage()));
        $feeCharged = $response->getFeeCharged();
        $this->assertGreaterThan(0,strlen($feeCharged->getMax()));
        $this->assertGreaterThan(0,strlen($feeCharged->getMin()));
        $this->assertGreaterThan(0,strlen($feeCharged->getMode()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP10()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP20()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP30()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP40()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP50()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP60()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP70()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP80()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP90()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP95()));
        $this->assertGreaterThan(0,strlen($feeCharged->getP99()));
        $maxFee = $response->getMaxFee();
        $this->assertGreaterThan(0,strlen($maxFee->getMax()));
        $this->assertGreaterThan(0,strlen($maxFee->getMin()));
        $this->assertGreaterThan(0,strlen($maxFee->getMode()));
        $this->assertGreaterThan(0,strlen($maxFee->getP10()));
        $this->assertGreaterThan(0,strlen($maxFee->getP20()));
        $this->assertGreaterThan(0,strlen($maxFee->getP30()));
        $this->assertGreaterThan(0,strlen($maxFee->getP40()));
        $this->assertGreaterThan(0,strlen($maxFee->getP50()));
        $this->assertGreaterThan(0,strlen($maxFee->getP60()));
        $this->assertGreaterThan(0,strlen($maxFee->getP70()));
        $this->assertGreaterThan(0,strlen($maxFee->getP80()));
        $this->assertGreaterThan(0,strlen($maxFee->getP90()));
        $this->assertGreaterThan(0,strlen($maxFee->getP95()));
        $this->assertGreaterThan(0,strlen($maxFee->getP99()));
    }
}