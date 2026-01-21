<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrAccountEntryV2;
use Soneso\StellarSDK\Xdr\XdrAccountEntryV2Ext;
use Soneso\StellarSDK\Xdr\XdrAccountEntryV3;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrExtensionPoint;
use Soneso\StellarSDK\Xdr\XdrLiabilities;
use Soneso\StellarSDK\Xdr\XdrTrustLineEntry;
use Soneso\StellarSDK\Xdr\XdrTrustLineEntryExt;
use Soneso\StellarSDK\Xdr\XdrTrustLineEntryV1;
use Soneso\StellarSDK\Xdr\XdrTrustLineEntryV1Ext;
use Soneso\StellarSDK\Xdr\XdrTrustlineAsset;

class XdrLedgerEntryExtTest extends TestCase
{
    private const ACCOUNT_ID_1 = "GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H";
    private const ACCOUNT_ID_2 = "GC5SIC4E3V56VOHJ3OZAX5SJDTWY52JYI2AFK6PUGSXFVRJQYQXXZBZF";

    #[Test]
    public function testTrustLineEntryExtV0RoundTrip(): void
    {
        $ext = new XdrTrustLineEntryExt(0, null);

        $encoded = $ext->encode();
        $decoded = XdrTrustLineEntryExt::decode(new XdrBuffer($encoded));

        $this->assertEquals(0, $decoded->getDiscriminant());
        $this->assertNull($decoded->getV1());
    }

    #[Test]
    public function testTrustLineEntryExtV1RoundTrip(): void
    {
        $liabilities = new XdrLiabilities(
            new BigInteger(1000),
            new BigInteger(2000)
        );
        $v1Ext = new XdrTrustLineEntryV1Ext(0);
        $v1 = new XdrTrustLineEntryV1($liabilities, $v1Ext);

        $ext = new XdrTrustLineEntryExt(1, $v1);

        $encoded = $ext->encode();
        $decoded = XdrTrustLineEntryExt::decode(new XdrBuffer($encoded));

        $this->assertEquals(1, $decoded->getDiscriminant());
        $this->assertNotNull($decoded->getV1());
        $this->assertEquals('1000', $decoded->getV1()->getLiabilities()->getBuying()->toString());
        $this->assertEquals('2000', $decoded->getV1()->getLiabilities()->getSelling()->toString());
    }

    #[Test]
    public function testTrustLineEntryExtGettersAndSetters(): void
    {
        $ext = new XdrTrustLineEntryExt(0, null);

        $this->assertEquals(0, $ext->getDiscriminant());
        $this->assertNull($ext->getV1());

        $ext->setDiscriminant(1);
        $this->assertEquals(1, $ext->getDiscriminant());

        $liabilities = new XdrLiabilities(
            new BigInteger(500),
            new BigInteger(1500)
        );
        $v1Ext = new XdrTrustLineEntryV1Ext(0);
        $v1 = new XdrTrustLineEntryV1($liabilities, $v1Ext);

        $ext->setV1($v1);
        $this->assertNotNull($ext->getV1());
        $this->assertEquals('500', $ext->getV1()->getLiabilities()->getBuying()->toString());
    }

    #[Test]
    public function testTrustLineEntryV1RoundTrip(): void
    {
        $liabilities = new XdrLiabilities(
            new BigInteger(3000),
            new BigInteger(4000)
        );
        $v1Ext = new XdrTrustLineEntryV1Ext(0);
        $v1 = new XdrTrustLineEntryV1($liabilities, $v1Ext);

        $encoded = $v1->encode();
        $decoded = XdrTrustLineEntryV1::decode(new XdrBuffer($encoded));

        $this->assertEquals('3000', $decoded->getLiabilities()->getBuying()->toString());
        $this->assertEquals('4000', $decoded->getLiabilities()->getSelling()->toString());
        $this->assertEquals(0, $decoded->getExt()->getDiscriminant());
    }

    #[Test]
    public function testTrustLineEntryV1GettersAndSetters(): void
    {
        $liabilities = new XdrLiabilities(
            new BigInteger(100),
            new BigInteger(200)
        );
        $v1Ext = new XdrTrustLineEntryV1Ext(0);
        $v1 = new XdrTrustLineEntryV1($liabilities, $v1Ext);

        $this->assertEquals('100', $v1->getLiabilities()->getBuying()->toString());
        $this->assertEquals('200', $v1->getLiabilities()->getSelling()->toString());

        $newLiabilities = new XdrLiabilities(
            new BigInteger(300),
            new BigInteger(400)
        );
        $v1->setLiabilities($newLiabilities);

        $this->assertEquals('300', $v1->getLiabilities()->getBuying()->toString());
        $this->assertEquals('400', $v1->getLiabilities()->getSelling()->toString());

        $newExt = new XdrTrustLineEntryV1Ext(2);
        $v1->setExt($newExt);

        $this->assertEquals(2, $v1->getExt()->getDiscriminant());
    }

