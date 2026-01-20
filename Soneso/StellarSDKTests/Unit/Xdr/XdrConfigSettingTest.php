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
use Soneso\StellarSDK\Xdr\XdrContractCostType;
use Soneso\StellarSDK\Xdr\XdrContractCostParams;
use Soneso\StellarSDK\Xdr\XdrContractCostParamEntry;
use Soneso\StellarSDK\Xdr\XdrContractCodeCostInputs;
use Soneso\StellarSDK\Xdr\XdrStateArchivalSettings;
use Soneso\StellarSDK\Xdr\XdrEvictionIterator;
use Soneso\StellarSDK\Xdr\XdrExtensionPoint;

class XdrConfigSettingTest extends TestCase
{
    /**
     * Test XdrConfigSettingID encode/decode round-trip
     */
    public function testXdrConfigSettingIDRoundTrip(): void
    {
        $testValues = [
            XdrConfigSettingID::CONFIG_SETTING_CONTRACT_MAX_SIZE_BYTES,
            XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COMPUTE_V0,
            XdrConfigSettingID::CONFIG_SETTING_CONTRACT_LEDGER_COST_V0,
            XdrConfigSettingID::CONFIG_SETTING_CONTRACT_HISTORICAL_DATA_V0,
            XdrConfigSettingID::CONFIG_SETTING_CONTRACT_EVENTS_V0,
            XdrConfigSettingID::CONFIG_SETTING_CONTRACT_BANDWIDTH_V0,
            XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COST_PARAMS_CPU_INSTRUCTIONS,
            XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COST_PARAMS_MEMORY_BYTES,
            XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_KEY_SIZE_BYTES,
            XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_ENTRY_SIZE_BYTES,
            XdrConfigSettingID::CONFIG_SETTING_STATE_ARCHIVAL,
            XdrConfigSettingID::CONFIG_SETTING_CONTRACT_EXECUTION_LANES,
            XdrConfigSettingID::CONFIG_SETTING_LIVE_SOROBAN_STATE_SIZE_WINDOW,
            XdrConfigSettingID::CONFIG_SETTING_EVICTION_ITERATOR,
            XdrConfigSettingID::CONFIG_SETTING_CONTRACT_PARALLEL_COMPUTE_V0,
            XdrConfigSettingID::CONFIG_SETTING_CONTRACT_LEDGER_COST_EXT_V0,
            XdrConfigSettingID::CONFIG_SETTING_SCP_TIMING,
        ];

        foreach ($testValues as $value) {
            $original = new XdrConfigSettingID($value);

            // Encode to bytes
            $encoded = $original->encode();
            $this->assertNotEmpty($encoded);

            // Decode from bytes
            $decoded = XdrConfigSettingID::decode(new XdrBuffer($encoded));

            // Verify value matches
            $this->assertEquals($original->getValue(), $decoded->getValue());
            $this->assertEquals($value, $decoded->getValue());

            // Re-encode and compare bytes
            $reEncoded = $decoded->encode();
            $this->assertEquals($encoded, $reEncoded);
        }
    }

