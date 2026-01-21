<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use PHPUnit\Framework\TestCase;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Xdr\XdrAccountEntry;
use Soneso\StellarSDK\Xdr\XdrAccountEntryExt;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceEntry;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceEntryExt;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceID;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceIDType;
use Soneso\StellarSDK\Xdr\XdrClaimant;
use Soneso\StellarSDK\Xdr\XdrClaimantType;
use Soneso\StellarSDK\Xdr\XdrClaimantV0;
use Soneso\StellarSDK\Xdr\XdrClaimPredicate;
use Soneso\StellarSDK\Xdr\XdrClaimPredicateType;
use Soneso\StellarSDK\Xdr\XdrConfigSettingContractComputeV0;
use Soneso\StellarSDK\Xdr\XdrConfigSettingEntry;
use Soneso\StellarSDK\Xdr\XdrConfigSettingID;
use Soneso\StellarSDK\Xdr\XdrConstantProduct;
use Soneso\StellarSDK\Xdr\XdrContractCostParamEntry;
use Soneso\StellarSDK\Xdr\XdrContractCostParams;
use Soneso\StellarSDK\Xdr\XdrDataEntry;
use Soneso\StellarSDK\Xdr\XdrDataEntryExt;
use Soneso\StellarSDK\Xdr\XdrDataValueMandatory;
use Soneso\StellarSDK\Xdr\XdrExtensionPoint;
use Soneso\StellarSDK\Xdr\XdrLedgerEntry;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryChange;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryChangeType;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryData;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryExt;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyAccount;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyData;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolBody;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolConstantProductParameters;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolEntry;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolType;
use Soneso\StellarSDK\Xdr\XdrOfferEntry;
use Soneso\StellarSDK\Xdr\XdrOfferEntryExt;
use Soneso\StellarSDK\Xdr\XdrPrice;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;
use Soneso\StellarSDK\Xdr\XdrTrustLineAsset;
use Soneso\StellarSDK\Xdr\XdrTrustLineEntry;
use Soneso\StellarSDK\Xdr\XdrTrustLineEntryExt;

class XdrEntryClassesTest extends TestCase
{
    private const TEST_ACCOUNT_ID = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';
    private const TEST_ACCOUNT_ID_2 = 'GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ';

    // XdrLedgerEntryData Tests

    public function testXdrLedgerEntryDataAccountRoundTrip(): void
    {
        $accountId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $balance = new BigInteger(10000000000);
        $seqNum = new XdrSequenceNumber(new BigInteger(12345));
        $ext = new XdrAccountEntryExt(0, null);

        $accountEntry = new XdrAccountEntry(
            $accountId,
            $balance,
            $seqNum,
            0,
            null,
            0,
            "",
            chr(1) . chr(0) . chr(0) . chr(0),
            [],
            $ext
        );

        $ledgerEntryData = new XdrLedgerEntryData(XdrLedgerEntryType::ACCOUNT());
        $ledgerEntryData->setAccount($accountEntry);

        $encoded = $ledgerEntryData->encode();
        $decoded = XdrLedgerEntryData::decode(new XdrBuffer($encoded));

        $this->assertEquals($ledgerEntryData->type->value, $decoded->type->value);
        $this->assertNotNull($decoded->getAccount());
        $this->assertEquals(
            $accountEntry->getAccountId()->getAccountId(),
            $decoded->getAccount()->getAccountId()->getAccountId()
        );
    }

