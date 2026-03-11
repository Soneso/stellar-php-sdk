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
        $ext = new XdrClaimableBalanceEntryExt(0);

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