    #[Test]
    public function testTrustLineEntryV1ExtRoundTrip(): void
    {
        $ext = new XdrTrustLineEntryV1Ext(0);

        $encoded = $ext->encode();
        $decoded = XdrTrustLineEntryV1Ext::decode(new XdrBuffer($encoded));

        $this->assertEquals(0, $decoded->getDiscriminant());
    }

    #[Test]
    public function testTrustLineEntryV1ExtGettersAndSetters(): void
    {
        $ext = new XdrTrustLineEntryV1Ext(0);

        $this->assertEquals(0, $ext->getDiscriminant());

        $ext->setDiscriminant(2);
        $this->assertEquals(2, $ext->getDiscriminant());
    }

    #[Test]
    public function testTrustLineEntryFullRoundTrip(): void
    {
        $accountId = XdrAccountID::fromAccountId(self::ACCOUNT_ID_1);
        $asset = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $balance = 10000000;
        $limit = 100000000;
        $flags = 1;
        $ext = new XdrTrustLineEntryExt(0, null);

        $trustLine = new XdrTrustLineEntry($accountId, $asset, $balance, $limit, $flags, $ext);

        $encoded = $trustLine->encode();
        $decoded = XdrTrustLineEntry::decode(new XdrBuffer($encoded));

        $this->assertEquals(self::ACCOUNT_ID_1, $decoded->getAccountID()->getAccountId());
        $this->assertEquals(XdrAssetType::ASSET_TYPE_NATIVE, $decoded->getAsset()->getType()->getValue());
        $this->assertEquals($balance, $decoded->getBalance());
        $this->assertEquals($limit, $decoded->getLimit());
        $this->assertEquals($flags, $decoded->getFlags());
        $this->assertEquals(0, $decoded->getExt()->getDiscriminant());
    }

    #[Test]
    public function testTrustLineEntryWithV1Extension(): void
    {
        $accountId = XdrAccountID::fromAccountId(self::ACCOUNT_ID_1);
        $asset = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $balance = 50000000;
        $limit = 500000000;
        $flags = 2;

        $liabilities = new XdrLiabilities(
            new BigInteger(1000000),
            new BigInteger(2000000)
        );
        $v1Ext = new XdrTrustLineEntryV1Ext(0);
        $v1 = new XdrTrustLineEntryV1($liabilities, $v1Ext);
        $ext = new XdrTrustLineEntryExt(1, $v1);

        $trustLine = new XdrTrustLineEntry($accountId, $asset, $balance, $limit, $flags, $ext);

        $encoded = $trustLine->encode();
        $decoded = XdrTrustLineEntry::decode(new XdrBuffer($encoded));

        $this->assertEquals(self::ACCOUNT_ID_1, $decoded->getAccountID()->getAccountId());
        $this->assertEquals($balance, $decoded->getBalance());
        $this->assertEquals(1, $decoded->getExt()->getDiscriminant());
        $this->assertNotNull($decoded->getExt()->getV1());
        $this->assertEquals('1000000', $decoded->getExt()->getV1()->getLiabilities()->getBuying()->toString());
        $this->assertEquals('2000000', $decoded->getExt()->getV1()->getLiabilities()->getSelling()->toString());
    }

    #[Test]
    public function testAccountEntryV2RoundTrip(): void
    {
        $numSponsored = 5;
        $numSponsoring = 10;
        $signerSponsoringIDs = [];
        $ext = new XdrAccountEntryV2Ext(0, null);

        $accountEntryV2 = new XdrAccountEntryV2($numSponsored, $numSponsoring, $signerSponsoringIDs, $ext);

        $encoded = $accountEntryV2->encode();
        $decoded = XdrAccountEntryV2::decode(new XdrBuffer($encoded));

        $this->assertEquals($numSponsored, $decoded->getNumSponsored());
        $this->assertEquals($numSponsoring, $decoded->getNumSponsoring());
        $this->assertCount(0, $decoded->getSignerSponsoringIDs());
        $this->assertEquals(0, $decoded->getExt()->getDiscriminant());
    }

