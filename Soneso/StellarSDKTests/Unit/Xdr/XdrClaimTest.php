<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Crypto\StrKey;
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
use Soneso\StellarSDK\Xdr\XdrSCAddress;

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
        $balanceId = XdrClaimableBalanceID::forClaimableBalanceId(self::TEST_BALANCE_ID);

        $paddedHex = $balanceId->getPaddedBalanceIdHex();

        $this->assertEquals(72, strlen($paddedHex));
        // The eight leading zeros are the 4-byte XDR union discriminant naming
        // CLAIMABLE_BALANCE_ID_TYPE_V0; the rest is the balance hash unchanged.
        $this->assertEquals('00000000' . self::TEST_BALANCE_ID, $paddedHex);
    }

    public function testXdrClaimableBalanceIDPaddedBalanceIdHexRejectsAShortHash(): void
    {
        // Padding a hash shorter than 32 bytes would invent hash material and hand back
        // an id Horizon would answer for a different balance.
        $type = XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0();
        $balanceId = new XdrClaimableBalanceID($type, '1234567890abcdef');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Claimable balance id must be 58 characters as a "B..." strkey, or the balance'
            . ' hash as 64 hexadecimal characters, which a type discriminant may prefix to'
            . ' 66 or 72 characters; 16 characters given'
        );
        $balanceId->getPaddedBalanceIdHex();
    }

    public function testXdrClaimableBalanceIDReadersAgreeOnEverySpellingOfTheHash(): void
    {
        $hashHex = 'ceab14eebbdbfe25a1830e39e311c2180846df74947ba24a386b8314ccba6622';
        $strKey = StrKey::encodeClaimableBalanceIdHex('00' . $hashHex);
        $spellings = [
            'bare hash as 64 hexadecimal characters' => $hashHex,
            'hash behind the 1-byte strkey discriminant' => '00' . $hashHex,
            'hash behind the 4-byte XDR discriminant' => '00000000' . $hashHex,
            '"B..." strkey' => $strKey,
        ];

        $expectedXdr = XdrClaimableBalanceID::forClaimableBalanceId($hashHex)->encode();

        foreach ($spellings as $label => $value) {
            $balanceId = XdrClaimableBalanceID::forClaimableBalanceId($value);

            $this->assertEquals($expectedXdr, $balanceId->encode(), $label);
            $this->assertEquals($strKey, $balanceId->toJsonValue(), $label);
            $this->assertEquals($strKey, XdrSCAddress::forClaimableBalanceId($value)->toStrKey(), $label);
            $this->assertEquals('00000000' . $hashHex, $balanceId->getPaddedBalanceIdHex(), $label);

            $lines = [];
            $balanceId->toTxRep('bal', $lines);
            $this->assertEquals(
                [
                    'bal.type' => 'CLAIMABLE_BALANCE_ID_TYPE_V0',
                    'bal.v0' => $hashHex,
                ],
                $lines,
                $label
            );

            $decoded = XdrClaimableBalanceID::decode(new XdrBuffer($balanceId->encode()));
            $this->assertEquals($hashHex, $decoded->getHash(), $label);
        }
    }

    public function testXdrClaimableBalanceIDRejectsANonZeroTypePrefix(): void
    {
        // A prefixed spelling carries the union discriminant ahead of the hash, and
        // CLAIMABLE_BALANCE_ID_TYPE_V0 (0) is the only case ClaimableBalanceID defines.
        // A prefix naming any other type has to be refused rather than dropped, which
        // would relabel the id as V0 and denote a balance the caller never named.
        $hashHex = 'ceab14eebbdbfe25a1830e39e311c2180846df74947ba24a386b8314ccba6622';

        foreach (['00000001', 'ff'] as $prefix) {
            $value = $prefix . $hashHex;
            $expected = sprintf(
                'Claimable balance id carries the type prefix "%s", which does not name '
                    . 'CLAIMABLE_BALANCE_ID_TYPE_V0 (0), the only case ClaimableBalanceID has',
                $prefix
            );

            $readers = [
                'encode' => function () use ($value) {
                    XdrClaimableBalanceID::forClaimableBalanceId($value)->encode();
                },
                'toJsonValue' => function () use ($value) {
                    XdrClaimableBalanceID::forClaimableBalanceId($value)->toJsonValue();
                },
                'getPaddedBalanceIdHex' => function () use ($value) {
                    XdrClaimableBalanceID::forClaimableBalanceId($value)->getPaddedBalanceIdHex();
                },
                'toTxRep' => function () use ($value) {
                    $lines = [];
                    XdrClaimableBalanceID::forClaimableBalanceId($value)->toTxRep('bal', $lines);
                },
                'toStrKey' => function () use ($value) {
                    XdrSCAddress::forClaimableBalanceId($value)->toStrKey();
                },
            ];

            foreach ($readers as $label => $reader) {
                try {
                    $reader();
                    $this->fail($label . ' accepted the type prefix ' . $prefix);
                } catch (InvalidArgumentException $e) {
                    $this->assertEquals($expected, $e->getMessage(), $label);
                }
            }
        }
    }

    public function testXdrClaimableBalanceIDReadersAgreeOnEitherCaseOfTheHash(): void
    {
        // Hexadecimal is case insensitive, so the two spellings denote one balance.
        // Every reader has to report it as one string, including the two that hand the
        // hexadecimal on unchanged: the TxRep form and the padded hex.
        $lowerHex = 'ceab14eebbdbfe25a1830e39e311c2180846df74947ba24a386b8314ccba6622';
        $upperHex = strtoupper($lowerHex);

        $lower = XdrClaimableBalanceID::forClaimableBalanceId($lowerHex);
        $upper = XdrClaimableBalanceID::forClaimableBalanceId($upperHex);

        $this->assertEquals($lower->encode(), $upper->encode());
        $this->assertEquals($lower->toJsonValue(), $upper->toJsonValue());
        $this->assertEquals(
            XdrSCAddress::forClaimableBalanceId($lowerHex)->toStrKey(),
            XdrSCAddress::forClaimableBalanceId($upperHex)->toStrKey()
        );
        $this->assertEquals($lower->getPaddedBalanceIdHex(), $upper->getPaddedBalanceIdHex());

        $lowerLines = [];
        $lower->toTxRep('bal', $lowerLines);
        $upperLines = [];
        $upper->toTxRep('bal', $upperLines);
        $this->assertEquals($lowerLines, $upperLines);

        // The shared spelling is the lower case one, which is what SEP-11 TxRep and
        // Horizon carry.
        $this->assertEquals('00000000' . $lowerHex, $upper->getPaddedBalanceIdHex());
        $this->assertEquals(
            [
                'bal.type' => 'CLAIMABLE_BALANCE_ID_TYPE_V0',
                'bal.v0' => $lowerHex,
            ],
            $upperLines
        );

        // The longer hexadecimal spellings normalise the same way.
        $this->assertEquals(
            '00000000' . $lowerHex,
            XdrClaimableBalanceID::forClaimableBalanceId('00' . $upperHex)->getPaddedBalanceIdHex()
        );
        $this->assertEquals(
            '00000000' . $lowerHex,
            XdrClaimableBalanceID::forClaimableBalanceId('00000000' . $upperHex)->getPaddedBalanceIdHex()
        );
    }

    public function testXdrClaimableBalanceIDRejectsAnUnsetHash(): void
    {
        $id = new XdrClaimableBalanceID(
            XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0(),
            ''
        );
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Claimable balance id is not set');
        $id->getCanonicalHashHex();
    }

    /**
     * A 58-character value is read as a strkey, so a non-hex one is refused with the
     * strkey rejection rather than the spelling list.
     */
    public function testXdrClaimableBalanceIDReportsTheStrkeyRejectionForAStrkeyShapedNonHexId(): void
    {
        $id = new XdrClaimableBalanceID(
            XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0(),
            'B' . str_repeat('Z', 57)
        );
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid encoded string');
        $id->getCanonicalHashHex();
    }

    public function testXdrClaimableBalanceIDRejectsANonHexId(): void
    {
        $id = new XdrClaimableBalanceID(
            XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0(),
            'not a balance id'
        );
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a "B..." strkey or a hexadecimal string');
        $id->getCanonicalHashHex();
    }

    public function testXdrClaimableBalanceIDToJsonValueRejectsAnUnknownDiscriminant(): void
    {
        $id = new XdrClaimableBalanceID(
            new XdrClaimableBalanceIDType(7),
            '00' . self::TEST_BALANCE_ID
        );
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown XdrClaimableBalanceID discriminant: 7');
        $id->toJsonValue();
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
