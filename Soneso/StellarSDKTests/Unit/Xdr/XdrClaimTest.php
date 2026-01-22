<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use PHPUnit\Framework\TestCase;
use phpseclib3\Math\BigInteger;
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

class XdrClaimTest extends TestCase
{
    private const TEST_ACCOUNT_ID = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';
    private const TEST_ACCOUNT_ID_2 = 'GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ';
    private const TEST_BALANCE_ID = '0000000000000000000000000000000000000000000000000000000000001234';

    public function testXdrClaimableBalanceIDTypeEncodeDecode(): void
    {
        $type = new XdrClaimableBalanceIDType(XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0);

        $encoded = $type->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrClaimableBalanceIDType::decode($xdrBuffer);

        $this->assertEquals($type->getValue(), $decoded->getValue());
    }

    public function testXdrClaimableBalanceIDTypeStaticMethod(): void
    {
        $type = XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0();

        $this->assertEquals(XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0, $type->getValue());
    }

    public function testXdrClaimableBalanceIDEncodeDecode(): void
    {
        $hash = self::TEST_BALANCE_ID;
        $type = XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0();
        $balanceId = new XdrClaimableBalanceID($type, $hash);

        $encoded = $balanceId->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrClaimableBalanceID::decode($xdrBuffer);

        $this->assertEquals($type->getValue(), $decoded->getType()->getValue());
        $this->assertEquals($hash, $decoded->getHash());
    }

    public function testXdrClaimableBalanceIDForClaimableBalanceId(): void
    {
        $balanceIdHex = 'abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890';
        $balanceId = XdrClaimableBalanceID::forClaimableBalanceId($balanceIdHex);

        $this->assertEquals(
            XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0,
            $balanceId->getType()->getValue()
        );
        $this->assertEquals($balanceIdHex, $balanceId->getHash());

        $encoded = $balanceId->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrClaimableBalanceID::decode($xdrBuffer);

        $this->assertEquals($balanceIdHex, $decoded->getHash());
    }

    public function testXdrClaimableBalanceIDPaddedBalanceIdHex(): void
    {
        $shortHash = '1234567890abcdef';
        $type = XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0();
        $balanceId = new XdrClaimableBalanceID($type, $shortHash);

        $paddedHex = $balanceId->getPaddedBalanceIdHex();

        $this->assertEquals(72, strlen($paddedHex));
        $this->assertTrue(str_starts_with($paddedHex, '00000000000000000000000000000000000000000000000000000000'));
        $this->assertTrue(str_ends_with($paddedHex, $shortHash));
    }

    public function testXdrClaimPredicateTypeEncodeDecode(): void
    {
        $types = [
            XdrClaimPredicateType::UNCONDITIONAL,
            XdrClaimPredicateType::AND,
            XdrClaimPredicateType::OR,
            XdrClaimPredicateType::NOT,
            XdrClaimPredicateType::BEFORE_ABSOLUTE_TIME,
            XdrClaimPredicateType::BEFORE_RELATIVE_TIME,
        ];

        foreach ($types as $typeValue) {
            $type = new XdrClaimPredicateType($typeValue);
            $encoded = $type->encode();
            $xdrBuffer = new XdrBuffer($encoded);
            $decoded = XdrClaimPredicateType::decode($xdrBuffer);

            $this->assertEquals($type->getValue(), $decoded->getValue());
        }
    }

    public function testXdrClaimPredicateUnconditional(): void
    {
        $type = new XdrClaimPredicateType(XdrClaimPredicateType::UNCONDITIONAL);
        $predicate = new XdrClaimPredicate($type);

        $encoded = $predicate->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrClaimPredicate::decode($xdrBuffer);

        $this->assertEquals($type->getValue(), $decoded->getType()->getValue());
        $this->assertNull($decoded->getAndPredicates());
        $this->assertNull($decoded->getOrPredicates());
        $this->assertNull($decoded->getNotPredicate());
        $this->assertNull($decoded->getAbsBefore());
        $this->assertNull($decoded->getRelBefore());
    }