    #[Test]
    public function testAccountEntryV2WithSignerSponsoringIDs(): void
    {
        $numSponsored = 2;
        $numSponsoring = 3;
        $signerSponsoringIDs = [
            XdrAccountID::fromAccountId(self::ACCOUNT_ID_1),
            null,
            XdrAccountID::fromAccountId(self::ACCOUNT_ID_2)
        ];
        $ext = new XdrAccountEntryV2Ext(0, null);

        $accountEntryV2 = new XdrAccountEntryV2($numSponsored, $numSponsoring, $signerSponsoringIDs, $ext);

        $encoded = $accountEntryV2->encode();
        $decoded = XdrAccountEntryV2::decode(new XdrBuffer($encoded));

        $this->assertEquals($numSponsored, $decoded->getNumSponsored());
        $this->assertEquals($numSponsoring, $decoded->getNumSponsoring());
        $this->assertCount(3, $decoded->getSignerSponsoringIDs());
        $this->assertEquals(self::ACCOUNT_ID_1, $decoded->getSignerSponsoringIDs()[0]->getAccountId());
        $this->assertNull($decoded->getSignerSponsoringIDs()[1]);
        $this->assertEquals(self::ACCOUNT_ID_2, $decoded->getSignerSponsoringIDs()[2]->getAccountId());
    }

    #[Test]
    public function testAccountEntryV2GettersAndSetters(): void
    {
        $accountEntryV2 = new XdrAccountEntryV2(0, 0, [], new XdrAccountEntryV2Ext(0, null));

        $this->assertEquals(0, $accountEntryV2->getNumSponsored());
        $this->assertEquals(0, $accountEntryV2->getNumSponsoring());

        $accountEntryV2->setNumSponsored(15);
        $accountEntryV2->setNumSponsoring(20);

        $this->assertEquals(15, $accountEntryV2->getNumSponsored());
        $this->assertEquals(20, $accountEntryV2->getNumSponsoring());

        $newSigners = [XdrAccountID::fromAccountId(self::ACCOUNT_ID_1)];
        $accountEntryV2->setSignerSponsoringIDs($newSigners);

        $this->assertCount(1, $accountEntryV2->getSignerSponsoringIDs());

        $newExt = new XdrAccountEntryV2Ext(3, null);
        $accountEntryV2->setExt($newExt);

        $this->assertEquals(3, $accountEntryV2->getExt()->getDiscriminant());
    }

    #[Test]
    public function testAccountEntryV2ExtRoundTrip(): void
    {
        $ext = new XdrAccountEntryV2Ext(0, null);

        $encoded = $ext->encode();
        $decoded = XdrAccountEntryV2Ext::decode(new XdrBuffer($encoded));

        $this->assertEquals(0, $decoded->getDiscriminant());
        $this->assertNull($decoded->getV3());
    }

    #[Test]
    public function testAccountEntryV2ExtWithV3(): void
    {
        $extensionPoint = new XdrExtensionPoint(0);
        $seqLedger = 12345;
        $seqTime = 1234567890;
        $v3 = new XdrAccountEntryV3($extensionPoint, $seqLedger, $seqTime);

        $ext = new XdrAccountEntryV2Ext(3, $v3);

        $encoded = $ext->encode();
        $decoded = XdrAccountEntryV2Ext::decode(new XdrBuffer($encoded));

        $this->assertEquals(3, $decoded->getDiscriminant());
        $this->assertNotNull($decoded->getV3());
        $this->assertEquals($seqLedger, $decoded->getV3()->getSeqLedger());
        $this->assertEquals($seqTime, $decoded->getV3()->getSeqTime());
    }

    #[Test]
    public function testAccountEntryV2ExtGettersAndSetters(): void
    {
        $ext = new XdrAccountEntryV2Ext(0, null);

        $this->assertEquals(0, $ext->getDiscriminant());
        $this->assertNull($ext->getV3());

        $ext->setDiscriminant(3);
        $this->assertEquals(3, $ext->getDiscriminant());

        $extensionPoint = new XdrExtensionPoint(0);
        $v3 = new XdrAccountEntryV3($extensionPoint, 100, 200);
        $ext->setV3($v3);

        $this->assertNotNull($ext->getV3());
        $this->assertEquals(100, $ext->getV3()->getSeqLedger());
    }

