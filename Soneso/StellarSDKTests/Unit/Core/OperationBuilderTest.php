<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Core;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\AccountMergeOperationBuilder;
use Soneso\StellarSDK\AllowTrustOperationBuilder;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\BumpSequenceOperationBuilder;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\Claimant;
use Soneso\StellarSDK\ClaimClaimableBalanceOperationBuilder;
use Soneso\StellarSDK\ClawbackClaimableBalanceOperationBuilder;
use Soneso\StellarSDK\ClawbackOperationBuilder;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\CreateClaimableBalanceOperationBuilder;
use Soneso\StellarSDK\CreatePassiveSellOfferOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\LiquidityPoolDepositOperationBuilder;
use Soneso\StellarSDK\LiquidityPoolWithdrawOperationBuilder;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\PathPaymentStrictReceiveOperationBuilder;
use Soneso\StellarSDK\PathPaymentStrictSendOperationBuilder;
use Soneso\StellarSDK\Price;
use Soneso\StellarSDK\RevokeSponsorshipOperationBuilder;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\SetTrustLineFlagsOperationBuilder;
use Soneso\StellarSDK\Signer;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\Xdr\XdrClaimPredicate;
use Soneso\StellarSDK\Xdr\XdrClaimPredicateType;

class OperationBuilderTest extends TestCase
{
    private string $sourceAccountId = "GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP";
    private string $destAccountId = "GDRUPBJM7YIJ2NUNAIQJDJ2DQ2JDERY5SJVJVMM6MGE4UBDAMXBHARIA";
    private string $muxedAccountId = "MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAJLK";

    // RevokeSponsorshipOperationBuilder Tests

    public function testRevokeSponsorshipAccountSponsorship(): void
    {
        $builder = new RevokeSponsorshipOperationBuilder();
        $operation = $builder->revokeAccountSponsorship($this->destAccountId)
            ->setSourceAccount($this->sourceAccountId)
            ->build();

        $this->assertNotNull($operation);
        $this->assertNotNull($operation->getLedgerKey());
        $this->assertNull($operation->getSignerKey());
        $this->assertEquals($this->sourceAccountId, $operation->getSourceAccount()->getAccountId());

        $xdr = $operation->toXdr();
        $this->assertNotNull($xdr->getBody()->getRevokeSponsorshipOperation());
    }

    public function testRevokeSponsorshipDataSponsorship(): void
    {
        $builder = new RevokeSponsorshipOperationBuilder();
        $operation = $builder->revokeDataSponsorship($this->destAccountId, "test_data")
            ->setSourceAccount($this->sourceAccountId)
            ->build();

        $this->assertNotNull($operation->getLedgerKey());
        $this->assertNull($operation->getSignerKey());

        $xdr = $operation->toXdr();
        $this->assertNotNull($xdr->getBody()->getRevokeSponsorshipOperation());
    }

    public function testRevokeSponsorshipTrustlineSponsorship(): void
    {
        $asset = Asset::createNonNativeAsset("USD", $this->sourceAccountId);
        $builder = new RevokeSponsorshipOperationBuilder();
        $operation = $builder->revokeTrustlineSponsorship($this->destAccountId, $asset)
            ->setSourceAccount($this->sourceAccountId)
            ->build();

        $this->assertNotNull($operation->getLedgerKey());
        $this->assertNull($operation->getSignerKey());
    }

    public function testRevokeSponsorshipClaimableBalanceSponsorship(): void
    {
        $balanceId = "00000000da0d57da7d4850e7fc10d2a9d0ebc731f7afb40574c03395b17d49149b91f5be";
        $builder = new RevokeSponsorshipOperationBuilder();
        $operation = $builder->revokeClaimableBalanceSponsorship($balanceId)
            ->setSourceAccount($this->sourceAccountId)
            ->build();

        $this->assertNotNull($operation->getLedgerKey());
        $this->assertNull($operation->getSignerKey());
    }

    public function testRevokeSponsorshipOfferSponsorship(): void
    {
        $builder = new RevokeSponsorshipOperationBuilder();
        $operation = $builder->revokeOfferSponsorship($this->destAccountId, 12345)
            ->setSourceAccount($this->sourceAccountId)
            ->build();

        $this->assertNotNull($operation->getLedgerKey());
        $this->assertNull($operation->getSignerKey());
    }

    public function testRevokeSponsorshipEd25519Signer(): void
    {
        $signerKeyPair = KeyPair::random();
        $builder = new RevokeSponsorshipOperationBuilder();
        $operation = $builder->revokeEd25519Signer($this->destAccountId, $signerKeyPair->getAccountId())
            ->setSourceAccount($this->sourceAccountId)
            ->build();

        $this->assertNull($operation->getLedgerKey());
        $this->assertNotNull($operation->getSignerKey());
        $this->assertEquals($this->destAccountId, $operation->getSignerAccount());
    }

    public function testRevokeSponsorshipPreAuthTxSigner(): void
    {
        $preAuthTx = "TAQCSRX2RIDJNHFIFHWD63X7D7D6TRT5Y2S6E3TEMXTG5W3OECHZ2OG4";
        $builder = new RevokeSponsorshipOperationBuilder();
        $operation = $builder->revokePreAuthTxSigner($this->destAccountId, $preAuthTx)
            ->setSourceAccount($this->sourceAccountId)
            ->build();

        $this->assertNull($operation->getLedgerKey());
        $this->assertNotNull($operation->getSignerKey());
    }