    public function testXdrClaimPredicateBeforeAbsoluteTime(): void
    {
        $type = new XdrClaimPredicateType(XdrClaimPredicateType::BEFORE_ABSOLUTE_TIME);
        $predicate = new XdrClaimPredicate($type);
        $absoluteTime = 1700000000;
        $predicate->setAbsBefore($absoluteTime);

        $encoded = $predicate->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrClaimPredicate::decode($xdrBuffer);

        $this->assertEquals($type->getValue(), $decoded->getType()->getValue());
        $this->assertEquals($absoluteTime, $decoded->getAbsBefore());
    }

    public function testXdrClaimPredicateBeforeRelativeTime(): void
    {
        $type = new XdrClaimPredicateType(XdrClaimPredicateType::BEFORE_RELATIVE_TIME);
        $predicate = new XdrClaimPredicate($type);
        $relativeTime = 86400;
        $predicate->setRelBefore($relativeTime);

        $encoded = $predicate->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrClaimPredicate::decode($xdrBuffer);

        $this->assertEquals($type->getValue(), $decoded->getType()->getValue());
        $this->assertEquals($relativeTime, $decoded->getRelBefore());
    }

    public function testXdrClaimPredicateAnd(): void
    {
        $unconditionalType = new XdrClaimPredicateType(XdrClaimPredicateType::UNCONDITIONAL);
        $predicate1 = new XdrClaimPredicate($unconditionalType);

        $absTimeType = new XdrClaimPredicateType(XdrClaimPredicateType::BEFORE_ABSOLUTE_TIME);
        $predicate2 = new XdrClaimPredicate($absTimeType);
        $predicate2->setAbsBefore(1700000000);

        $andType = new XdrClaimPredicateType(XdrClaimPredicateType::AND);
        $andPredicate = new XdrClaimPredicate($andType);
        $andPredicate->setAndPredicates([$predicate1, $predicate2]);

        $encoded = $andPredicate->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrClaimPredicate::decode($xdrBuffer);

        $this->assertEquals($andType->getValue(), $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getAndPredicates());
        $this->assertCount(2, $decoded->getAndPredicates());
        $this->assertEquals(
            XdrClaimPredicateType::UNCONDITIONAL,
            $decoded->getAndPredicates()[0]->getType()->getValue()
        );
        $this->assertEquals(
            XdrClaimPredicateType::BEFORE_ABSOLUTE_TIME,
            $decoded->getAndPredicates()[1]->getType()->getValue()
        );
    }

    public function testXdrClaimPredicateOr(): void
    {
        $absTimeType = new XdrClaimPredicateType(XdrClaimPredicateType::BEFORE_ABSOLUTE_TIME);
        $predicate1 = new XdrClaimPredicate($absTimeType);
        $predicate1->setAbsBefore(1700000000);

        $relTimeType = new XdrClaimPredicateType(XdrClaimPredicateType::BEFORE_RELATIVE_TIME);
        $predicate2 = new XdrClaimPredicate($relTimeType);
        $predicate2->setRelBefore(3600);

        $orType = new XdrClaimPredicateType(XdrClaimPredicateType::OR);
        $orPredicate = new XdrClaimPredicate($orType);
        $orPredicate->setOrPredicates([$predicate1, $predicate2]);

        $encoded = $orPredicate->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrClaimPredicate::decode($xdrBuffer);

        $this->assertEquals($orType->getValue(), $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getOrPredicates());
        $this->assertCount(2, $decoded->getOrPredicates());
        $this->assertEquals(1700000000, $decoded->getOrPredicates()[0]->getAbsBefore());
        $this->assertEquals(3600, $decoded->getOrPredicates()[1]->getRelBefore());
    }

    public function testXdrClaimPredicateNot(): void
    {
        $absTimeType = new XdrClaimPredicateType(XdrClaimPredicateType::BEFORE_ABSOLUTE_TIME);
        $innerPredicate = new XdrClaimPredicate($absTimeType);
        $innerPredicate->setAbsBefore(1700000000);

        $notType = new XdrClaimPredicateType(XdrClaimPredicateType::NOT);
        $notPredicate = new XdrClaimPredicate($notType);
        $notPredicate->setNotPredicate($innerPredicate);

        $encoded = $notPredicate->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrClaimPredicate::decode($xdrBuffer);

        $this->assertEquals($notType->getValue(), $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getNotPredicate());
        $this->assertEquals(
            XdrClaimPredicateType::BEFORE_ABSOLUTE_TIME,
            $decoded->getNotPredicate()->getType()->getValue()
        );
        $this->assertEquals(1700000000, $decoded->getNotPredicate()->getAbsBefore());
    }