    /**
     * Test XdrConfigSettingID static factory methods
     */
    public function testXdrConfigSettingIDStaticFactories(): void
    {
        $maxSizeBytes = XdrConfigSettingID::CONFIG_SETTING_CONTRACT_MAX_SIZE_BYTES();
        $this->assertEquals(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_MAX_SIZE_BYTES, $maxSizeBytes->getValue());

        $computeV0 = XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COMPUTE_V0();
        $this->assertEquals(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COMPUTE_V0, $computeV0->getValue());

        $ledgerCostV0 = XdrConfigSettingID::CONFIG_SETTING_CONTRACT_LEDGER_COST_V0();
        $this->assertEquals(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_LEDGER_COST_V0, $ledgerCostV0->getValue());

        $historicalDataV0 = XdrConfigSettingID::CONFIG_SETTING_CONTRACT_HISTORICAL_DATA_V0();
        $this->assertEquals(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_HISTORICAL_DATA_V0, $historicalDataV0->getValue());

        $eventsV0 = XdrConfigSettingID::CONFIG_SETTING_CONTRACT_EVENTS_V0();
        $this->assertEquals(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_EVENTS_V0, $eventsV0->getValue());

        $bandwidthV0 = XdrConfigSettingID::CONFIG_SETTING_CONTRACT_BANDWIDTH_V0();
        $this->assertEquals(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_BANDWIDTH_V0, $bandwidthV0->getValue());

        $cpuInsns = XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COST_PARAMS_CPU_INSTRUCTIONS();
        $this->assertEquals(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COST_PARAMS_CPU_INSTRUCTIONS, $cpuInsns->getValue());

        $memBytes = XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COST_PARAMS_MEMORY_BYTES();
        $this->assertEquals(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COST_PARAMS_MEMORY_BYTES, $memBytes->getValue());

        $dataKeySize = XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_KEY_SIZE_BYTES();
        $this->assertEquals(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_KEY_SIZE_BYTES, $dataKeySize->getValue());

        $dataEntrySize = XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_ENTRY_SIZE_BYTES();
        $this->assertEquals(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_ENTRY_SIZE_BYTES, $dataEntrySize->getValue());

        $stateArchival = XdrConfigSettingID::CONFIG_SETTING_STATE_ARCHIVAL();
        $this->assertEquals(XdrConfigSettingID::CONFIG_SETTING_STATE_ARCHIVAL, $stateArchival->getValue());

        $executionLanes = XdrConfigSettingID::CONFIG_SETTING_CONTRACT_EXECUTION_LANES();
        $this->assertEquals(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_EXECUTION_LANES, $executionLanes->getValue());

        $sorobanWindow = XdrConfigSettingID::CONFIG_SETTING_LIVE_SOROBAN_STATE_SIZE_WINDOW();
        $this->assertEquals(XdrConfigSettingID::CONFIG_SETTING_LIVE_SOROBAN_STATE_SIZE_WINDOW, $sorobanWindow->getValue());

        $evictionIterator = XdrConfigSettingID::CONFIG_SETTING_EVICTION_ITERATOR();
        $this->assertEquals(XdrConfigSettingID::CONFIG_SETTING_EVICTION_ITERATOR, $evictionIterator->getValue());

        $parallelCompute = XdrConfigSettingID::CONFIG_SETTING_CONTRACT_PARALLEL_COMPUTE_V0();
        $this->assertEquals(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_PARALLEL_COMPUTE_V0, $parallelCompute->getValue());

        $ledgerCostExt = XdrConfigSettingID::CONFIG_SETTING_CONTRACT_LEDGER_COST_EXT_V0();
        $this->assertEquals(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_LEDGER_COST_EXT_V0, $ledgerCostExt->getValue());

        $scpTiming = XdrConfigSettingID::CONFIG_SETTING_SCP_TIMING();
        $this->assertEquals(XdrConfigSettingID::CONFIG_SETTING_SCP_TIMING, $scpTiming->getValue());
    }