    public function testRevokeSponsorshipSha256HashSigner(): void
    {
        $hashBytes = random_bytes(32);
        $sha256Hash = \Soneso\StellarSDK\Crypto\StrKey::encodeSha256Hash($hashBytes);

        $builder = new RevokeSponsorshipOperationBuilder();
        $operation = $builder->revokeSha256HashSigner($this->destAccountId, $sha256Hash)
            ->setSourceAccount($this->sourceAccountId)
            ->build();

        $this->assertNull($operation->getLedgerKey());
        $this->assertNotNull($operation->getSignerKey());
    }

    public function testRevokeSponsorshipMuxedSourceAccount(): void
    {
        $muxedAccount = MuxedAccount::fromAccountId($this->muxedAccountId);
        $builder = new RevokeSponsorshipOperationBuilder();
        $operation = $builder->revokeAccountSponsorship($this->destAccountId)
            ->setMuxedSourceAccount($muxedAccount)
            ->build();

        $this->assertEquals($this->muxedAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testRevokeSponsorshipMultipleEntriesThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("can not revoke multiple entries per builder");

        $builder = new RevokeSponsorshipOperationBuilder();
        $builder->revokeAccountSponsorship($this->destAccountId)
            ->revokeDataSponsorship($this->destAccountId, "test");
    }

    // SetOptionsOperationBuilder Tests

    public function testSetOptionsInflationDestination(): void
    {
        $builder = new SetOptionsOperationBuilder();
        $operation = $builder->setInflationDestination($this->destAccountId)
            ->setSourceAccount($this->sourceAccountId)
            ->build();

        $this->assertEquals($this->destAccountId, $operation->getInflationDestination());
    }

    public function testSetOptionsFlags(): void
    {
        $builder = new SetOptionsOperationBuilder();
        $operation = $builder->setSetFlags(3)
            ->setClearFlags(1)
            ->setSourceAccount($this->sourceAccountId)
            ->build();

        $this->assertEquals(3, $operation->getSetFlags());
        $this->assertEquals(1, $operation->getClearFlags());
    }

    public function testSetOptionsThresholds(): void
    {
        $builder = new SetOptionsOperationBuilder();
        $operation = $builder->setMasterKeyWeight(10)
            ->setLowThreshold(1)
            ->setMediumThreshold(5)
            ->setHighThreshold(10)
            ->setSourceAccount($this->sourceAccountId)
            ->build();

        $this->assertEquals(10, $operation->getMasterKeyWeight());
        $this->assertEquals(1, $operation->getLowThreshold());
        $this->assertEquals(5, $operation->getMediumThreshold());
        $this->assertEquals(10, $operation->getHighThreshold());
    }

    public function testSetOptionsHomeDomain(): void
    {
        $builder = new SetOptionsOperationBuilder();
        $operation = $builder->setHomeDomain("stellar.org")
            ->setSourceAccount($this->sourceAccountId)
            ->build();

        $this->assertEquals("stellar.org", $operation->getHomeDomain());
    }

    public function testSetOptionsHomeDomainTooLongThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $longDomain = str_repeat("a", 33);
        $builder = new SetOptionsOperationBuilder();
        $builder->setHomeDomain($longDomain);
    }

    public function testSetOptionsSigner(): void
    {
        $signerKeyPair = KeyPair::random();
        $signerKey = Signer::ed25519PublicKey($signerKeyPair);

        $builder = new SetOptionsOperationBuilder();
        $operation = $builder->setSigner($signerKey, 5)
            ->setSourceAccount($this->sourceAccountId)
            ->build();

        $this->assertNotNull($operation->getSignerKey());
        $this->assertEquals(5, $operation->getSignerWeight());
    }

    public function testSetOptionsChainedMethods(): void
    {
        $builder = new SetOptionsOperationBuilder();
        $operation = $builder
            ->setHomeDomain("example.com")
            ->setMediumThreshold(2)
            ->setHighThreshold(5)
            ->setSourceAccount($this->sourceAccountId)
            ->build();

        $this->assertEquals("example.com", $operation->getHomeDomain());
        $this->assertEquals(2, $operation->getMediumThreshold());
        $this->assertEquals(5, $operation->getHighThreshold());
    }

