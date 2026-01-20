<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Responses;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Responses\Link\LinkResponse;
use Soneso\StellarSDK\Responses\Health\HealthResponse;
use Soneso\StellarSDK\Responses\FeeStats\FeeStatsResponse;
use Soneso\StellarSDK\Responses\FeeStats\FeeChargedResponse;
use Soneso\StellarSDK\Responses\FeeStats\MaxFeeResponse;
use Soneso\StellarSDK\Responses\Root\RootResponse;

/**
 * Unit tests for simple Response classes
 *
 * Tests JSON parsing and getter methods for Link, Health, FeeStats, and Root responses.
 * These are simpler response types with fewer dependencies.
 */
class SimpleResponsesTest extends TestCase
{
    // LinkResponse Tests

    public function testLinkResponseFromJson(): void
    {
        $json = [
            'href' => 'https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'templated' => false
        ];

        $link = LinkResponse::fromJson($json);

        $this->assertEquals('https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $link->getHref());
        $this->assertFalse($link->isTemplated());
    }

    public function testLinkResponseTemplated(): void
    {
        $json = [
            'href' => 'https://horizon.stellar.org/accounts/{account_id}',
            'templated' => true
        ];

        $link = LinkResponse::fromJson($json);

        $this->assertEquals('https://horizon.stellar.org/accounts/{account_id}', $link->getHref());
        $this->assertTrue($link->isTemplated());
    }

    // HealthResponse Tests

    public function testHealthResponseHealthy(): void
    {
        $json = [
            'database_connected' => true,
            'core_up' => true,
            'core_synced' => true
        ];

        $health = HealthResponse::fromJson($json);

        $this->assertTrue($health->getDatabaseConnected());
        $this->assertTrue($health->getCoreUp());
        $this->assertTrue($health->getCoreSynced());
    }

    public function testHealthResponseUnhealthy(): void
    {
        $json = [
            'database_connected' => true,
            'core_up' => false,
            'core_synced' => false
        ];

        $health = HealthResponse::fromJson($json);

        $this->assertTrue($health->getDatabaseConnected());
        $this->assertFalse($health->getCoreUp());
        $this->assertFalse($health->getCoreSynced());
    }

    // FeeStatsResponse Tests

    public function testFeeStatsResponseFromJson(): void
    {
        $json = [
            'last_ledger' => '1234567',
            'last_ledger_base_fee' => '100',
            'ledger_capacity_usage' => '0.75',
            'fee_charged' => [
                'min' => '100',
                'max' => '50000',
                'mode' => '100',
                'p10' => '100',
                'p20' => '100',
                'p30' => '100',
                'p40' => '100',
                'p50' => '100',
                'p60' => '200',
                'p70' => '500',
                'p80' => '1000',
                'p90' => '5000',
                'p95' => '10000',
                'p99' => '25000'
            ],
            'max_fee' => [
                'min' => '1000',
                'max' => '100000',
                'mode' => '10000',
                'p10' => '1000',
                'p20' => '5000',
                'p30' => '10000',
                'p40' => '10000',
                'p50' => '10000',
                'p60' => '15000',
                'p70' => '20000',
                'p80' => '25000',
                'p90' => '50000',
                'p95' => '75000',
                'p99' => '100000'
            ]
        ];

        $feeStats = FeeStatsResponse::fromJson($json);

        $this->assertEquals('1234567', $feeStats->getLastLedger());
        $this->assertEquals('100', $feeStats->getLastLedgerBaseFee());
        $this->assertEquals('0.75', $feeStats->getLedgerCapacityUsage());

        $feeCharged = $feeStats->getFeeCharged();
        $this->assertInstanceOf(FeeChargedResponse::class, $feeCharged);
        $this->assertEquals('100', $feeCharged->getMin());
        $this->assertEquals('50000', $feeCharged->getMax());
        $this->assertEquals('100', $feeCharged->getMode());
        $this->assertEquals('100', $feeCharged->getP10());
        $this->assertEquals('100', $feeCharged->getP50());
        $this->assertEquals('5000', $feeCharged->getP90());
        $this->assertEquals('25000', $feeCharged->getP99());

        $maxFee = $feeStats->getMaxFee();
        $this->assertInstanceOf(MaxFeeResponse::class, $maxFee);
        $this->assertEquals('1000', $maxFee->getMin());
        $this->assertEquals('100000', $maxFee->getMax());
        $this->assertEquals('10000', $maxFee->getMode());
        $this->assertEquals('10000', $maxFee->getP50());
        $this->assertEquals('50000', $maxFee->getP90());
    }

    public function testFeeChargedResponseFromJson(): void
    {
        $json = [
            'min' => '100',
            'max' => '50000',
            'mode' => '100',
            'p10' => '100',
            'p20' => '100',
            'p30' => '100',
            'p40' => '100',
            'p50' => '100',
            'p60' => '200',
            'p70' => '500',
            'p80' => '1000',
            'p90' => '5000',
            'p95' => '10000',
            'p99' => '25000'
        ];

        $feeCharged = FeeChargedResponse::fromJson($json);

        $this->assertEquals('100', $feeCharged->getMin());
        $this->assertEquals('50000', $feeCharged->getMax());
        $this->assertEquals('100', $feeCharged->getMode());
        $this->assertEquals('100', $feeCharged->getP10());
        $this->assertEquals('100', $feeCharged->getP20());
        $this->assertEquals('100', $feeCharged->getP30());
        $this->assertEquals('100', $feeCharged->getP40());
        $this->assertEquals('100', $feeCharged->getP50());
        $this->assertEquals('200', $feeCharged->getP60());
        $this->assertEquals('500', $feeCharged->getP70());
        $this->assertEquals('1000', $feeCharged->getP80());
        $this->assertEquals('5000', $feeCharged->getP90());
        $this->assertEquals('10000', $feeCharged->getP95());
        $this->assertEquals('25000', $feeCharged->getP99());
    }

    public function testMaxFeeResponseFromJson(): void
    {
        $json = [
            'min' => '1000',
            'max' => '100000',
            'mode' => '10000',
            'p10' => '1000',
            'p20' => '5000',
            'p30' => '10000',
            'p40' => '10000',
            'p50' => '10000',
            'p60' => '15000',
            'p70' => '20000',
            'p80' => '25000',
            'p90' => '50000',
            'p95' => '75000',
            'p99' => '100000'
        ];

        $maxFee = MaxFeeResponse::fromJson($json);

        $this->assertEquals('1000', $maxFee->getMin());
        $this->assertEquals('100000', $maxFee->getMax());
        $this->assertEquals('10000', $maxFee->getMode());
        $this->assertEquals('1000', $maxFee->getP10());
        $this->assertEquals('10000', $maxFee->getP50());
        $this->assertEquals('50000', $maxFee->getP90());
        $this->assertEquals('100000', $maxFee->getP99());
    }

    // RootResponse Tests

    public function testRootResponseFromJson(): void
    {
        $json = [
            'horizon_version' => '2.29.0',
            'core_version' => 'v20.1.0',
            'history_latest_ledger' => 47578999,
            'history_elder_ledger' => 2,
            'core_latest_ledger' => 47579005,
            'network_passphrase' => 'Public Global Stellar Network ; September 2015',
            'current_protocol_version' => 20,
            'core_supported_protocol_version' => 20
        ];

        $root = RootResponse::fromJson($json);

        $this->assertEquals('2.29.0', $root->getHorizonVersion());
        $this->assertEquals('v20.1.0', $root->getCoreVersion());
        $this->assertEquals(47578999, $root->getHistoryLatestLedger());
        $this->assertEquals(2, $root->getHistoryElderLedger());
        $this->assertEquals(47579005, $root->getCoreLatestLedger());
        $this->assertEquals('Public Global Stellar Network ; September 2015', $root->getNetworkPassphrase());
        $this->assertEquals(20, $root->getCurrentProtocolVersion());
        $this->assertEquals(20, $root->getCoreSupportedProtocolVersion());
    }
}
