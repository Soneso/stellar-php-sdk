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
use Soneso\StellarSDK\Xdr\XdrContractDataDurability;
use Soneso\StellarSDK\Xdr\XdrDataEntry;
use Soneso\StellarSDK\Xdr\XdrLedgerEntry;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryData;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryExt;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryV1;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryV1Ext;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyAccount;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyData;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyOffer;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyTrustLine;
use Soneso\StellarSDK\Xdr\XdrOfferEntry;
use Soneso\StellarSDK\Xdr\XdrOfferEntryExt;
use Soneso\StellarSDK\Xdr\XdrPrice;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;
use Soneso\StellarSDK\Xdr\XdrSignerKey;
use Soneso\StellarSDK\Xdr\XdrTrustLineAsset;
use Soneso\StellarSDK\Xdr\XdrTrustLineEntry;
use Soneso\StellarSDK\Xdr\XdrTrustLineEntryExt;
use Soneso\StellarSDK\Xdr\XdrDataValueMandatory;
use Soneso\StellarSDK\Xdr\XdrDataEntryExt;

class XdrLedgerTest extends TestCase
{
    private const TEST_ACCOUNT_ID = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';
    private const TEST_ACCOUNT_ID_2 = 'GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ';

    public function testXdrLedgerEntryTypeEncodeDecode(): void
    {
        $types = [
            XdrLedgerEntryType::ACCOUNT,
            XdrLedgerEntryType::TRUSTLINE,
            XdrLedgerEntryType::OFFER,
            XdrLedgerEntryType::DATA,
            XdrLedgerEntryType::CLAIMABLE_BALANCE,
            XdrLedgerEntryType::LIQUIDITY_POOL,
            XdrLedgerEntryType::CONTRACT_DATA,
            XdrLedgerEntryType::CONTRACT_CODE,
            XdrLedgerEntryType::CONFIG_SETTING,
            XdrLedgerEntryType::TTL,
        ];

        foreach ($types as $typeValue) {
            $type = new XdrLedgerEntryType($typeValue);
            $encoded = $type->encode();
            $xdrBuffer = new XdrBuffer($encoded);
            $decoded = XdrLedgerEntryType::decode($xdrBuffer);

            $this->assertEquals($type->getValue(), $decoded->getValue());
        }
    }

    public function testXdrLedgerEntryTypeStaticMethods(): void
    {
        $this->assertEquals(XdrLedgerEntryType::ACCOUNT, XdrLedgerEntryType::ACCOUNT()->getValue());
        $this->assertEquals(XdrLedgerEntryType::TRUSTLINE, XdrLedgerEntryType::TRUSTLINE()->getValue());
        $this->assertEquals(XdrLedgerEntryType::OFFER, XdrLedgerEntryType::OFFER()->getValue());
        $this->assertEquals(XdrLedgerEntryType::DATA, XdrLedgerEntryType::DATA()->getValue());
        $this->assertEquals(XdrLedgerEntryType::CLAIMABLE_BALANCE, XdrLedgerEntryType::CLAIMABLE_BALANCE()->getValue());
        $this->assertEquals(XdrLedgerEntryType::LIQUIDITY_POOL, XdrLedgerEntryType::LIQUIDITY_POOL()->getValue());
        $this->assertEquals(XdrLedgerEntryType::CONTRACT_DATA, XdrLedgerEntryType::CONTRACT_DATA()->getValue());
        $this->assertEquals(XdrLedgerEntryType::CONTRACT_CODE, XdrLedgerEntryType::CONTRACT_CODE()->getValue());
        $this->assertEquals(XdrLedgerEntryType::CONFIG_SETTING, XdrLedgerEntryType::CONFIG_SETTING()->getValue());
        $this->assertEquals(XdrLedgerEntryType::TTL, XdrLedgerEntryType::EXPIRATION()->getValue());
    }

