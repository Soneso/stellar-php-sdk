<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrConfigSettingEntry;
use Soneso\StellarSDK\Xdr\XdrConfigSettingID;
use Soneso\StellarSDK\Xdr\XdrConfigSettingContractLedgerCostV0;
use Soneso\StellarSDK\Xdr\XdrContractCostParams;
use Soneso\StellarSDK\Xdr\XdrContractCostParamEntry;
use Soneso\StellarSDK\Xdr\XdrContractCodeCostInputs;
use Soneso\StellarSDK\Xdr\XdrStateArchivalSettings;
use Soneso\StellarSDK\Xdr\XdrEvictionIterator;
use Soneso\StellarSDK\Xdr\XdrExtensionPoint;

class XdrConfigSettingTest extends TestCase
{
    /**
     * Test XdrConfigSettingContractLedgerCostV0 getters/setters
     */
    public function testXdrConfigSettingContractLedgerCostV0GettersSetters(): void
    {
        $ledgerCost = new XdrConfigSettingContractLedgerCostV0(
            1000, 5000000, 2000, 10000000,
            100, 100000, 200, 200000,
            50, 100, 25, 100000000,
            1000, 5000, 10
        );

        $ledgerCost->setLedgerMaxDiskReadLedgerEntries(1500);
        $this->assertEquals(1500, $ledgerCost->getLedgerMaxDiskReadLedgerEntries());

        $ledgerCost->setLedgerMaxDiskReadBytes(6000000);
        $this->assertEquals(6000000, $ledgerCost->getLedgerMaxDiskReadBytes());

        $ledgerCost->setLedgerMaxWriteLedgerEntries(2500);
        $this->assertEquals(2500, $ledgerCost->getLedgerMaxWriteLedgerEntries());

        $ledgerCost->setLedgerMaxWriteBytes(12000000);
        $this->assertEquals(12000000, $ledgerCost->getLedgerMaxWriteBytes());

        $ledgerCost->setTxMaxDiskReadEntries(150);
        $this->assertEquals(150, $ledgerCost->getTxMaxDiskReadEntries());

        $ledgerCost->setTxMaxDiskReadBytes(150000);
        $this->assertEquals(150000, $ledgerCost->getTxMaxDiskReadBytes());

        $ledgerCost->setTxMaxWriteLedgerEntries(250);
        $this->assertEquals(250, $ledgerCost->getTxMaxWriteLedgerEntries());

        $ledgerCost->setTxMaxWriteBytes(250000);
        $this->assertEquals(250000, $ledgerCost->getTxMaxWriteBytes());

        $ledgerCost->setFeeDiskReadLedgerEntry(75);
        $this->assertEquals(75, $ledgerCost->getFeeDiskReadLedgerEntry());

        $ledgerCost->setFeeWriteLedgerEntry(150);
        $this->assertEquals(150, $ledgerCost->getFeeWriteLedgerEntry());

        $ledgerCost->setFeeDiskRead1KB(30);
        $this->assertEquals(30, $ledgerCost->getFeeDiskRead1KB());

        $ledgerCost->setSorobanStateTargetSizeBytes(200000000);
        $this->assertEquals(200000000, $ledgerCost->getSorobanStateTargetSizeBytes());

        $ledgerCost->setRentFee1KBSorobanStateSizeLow(2000);
        $this->assertEquals(2000, $ledgerCost->getRentFee1KBSorobanStateSizeLow());

        $ledgerCost->setRentFee1KBSorobanStateSizeHigh(7500);
        $this->assertEquals(7500, $ledgerCost->getRentFee1KBSorobanStateSizeHigh());

        $ledgerCost->setSorobanStateRentFeeGrowthFactor(15);
        $this->assertEquals(15, $ledgerCost->getSorobanStateRentFeeGrowthFactor());
    }

    /**
     * Test XdrContractCostParams base64 conversion
     */
    public function testXdrContractCostParamsBase64Conversion(): void
    {
        $ext = new XdrExtensionPoint(0);
        $entries = [
            new XdrContractCostParamEntry($ext, 1000, 2000),
            new XdrContractCostParamEntry($ext, 3000, 4000),
        ];

        $original = new XdrContractCostParams($entries);

        // Convert to base64
        $base64 = $original->toBase64Xdr();
        $this->assertNotEmpty($base64);

        // Convert from base64
        $decoded = XdrContractCostParams::fromBase64Xdr($base64);

        // Verify all entries match
        $this->assertCount(count($original->entries), $decoded->entries);
        for ($i = 0; $i < count($entries); $i++) {
            $this->assertEquals($original->entries[$i]->getConstTerm(), $decoded->entries[$i]->getConstTerm());
            $this->assertEquals($original->entries[$i]->getLinearTerm(), $decoded->entries[$i]->getLinearTerm());
        }
    }