    /**
     * Test XdrConfigSettingContractLedgerCostV0 encode/decode round-trip
     */
    public function testXdrConfigSettingContractLedgerCostV0RoundTrip(): void
    {
        $original = new XdrConfigSettingContractLedgerCostV0(
            1000,       // ledgerMaxDiskReadLedgerEntries
            5000000,    // ledgerMaxDiskReadBytes
            2000,       // ledgerMaxWriteLedgerEntries
            10000000,   // ledgerMaxWriteBytes
            100,        // txMaxDiskReadEntries
            100000,     // txMaxDiskReadBytes
            200,        // txMaxWriteLedgerEntries
            200000,     // txMaxWriteBytes
            50,         // feeDiskReadLedgerEntry
            100,        // feeWriteLedgerEntry
            25,         // feeDiskRead1KB
            100000000,  // sorobanStateTargetSizeBytes
            1000,       // rentFee1KBSorobanStateSizeLow
            5000,       // rentFee1KBSorobanStateSizeHigh
            10          // sorobanStateRentFeeGrowthFactor
        );

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrConfigSettingContractLedgerCostV0::decode(new XdrBuffer($encoded));

        // Verify all fields match
        $this->assertEquals($original->getLedgerMaxDiskReadLedgerEntries(), $decoded->getLedgerMaxDiskReadLedgerEntries());
        $this->assertEquals($original->getLedgerMaxDiskReadBytes(), $decoded->getLedgerMaxDiskReadBytes());
        $this->assertEquals($original->getLedgerMaxWriteLedgerEntries(), $decoded->getLedgerMaxWriteLedgerEntries());
        $this->assertEquals($original->getLedgerMaxWriteBytes(), $decoded->getLedgerMaxWriteBytes());
        $this->assertEquals($original->getTxMaxDiskReadEntries(), $decoded->getTxMaxDiskReadEntries());
        $this->assertEquals($original->getTxMaxDiskReadBytes(), $decoded->getTxMaxDiskReadBytes());
        $this->assertEquals($original->getTxMaxWriteLedgerEntries(), $decoded->getTxMaxWriteLedgerEntries());
        $this->assertEquals($original->getTxMaxWriteBytes(), $decoded->getTxMaxWriteBytes());
        $this->assertEquals($original->getFeeDiskReadLedgerEntry(), $decoded->getFeeDiskReadLedgerEntry());
        $this->assertEquals($original->getFeeWriteLedgerEntry(), $decoded->getFeeWriteLedgerEntry());
        $this->assertEquals($original->getFeeDiskRead1KB(), $decoded->getFeeDiskRead1KB());
        $this->assertEquals($original->getSorobanStateTargetSizeBytes(), $decoded->getSorobanStateTargetSizeBytes());
        $this->assertEquals($original->getRentFee1KBSorobanStateSizeLow(), $decoded->getRentFee1KBSorobanStateSizeLow());
        $this->assertEquals($original->getRentFee1KBSorobanStateSizeHigh(), $decoded->getRentFee1KBSorobanStateSizeHigh());
        $this->assertEquals($original->getSorobanStateRentFeeGrowthFactor(), $decoded->getSorobanStateRentFeeGrowthFactor());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

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
     * Test XdrContractCostType encode/decode round-trip
     */
    public function testXdrContractCostTypeRoundTrip(): void
    {
        $testValues = [
            XdrContractCostType::WasmInsnExec,
            XdrContractCostType::MemAlloc,
            XdrContractCostType::MemCpy,
            XdrContractCostType::MemCmp,
            XdrContractCostType::DispatchHostFunction,
            XdrContractCostType::VisitObject,
            XdrContractCostType::ValSer,
            XdrContractCostType::ValDeser,
            XdrContractCostType::ComputeSha256Hash,
            XdrContractCostType::ComputeEd25519PubKey,
            XdrContractCostType::VerifyEd25519Sig,
            XdrContractCostType::VmInstantiation,
            XdrContractCostType::VmCachedInstantiation,
            XdrContractCostType::InvokeVMFunction,
            XdrContractCostType::ComputeKeccak256Hash,
            XdrContractCostType::DecodeEcdsaCurve256Sig,
            XdrContractCostType::RecoverEcdsaSecp256k1Key,
            XdrContractCostType::Int256AddSub,
            XdrContractCostType::Int256Mul,
            XdrContractCostType::Int256Div,
            XdrContractCostType::Int256Pow,
            XdrContractCostType::Int256Shift,
            XdrContractCostType::ChaCha20DrawBytes,
            XdrContractCostType::ParseWasmInstructions,
            XdrContractCostType::ParseWasmFunctions,
            XdrContractCostType::ParseWasmGlobals,
            XdrContractCostType::ParseWasmTableEntries,
            XdrContractCostType::ParseWasmTypes,
            XdrContractCostType::ParseWasmDataSegments,
            XdrContractCostType::ParseWasmElemSegments,
            XdrContractCostType::ParseWasmImports,
            XdrContractCostType::ParseWasmExports,
            XdrContractCostType::ParseWasmDataSegmentBytes,
            XdrContractCostType::InstantiateWasmInstructions,
            XdrContractCostType::InstantiateWasmFunctions,
            XdrContractCostType::InstantiateWasmGlobals,
            XdrContractCostType::InstantiateWasmTableEntries,
            XdrContractCostType::InstantiateWasmTypes,
            XdrContractCostType::InstantiateWasmDataSegments,
            XdrContractCostType::InstantiateWasmElemSegments,
            XdrContractCostType::InstantiateWasmImports,
            XdrContractCostType::InstantiateWasmExports,
            XdrContractCostType::InstantiateWasmDataSegmentBytes,
            XdrContractCostType::Sec1DecodePointUncompressed,
            XdrContractCostType::VerifyEcdsaSecp256r1Sig,
        ];

        foreach ($testValues as $value) {
            $original = new XdrContractCostType($value);

            // Encode to bytes
            $encoded = $original->encode();
            $this->assertNotEmpty($encoded);

            // Decode from bytes
            $decoded = $original->decode(new XdrBuffer($encoded));

            // Verify value matches
            $this->assertEquals($original->getValue(), $decoded->getValue());
            $this->assertEquals($value, $decoded->getValue());

            // Re-encode and compare bytes
            $reEncoded = $decoded->encode();
            $this->assertEquals($encoded, $reEncoded);
        }
    }

    /**
     * Test XdrContractCostParams encode/decode round-trip
     */
    public function testXdrContractCostParamsRoundTrip(): void
    {
        $ext = new XdrExtensionPoint(0);

        $entries = [
            new XdrContractCostParamEntry($ext, 100, 200),
            new XdrContractCostParamEntry($ext, 150, 250),
            new XdrContractCostParamEntry($ext, 200, 300),
            new XdrContractCostParamEntry($ext, 300, 400),
            new XdrContractCostParamEntry($ext, 500, 600),
        ];

        $original = new XdrContractCostParams($entries);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrContractCostParams::decode(new XdrBuffer($encoded));

        // Verify entries count matches
        $this->assertCount(count($original->entries), $decoded->entries);

        // Verify all entries match
        for ($i = 0; $i < count($entries); $i++) {
            $this->assertEquals($original->entries[$i]->getConstTerm(), $decoded->entries[$i]->getConstTerm());
            $this->assertEquals($original->entries[$i]->getLinearTerm(), $decoded->entries[$i]->getLinearTerm());
            $this->assertEquals($original->entries[$i]->getExt()->getDiscriminant(), $decoded->entries[$i]->getExt()->getDiscriminant());
        }

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
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
     * Test XdrContractCostParams with empty entries
     */
    public function testXdrContractCostParamsEmptyEntries(): void
    {
        $original = new XdrContractCostParams([]);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrContractCostParams::decode(new XdrBuffer($encoded));

        // Verify entries are empty
        $this->assertCount(0, $decoded->entries);

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrContractCodeCostInputs encode/decode round-trip
     */
    public function testXdrContractCodeCostInputsRoundTrip(): void
    {
        $ext = new XdrExtensionPoint(0);

        $original = new XdrContractCodeCostInputs(
            $ext,
            10000,      // nInstructions
            100,        // nFunctions
            50,         // nGlobals
            25,         // nTableEntries
            75,         // nTypes
            10,         // nDataSegments
            5,          // nElemSegments
            20,         // nImports
            15,         // nExports
            5000        // nDataSegmentBytes
        );

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrContractCodeCostInputs::decode(new XdrBuffer($encoded));

        // Verify all fields match
        $this->assertEquals($original->getExt()->getDiscriminant(), $decoded->getExt()->getDiscriminant());
        $this->assertEquals($original->getNInstructions(), $decoded->getNInstructions());
        $this->assertEquals($original->getNFunctions(), $decoded->getNFunctions());
        $this->assertEquals($original->getNGlobals(), $decoded->getNGlobals());
        $this->assertEquals($original->getNTableEntries(), $decoded->getNTableEntries());
        $this->assertEquals($original->getNTypes(), $decoded->getNTypes());
        $this->assertEquals($original->getNDataSegments(), $decoded->getNDataSegments());
        $this->assertEquals($original->getNElemSegments(), $decoded->getNElemSegments());
        $this->assertEquals($original->getNImports(), $decoded->getNImports());
        $this->assertEquals($original->getNExports(), $decoded->getNExports());
        $this->assertEquals($original->getNDataSegmentBytes(), $decoded->getNDataSegmentBytes());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
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
     * Test XdrStateArchivalSettings encode/decode round-trip
     */
    public function testXdrStateArchivalSettingsRoundTrip(): void
    {
        $original = new XdrStateArchivalSettings(
            100000,     // maxEntryTTL
            1000,       // minTemporaryTTL
            5000,       // minPersistentTTL
            100,        // persistentRentRateDenominator
            50,         // tempRentRateDenominator
            500,        // maxEntriesToArchive
            100,        // liveSorobanStateSizeWindowSampleSize
            10,         // liveSorobanStateSizeWindowSamplePeriod
            1000,       // evictionScanSize
            5           // startingEvictionScanLevel
        );

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrStateArchivalSettings::decode(new XdrBuffer($encoded));

        // Verify all fields match
        $this->assertEquals($original->maxEntryTTL, $decoded->maxEntryTTL);
        $this->assertEquals($original->minTemporaryTTL, $decoded->minTemporaryTTL);
        $this->assertEquals($original->minPersistentTTL, $decoded->minPersistentTTL);
        $this->assertEquals($original->persistentRentRateDenominator, $decoded->persistentRentRateDenominator);
        $this->assertEquals($original->tempRentRateDenominator, $decoded->tempRentRateDenominator);
        $this->assertEquals($original->maxEntriesToArchive, $decoded->maxEntriesToArchive);
        $this->assertEquals($original->liveSorobanStateSizeWindowSampleSize, $decoded->liveSorobanStateSizeWindowSampleSize);
        $this->assertEquals($original->liveSorobanStateSizeWindowSamplePeriod, $decoded->liveSorobanStateSizeWindowSamplePeriod);
        $this->assertEquals($original->evictionScanSize, $decoded->evictionScanSize);
        $this->assertEquals($original->startingEvictionScanLevel, $decoded->startingEvictionScanLevel);

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrEvictionIterator encode/decode round-trip
     */
    public function testXdrEvictionIteratorRoundTrip(): void
    {
        $original = new XdrEvictionIterator(
            5,          // bucketListLevel
            true,       // isCurrBucket
            123456789   // bucketFileOffset
        );

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrEvictionIterator::decode(new XdrBuffer($encoded));

        // Verify all fields match
        $this->assertEquals($original->getBucketListLevel(), $decoded->getBucketListLevel());
        $this->assertEquals($original->isCurrBucket(), $decoded->isCurrBucket());
        $this->assertEquals($original->getBucketFileOffset(), $decoded->getBucketFileOffset());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrEvictionIterator with false isCurrBucket
     */
    public function testXdrEvictionIteratorWithFalseFlag(): void
    {
        $original = new XdrEvictionIterator(
            10,         // bucketListLevel
            false,      // isCurrBucket
            987654321   // bucketFileOffset
        );

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrEvictionIterator::decode(new XdrBuffer($encoded));

        // Verify all fields match
        $this->assertEquals($original->getBucketListLevel(), $decoded->getBucketListLevel());
        $this->assertEquals($original->isCurrBucket(), $decoded->isCurrBucket());
        $this->assertFalse($decoded->isCurrBucket());
        $this->assertEquals($original->getBucketFileOffset(), $decoded->getBucketFileOffset());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
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
     * Test XdrConfigSettingEntry with CONTRACT_MAX_SIZE_BYTES
     */
    public function testXdrConfigSettingEntryContractMaxSizeBytes(): void
    {
        $configID = new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_MAX_SIZE_BYTES);
        $original = new XdrConfigSettingEntry($configID);
        $original->setContractMaxSizeBytes(65536);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrConfigSettingEntry::decode(new XdrBuffer($encoded));

        // Verify fields match
        $this->assertEquals($original->getConfigSettingID()->getValue(), $decoded->getConfigSettingID()->getValue());
        $this->assertEquals($original->getContractMaxSizeBytes(), $decoded->getContractMaxSizeBytes());
        $this->assertEquals(65536, $decoded->getContractMaxSizeBytes());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
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
     * Test XdrConfigSettingEntry with CONTRACT_DATA_KEY_SIZE_BYTES
     */
    public function testXdrConfigSettingEntryContractDataKeySizeBytes(): void
    {
        $configID = new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_KEY_SIZE_BYTES);
        $original = new XdrConfigSettingEntry($configID);
        $original->setContractDataKeySizeBytes(256);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrConfigSettingEntry::decode(new XdrBuffer($encoded));

        // Verify fields match
        $this->assertEquals($original->getConfigSettingID()->getValue(), $decoded->getConfigSettingID()->getValue());
        $this->assertEquals(256, $decoded->getContractDataKeySizeBytes());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrConfigSettingEntry with CONTRACT_DATA_ENTRY_SIZE_BYTES
     */
    public function testXdrConfigSettingEntryContractDataEntrySizeBytes(): void
    {
        $configID = new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_ENTRY_SIZE_BYTES);
        $original = new XdrConfigSettingEntry($configID);
        $original->setContractDataEntrySizeBytes(65536);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrConfigSettingEntry::decode(new XdrBuffer($encoded));

        // Verify fields match
        $this->assertEquals($original->getConfigSettingID()->getValue(), $decoded->getConfigSettingID()->getValue());
        $this->assertEquals(65536, $decoded->getContractDataEntrySizeBytes());

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
     * Test XdrConfigSettingEntry with empty LIVE_SOROBAN_STATE_SIZE_WINDOW
     */
    public function testXdrConfigSettingEntryLiveSorobanStateSizeWindowEmpty(): void
    {
        $configID = new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_LIVE_SOROBAN_STATE_SIZE_WINDOW);
        $original = new XdrConfigSettingEntry($configID);
        $original->setLiveSorobanStateSizeWindow([]);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrConfigSettingEntry::decode(new XdrBuffer($encoded));

        // Verify fields match
        $this->assertEquals($original->getConfigSettingID()->getValue(), $decoded->getConfigSettingID()->getValue());
        $this->assertNotNull($decoded->getLiveSorobanStateSizeWindow());
        $this->assertCount(0, $decoded->getLiveSorobanStateSizeWindow());

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
     * Test XdrConfigSettingEntry with large values for boundary testing
     */
    public function testXdrConfigSettingEntryLargeValues(): void
    {
        $configID = new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_MAX_SIZE_BYTES);
        $original = new XdrConfigSettingEntry($configID);
        $original->setContractMaxSizeBytes(2147483647); // Max int32

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrConfigSettingEntry::decode(new XdrBuffer($encoded));

        // Verify fields match
        $this->assertEquals(2147483647, $decoded->getContractMaxSizeBytes());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrContractCostParamEntry encode/decode round-trip
     */
    public function testXdrContractCostParamEntryRoundTrip(): void
    {
        $ext = new XdrExtensionPoint(0);
        $original = new XdrContractCostParamEntry($ext, 1000, 2000);

        // Encode to bytes
        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        // Decode from bytes
        $decoded = XdrContractCostParamEntry::decode(new XdrBuffer($encoded));

        // Verify all fields match
        $this->assertEquals($original->getExt()->getDiscriminant(), $decoded->getExt()->getDiscriminant());
        $this->assertEquals($original->getConstTerm(), $decoded->getConstTerm());
        $this->assertEquals($original->getLinearTerm(), $decoded->getLinearTerm());

        // Re-encode and compare bytes
        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
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

    /**
     * Test XdrExtensionPoint encode/decode round-trip
     */
    public function testXdrExtensionPointRoundTrip(): void
    {
        $testValues = [0, 1, 5, 100];

        foreach ($testValues as $value) {
            $original = new XdrExtensionPoint($value);

            // Encode to bytes
            $encoded = $original->encode();
            $this->assertNotEmpty($encoded);

            // Decode from bytes
            $decoded = XdrExtensionPoint::decode(new XdrBuffer($encoded));

            // Verify discriminant matches
            $this->assertEquals($original->getDiscriminant(), $decoded->getDiscriminant());
            $this->assertEquals($value, $decoded->getDiscriminant());

            // Re-encode and compare bytes
            $reEncoded = $decoded->encode();
            $this->assertEquals($encoded, $reEncoded);
        }
    }
}