    public function testXdrLedgerEntryDataWithAccountEntry(): void
    {
        $accountId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $balance = new BigInteger(10000000000);
        $seqNum = new XdrSequenceNumber(new BigInteger(12345));
        $numSubEntries = 0;
        $inflationDest = null;
        $flags = 0;
        $homeDomain = "";
        $thresholds = chr(1) . chr(0) . chr(0) . chr(0);
        $signers = [];
        $ext = new XdrAccountEntryExt(0, null);

        $accountEntry = new XdrAccountEntry(
            $accountId,
            $balance,
            $seqNum,
            $numSubEntries,
            $inflationDest,
            $flags,
            $homeDomain,
            $thresholds,
            $signers,
            $ext
        );

        $ledgerEntryData = new XdrLedgerEntryData(XdrLedgerEntryType::ACCOUNT());
        $ledgerEntryData->setAccount($accountEntry);

        $encoded = $ledgerEntryData->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerEntryData::decode($xdrBuffer);

        $this->assertEquals($ledgerEntryData->getType()->getValue(), $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getAccount());
        $this->assertEquals(
            $accountEntry->getAccountId()->getAccountId(),
            $decoded->getAccount()->getAccountId()->getAccountId()
        );
    }

    public function testXdrLedgerEntryDataWithTrustLineEntry(): void
    {
        $accountId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $nativeAsset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $asset = XdrTrustLineAsset::fromXdrAsset($nativeAsset);
        $balance = 50000000;
        $limit = 100000000;
        $flags = 1;
        $ext = new XdrTrustLineEntryExt(0, null);

        $trustLineEntry = new XdrTrustLineEntry(
            $accountId,
            $asset,
            $balance,
            $limit,
            $flags,
            $ext
        );

        $ledgerEntryData = new XdrLedgerEntryData(XdrLedgerEntryType::TRUSTLINE());
        $ledgerEntryData->setTrustline($trustLineEntry);

        $encoded = $ledgerEntryData->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerEntryData::decode($xdrBuffer);

        $this->assertEquals($ledgerEntryData->getType()->getValue(), $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getTrustline());
        $this->assertEquals(
            $trustLineEntry->getAccountId()->getAccountId(),
            $decoded->getTrustline()->getAccountId()->getAccountId()
        );
    }

    public function testXdrLedgerEntryDataWithOfferEntry(): void
    {
        $sellerId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $offerId = 123456;
        $selling = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $buying = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $amount = new BigInteger(1000000);
        $price = new XdrPrice(1, 1);
        $flags = 0;
        $ext = new XdrOfferEntryExt(0, null);

        $offerEntry = new XdrOfferEntry();
        $offerEntry->setSellerID($sellerId);
        $offerEntry->setOfferId($offerId);
        $offerEntry->setSelling($selling);
        $offerEntry->setBuying($buying);
        $offerEntry->setAmount($amount);
        $offerEntry->setPrice($price);
        $offerEntry->setFlags($flags);
        $offerEntry->setExt($ext);

        $ledgerEntryData = new XdrLedgerEntryData(XdrLedgerEntryType::OFFER());
        $ledgerEntryData->setOffer($offerEntry);

        $encoded = $ledgerEntryData->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerEntryData::decode($xdrBuffer);

        $this->assertEquals($ledgerEntryData->getType()->getValue(), $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getOffer());
        $this->assertEquals($offerId, $decoded->getOffer()->getOfferId());
    }

    public function testXdrLedgerEntryDataWithDataEntry(): void
    {
        $accountId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $dataName = "test_data";
        $dataValue = new XdrDataValueMandatory("test_value_123");
        $ext = new XdrDataEntryExt(0);

        $dataEntry = new XdrDataEntry($accountId, $dataName, $dataValue, $ext);

        $ledgerEntryData = new XdrLedgerEntryData(XdrLedgerEntryType::DATA());
        $ledgerEntryData->setData($dataEntry);

        $encoded = $ledgerEntryData->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerEntryData::decode($xdrBuffer);

        $this->assertEquals($ledgerEntryData->getType()->getValue(), $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getData());
        $this->assertEquals($dataName, $decoded->getData()->getDataName());
        $this->assertEquals("test_value_123", $decoded->getData()->getDataValue()->getValue());
    }