    /**
     * Test XdrContractCodeCostInputs getters/setters
     */
    public function testXdrContractCodeCostInputsGettersSetters(): void
    {
        $ext = new XdrExtensionPoint(0);
        $codeCost = new XdrContractCodeCostInputs(
            $ext, 10000, 100, 50, 25, 75, 10, 5, 20, 15, 5000
        );

        $newExt = new XdrExtensionPoint(1);
        $codeCost->setExt($newExt);
        $this->assertEquals(1, $codeCost->getExt()->getDiscriminant());

        $codeCost->setNInstructions(15000);
        $this->assertEquals(15000, $codeCost->getNInstructions());

        $codeCost->setNFunctions(150);
        $this->assertEquals(150, $codeCost->getNFunctions());

        $codeCost->setNGlobals(75);
        $this->assertEquals(75, $codeCost->getNGlobals());

        $codeCost->setNTableEntries(35);
        $this->assertEquals(35, $codeCost->getNTableEntries());

        $codeCost->setNTypes(100);
        $this->assertEquals(100, $codeCost->getNTypes());

        $codeCost->setNDataSegments(15);
        $this->assertEquals(15, $codeCost->getNDataSegments());

        $codeCost->setNElemSegments(8);
        $this->assertEquals(8, $codeCost->getNElemSegments());

        $codeCost->setNImports(30);
        $this->assertEquals(30, $codeCost->getNImports());

        $codeCost->setNExports(25);
        $this->assertEquals(25, $codeCost->getNExports());

        $codeCost->setNDataSegmentBytes(7500);
        $this->assertEquals(7500, $codeCost->getNDataSegmentBytes());
    }

    /**
     * Test XdrEvictionIterator getters/setters
     */
    public function testXdrEvictionIteratorGettersSetters(): void
    {
        $iterator = new XdrEvictionIterator(5, true, 123456789);

        $iterator->setBucketListLevel(8);
        $this->assertEquals(8, $iterator->getBucketListLevel());

        $iterator->setIsCurrBucket(false);
        $this->assertFalse($iterator->isCurrBucket());

        $iterator->setBucketFileOffset(987654321);
        $this->assertEquals(987654321, $iterator->getBucketFileOffset());
    }

    /**
     * Test XdrConfigSettingEntry with CONTRACT_LEDGER_COST_V0
     */
    public function testXdrConfigSettingEntryContractLedgerCostV0(): void
    {
        $configID = new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_LEDGER_COST_V0);
        $original = new XdrConfigSettingEntry($configID);

        $ledgerCost = new XdrConfigSettingContractLedgerCostV0(
            1000, 5000000, 2000, 10000000,
            100, 100000, 200, 200000,
            50, 100, 25, 100000000,
            1000, 5000, 10
        );
        $original->setContractLedgerCost($ledgerCost);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrConfigSettingEntry::decode(new XdrBuffer($encoded));

        // Verify fields match
        $this->assertEquals($original->getConfigSettingID()->getValue(), $decoded->getConfigSettingID()->getValue());
        $this->assertNotNull($decoded->getContractLedgerCost());
        $this->assertEquals($ledgerCost->getLedgerMaxDiskReadLedgerEntries(), $decoded->getContractLedgerCost()->getLedgerMaxDiskReadLedgerEntries());
        $this->assertEquals($ledgerCost->getSorobanStateRentFeeGrowthFactor(), $decoded->getContractLedgerCost()->getSorobanStateRentFeeGrowthFactor());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrConfigSettingEntry with CONTRACT_COST_PARAMS_CPU_INSTRUCTIONS
     */
    public function testXdrConfigSettingEntryContractCostParamsCpuInsns(): void
    {
        $configID = new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COST_PARAMS_CPU_INSTRUCTIONS);
        $original = new XdrConfigSettingEntry($configID);

        $ext = new XdrExtensionPoint(0);
        $entries = [
            new XdrContractCostParamEntry($ext, 100, 200),
            new XdrContractCostParamEntry($ext, 150, 250),
        ];
        $costParams = new XdrContractCostParams($entries);
        $original->setContractCostParamsCpuInsns($costParams);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrConfigSettingEntry::decode(new XdrBuffer($encoded));

        // Verify fields match
        $this->assertEquals($original->getConfigSettingID()->getValue(), $decoded->getConfigSettingID()->getValue());
        $this->assertNotNull($decoded->getContractCostParamsCpuInsns());
        $this->assertCount(2, $decoded->getContractCostParamsCpuInsns()->entries);

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrConfigSettingEntry with CONTRACT_COST_PARAMS_MEMORY_BYTES
     */
    public function testXdrConfigSettingEntryContractCostParamsMemBytes(): void
    {
        $configID = new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COST_PARAMS_MEMORY_BYTES);
        $original = new XdrConfigSettingEntry($configID);

        $ext = new XdrExtensionPoint(0);
        $entries = [
            new XdrContractCostParamEntry($ext, 200, 300),
            new XdrContractCostParamEntry($ext, 250, 350),
            new XdrContractCostParamEntry($ext, 300, 400),
        ];
        $costParams = new XdrContractCostParams($entries);
        $original->setContractCostParamsMemBytes($costParams);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrConfigSettingEntry::decode(new XdrBuffer($encoded));