    #[Test]
    public function testAccountEntryV3RoundTrip(): void
    {
        $extensionPoint = new XdrExtensionPoint(0);
        $seqLedger = 54321;
        $seqTime = 9876543210;

        $v3 = new XdrAccountEntryV3($extensionPoint, $seqLedger, $seqTime);

        $encoded = $v3->encode();
        $decoded = XdrAccountEntryV3::decode(new XdrBuffer($encoded));

        $this->assertEquals(0, $decoded->getExt()->getDiscriminant());
        $this->assertEquals($seqLedger, $decoded->getSeqLedger());
        $this->assertEquals($seqTime, $decoded->getSeqTime());
    }

    #[Test]
    public function testAccountEntryV3GettersAndSetters(): void
    {
        $extensionPoint = new XdrExtensionPoint(0);
        $v3 = new XdrAccountEntryV3($extensionPoint, 0, 0);

        $this->assertEquals(0, $v3->getSeqLedger());
        $this->assertEquals(0, $v3->getSeqTime());
        $this->assertEquals(0, $v3->getExt()->getDiscriminant());

        $v3->setSeqLedger(999);
        $v3->setSeqTime(888);

        $this->assertEquals(999, $v3->getSeqLedger());
        $this->assertEquals(888, $v3->getSeqTime());

        $newExt = new XdrExtensionPoint(1);
        $v3->setExt($newExt);

        $this->assertEquals(1, $v3->getExt()->getDiscriminant());
    }

    #[Test]
    public function testLiabilitiesRoundTrip(): void
    {
        $buying = new BigInteger(123456789);
        $selling = new BigInteger(987654321);

        $liabilities = new XdrLiabilities($buying, $selling);

        $encoded = $liabilities->encode();
        $decoded = XdrLiabilities::decode(new XdrBuffer($encoded));

        $this->assertEquals($buying->toString(), $decoded->getBuying()->toString());
        $this->assertEquals($selling->toString(), $decoded->getSelling()->toString());
    }

    #[Test]
    public function testLiabilitiesGettersAndSetters(): void
    {
        $liabilities = new XdrLiabilities(
            new BigInteger(1000),
            new BigInteger(2000)
        );

        $this->assertEquals('1000', $liabilities->getBuying()->toString());
        $this->assertEquals('2000', $liabilities->getSelling()->toString());

        $liabilities->setBuying(new BigInteger(3000));
        $liabilities->setSelling(new BigInteger(4000));

        $this->assertEquals('3000', $liabilities->getBuying()->toString());
        $this->assertEquals('4000', $liabilities->getSelling()->toString());
    }

    #[Test]
    public function testExtensionPointRoundTrip(): void
    {
        $ext = new XdrExtensionPoint(0);

        $encoded = $ext->encode();
        $decoded = XdrExtensionPoint::decode(new XdrBuffer($encoded));

        $this->assertEquals(0, $decoded->getDiscriminant());
    }

    #[Test]
    public function testExtensionPointGettersAndSetters(): void
    {
        $ext = new XdrExtensionPoint(0);

        $this->assertEquals(0, $ext->getDiscriminant());

        $ext->setDiscriminant(1);
        $this->assertEquals(1, $ext->getDiscriminant());
    }

    #[Test]
    public function testAccountEntryV2WithV3Extension(): void
    {
        $extensionPoint = new XdrExtensionPoint(0);
        $v3 = new XdrAccountEntryV3($extensionPoint, 77777, 88888);
        $ext = new XdrAccountEntryV2Ext(3, $v3);

        $numSponsored = 1;
        $numSponsoring = 2;
        $signerSponsoringIDs = [XdrAccountID::fromAccountId(self::ACCOUNT_ID_1)];

        $accountEntryV2 = new XdrAccountEntryV2($numSponsored, $numSponsoring, $signerSponsoringIDs, $ext);

        $encoded = $accountEntryV2->encode();
        $decoded = XdrAccountEntryV2::decode(new XdrBuffer($encoded));

        $this->assertEquals($numSponsored, $decoded->getNumSponsored());
        $this->assertEquals($numSponsoring, $decoded->getNumSponsoring());
        $this->assertEquals(3, $decoded->getExt()->getDiscriminant());
        $this->assertNotNull($decoded->getExt()->getV3());
        $this->assertEquals(77777, $decoded->getExt()->getV3()->getSeqLedger());
        $this->assertEquals(88888, $decoded->getExt()->getV3()->getSeqTime());
    }
}