    public function testXdrLedgerEntryWithAccountEntry(): void
    {
        $lastModifiedLedgerSeq = 12345;

        $accountId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $balance = new BigInteger(10000000000);
        $seqNum = new XdrSequenceNumber(new BigInteger(67890));
        $numSubEntries = 0;
        $inflationDest = null;
        $flags = 0;
        $homeDomain = "";
        $thresholds = chr(1) . chr(0) . chr(0) . chr(0);
        $signers = [];
        $accountExt = new XdrAccountEntryExt(0, null);

        $accountEntry = new XdrAccountEntry(
            $accountId,
            $balance,
            $seqNum,
            $numSubEntries,
            $inflationDest,
            $flags,
            $homeDomain,
            $thresholds,
            $signers,
            $accountExt
        );

        $ledgerEntryData = new XdrLedgerEntryData(XdrLedgerEntryType::ACCOUNT());
        $ledgerEntryData->setAccount($accountEntry);

        $ext = new XdrLedgerEntryExt(0, null);
        $ledgerEntry = new XdrLedgerEntry($lastModifiedLedgerSeq, $ledgerEntryData, $ext);

        $encoded = $ledgerEntry->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerEntry::decode($xdrBuffer);

        $this->assertEquals($lastModifiedLedgerSeq, $decoded->getLastModifiedLedgerSeq());
        $this->assertEquals(XdrLedgerEntryType::ACCOUNT, $decoded->getData()->getType()->getValue());
        $this->assertNotNull($decoded->getData()->getAccount());
    }

    public function testXdrLedgerEntryBase64Methods(): void
    {
        $lastModifiedLedgerSeq = 98765;

        $accountId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $balance = new BigInteger(5000000000);
        $seqNum = new XdrSequenceNumber(new BigInteger(11111));
        $numSubEntries = 2;
        $inflationDest = null;
        $flags = 0;
        $homeDomain = "example.com";
        $thresholds = chr(1) . chr(1) . chr(1) . chr(1);
        $signers = [];
        $accountExt = new XdrAccountEntryExt(0, null);

        $accountEntry = new XdrAccountEntry(
            $accountId,
            $balance,
            $seqNum,
            $numSubEntries,
            $inflationDest,
            $flags,
            $homeDomain,
            $thresholds,
            $signers,
            $accountExt
        );

        $ledgerEntryData = new XdrLedgerEntryData(XdrLedgerEntryType::ACCOUNT());
        $ledgerEntryData->setAccount($accountEntry);

        $ext = new XdrLedgerEntryExt(0, null);
        $ledgerEntry = new XdrLedgerEntry($lastModifiedLedgerSeq, $ledgerEntryData, $ext);

        $base64 = base64_encode($ledgerEntry->encode());
        $decoded = XdrLedgerEntry::fromBase64Xdr($base64);

        $this->assertEquals($lastModifiedLedgerSeq, $decoded->getLastModifiedLedgerSeq());
        $this->assertEquals($homeDomain, $decoded->getData()->getAccount()->getHomeDomain());
    }

    public function testXdrLedgerKeyForAccountId(): void
    {
        $ledgerKey = XdrLedgerKey::forAccountId(self::TEST_ACCOUNT_ID);

        $this->assertEquals(XdrLedgerEntryType::ACCOUNT, $ledgerKey->getType()->getValue());
        $this->assertNotNull($ledgerKey->getAccount());
        $this->assertEquals(self::TEST_ACCOUNT_ID, $ledgerKey->getAccount()->getAccountId()->getAccountId());

        $encoded = $ledgerKey->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerKey::decode($xdrBuffer);

        $this->assertEquals($ledgerKey->getType()->getValue(), $decoded->getType()->getValue());
        $this->assertEquals(
            $ledgerKey->getAccount()->getAccountId()->getAccountId(),
            $decoded->getAccount()->getAccountId()->getAccountId()
        );
    }

    public function testXdrLedgerKeyForTrustLine(): void
    {
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $ledgerKey = XdrLedgerKey::forTrustLine(self::TEST_ACCOUNT_ID, $asset);

        $this->assertEquals(XdrLedgerEntryType::TRUSTLINE, $ledgerKey->getType()->getValue());
        $this->assertNotNull($ledgerKey->getTrustLine());

        $encoded = $ledgerKey->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerKey::decode($xdrBuffer);

        $this->assertEquals($ledgerKey->getType()->getValue(), $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getTrustLine());
    }

