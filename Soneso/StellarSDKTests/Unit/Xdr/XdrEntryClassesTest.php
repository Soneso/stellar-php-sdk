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
use Soneso\StellarSDK\Xdr\XdrConstantProduct;
use Soneso\StellarSDK\Xdr\XdrLedgerEntry;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryChange;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryChangeType;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryData;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryExt;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyAccount;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolBody;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolConstantProductParameters;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolEntry;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolType;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;

class XdrEntryClassesTest extends TestCase
{
    private const TEST_ACCOUNT_ID = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';
    private const TEST_ACCOUNT_ID_2 = 'GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ';

    // XdrLedgerEntryChange Tests

    public function testXdrLedgerEntryChangeCreatedRoundTrip(): void
    {
        $accountId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $balance = new BigInteger(10000000000);
        $seqNum = new XdrSequenceNumber(new BigInteger(12345));
        $ext = new XdrAccountEntryExt(0);

        $accountEntry = new XdrAccountEntry(
            $accountId,
            $balance,
            $seqNum,
            0,
            0,
            "",
            chr(1) . chr(0) . chr(0) . chr(0),
            [],
            $ext
        );

        $ledgerEntryData = new XdrLedgerEntryData(XdrLedgerEntryType::ACCOUNT());
        $ledgerEntryData->setAccount($accountEntry);

        $ledgerEntry = new XdrLedgerEntry(123, $ledgerEntryData, new XdrLedgerEntryExt(0));

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
        $ext = new XdrAccountEntryExt(0);

        $accountEntry = new XdrAccountEntry(
            $accountId,
            $balance,
            $seqNum,
            0,
            0,
            "",
            chr(1) . chr(0) . chr(0) . chr(0),
            [],
            $ext
        );

        $ledgerEntryData = new XdrLedgerEntryData(XdrLedgerEntryType::ACCOUNT());
        $ledgerEntryData->setAccount($accountEntry);

        $ledgerEntry = new XdrLedgerEntry(456, $ledgerEntryData, new XdrLedgerEntryExt(0));

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
        $ext = new XdrAccountEntryExt(0);

        $accountEntry = new XdrAccountEntry(
            $accountId,
            $balance,
            $seqNum,
            0,
            0,
            "",
            chr(1) . chr(0) . chr(0) . chr(0),
            [],
            $ext
        );

        $ledgerEntryData = new XdrLedgerEntryData(XdrLedgerEntryType::ACCOUNT());
        $ledgerEntryData->setAccount($accountEntry);

        $ledgerEntry = new XdrLedgerEntry(789, $ledgerEntryData, new XdrLedgerEntryExt(0));

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
        $ext = new XdrAccountEntryExt(0);

        $accountEntry = new XdrAccountEntry(
            $accountId,
            $balance,
            $seqNum,
            0,
            0,
            "",
            chr(1) . chr(0) . chr(0) . chr(0),
            [],
            $ext
        );

        $ledgerEntryData = new XdrLedgerEntryData(XdrLedgerEntryType::ACCOUNT());
        $ledgerEntryData->setAccount($accountEntry);

        $ledgerEntry = new XdrLedgerEntry(321, $ledgerEntryData, new XdrLedgerEntryExt(0));

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

    // XdrConstantProduct Tests

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