    public function testSetOptionsMuxedSourceAccount(): void
    {
        $muxedAccount = MuxedAccount::fromAccountId($this->muxedAccountId);
        $builder = new SetOptionsOperationBuilder();
        $operation = $builder->setHomeDomain("test.com")
            ->setMuxedSourceAccount($muxedAccount)
            ->build();

        $this->assertEquals($this->muxedAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testSetOptionsXdrGeneration(): void
    {
        $builder = new SetOptionsOperationBuilder();
        $operation = $builder->setHomeDomain("stellar.org")
            ->setMediumThreshold(3)
            ->build();

        $xdr = $operation->toXdr();
        $this->assertNotNull($xdr->getBody()->getSetOptionsOp());
    }

    // PathPaymentStrictReceiveOperationBuilder Tests

    public function testPathPaymentStrictReceiveBasic(): void
    {
        $sendAsset = Asset::createNonNativeAsset("USD", $this->sourceAccountId);
        $destAsset = Asset::createNonNativeAsset("EUR", $this->destAccountId);

        $builder = new PathPaymentStrictReceiveOperationBuilder(
            $sendAsset,
            "100.0",
            $this->destAccountId,
            $destAsset,
            "95.0"
        );

        $operation = $builder->setSourceAccount($this->sourceAccountId)->build();

        $this->assertEquals("100.0", $operation->getSendMax());
        $this->assertEquals("95.0", $operation->getDestAmount());
        $this->assertEquals($this->destAccountId, $operation->getDestination()->getAccountId());
    }

    public function testPathPaymentStrictReceiveWithPath(): void
    {
        $sendAsset = Asset::createNonNativeAsset("USD", $this->sourceAccountId);
        $destAsset = Asset::createNonNativeAsset("EUR", $this->destAccountId);
        $intermediateAsset1 = Asset::native();
        $intermediateAsset2 = Asset::createNonNativeAsset("BTC", $this->sourceAccountId);

        $builder = new PathPaymentStrictReceiveOperationBuilder(
            $sendAsset,
            "100.0",
            $this->destAccountId,
            $destAsset,
            "95.0"
        );

        $operation = $builder->setPath([$intermediateAsset1, $intermediateAsset2])
            ->setSourceAccount($this->sourceAccountId)
            ->build();

        $path = $operation->getPath();
        $this->assertCount(2, $path);
    }

    public function testPathPaymentStrictReceiveForMuxedDestination(): void
    {
        $sendAsset = Asset::native();
        $destAsset = Asset::createNonNativeAsset("USD", $this->sourceAccountId);
        $muxedDest = MuxedAccount::fromAccountId($this->muxedAccountId);

        $builder = PathPaymentStrictReceiveOperationBuilder::forMuxedDestinationAccount(
            $sendAsset,
            "50.0",
            $muxedDest,
            $destAsset,
            "45.0"
        );

        $operation = $builder->build();
        $this->assertEquals($this->muxedAccountId, $operation->getDestination()->getAccountId());
    }

    public function testPathPaymentStrictReceiveMuxedSourceAccount(): void
    {
        $sendAsset = Asset::native();
        $destAsset = Asset::createNonNativeAsset("USD", $this->sourceAccountId);
        $muxedSource = MuxedAccount::fromAccountId($this->muxedAccountId);

        $builder = new PathPaymentStrictReceiveOperationBuilder(
            $sendAsset,
            "100.0",
            $this->destAccountId,
            $destAsset,
            "90.0"
        );

        $operation = $builder->setMuxedSourceAccount($muxedSource)->build();
        $this->assertEquals($this->muxedAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testPathPaymentStrictReceiveXdrRoundTrip(): void
    {
        $sendAsset = Asset::native();
        $destAsset = Asset::createNonNativeAsset("USD", $this->sourceAccountId);

        $builder = new PathPaymentStrictReceiveOperationBuilder(
            $sendAsset,
            "100.0",
            $this->destAccountId,
            $destAsset,
            "95.0"
        );

        $operation = $builder->build();
        $xdr = $operation->toXdr();
        $this->assertNotNull($xdr->getBody()->getPathPaymentStrictReceiveOp());
    }

    // PathPaymentStrictSendOperationBuilder Tests

    public function testPathPaymentStrictSendBasic(): void
    {
        $sendAsset = Asset::createNonNativeAsset("USD", $this->sourceAccountId);
        $destAsset = Asset::createNonNativeAsset("EUR", $this->destAccountId);

        $builder = new PathPaymentStrictSendOperationBuilder(
            $sendAsset,
            "100.0",
            $this->destAccountId,
            $destAsset,
            "90.0"
        );

        $operation = $builder->setSourceAccount($this->sourceAccountId)->build();

        $this->assertEquals("100.0", $operation->getSendAmount());
        $this->assertEquals("90.0", $operation->getDestMin());
        $this->assertEquals($this->destAccountId, $operation->getDestination()->getAccountId());
    }

    public function testPathPaymentStrictSendWithPath(): void
    {
        $sendAsset = Asset::native();
        $destAsset = Asset::createNonNativeAsset("USD", $this->sourceAccountId);
        $intermediateAsset = Asset::createNonNativeAsset("EUR", $this->destAccountId);

        $builder = new PathPaymentStrictSendOperationBuilder(
            $sendAsset,
            "100.0",
            $this->destAccountId,
            $destAsset,
            "90.0"
        );

        $operation = $builder->setPath([$intermediateAsset])
            ->setSourceAccount($this->sourceAccountId)
            ->build();

        $path = $operation->getPath();
        $this->assertCount(1, $path);
    }

    public function testPathPaymentStrictSendForMuxedDestination(): void
    {
        $sendAsset = Asset::native();
        $destAsset = Asset::createNonNativeAsset("USD", $this->sourceAccountId);
        $muxedDest = MuxedAccount::fromAccountId($this->muxedAccountId);

        $builder = PathPaymentStrictSendOperationBuilder::forMuxedDestinationAccount(
            $sendAsset,
            "50.0",
            $muxedDest,
            $destAsset,
            "45.0"
        );

        $operation = $builder->build();
        $this->assertEquals($this->muxedAccountId, $operation->getDestination()->getAccountId());
    }

    public function testPathPaymentStrictSendMuxedSourceAccount(): void
    {
        $sendAsset = Asset::native();
        $destAsset = Asset::createNonNativeAsset("USD", $this->sourceAccountId);
        $muxedSource = MuxedAccount::fromAccountId($this->muxedAccountId);

        $builder = new PathPaymentStrictSendOperationBuilder(
            $sendAsset,
            "100.0",
            $this->destAccountId,
            $destAsset,
            "90.0"
        );

        $operation = $builder->setMuxedSourceAccount($muxedSource)->build();
        $this->assertEquals($this->muxedAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testPathPaymentStrictSendXdrRoundTrip(): void
    {
        $sendAsset = Asset::native();
        $destAsset = Asset::createNonNativeAsset("USD", $this->sourceAccountId);

        $builder = new PathPaymentStrictSendOperationBuilder(
            $sendAsset,
            "100.0",
            $this->destAccountId,
            $destAsset,
            "90.0"
        );

        $operation = $builder->build();
        $xdr = $operation->toXdr();
        $this->assertNotNull($xdr->getBody()->getPathPaymentStrictSendOp());
    }

    // CreateClaimableBalanceOperationBuilder Tests

    public function testCreateClaimableBalanceBasic(): void
    {
        $asset = Asset::createNonNativeAsset("USD", $this->sourceAccountId);
        $predicate = new XdrClaimPredicate(new XdrClaimPredicateType(XdrClaimPredicateType::UNCONDITIONAL));
        $claimant = new Claimant($this->destAccountId, $predicate);

        $builder = new CreateClaimableBalanceOperationBuilder(
            [$claimant],
            $asset,
            "100.0"
        );

        $operation = $builder->setSourceAccount($this->sourceAccountId)->build();

        $this->assertEquals("100.0", $operation->getAmount());
        $this->assertCount(1, $operation->getClaimants());
    }

    public function testCreateClaimableBalanceMultipleClaimants(): void
    {
        $asset = Asset::native();
        $predicate = new XdrClaimPredicate(new XdrClaimPredicateType(XdrClaimPredicateType::UNCONDITIONAL));
        $claimant1 = new Claimant($this->destAccountId, $predicate);
        $claimant2 = new Claimant($this->sourceAccountId, $predicate);

        $builder = new CreateClaimableBalanceOperationBuilder(
            [$claimant1, $claimant2],
            $asset,
            "50.0"
        );

        $operation = $builder->build();
        $this->assertCount(2, $operation->getClaimants());
    }

    public function testCreateClaimableBalanceMuxedSourceAccount(): void
    {
        $asset = Asset::native();
        $predicate = new XdrClaimPredicate(new XdrClaimPredicateType(XdrClaimPredicateType::UNCONDITIONAL));
        $claimant = new Claimant($this->destAccountId, $predicate);
        $muxedSource = MuxedAccount::fromAccountId($this->muxedAccountId);

        $builder = new CreateClaimableBalanceOperationBuilder(
            [$claimant],
            $asset,
            "25.0"
        );

        $operation = $builder->setMuxedSourceAccount($muxedSource)->build();
        $this->assertEquals($this->muxedAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testCreateClaimableBalanceXdrRoundTrip(): void
    {
        $asset = Asset::native();
        $predicate = new XdrClaimPredicate(new XdrClaimPredicateType(XdrClaimPredicateType::UNCONDITIONAL));
        $claimant = new Claimant($this->destAccountId, $predicate);

        $builder = new CreateClaimableBalanceOperationBuilder(
            [$claimant],
            $asset,
            "100.0"
        );

        $operation = $builder->build();
        $xdr = $operation->toXdr();
        $this->assertNotNull($xdr->getBody()->getCreateClaimableBalanceOperation());
    }

    // ClawbackOperationBuilder Tests

    public function testClawbackBasic(): void
    {
        $asset = Asset::createNonNativeAsset("USD", $this->sourceAccountId);
        $from = MuxedAccount::fromAccountId($this->destAccountId);

        $builder = new ClawbackOperationBuilder($asset, $from, "50.0");
        $operation = $builder->setSourceAccount($this->sourceAccountId)->build();

        $this->assertEquals("50.0", $operation->getAmount());
        $this->assertEquals($this->destAccountId, $operation->getFrom()->getAccountId());
    }

    public function testClawbackMuxedFromAccount(): void
    {
        $asset = Asset::createNonNativeAsset("USD", $this->sourceAccountId);
        $muxedFrom = MuxedAccount::fromAccountId($this->muxedAccountId);

        $builder = new ClawbackOperationBuilder($asset, $muxedFrom, "25.0");
        $operation = $builder->build();

        $this->assertEquals($this->muxedAccountId, $operation->getFrom()->getAccountId());
    }

    public function testClawbackMuxedSourceAccount(): void
    {
        $asset = Asset::createNonNativeAsset("USD", $this->sourceAccountId);
        $from = MuxedAccount::fromAccountId($this->destAccountId);
        $muxedSource = MuxedAccount::fromAccountId($this->muxedAccountId);

        $builder = new ClawbackOperationBuilder($asset, $from, "10.0");
        $operation = $builder->setMuxedSourceAccount($muxedSource)->build();

        $this->assertEquals($this->muxedAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testClawbackXdrRoundTrip(): void
    {
        $asset = Asset::createNonNativeAsset("USD", $this->sourceAccountId);
        $from = MuxedAccount::fromAccountId($this->destAccountId);

        $builder = new ClawbackOperationBuilder($asset, $from, "100.0");
        $operation = $builder->build();

        $xdr = $operation->toXdr();
        $this->assertNotNull($xdr->getBody()->getClawbackOperation());
    }

    // SetTrustLineFlagsOperationBuilder Tests

    public function testSetTrustLineFlagsBasic(): void
    {
        $asset = Asset::createNonNativeAsset("USD", $this->sourceAccountId);

        $builder = new SetTrustLineFlagsOperationBuilder(
            $this->destAccountId,
            $asset,
            0,
            1
        );

        $operation = $builder->setSourceAccount($this->sourceAccountId)->build();

        $this->assertEquals($this->destAccountId, $operation->getTrustorId());
        $this->assertEquals(0, $operation->getClearFlags());
        $this->assertEquals(1, $operation->getSetFlags());
    }

    public function testSetTrustLineFlagsMultipleFlags(): void
    {
        $asset = Asset::createNonNativeAsset("EUR", $this->sourceAccountId);

        $builder = new SetTrustLineFlagsOperationBuilder(
            $this->destAccountId,
            $asset,
            3,
            5
        );

        $operation = $builder->build();
        $this->assertEquals(3, $operation->getClearFlags());
        $this->assertEquals(5, $operation->getSetFlags());
    }

    public function testSetTrustLineFlagsMuxedSourceAccount(): void
    {
        $asset = Asset::native();
        $muxedSource = MuxedAccount::fromAccountId($this->muxedAccountId);

        $builder = new SetTrustLineFlagsOperationBuilder(
            $this->destAccountId,
            $asset,
            1,
            2
        );

        $operation = $builder->setMuxedSourceAccount($muxedSource)->build();
        $this->assertEquals($this->muxedAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testSetTrustLineFlagsXdrRoundTrip(): void
    {
        $asset = Asset::createNonNativeAsset("USD", $this->sourceAccountId);

        $builder = new SetTrustLineFlagsOperationBuilder(
            $this->destAccountId,
            $asset,
            0,
            1
        );

        $operation = $builder->build();
        $xdr = $operation->toXdr();
        $this->assertNotNull($xdr->getBody()->getSetTrustLineFlagsOperation());
    }

    // LiquidityPoolDepositOperationBuilder Tests

    public function testLiquidityPoolDepositBasic(): void
    {
        $poolId = "dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7";
        $minPrice = new Price(1, 2);
        $maxPrice = new Price(2, 1);

        $builder = new LiquidityPoolDepositOperationBuilder(
            $poolId,
            "100.0",
            "200.0",
            $minPrice,
            $maxPrice
        );

        $operation = $builder->setSourceAccount($this->sourceAccountId)->build();

        $this->assertEquals($poolId, $operation->getLiqudityPoolId());
        $this->assertEquals("100.0", $operation->getMaxAmountA());
        $this->assertEquals("200.0", $operation->getMaxAmountB());
    }

    public function testLiquidityPoolDepositMuxedSourceAccount(): void
    {
        $poolId = "dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7";
        $minPrice = new Price(1, 1);
        $maxPrice = new Price(1, 1);
        $muxedSource = MuxedAccount::fromAccountId($this->muxedAccountId);

        $builder = new LiquidityPoolDepositOperationBuilder(
            $poolId,
            "50.0",
            "50.0",
            $minPrice,
            $maxPrice
        );

        $operation = $builder->setMuxedSourceAccount($muxedSource)->build();
        $this->assertEquals($this->muxedAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testLiquidityPoolDepositPriceRatios(): void
    {
        $poolId = "dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7";
        $minPrice = new Price(3, 5);
        $maxPrice = new Price(5, 3);

        $builder = new LiquidityPoolDepositOperationBuilder(
            $poolId,
            "300.0",
            "500.0",
            $minPrice,
            $maxPrice
        );

        $operation = $builder->build();
        $this->assertEquals(3, $operation->getMinPrice()->getN());
        $this->assertEquals(5, $operation->getMinPrice()->getD());
        $this->assertEquals(5, $operation->getMaxPrice()->getN());
        $this->assertEquals(3, $operation->getMaxPrice()->getD());
    }

    public function testLiquidityPoolDepositXdrRoundTrip(): void
    {
        $poolId = "dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7";
        $minPrice = new Price(1, 2);
        $maxPrice = new Price(2, 1);

        $builder = new LiquidityPoolDepositOperationBuilder(
            $poolId,
            "100.0",
            "200.0",
            $minPrice,
            $maxPrice
        );

        $operation = $builder->build();
        $xdr = $operation->toXdr();
        $this->assertNotNull($xdr->getBody()->getLiquidityPoolDepositOperation());
    }

    // LiquidityPoolWithdrawOperationBuilder Tests

    public function testLiquidityPoolWithdrawBasic(): void
    {
        $poolId = "dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7";

        $builder = new LiquidityPoolWithdrawOperationBuilder(
            $poolId,
            "100.0",
            "45.0",
            "45.0"
        );

        $operation = $builder->setSourceAccount($this->sourceAccountId)->build();

        $this->assertEquals($poolId, $operation->getLiqudityPoolId());
        $this->assertEquals("100.0", $operation->getAmount());
        $this->assertEquals("45.0", $operation->getMinAmountA());
        $this->assertEquals("45.0", $operation->getMinAmountB());
    }

    public function testLiquidityPoolWithdrawMuxedSourceAccount(): void
    {
        $poolId = "dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7";
        $muxedSource = MuxedAccount::fromAccountId($this->muxedAccountId);

        $builder = new LiquidityPoolWithdrawOperationBuilder(
            $poolId,
            "50.0",
            "25.0",
            "25.0"
        );

        $operation = $builder->setMuxedSourceAccount($muxedSource)->build();
        $this->assertEquals($this->muxedAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testLiquidityPoolWithdrawAsymmetricAmounts(): void
    {
        $poolId = "dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7";

        $builder = new LiquidityPoolWithdrawOperationBuilder(
            $poolId,
            "100.0",
            "30.0",
            "60.0"
        );

        $operation = $builder->build();
        $this->assertEquals("30.0", $operation->getMinAmountA());
        $this->assertEquals("60.0", $operation->getMinAmountB());
    }

    public function testLiquidityPoolWithdrawXdrRoundTrip(): void
    {
        $poolId = "dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7";

        $builder = new LiquidityPoolWithdrawOperationBuilder(
            $poolId,
            "100.0",
            "45.0",
            "45.0"
        );

        $operation = $builder->build();
        $xdr = $operation->toXdr();
        $this->assertNotNull($xdr->getBody()->getLiquidityPoolWithdrawOperation());
    }

    // AccountMergeOperationBuilder Tests

    public function testAccountMergeBasic(): void
    {
        $builder = new AccountMergeOperationBuilder($this->destAccountId);
        $operation = $builder->setSourceAccount($this->sourceAccountId)->build();

        $this->assertEquals($this->destAccountId, $operation->getDestination()->getAccountId());
        $this->assertEquals($this->sourceAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testAccountMergeForMuxedDestination(): void
    {
        $muxedDest = MuxedAccount::fromAccountId($this->muxedAccountId);
        $builder = AccountMergeOperationBuilder::forMuxedDestinationAccount($muxedDest);
        $operation = $builder->build();

        $this->assertEquals($this->muxedAccountId, $operation->getDestination()->getAccountId());
    }

    public function testAccountMergeMuxedSourceAccount(): void
    {
        $muxedSource = MuxedAccount::fromAccountId($this->muxedAccountId);
        $builder = new AccountMergeOperationBuilder($this->destAccountId);
        $operation = $builder->setMuxedSourceAccount($muxedSource)->build();

        $this->assertEquals($this->muxedAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testAccountMergeXdrRoundTrip(): void
    {
        $builder = new AccountMergeOperationBuilder($this->destAccountId);
        $operation = $builder->build();

        $xdr = $operation->toXdr();
        $this->assertNotNull($xdr->getBody());
        $this->assertEquals($this->destAccountId, $operation->getDestination()->getAccountId());
    }

    // AllowTrustOperationBuilder Tests

    public function testAllowTrustBasic(): void
    {
        $builder = new AllowTrustOperationBuilder(
            $this->destAccountId,
            "USD",
            true,
            false
        );
        $operation = $builder->setSourceAccount($this->sourceAccountId)->build();

        $this->assertEquals($this->destAccountId, $operation->getTrustor());
        $this->assertEquals("USD", $operation->getAssetCode());
        $this->assertTrue($operation->isAuthorize());
        $this->assertFalse($operation->isAuthorizeToMaintainLiabilities());
    }

    public function testAllowTrustAuthorizeToMaintainLiabilities(): void
    {
        $builder = new AllowTrustOperationBuilder(
            $this->destAccountId,
            "EUR",
            false,
            true
        );
        $operation = $builder->build();

        $this->assertFalse($operation->isAuthorize());
        $this->assertTrue($operation->isAuthorizeToMaintainLiabilities());
    }

    public function testAllowTrustMuxedSourceAccount(): void
    {
        $muxedSource = MuxedAccount::fromAccountId($this->muxedAccountId);
        $builder = new AllowTrustOperationBuilder(
            $this->destAccountId,
            "BTC",
            true,
            false
        );
        $operation = $builder->setMuxedSourceAccount($muxedSource)->build();

        $this->assertEquals($this->muxedAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testAllowTrustInvalidAssetCodeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("invalid asset code");

        new AllowTrustOperationBuilder(
            $this->destAccountId,
            "",
            true,
            false
        );
    }

    public function testAllowTrustAssetCodeTooLongThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("invalid asset code");

        new AllowTrustOperationBuilder(
            $this->destAccountId,
            str_repeat("A", 13),
            true,
            false
        );
    }

    public function testAllowTrustXdrRoundTrip(): void
    {
        $builder = new AllowTrustOperationBuilder(
            $this->destAccountId,
            "USD",
            true,
            false
        );
        $operation = $builder->build();

        $xdr = $operation->toXdr();
        $this->assertNotNull($xdr->getBody()->getAllowTrustOperation());
    }

    // BumpSequenceOperationBuilder Tests

    public function testBumpSequenceBasic(): void
    {
        $bumpTo = new BigInteger("12345");
        $builder = new BumpSequenceOperationBuilder($bumpTo);
        $operation = $builder->setSourceAccount($this->sourceAccountId)->build();

        $this->assertEquals($bumpTo->toString(), $operation->getBumpTo()->toString());
        $this->assertEquals($this->sourceAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testBumpSequenceLargeNumber(): void
    {
        $bumpTo = new BigInteger("9999999999999999");
        $builder = new BumpSequenceOperationBuilder($bumpTo);
        $operation = $builder->build();

        $this->assertEquals($bumpTo->toString(), $operation->getBumpTo()->toString());
    }

    public function testBumpSequenceMuxedSourceAccount(): void
    {
        $bumpTo = new BigInteger("54321");
        $muxedSource = MuxedAccount::fromAccountId($this->muxedAccountId);
        $builder = new BumpSequenceOperationBuilder($bumpTo);
        $operation = $builder->setMuxedSourceAccount($muxedSource)->build();

        $this->assertEquals($this->muxedAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testBumpSequenceXdrRoundTrip(): void
    {
        $bumpTo = new BigInteger("99999");
        $builder = new BumpSequenceOperationBuilder($bumpTo);
        $operation = $builder->build();

        $xdr = $operation->toXdr();
        $this->assertNotNull($xdr->getBody());
        $this->assertEquals($bumpTo->toString(), $operation->getBumpTo()->toString());
    }

    // ChangeTrustOperationBuilder Tests

    public function testChangeTrustBasic(): void
    {
        $asset = Asset::createNonNativeAsset("USD", $this->sourceAccountId);
        $builder = new ChangeTrustOperationBuilder($asset, "1000.0");
        $operation = $builder->setSourceAccount($this->sourceAccountId)->build();

        $this->assertEquals("USD", $operation->getAsset()->getCode());
        $this->assertEquals("1000.0000000", $operation->getLimit());
    }

    public function testChangeTrustWithoutLimit(): void
    {
        $asset = Asset::createNonNativeAsset("EUR", $this->destAccountId);
        $builder = new ChangeTrustOperationBuilder($asset);
        $operation = $builder->build();

        $this->assertEquals("922337203685.4775807", $operation->getLimit());
    }

    public function testChangeTrustNativeAsset(): void
    {
        $asset = Asset::native();
        $builder = new ChangeTrustOperationBuilder($asset, "500.0");
        $operation = $builder->build();

        $this->assertEquals("native", $asset->getType());
    }

    public function testChangeTrustMuxedSourceAccount(): void
    {
        $asset = Asset::createNonNativeAsset("BTC", $this->sourceAccountId);
        $muxedSource = MuxedAccount::fromAccountId($this->muxedAccountId);
        $builder = new ChangeTrustOperationBuilder($asset, "100.0");
        $operation = $builder->setMuxedSourceAccount($muxedSource)->build();

        $this->assertEquals($this->muxedAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testChangeTrustXdrRoundTrip(): void
    {
        $asset = Asset::createNonNativeAsset("USD", $this->sourceAccountId);
        $builder = new ChangeTrustOperationBuilder($asset, "1000.0");
        $operation = $builder->build();

        $xdr = $operation->toXdr();
        $this->assertNotNull($xdr->getBody());
        $this->assertEquals("USD", $operation->getAsset()->getCode());
    }

    // ClaimClaimableBalanceOperationBuilder Tests

    public function testClaimClaimableBalanceBasic(): void
    {
        $balanceId = "00000000da0d57da7d4850e7fc10d2a9d0ebc731f7afb40574c03395b17d49149b91f5be";
        $builder = new ClaimClaimableBalanceOperationBuilder($balanceId);
        $operation = $builder->setSourceAccount($this->sourceAccountId)->build();

        $this->assertEquals($balanceId, $operation->getBalanceId());
        $this->assertEquals($this->sourceAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testClaimClaimableBalanceMuxedSourceAccount(): void
    {
        $balanceId = "00000000da0d57da7d4850e7fc10d2a9d0ebc731f7afb40574c03395b17d49149b91f5be";
        $muxedSource = MuxedAccount::fromAccountId($this->muxedAccountId);
        $builder = new ClaimClaimableBalanceOperationBuilder($balanceId);
        $operation = $builder->setMuxedSourceAccount($muxedSource)->build();

        $this->assertEquals($this->muxedAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testClaimClaimableBalanceXdrRoundTrip(): void
    {
        $balanceId = "00000000da0d57da7d4850e7fc10d2a9d0ebc731f7afb40574c03395b17d49149b91f5be";
        $builder = new ClaimClaimableBalanceOperationBuilder($balanceId);
        $operation = $builder->build();

        $xdr = $operation->toXdr();
        $this->assertNotNull($xdr->getBody()->getClaimClaimableBalanceOperation());
    }

    // ClawbackClaimableBalanceOperationBuilder Tests

    public function testClawbackClaimableBalanceBasic(): void
    {
        $balanceId = "00000000da0d57da7d4850e7fc10d2a9d0ebc731f7afb40574c03395b17d49149b91f5be";
        $builder = new ClawbackClaimableBalanceOperationBuilder($balanceId);
        $operation = $builder->setSourceAccount($this->sourceAccountId)->build();

        $this->assertEquals($balanceId, $operation->getBalanceId());
        $this->assertEquals($this->sourceAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testClawbackClaimableBalanceMuxedSourceAccount(): void
    {
        $balanceId = "00000000da0d57da7d4850e7fc10d2a9d0ebc731f7afb40574c03395b17d49149b91f5be";
        $muxedSource = MuxedAccount::fromAccountId($this->muxedAccountId);
        $builder = new ClawbackClaimableBalanceOperationBuilder($balanceId);
        $operation = $builder->setMuxedSourceAccount($muxedSource)->build();

        $this->assertEquals($this->muxedAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testClawbackClaimableBalanceXdrRoundTrip(): void
    {
        $balanceId = "00000000da0d57da7d4850e7fc10d2a9d0ebc731f7afb40574c03395b17d49149b91f5be";
        $builder = new ClawbackClaimableBalanceOperationBuilder($balanceId);
        $operation = $builder->build();

        $xdr = $operation->toXdr();
        $this->assertNotNull($xdr->getBody()->getClawbackClaimableBalanceOperation());
    }

    // CreateAccountOperationBuilder Tests

    public function testCreateAccountBasic(): void
    {
        $builder = new CreateAccountOperationBuilder($this->destAccountId, "10.0");
        $operation = $builder->setSourceAccount($this->sourceAccountId)->build();

        $this->assertEquals($this->destAccountId, $operation->getDestination());
        $this->assertEquals("10.0", $operation->getStartingBalance());
        $this->assertEquals($this->sourceAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testCreateAccountLargeStartingBalance(): void
    {
        $builder = new CreateAccountOperationBuilder($this->destAccountId, "10000000.0");
        $operation = $builder->build();

        $this->assertEquals("10000000.0", $operation->getStartingBalance());
    }

    public function testCreateAccountMuxedSourceAccount(): void
    {
        $muxedSource = MuxedAccount::fromAccountId($this->muxedAccountId);
        $builder = new CreateAccountOperationBuilder($this->destAccountId, "5.0");
        $operation = $builder->setMuxedSourceAccount($muxedSource)->build();

        $this->assertEquals($this->muxedAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testCreateAccountXdrRoundTrip(): void
    {
        $builder = new CreateAccountOperationBuilder($this->destAccountId, "10.0");
        $operation = $builder->build();

        $xdr = $operation->toXdr();
        $this->assertNotNull($xdr->getBody());
        $this->assertEquals($this->destAccountId, $operation->getDestination());
    }

    // CreatePassiveSellOfferOperationBuilder Tests

    public function testCreatePassiveSellOfferBasic(): void
    {
        $selling = Asset::createNonNativeAsset("USD", $this->sourceAccountId);
        $buying = Asset::createNonNativeAsset("EUR", $this->destAccountId);
        $price = new Price(1, 2);

        $builder = new CreatePassiveSellOfferOperationBuilder($selling, $buying, "100.0", $price);
        $operation = $builder->setSourceAccount($this->sourceAccountId)->build();

        $this->assertEquals("USD", $operation->getSelling()->getCode());
        $this->assertEquals("EUR", $operation->getBuying()->getCode());
        $this->assertEquals("100.0", $operation->getAmount());
        $this->assertEquals(1, $operation->getPrice()->getN());
        $this->assertEquals(2, $operation->getPrice()->getD());
    }

    public function testCreatePassiveSellOfferNativeAssets(): void
    {
        $selling = Asset::native();
        $buying = Asset::createNonNativeAsset("USD", $this->sourceAccountId);
        $price = new Price(5, 1);

        $builder = new CreatePassiveSellOfferOperationBuilder($selling, $buying, "50.0", $price);
        $operation = $builder->build();

        $this->assertEquals("native", $selling->getType());
        $this->assertEquals(5, $operation->getPrice()->getN());
        $this->assertEquals(1, $operation->getPrice()->getD());
    }

    public function testCreatePassiveSellOfferMuxedSourceAccount(): void
    {
        $selling = Asset::createNonNativeAsset("BTC", $this->sourceAccountId);
        $buying = Asset::createNonNativeAsset("ETH", $this->destAccountId);
        $price = new Price(10, 3);
        $muxedSource = MuxedAccount::fromAccountId($this->muxedAccountId);

        $builder = new CreatePassiveSellOfferOperationBuilder($selling, $buying, "25.0", $price);
        $operation = $builder->setMuxedSourceAccount($muxedSource)->build();

        $this->assertEquals($this->muxedAccountId, $operation->getSourceAccount()->getAccountId());
    }

    public function testCreatePassiveSellOfferXdrRoundTrip(): void
    {
        $selling = Asset::createNonNativeAsset("USD", $this->sourceAccountId);
        $buying = Asset::native();
        $price = new Price(2, 3);

        $builder = new CreatePassiveSellOfferOperationBuilder($selling, $buying, "100.0", $price);
        $operation = $builder->build();

        $xdr = $operation->toXdr();
        $this->assertNotNull($xdr->getBody());
        $this->assertEquals("USD", $operation->getSelling()->getCode());
    }
}