    public function testXdrLedgerKeyForOffer(): void
    {
        $offerId = 999888;
        $ledgerKey = XdrLedgerKey::forOffer(self::TEST_ACCOUNT_ID, $offerId);

        $this->assertEquals(XdrLedgerEntryType::OFFER, $ledgerKey->getType()->getValue());
        $this->assertNotNull($ledgerKey->getOffer());
        $this->assertEquals($offerId, $ledgerKey->getOffer()->getOfferId());

        $encoded = $ledgerKey->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerKey::decode($xdrBuffer);

        $this->assertEquals($ledgerKey->getType()->getValue(), $decoded->getType()->getValue());
        $this->assertEquals($offerId, $decoded->getOffer()->getOfferId());
    }

    public function testXdrLedgerKeyForData(): void
    {
        $dataName = "my_data_key";
        $ledgerKey = XdrLedgerKey::forData(self::TEST_ACCOUNT_ID, $dataName);

        $this->assertEquals(XdrLedgerEntryType::DATA, $ledgerKey->getType()->getValue());
        $this->assertNotNull($ledgerKey->getData());
        $this->assertEquals($dataName, $ledgerKey->getData()->getDataName());

        $encoded = $ledgerKey->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerKey::decode($xdrBuffer);

        $this->assertEquals($ledgerKey->getType()->getValue(), $decoded->getType()->getValue());
        $this->assertEquals($dataName, $decoded->getData()->getDataName());
    }

    public function testXdrLedgerKeyForClaimableBalanceId(): void
    {
        $balanceIdHex = str_pad('abc123', 64, '0', STR_PAD_LEFT);
        $ledgerKey = XdrLedgerKey::forClaimableBalanceId($balanceIdHex);

        $this->assertEquals(XdrLedgerEntryType::CLAIMABLE_BALANCE, $ledgerKey->getType()->getValue());
        $this->assertNotNull($ledgerKey->getBalanceID());

        $encoded = $ledgerKey->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerKey::decode($xdrBuffer);

        $this->assertEquals($ledgerKey->getType()->getValue(), $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getBalanceID());
    }

    public function testXdrLedgerKeyForLiquidityPoolId(): void
    {
        $poolIdHex = str_pad('def456', 64, '0', STR_PAD_LEFT);
        $ledgerKey = XdrLedgerKey::forLiquidityPoolId($poolIdHex);

        $this->assertEquals(XdrLedgerEntryType::LIQUIDITY_POOL, $ledgerKey->getType()->getValue());
        $this->assertNotNull($ledgerKey->getLiquidityPoolID());
        $this->assertEquals($poolIdHex, $ledgerKey->getLiquidityPoolID());

        $encoded = $ledgerKey->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerKey::decode($xdrBuffer);

        $this->assertEquals($ledgerKey->getType()->getValue(), $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getLiquidityPoolID());
    }

    public function testXdrLedgerKeyForContractData(): void
    {
        $contractIdHex = str_pad('1234567890abcdef', 64, '0', STR_PAD_LEFT);
        $contract = XdrSCAddress::forContractId($contractIdHex);
        $key = XdrSCVal::forU32(123);
        $durability = XdrContractDataDurability::PERSISTENT();

        $ledgerKey = XdrLedgerKey::forContractData($contract, $key, $durability);

        $this->assertEquals(XdrLedgerEntryType::CONTRACT_DATA, $ledgerKey->getType()->getValue());
        $this->assertNotNull($ledgerKey->getContractData());

        $encoded = $ledgerKey->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerKey::decode($xdrBuffer);

        $this->assertEquals($ledgerKey->getType()->getValue(), $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getContractData());
    }

    public function testXdrLedgerKeyForContractCode(): void
    {
        $codeHashHex = str_pad('fedcba', 64, '0', STR_PAD_LEFT);
        $codeHashBytes = hex2bin($codeHashHex);
        $ledgerKey = XdrLedgerKey::forContractCode($codeHashBytes);

        $this->assertEquals(XdrLedgerEntryType::CONTRACT_CODE, $ledgerKey->getType()->getValue());
        $this->assertNotNull($ledgerKey->getContractCode());

        $encoded = $ledgerKey->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerKey::decode($xdrBuffer);

        $this->assertEquals($ledgerKey->getType()->getValue(), $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getContractCode());
    }