    public function testXdrLedgerEntryDataTrustlineRoundTrip(): void
    {
        $accountId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $nativeAsset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $asset = XdrTrustLineAsset::fromXdrAsset($nativeAsset);
        $ext = new XdrTrustLineEntryExt(0, null);

        $trustLineEntry = new XdrTrustLineEntry(
            $accountId,
            $asset,
            50000000,
            100000000,
            1,
            $ext
        );

        $ledgerEntryData = new XdrLedgerEntryData(XdrLedgerEntryType::TRUSTLINE());
        $ledgerEntryData->setTrustline($trustLineEntry);

        $encoded = $ledgerEntryData->encode();
        $decoded = XdrLedgerEntryData::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryType::TRUSTLINE, $decoded->type->value);
        $this->assertNotNull($decoded->getTrustline());
        $this->assertEquals(50000000, $decoded->getTrustline()->getBalance());
        $this->assertEquals(100000000, $decoded->getTrustline()->getLimit());
    }

    public function testXdrLedgerEntryDataOfferRoundTrip(): void
    {
        $sellerId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $selling = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $buying = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $price = new XdrPrice(1, 2);
        $ext = new XdrOfferEntryExt(0);

        $offerEntry = new XdrOfferEntry();
        $offerEntry->setSellerID($sellerId);
        $offerEntry->setOfferId(12345);
        $offerEntry->setSelling($selling);
        $offerEntry->setBuying($buying);
        $offerEntry->setAmount(new \phpseclib3\Math\BigInteger(1000000));
        $offerEntry->setPrice($price);
        $offerEntry->setFlags(0);
        $offerEntry->setExt($ext);

        $ledgerEntryData = new XdrLedgerEntryData(XdrLedgerEntryType::OFFER());
        $ledgerEntryData->setOffer($offerEntry);

        $encoded = $ledgerEntryData->encode();
        $decoded = XdrLedgerEntryData::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryType::OFFER, $decoded->type->value);
        $this->assertNotNull($decoded->getOffer());
        $this->assertEquals(12345, $decoded->getOffer()->getOfferId());
        $this->assertEquals("1000000", $decoded->getOffer()->getAmount()->toString());
    }

    public function testXdrLedgerEntryDataDataEntryRoundTrip(): void
    {
        $accountId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $dataName = "test_data";
        $dataValue = new XdrDataValueMandatory("test_value");
        $ext = new XdrDataEntryExt(0);

        $dataEntry = new XdrDataEntry($accountId, $dataName, $dataValue, $ext);

        $ledgerEntryData = new XdrLedgerEntryData(XdrLedgerEntryType::DATA());
        $ledgerEntryData->setData($dataEntry);

        $encoded = $ledgerEntryData->encode();
        $decoded = XdrLedgerEntryData::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryType::DATA, $decoded->type->value);
        $this->assertNotNull($decoded->getData());
        $this->assertEquals($dataName, $decoded->getData()->getDataName());
    }

    public function testXdrLedgerEntryDataClaimableBalanceRoundTrip(): void
    {
        $v0Hash = hex2bin("da0d57da7d4850e7fc10d2a9d0ebc731f7afb40574c03395b17d49149b91f5be");
        $balanceID = new XdrClaimableBalanceID(
            new XdrClaimableBalanceIDType(XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0),
            bin2hex($v0Hash)
        );

        $claimant = new XdrClaimant(new XdrClaimantType(XdrClaimantType::V0));
        $claimant->setV0(
            new XdrClaimantV0(
                XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID),
                new XdrClaimPredicate(new XdrClaimPredicateType(XdrClaimPredicateType::UNCONDITIONAL))
            )
        );
        $claimants = [$claimant];

        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $amount = new BigInteger(1000000);
        $ext = new XdrClaimableBalanceEntryExt(0, null);

        $claimableBalanceEntry = new XdrClaimableBalanceEntry($balanceID, $claimants, $asset, $amount, $ext);

        $ledgerEntryData = new XdrLedgerEntryData(XdrLedgerEntryType::CLAIMABLE_BALANCE());
        $ledgerEntryData->setClaimableBalance($claimableBalanceEntry);

        $encoded = $ledgerEntryData->encode();
        $decoded = XdrLedgerEntryData::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryType::CLAIMABLE_BALANCE, $decoded->type->value);
        $this->assertNotNull($decoded->getClaimableBalance());
        $this->assertNotNull($decoded->getClaimableBalance()->asset);
        $this->assertCount(1, $decoded->getClaimableBalance()->claimants);
    }

    public function testXdrLedgerEntryDataLiquidityPoolRoundTrip(): void
    {
        $poolId = hex2bin("dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7");

        $assetA = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $assetB = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $params = new XdrLiquidityPoolConstantProductParameters($assetA, $assetB, 30);

        $constantProduct = new XdrConstantProduct(
            $params,
            new BigInteger(1000000),
            new BigInteger(2000000),
            new BigInteger(1500000),
            10
        );

        $body = new XdrLiquidityPoolBody(
            new XdrLiquidityPoolType(XdrLiquidityPoolType::LIQUIDITY_POOL_CONSTANT_PRODUCT)
        );
        $body->setConstantProduct($constantProduct);

        $liquidityPoolEntry = new XdrLiquidityPoolEntry($poolId, $body);

        $ledgerEntryData = new XdrLedgerEntryData(XdrLedgerEntryType::LIQUIDITY_POOL());
        $ledgerEntryData->setLiquidityPool($liquidityPoolEntry);

        $encoded = $ledgerEntryData->encode();
        $decoded = XdrLedgerEntryData::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryType::LIQUIDITY_POOL, $decoded->type->value);
        $this->assertNotNull($decoded->getLiquidityPool());
        $this->assertEquals(bin2hex($poolId), bin2hex($decoded->getLiquidityPool()->getLiquidityPoolID()));
    }

    // XdrLedgerEntryChange Tests

    public function testXdrLedgerEntryChangeCreatedRoundTrip(): void
    {
        $accountId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $balance = new BigInteger(10000000000);
        $seqNum = new XdrSequenceNumber(new BigInteger(12345));
        $ext = new XdrAccountEntryExt(0, null);

        $accountEntry = new XdrAccountEntry(
            $accountId,
            $balance,
            $seqNum,
            0,
            null,
            0,
            "",
            chr(1) . chr(0) . chr(0) . chr(0),
            [],
            $ext
        );

        $ledgerEntryData = new XdrLedgerEntryData(XdrLedgerEntryType::ACCOUNT());
        $ledgerEntryData->setAccount($accountEntry);

        $ledgerEntry = new XdrLedgerEntry(123, $ledgerEntryData, new XdrLedgerEntryExt(0, null));

        $change = new XdrLedgerEntryChange(
            new XdrLedgerEntryChangeType(XdrLedgerEntryChangeType::LEDGER_ENTRY_CREATED)
        );
        $change->setCreated($ledgerEntry);

        $encoded = $change->encode();
        $decoded = XdrLedgerEntryChange::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryChangeType::LEDGER_ENTRY_CREATED, $decoded->type->value);
        $this->assertNotNull($decoded->getCreated());
        $this->assertEquals(123, $decoded->getCreated()->getLastModifiedLedgerSeq());
    }

    public function testXdrLedgerEntryChangeUpdatedRoundTrip(): void
    {
        $accountId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $balance = new BigInteger(20000000000);
        $seqNum = new XdrSequenceNumber(new BigInteger(54321));
        $ext = new XdrAccountEntryExt(0, null);

        $accountEntry = new XdrAccountEntry(
            $accountId,
            $balance,
            $seqNum,
            0,
            null,
            0,
            "",
            chr(1) . chr(0) . chr(0) . chr(0),
            [],
            $ext
        );

        $ledgerEntryData = new XdrLedgerEntryData(XdrLedgerEntryType::ACCOUNT());
        $ledgerEntryData->setAccount($accountEntry);

        $ledgerEntry = new XdrLedgerEntry(456, $ledgerEntryData, new XdrLedgerEntryExt(0, null));

        $change = new XdrLedgerEntryChange(
            new XdrLedgerEntryChangeType(XdrLedgerEntryChangeType::LEDGER_ENTRY_UPDATED)
        );
        $change->setUpdated($ledgerEntry);

        $encoded = $change->encode();
        $decoded = XdrLedgerEntryChange::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryChangeType::LEDGER_ENTRY_UPDATED, $decoded->type->value);
        $this->assertNotNull($decoded->getUpdated());
        $this->assertEquals(456, $decoded->getUpdated()->getLastModifiedLedgerSeq());
    }

    public function testXdrLedgerEntryChangeRemovedRoundTrip(): void
    {
        $accountId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $ledgerKeyAccount = new XdrLedgerKeyAccount($accountId);

        $ledgerKey = new XdrLedgerKey(XdrLedgerEntryType::ACCOUNT());
        $ledgerKey->setAccount($ledgerKeyAccount);

        $change = new XdrLedgerEntryChange(
            new XdrLedgerEntryChangeType(XdrLedgerEntryChangeType::LEDGER_ENTRY_REMOVED)
        );
        $change->setRemoved($ledgerKey);

        $encoded = $change->encode();
        $decoded = XdrLedgerEntryChange::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryChangeType::LEDGER_ENTRY_REMOVED, $decoded->type->value);
        $this->assertNotNull($decoded->getRemoved());
        $this->assertEquals(XdrLedgerEntryType::ACCOUNT, $decoded->getRemoved()->getType()->getValue());
    }

    public function testXdrLedgerEntryChangeStateRoundTrip(): void
    {
        $accountId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $balance = new BigInteger(5000000000);
        $seqNum = new XdrSequenceNumber(new BigInteger(99999));
        $ext = new XdrAccountEntryExt(0, null);

        $accountEntry = new XdrAccountEntry(
            $accountId,
            $balance,
            $seqNum,
            0,
            null,
            0,
            "",
            chr(1) . chr(0) . chr(0) . chr(0),
            [],
            $ext
        );

        $ledgerEntryData = new XdrLedgerEntryData(XdrLedgerEntryType::ACCOUNT());
        $ledgerEntryData->setAccount($accountEntry);

        $ledgerEntry = new XdrLedgerEntry(789, $ledgerEntryData, new XdrLedgerEntryExt(0, null));

        $change = new XdrLedgerEntryChange(
            new XdrLedgerEntryChangeType(XdrLedgerEntryChangeType::LEDGER_ENTRY_STATE)
        );
        $change->setState($ledgerEntry);

        $encoded = $change->encode();
        $decoded = XdrLedgerEntryChange::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryChangeType::LEDGER_ENTRY_STATE, $decoded->type->value);
        $this->assertNotNull($decoded->getState());
        $this->assertEquals(789, $decoded->getState()->getLastModifiedLedgerSeq());
    }

    public function testXdrLedgerEntryChangeRestoredRoundTrip(): void
    {
        $accountId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID_2);
        $balance = new BigInteger(15000000000);
        $seqNum = new XdrSequenceNumber(new BigInteger(11111));
        $ext = new XdrAccountEntryExt(0, null);

        $accountEntry = new XdrAccountEntry(
            $accountId,
            $balance,
            $seqNum,
            0,
            null,
            0,
            "",
            chr(1) . chr(0) . chr(0) . chr(0),
            [],
            $ext
        );

        $ledgerEntryData = new XdrLedgerEntryData(XdrLedgerEntryType::ACCOUNT());
        $ledgerEntryData->setAccount($accountEntry);

        $ledgerEntry = new XdrLedgerEntry(321, $ledgerEntryData, new XdrLedgerEntryExt(0, null));

        $change = new XdrLedgerEntryChange(
            new XdrLedgerEntryChangeType(XdrLedgerEntryChangeType::LEDGER_ENTRY_RESTORED)
        );
        $change->restored = $ledgerEntry;

        $encoded = $change->encode();
        $decoded = XdrLedgerEntryChange::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLedgerEntryChangeType::LEDGER_ENTRY_RESTORED, $decoded->type->value);
        $this->assertNotNull($decoded->restored);
        $this->assertEquals(321, $decoded->restored->getLastModifiedLedgerSeq());
    }

    // XdrConfigSettingEntry Tests

    public function testXdrConfigSettingEntryContractMaxSizeBytesRoundTrip(): void
    {
        $entry = new XdrConfigSettingEntry(
            new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_MAX_SIZE_BYTES)
        );
        $entry->contractMaxSizeBytes = 65536;

        $encoded = $entry->encode();
        $decoded = XdrConfigSettingEntry::decode(new XdrBuffer($encoded));

        $this->assertEquals(
            XdrConfigSettingID::CONFIG_SETTING_CONTRACT_MAX_SIZE_BYTES,
            $decoded->configSettingID->value
        );
        $this->assertEquals(65536, $decoded->contractMaxSizeBytes);
    }

    public function testXdrConfigSettingEntryContractComputeV0RoundTrip(): void
    {
        $compute = new XdrConfigSettingContractComputeV0(
            100000,
            1000000,
            200000,
            2000000,
            50000,
            500000,
            new XdrExtensionPoint(0)
        );

        $entry = new XdrConfigSettingEntry(
            new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COMPUTE_V0)
        );
        $entry->contractCompute = $compute;

        $encoded = $entry->encode();
        $decoded = XdrConfigSettingEntry::decode(new XdrBuffer($encoded));

        $this->assertEquals(
            XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COMPUTE_V0,
            $decoded->configSettingID->value
        );
        $this->assertNotNull($decoded->contractCompute);
        $this->assertEquals(100000, $decoded->contractCompute->ledgerMaxInstructions);
    }

    public function testXdrConfigSettingEntryContractCostParamsRoundTrip(): void
    {
        $costParams = new XdrContractCostParams([
            new XdrContractCostParamEntry(new XdrExtensionPoint(0), 100, 200),
            new XdrContractCostParamEntry(new XdrExtensionPoint(0), 150, 250),
        ]);

        $entry = new XdrConfigSettingEntry(
            new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COST_PARAMS_CPU_INSTRUCTIONS)
        );
        $entry->contractCostParamsCpuInsns = $costParams;

        $encoded = $entry->encode();
        $decoded = XdrConfigSettingEntry::decode(new XdrBuffer($encoded));

        $this->assertEquals(
            XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COST_PARAMS_CPU_INSTRUCTIONS,
            $decoded->configSettingID->value
        );
        $this->assertNotNull($decoded->contractCostParamsCpuInsns);
        $this->assertCount(2, $decoded->contractCostParamsCpuInsns->entries);
    }

    public function testXdrConfigSettingEntryContractDataKeySizeBytesRoundTrip(): void
    {
        $entry = new XdrConfigSettingEntry(
            new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_KEY_SIZE_BYTES)
        );
        $entry->contractDataKeySizeBytes = 256;

        $encoded = $entry->encode();
        $decoded = XdrConfigSettingEntry::decode(new XdrBuffer($encoded));

        $this->assertEquals(
            XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_KEY_SIZE_BYTES,
            $decoded->configSettingID->value
        );
        $this->assertEquals(256, $decoded->contractDataKeySizeBytes);
    }

    public function testXdrConfigSettingEntryContractDataEntrySizeBytesRoundTrip(): void
    {
        $entry = new XdrConfigSettingEntry(
            new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_ENTRY_SIZE_BYTES)
        );
        $entry->contractDataEntrySizeBytes = 4096;

        $encoded = $entry->encode();
        $decoded = XdrConfigSettingEntry::decode(new XdrBuffer($encoded));

        $this->assertEquals(
            XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_ENTRY_SIZE_BYTES,
            $decoded->configSettingID->value
        );
        $this->assertEquals(4096, $decoded->contractDataEntrySizeBytes);
    }

    // XdrConstantProduct Tests

    public function testXdrConstantProductRoundTrip(): void
    {
        $assetA = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $assetB = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $params = new XdrLiquidityPoolConstantProductParameters($assetA, $assetB, 30);

        $constantProduct = new XdrConstantProduct(
            $params,
            new BigInteger(5000000),
            new BigInteger(10000000),
            new BigInteger(7500000),
            25
        );

        $encoded = $constantProduct->encode();
        $decoded = XdrConstantProduct::decode(new XdrBuffer($encoded));

        $this->assertEquals("5000000", $decoded->reserveA->toString());
        $this->assertEquals("10000000", $decoded->reserveB->toString());
        $this->assertEquals("7500000", $decoded->totalPoolShares->toString());
        $this->assertEquals(25, $decoded->poolSharesTrustLineCount);
    }

    public function testXdrConstantProductGettersSetters(): void
    {
        $assetA = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $assetB = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $params = new XdrLiquidityPoolConstantProductParameters($assetA, $assetB, 30);

        $constantProduct = new XdrConstantProduct(
            $params,
            new BigInteger(1000000),
            new BigInteger(2000000),
            new BigInteger(1500000),
            10
        );

        $constantProduct->setReserveA(new BigInteger(3000000));
        $constantProduct->setReserveB(new BigInteger(4000000));
        $constantProduct->setTotalPoolShares(new BigInteger(3500000));
        $constantProduct->setPoolSharesTrustLineCount(20);

        $this->assertEquals("3000000", $constantProduct->getReserveA()->toString());
        $this->assertEquals("4000000", $constantProduct->getReserveB()->toString());
        $this->assertEquals("3500000", $constantProduct->getTotalPoolShares()->toString());
        $this->assertEquals(20, $constantProduct->getPoolSharesTrustLineCount());
    }

    // XdrLiquidityPoolBody Tests

    public function testXdrLiquidityPoolBodyConstantProductRoundTrip(): void
    {
        $assetA = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $assetB = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $params = new XdrLiquidityPoolConstantProductParameters($assetA, $assetB, 30);

        $constantProduct = new XdrConstantProduct(
            $params,
            new BigInteger(8000000),
            new BigInteger(9000000),
            new BigInteger(8500000),
            15
        );

        $body = new XdrLiquidityPoolBody(
            new XdrLiquidityPoolType(XdrLiquidityPoolType::LIQUIDITY_POOL_CONSTANT_PRODUCT)
        );
        $body->setConstantProduct($constantProduct);

        $encoded = $body->encode();
        $decoded = XdrLiquidityPoolBody::decode(new XdrBuffer($encoded));

        $this->assertEquals(
            XdrLiquidityPoolType::LIQUIDITY_POOL_CONSTANT_PRODUCT,
            $decoded->type->getValue()
        );
        $this->assertNotNull($decoded->constantProduct);
        $this->assertEquals("8000000", $decoded->constantProduct->reserveA->toString());
    }

    public function testXdrLiquidityPoolBodyGettersSetters(): void
    {
        $assetA = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $assetB = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $params = new XdrLiquidityPoolConstantProductParameters($assetA, $assetB, 30);

        $constantProduct = new XdrConstantProduct(
            $params,
            new BigInteger(1000000),
            new BigInteger(2000000),
            new BigInteger(1500000),
            10
        );

        $body = new XdrLiquidityPoolBody(
            new XdrLiquidityPoolType(XdrLiquidityPoolType::LIQUIDITY_POOL_CONSTANT_PRODUCT)
        );
        $body->setConstantProduct($constantProduct);

        $this->assertNotNull($body->getConstantProduct());
        $this->assertEquals("1000000", $body->getConstantProduct()->reserveA->toString());
    }

    // XdrLiquidityPoolEntry Tests

    public function testXdrLiquidityPoolEntryRoundTrip(): void
    {
        $poolId = hex2bin("aa7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fab8");

        $assetA = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $assetB = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $params = new XdrLiquidityPoolConstantProductParameters($assetA, $assetB, 30);

        $constantProduct = new XdrConstantProduct(
            $params,
            new BigInteger(6000000),
            new BigInteger(7000000),
            new BigInteger(6500000),
            12
        );

        $body = new XdrLiquidityPoolBody(
            new XdrLiquidityPoolType(XdrLiquidityPoolType::LIQUIDITY_POOL_CONSTANT_PRODUCT)
        );
        $body->setConstantProduct($constantProduct);

        $entry = new XdrLiquidityPoolEntry($poolId, $body);

        $encoded = $entry->encode();
        $decoded = XdrLiquidityPoolEntry::decode(new XdrBuffer($encoded));

        $this->assertEquals(bin2hex($poolId), bin2hex($decoded->liquidityPoolID));
        $this->assertNotNull($decoded->body->constantProduct);
        $this->assertEquals("6000000", $decoded->body->constantProduct->reserveA->toString());
    }

    public function testXdrLiquidityPoolEntryGettersSetters(): void
    {
        $poolId = hex2bin("bb7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fab9");

        $assetA = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $assetB = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $params = new XdrLiquidityPoolConstantProductParameters($assetA, $assetB, 30);

        $constantProduct = new XdrConstantProduct(
            $params,
            new BigInteger(1000000),
            new BigInteger(2000000),
            new BigInteger(1500000),
            10
        );

        $body = new XdrLiquidityPoolBody(
            new XdrLiquidityPoolType(XdrLiquidityPoolType::LIQUIDITY_POOL_CONSTANT_PRODUCT)
        );
        $body->setConstantProduct($constantProduct);

        $entry = new XdrLiquidityPoolEntry($poolId, $body);

        $this->assertEquals(bin2hex($poolId), bin2hex($entry->getLiquidityPoolID()));
        $this->assertNotNull($entry->getBody());

        $newPoolId = hex2bin("cc7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380faba");
        $entry->setLiquidityPoolID($newPoolId);
        $this->assertEquals(bin2hex($newPoolId), bin2hex($entry->getLiquidityPoolID()));
    }
}