    public function testXdrClaimPredicateComplex(): void
    {
        $absTime1Type = new XdrClaimPredicateType(XdrClaimPredicateType::BEFORE_ABSOLUTE_TIME);
        $predicate1 = new XdrClaimPredicate($absTime1Type);
        $predicate1->setAbsBefore(1700000000);

        $relTimeType = new XdrClaimPredicateType(XdrClaimPredicateType::BEFORE_RELATIVE_TIME);
        $predicate2 = new XdrClaimPredicate($relTimeType);
        $predicate2->setRelBefore(86400);

        $orType = new XdrClaimPredicateType(XdrClaimPredicateType::OR);
        $orPredicate = new XdrClaimPredicate($orType);
        $orPredicate->setOrPredicates([$predicate1, $predicate2]);

        $absTime2Type = new XdrClaimPredicateType(XdrClaimPredicateType::BEFORE_ABSOLUTE_TIME);
        $predicate3 = new XdrClaimPredicate($absTime2Type);
        $predicate3->setAbsBefore(1800000000);

        $andType = new XdrClaimPredicateType(XdrClaimPredicateType::AND);
        $andPredicate = new XdrClaimPredicate($andType);
        $andPredicate->setAndPredicates([$orPredicate, $predicate3]);

        $encoded = $andPredicate->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrClaimPredicate::decode($xdrBuffer);

        $this->assertEquals($andType->getValue(), $decoded->getType()->getValue());
        $this->assertCount(2, $decoded->getAndPredicates());

        $decodedOrPredicate = $decoded->getAndPredicates()[0];
        $this->assertEquals(XdrClaimPredicateType::OR, $decodedOrPredicate->getType()->getValue());
        $this->assertCount(2, $decodedOrPredicate->getOrPredicates());

        $decodedPredicate3 = $decoded->getAndPredicates()[1];
        $this->assertEquals(1800000000, $decodedPredicate3->getAbsBefore());
    }

    public function testXdrClaimantTypeEncodeDecode(): void
    {
        $type = new XdrClaimantType(XdrClaimantType::V0);

        $encoded = $type->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrClaimantType::decode($xdrBuffer);

        $this->assertEquals($type->getValue(), $decoded->getValue());
    }

    public function testXdrClaimantV0EncodeDecode(): void
    {
        $destination = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $predicateType = new XdrClaimPredicateType(XdrClaimPredicateType::UNCONDITIONAL);
        $predicate = new XdrClaimPredicate($predicateType);

        $claimantV0 = new XdrClaimantV0($destination, $predicate);

        $encoded = $claimantV0->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrClaimantV0::decode($xdrBuffer);

        $this->assertEquals($destination->getAccountId(), $decoded->getDestination()->getAccountId());
        $this->assertEquals($predicateType->getValue(), $decoded->getPredicate()->getType()->getValue());
    }

    public function testXdrClaimantEncodeDecode(): void
    {
        $destination = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $predicateType = new XdrClaimPredicateType(XdrClaimPredicateType::BEFORE_ABSOLUTE_TIME);
        $predicate = new XdrClaimPredicate($predicateType);
        $predicate->setAbsBefore(1700000000);

        $claimantV0 = new XdrClaimantV0($destination, $predicate);
        $claimantType = new XdrClaimantType(XdrClaimantType::V0);
        $claimant = new XdrClaimant($claimantType);
        $claimant->setV0($claimantV0);

        $encoded = $claimant->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrClaimant::decode($xdrBuffer);

        $this->assertEquals($claimantType->getValue(), $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getV0());
        $this->assertEquals(
            $destination->getAccountId(),
            $decoded->getV0()->getDestination()->getAccountId()
        );
        $this->assertEquals(1700000000, $decoded->getV0()->getPredicate()->getAbsBefore());
    }