    public function testXdrLedgerKeyBase64Methods(): void
    {
        $ledgerKey = XdrLedgerKey::forAccountId(self::TEST_ACCOUNT_ID);

        $base64 = $ledgerKey->toBase64Xdr();
        $decoded = XdrLedgerKey::fromBase64Xdr($base64);

        $this->assertEquals($ledgerKey->getType()->getValue(), $decoded->getType()->getValue());
        $this->assertEquals(
            $ledgerKey->getAccount()->getAccountId()->getAccountId(),
            $decoded->getAccount()->getAccountId()->getAccountId()
        );
    }

    public function testXdrLedgerFootprintEncodeDecode(): void
    {
        $readOnlyKey1 = XdrLedgerKey::forAccountId(self::TEST_ACCOUNT_ID);
        $readOnlyKey2 = XdrLedgerKey::forAccountId(self::TEST_ACCOUNT_ID_2);
        $readWriteKey1 = XdrLedgerKey::forData(self::TEST_ACCOUNT_ID, "data1");
        $readWriteKey2 = XdrLedgerKey::forData(self::TEST_ACCOUNT_ID_2, "data2");

        $footprint = new XdrLedgerFootprint(
            [$readOnlyKey1, $readOnlyKey2],
            [$readWriteKey1, $readWriteKey2]
        );

        $encoded = $footprint->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerFootprint::decode($xdrBuffer);

        $this->assertCount(2, $decoded->getReadOnly());
        $this->assertCount(2, $decoded->getReadWrite());

        $this->assertEquals(
            $readOnlyKey1->getAccount()->getAccountId()->getAccountId(),
            $decoded->getReadOnly()[0]->getAccount()->getAccountId()->getAccountId()
        );

        $this->assertEquals(
            $readWriteKey1->getData()->getDataName(),
            $decoded->getReadWrite()[0]->getData()->getDataName()
        );
    }

    public function testXdrLedgerFootprintEmpty(): void
    {
        $footprint = new XdrLedgerFootprint([], []);

        $encoded = $footprint->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerFootprint::decode($xdrBuffer);

        $this->assertCount(0, $decoded->getReadOnly());
        $this->assertCount(0, $decoded->getReadWrite());
    }

    public function testXdrLedgerFootprintBase64Methods(): void
    {
        $readOnlyKey = XdrLedgerKey::forAccountId(self::TEST_ACCOUNT_ID);
        $readWriteKey = XdrLedgerKey::forData(self::TEST_ACCOUNT_ID, "test_data");

        $footprint = new XdrLedgerFootprint([$readOnlyKey], [$readWriteKey]);

        $base64 = $footprint->toBase64Xdr();
        $decoded = XdrLedgerFootprint::fromBase64Xdr($base64);

        $this->assertCount(1, $decoded->getReadOnly());
        $this->assertCount(1, $decoded->getReadWrite());
        $this->assertEquals("test_data", $decoded->getReadWrite()[0]->getData()->getDataName());
    }

    public function testXdrLedgerEntryExtV0(): void
    {
        $ext = new XdrLedgerEntryExt(0, null);

        $encoded = $ext->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerEntryExt::decode($xdrBuffer);

        $this->assertEquals(0, $decoded->getDiscriminant());
        $this->assertNull($decoded->getV1());
    }

    public function testXdrLedgerEntryExtV1(): void
    {
        $sponsoringId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $v1Ext = new XdrLedgerEntryV1Ext(0);
        $v1 = new XdrLedgerEntryV1($sponsoringId, $v1Ext);
        $ext = new XdrLedgerEntryExt(1, $v1);

        $encoded = $ext->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerEntryExt::decode($xdrBuffer);

        $this->assertEquals(1, $decoded->getDiscriminant());
        $this->assertNotNull($decoded->getV1());
        $this->assertEquals(
            $sponsoringId->getAccountId(),
            $decoded->getV1()->getSponsoringId()->getAccountId()
        );
    }
}