        // Verify fields match
        $this->assertEquals($original->getConfigSettingID()->getValue(), $decoded->getConfigSettingID()->getValue());
        $this->assertNotNull($decoded->getContractCostParamsMemBytes());
        $this->assertCount(3, $decoded->getContractCostParamsMemBytes()->entries);

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrConfigSettingEntry with STATE_ARCHIVAL
     */
    public function testXdrConfigSettingEntryStateArchival(): void
    {
        $configID = new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_STATE_ARCHIVAL);
        $original = new XdrConfigSettingEntry($configID);

        $archivalSettings = new XdrStateArchivalSettings(
            100000, 1000, 5000, 100, 50, 500, 100, 10, 1000, 5
        );
        $original->setStateArchivalSettings($archivalSettings);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrConfigSettingEntry::decode(new XdrBuffer($encoded));

        // Verify fields match
        $this->assertEquals($original->getConfigSettingID()->getValue(), $decoded->getConfigSettingID()->getValue());
        $this->assertNotNull($decoded->getStateArchivalSettings());
        $this->assertEquals(100000, $decoded->getStateArchivalSettings()->maxEntryTTL);

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrConfigSettingEntry with LIVE_SOROBAN_STATE_SIZE_WINDOW
     */
    public function testXdrConfigSettingEntryLiveSorobanStateSizeWindow(): void
    {
        $configID = new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_LIVE_SOROBAN_STATE_SIZE_WINDOW);
        $original = new XdrConfigSettingEntry($configID);

        $windowData = [1000, 2000, 3000, 4000, 5000];
        $original->setLiveSorobanStateSizeWindow($windowData);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrConfigSettingEntry::decode(new XdrBuffer($encoded));

        // Verify fields match
        $this->assertEquals($original->getConfigSettingID()->getValue(), $decoded->getConfigSettingID()->getValue());
        $this->assertNotNull($decoded->getLiveSorobanStateSizeWindow());
        $this->assertCount(5, $decoded->getLiveSorobanStateSizeWindow());
        $this->assertEquals([1000, 2000, 3000, 4000, 5000], $decoded->getLiveSorobanStateSizeWindow());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrConfigSettingEntry with EVICTION_ITERATOR
     */
    public function testXdrConfigSettingEntryEvictionIterator(): void
    {
        $configID = new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_EVICTION_ITERATOR);
        $original = new XdrConfigSettingEntry($configID);

        $evictionIterator = new XdrEvictionIterator(5, true, 123456789);
        $original->setEvictionIterator($evictionIterator);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrConfigSettingEntry::decode(new XdrBuffer($encoded));

        // Verify fields match
        $this->assertEquals($original->getConfigSettingID()->getValue(), $decoded->getConfigSettingID()->getValue());
        $this->assertNotNull($decoded->getEvictionIterator());
        $this->assertEquals(5, $decoded->getEvictionIterator()->getBucketListLevel());
        $this->assertTrue($decoded->getEvictionIterator()->isCurrBucket());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrConfigSettingEntry getters/setters
     */
    public function testXdrConfigSettingEntryGettersSetters(): void
    {
        $configID = new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_MAX_SIZE_BYTES);
        $entry = new XdrConfigSettingEntry($configID);

        $newConfigID = new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_STATE_ARCHIVAL);
        $entry->setConfigSettingID($newConfigID);
        $this->assertEquals(XdrConfigSettingID::CONFIG_SETTING_STATE_ARCHIVAL, $entry->getConfigSettingID()->getValue());

        $archivalSettings = new XdrStateArchivalSettings(
            100000, 1000, 5000, 100, 50, 500, 100, 10, 1000, 5
        );
        $entry->setStateArchivalSettings($archivalSettings);
        $this->assertNotNull($entry->getStateArchivalSettings());
        $this->assertEquals(100000, $entry->getStateArchivalSettings()->maxEntryTTL);

        $evictionIterator = new XdrEvictionIterator(10, false, 987654321);
        $entry->setEvictionIterator($evictionIterator);
        $this->assertNotNull($entry->getEvictionIterator());
        $this->assertEquals(10, $entry->getEvictionIterator()->getBucketListLevel());
    }

    /**
     * Test XdrContractCostParamEntry getters/setters
     */
    public function testXdrContractCostParamEntryGettersSetters(): void
    {
        $ext = new XdrExtensionPoint(0);
        $entry = new XdrContractCostParamEntry($ext, 1000, 2000);

        $entry->setConstTerm(1500);
        $this->assertEquals(1500, $entry->getConstTerm());

        $entry->setLinearTerm(2500);
        $this->assertEquals(2500, $entry->getLinearTerm());

        $newExt = new XdrExtensionPoint(1);
        $entry->setExt($newExt);
        $this->assertEquals(1, $entry->getExt()->getDiscriminant());
    }
}