    public function testXdrClaimantMultiple(): void
    {
        $destination1 = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $predicate1Type = new XdrClaimPredicateType(XdrClaimPredicateType::UNCONDITIONAL);
        $predicate1 = new XdrClaimPredicate($predicate1Type);
        $claimantV01 = new XdrClaimantV0($destination1, $predicate1);
        $claimantType1 = new XdrClaimantType(XdrClaimantType::V0);
        $claimant1 = new XdrClaimant($claimantType1);
        $claimant1->setV0($claimantV01);

        $destination2 = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID_2);
        $predicate2Type = new XdrClaimPredicateType(XdrClaimPredicateType::BEFORE_RELATIVE_TIME);
        $predicate2 = new XdrClaimPredicate($predicate2Type);
        $predicate2->setRelBefore(3600);
        $claimantV02 = new XdrClaimantV0($destination2, $predicate2);
        $claimantType2 = new XdrClaimantType(XdrClaimantType::V0);
        $claimant2 = new XdrClaimant($claimantType2);
        $claimant2->setV0($claimantV02);

        $claimants = [$claimant1, $claimant2];

        foreach ($claimants as $claimant) {
            $encoded = $claimant->encode();
            $xdrBuffer = new XdrBuffer($encoded);
            $decoded = XdrClaimant::decode($xdrBuffer);
            $this->assertEquals(XdrClaimantType::V0, $decoded->getType()->getValue());
        }
    }

    public function testXdrClaimableBalanceEntryEncodeDecode(): void
    {
        $balanceId = XdrClaimableBalanceID::forClaimableBalanceId(self::TEST_BALANCE_ID);

        $destination = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $predicateType = new XdrClaimPredicateType(XdrClaimPredicateType::UNCONDITIONAL);
        $predicate = new XdrClaimPredicate($predicateType);
        $claimantV0 = new XdrClaimantV0($destination, $predicate);
        $claimantType = new XdrClaimantType(XdrClaimantType::V0);
        $claimant = new XdrClaimant($claimantType);
        $claimant->setV0($claimantV0);

        $claimants = [$claimant];
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $amount = new BigInteger(1000000000);
        $ext = new XdrClaimableBalanceEntryExt(0, null);

        $entry = new XdrClaimableBalanceEntry($balanceId, $claimants, $asset, $amount, $ext);

        $encoded = $entry->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrClaimableBalanceEntry::decode($xdrBuffer);

        $this->assertEquals(
            $balanceId->getHash(),
            $decoded->accountID->getHash()
        );
        $this->assertCount(1, $decoded->claimants);
        $this->assertEquals(
            XdrAssetType::ASSET_TYPE_NATIVE,
            $decoded->asset->getType()->getValue()
        );
        // Amount is private, can't access directly via getter or property
    }

    public function testXdrClaimableBalanceEntryWithMultipleClaimants(): void
    {
        $balanceId = XdrClaimableBalanceID::forClaimableBalanceId(self::TEST_BALANCE_ID);

        $destination1 = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID);
        $predicate1Type = new XdrClaimPredicateType(XdrClaimPredicateType::BEFORE_ABSOLUTE_TIME);
        $predicate1 = new XdrClaimPredicate($predicate1Type);
        $predicate1->setAbsBefore(1700000000);
        $claimantV01 = new XdrClaimantV0($destination1, $predicate1);
        $claimantType1 = new XdrClaimantType(XdrClaimantType::V0);
        $claimant1 = new XdrClaimant($claimantType1);
        $claimant1->setV0($claimantV01);

        $destination2 = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID_2);
        $predicate2Type = new XdrClaimPredicateType(XdrClaimPredicateType::BEFORE_RELATIVE_TIME);
        $predicate2 = new XdrClaimPredicate($predicate2Type);
        $predicate2->setRelBefore(86400);
        $claimantV02 = new XdrClaimantV0($destination2, $predicate2);
        $claimantType2 = new XdrClaimantType(XdrClaimantType::V0);
        $claimant2 = new XdrClaimant($claimantType2);
        $claimant2->setV0($claimantV02);

        $claimants = [$claimant1, $claimant2];
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $amount = new BigInteger(5000000000);
        $ext = new XdrClaimableBalanceEntryExt(0, null);

        $entry = new XdrClaimableBalanceEntry($balanceId, $claimants, $asset, $amount, $ext);

        $encoded = $entry->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrClaimableBalanceEntry::decode($xdrBuffer);

        $this->assertCount(2, $decoded->claimants);
        $this->assertEquals(
            $destination1->getAccountId(),
            $decoded->claimants[0]->getV0()->getDestination()->getAccountId()
        );
        $this->assertEquals(
            $destination2->getAccountId(),
            $decoded->claimants[1]->getV0()->getDestination()->getAccountId()
        );
        $this->assertEquals(1700000000, $decoded->claimants[0]->getV0()->getPredicate()->getAbsBefore());
        $this->assertEquals(86400, $decoded->claimants[1]->getV0()->getPredicate()->getRelBefore());
    }

    public function testXdrClaimableBalanceEntryExtV0(): void
    {
        $ext = new XdrClaimableBalanceEntryExt(0, null);

        $encoded = $ext->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrClaimableBalanceEntryExt::decode($xdrBuffer);

        $this->assertEquals(0, $decoded->getDiscriminant());
        $this->assertNull($decoded->v1);
    }

    public function testXdrClaimPredicateEmptyAndOr(): void
    {
        $andType = new XdrClaimPredicateType(XdrClaimPredicateType::AND);
        $andPredicate = new XdrClaimPredicate($andType);
        $andPredicate->setAndPredicates([]);

        $encoded = $andPredicate->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrClaimPredicate::decode($xdrBuffer);

        $this->assertEquals($andType->getValue(), $decoded->getType()->getValue());
        $this->assertCount(0, $decoded->getAndPredicates());

        $orType = new XdrClaimPredicateType(XdrClaimPredicateType::OR);
        $orPredicate = new XdrClaimPredicate($orType);
        $orPredicate->setOrPredicates([]);

        $encoded = $orPredicate->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrClaimPredicate::decode($xdrBuffer);

        $this->assertEquals($orType->getValue(), $decoded->getType()->getValue());
        $this->assertCount(0, $decoded->getOrPredicates());
    }

    public function testXdrClaimPredicateNestedComplexStructure(): void
    {
        $unconditionalType = new XdrClaimPredicateType(XdrClaimPredicateType::UNCONDITIONAL);
        $unconditional = new XdrClaimPredicate($unconditionalType);

        $notType = new XdrClaimPredicateType(XdrClaimPredicateType::NOT);
        $notPredicate = new XdrClaimPredicate($notType);
        $notPredicate->setNotPredicate($unconditional);

        $absTimeType = new XdrClaimPredicateType(XdrClaimPredicateType::BEFORE_ABSOLUTE_TIME);
        $absTime = new XdrClaimPredicate($absTimeType);
        $absTime->setAbsBefore(1700000000);

        $orType = new XdrClaimPredicateType(XdrClaimPredicateType::OR);
        $orPredicate = new XdrClaimPredicate($orType);
        $orPredicate->setOrPredicates([$notPredicate, $absTime]);

        $relTimeType = new XdrClaimPredicateType(XdrClaimPredicateType::BEFORE_RELATIVE_TIME);
        $relTime = new XdrClaimPredicate($relTimeType);
        $relTime->setRelBefore(86400);

        $andType = new XdrClaimPredicateType(XdrClaimPredicateType::AND);
        $andPredicate = new XdrClaimPredicate($andType);
        $andPredicate->setAndPredicates([$orPredicate, $relTime]);

        $encoded = $andPredicate->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrClaimPredicate::decode($xdrBuffer);

        $this->assertEquals(XdrClaimPredicateType::AND, $decoded->getType()->getValue());
        $this->assertCount(2, $decoded->getAndPredicates());

        $decodedOr = $decoded->getAndPredicates()[0];
        $this->assertEquals(XdrClaimPredicateType::OR, $decodedOr->getType()->getValue());
        $this->assertCount(2, $decodedOr->getOrPredicates());

        $decodedNot = $decodedOr->getOrPredicates()[0];
        $this->assertEquals(XdrClaimPredicateType::NOT, $decodedNot->getType()->getValue());
        $this->assertNotNull($decodedNot->getNotPredicate());
        $this->assertEquals(
            XdrClaimPredicateType::UNCONDITIONAL,
            $decodedNot->getNotPredicate()->getType()->getValue()
        );

        $decodedAbsTime = $decodedOr->getOrPredicates()[1];
        $this->assertEquals(1700000000, $decodedAbsTime->getAbsBefore());

        $decodedRelTime = $decoded->getAndPredicates()[1];
        $this->assertEquals(86400, $decodedRelTime->getRelBefore());
    }
}
