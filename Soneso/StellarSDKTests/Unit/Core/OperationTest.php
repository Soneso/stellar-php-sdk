<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Core;

use Exception;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\AbstractOperation;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\CreateAccountOperation;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\PaymentOperation;
use Soneso\StellarSDK\SetOptionsOperation;
use Soneso\StellarSDK\ChangeTrustOperation;
use Soneso\StellarSDK\ManageDataOperation;
use Soneso\StellarSDK\BumpSequenceOperation;
use Soneso\StellarSDK\AccountMergeOperation;
use Soneso\StellarSDK\PathPaymentStrictReceiveOperation;
use Soneso\StellarSDK\PathPaymentStrictSendOperation;
use Soneso\StellarSDK\ManageSellOfferOperation;
use Soneso\StellarSDK\ManageBuyOfferOperation;
use Soneso\StellarSDK\CreatePassiveSellOfferOperation;
use Soneso\StellarSDK\AllowTrustOperation;
use Soneso\StellarSDK\CreateClaimableBalanceOperation;
use Soneso\StellarSDK\ClaimClaimableBalanceOperation;
use Soneso\StellarSDK\BeginSponsoringFutureReservesOperation;
use Soneso\StellarSDK\EndSponsoringFutureReservesOperation;
use Soneso\StellarSDK\RevokeSponsorshipOperation;
use Soneso\StellarSDK\ClawbackOperation;
use Soneso\StellarSDK\ClawbackClaimableBalanceOperation;
use Soneso\StellarSDK\SetTrustLineFlagsOperation;
use Soneso\StellarSDK\LiquidityPoolDepositOperation;
use Soneso\StellarSDK\LiquidityPoolWithdrawOperation;
use Soneso\StellarSDK\ExtendFootprintTTLOperation;
use Soneso\StellarSDK\RestoreFootprintOperation;
use Soneso\StellarSDK\InvokeHostFunctionOperation;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\UploadContractWasmHostFunction;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Price;
use Soneso\StellarSDK\Claimant;
use Soneso\StellarSDK\Xdr\XdrOperation;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;

class OperationTest extends TestCase
{
    private KeyPair $sourceKeyPair;
    private string $sourceAccountId;

    public function setUp(): void
    {
        error_reporting(E_ALL);
        $this->sourceKeyPair = KeyPair::random();
        $this->sourceAccountId = $this->sourceKeyPair->getAccountId();
    }

    public function testSourceAccountGetterSetter()
    {
        $operation = new CreateAccountOperation("GAKL4XMWLXQKYNYR6ZDVLZT5FXQK3PKC4GZW7OKJX4KQLJKRWBWDXNYK", "100");

        assertNull($operation->getSourceAccount());

        $muxedAccount = MuxedAccount::fromAccountId($this->sourceAccountId);
        $operation->setSourceAccount($muxedAccount);

        assertNotNull($operation->getSourceAccount());
        assertEquals($this->sourceAccountId, $operation->getSourceAccount()->getAccountId());

        $operation->setSourceAccount(null);
        assertNull($operation->getSourceAccount());
    }

    public function testToXdrAmount()
    {
        $amount = AbstractOperation::toXdrAmount("100.5000000");
        assertEquals("1005000000", $amount->toString());

        $amount = AbstractOperation::toXdrAmount("0.0000001");
        assertEquals("1", $amount->toString());

        $amount = AbstractOperation::toXdrAmount("922337203685.4775807");
        assertEquals("9223372036854775807", $amount->toString());
    }

    public function testFromXdrAmount()
    {
        $stroops = new BigInteger("1005000000");
        $amount = AbstractOperation::fromXdrAmount($stroops);
        assertEquals("100.5000000", $amount);

        $stroops = new BigInteger("1");
        $amount = AbstractOperation::fromXdrAmount($stroops);
        assertEquals("0.0000001", $amount);

        $stroops = new BigInteger("9223372036854775807");
        $amount = AbstractOperation::fromXdrAmount($stroops);
        assertEquals("922337203685.4775807", $amount);
    }

    public function testOperationToXdr()
    {
        $destination = "GAKL4XMWLXQKYNYR6ZDVLZT5FXQK3PKC4GZW7OKJX4KQLJKRWBWDXNYK";
        $amount = "100.25";

        $operation = new CreateAccountOperation($destination, $amount);
        $operation->setSourceAccount(MuxedAccount::fromAccountId($this->sourceAccountId));

        $xdr = $operation->toXdr();
        assertNotNull($xdr);
        assertNotNull($xdr->getSourceAccount());
        assertEquals($this->sourceAccountId, MuxedAccount::fromXdr($xdr->getSourceAccount())->getAccountId());
    }

    public function testCreateAccountFromXdr()
    {
        $destination = "GAKL4XMWLXQKYNYR6ZDVLZT5FXQK3PKC4GZW7OKJX4KQLJKRWBWDXNYK";
        $amount = "100.25";

        $operation = new CreateAccountOperation($destination, $amount);
        $operation->setSourceAccount(MuxedAccount::fromAccountId($this->sourceAccountId));

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(CreateAccountOperation::class, get_class($parsed));
        assertEquals($this->sourceAccountId, $parsed->getSourceAccount()->getAccountId());
        assertEquals($destination, $parsed->getDestination());
        assertEquals("100.2500000", $parsed->getStartingBalance());
    }

    public function testPaymentFromXdr()
    {
        $destinationId = "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO";
        $destination = MuxedAccount::fromAccountId($destinationId);
        $asset = Asset::native();
        $amount = "50.0";

        $operation = new PaymentOperation($destination, $asset, $amount);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(PaymentOperation::class, get_class($parsed));
        assertEquals($destinationId, $parsed->getDestination()->getAccountId());
        assertEquals(Asset::TYPE_NATIVE, $parsed->getAsset()->getType());
        assertEquals("50.0000000", $parsed->getAmount());
    }

    public function testSetOptionsFromXdr()
    {
        $inflationDest = "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO";
        $operation = new SetOptionsOperation(
            inflationDestination: $inflationDest,
            clearFlags: 2,
            setFlags: 1,
            masterKeyWeight: 10,
            lowThreshold: 5,
            mediumThreshold: 10,
            highThreshold: 15,
            homeDomain: "example.com"
        );

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(SetOptionsOperation::class, get_class($parsed));
        assertEquals($inflationDest, $parsed->getInflationDestination());
        assertEquals(1, $parsed->getSetFlags());
        assertEquals(2, $parsed->getClearFlags());
        assertEquals(10, $parsed->getMasterKeyWeight());
        assertEquals(5, $parsed->getLowThreshold());
        assertEquals(10, $parsed->getMediumThreshold());
        assertEquals(15, $parsed->getHighThreshold());
        assertEquals("example.com", $parsed->getHomeDomain());
    }

    public function testChangeTrustFromXdr()
    {
        $issuerId = "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO";
        $asset = Asset::createNonNativeAsset("USD", $issuerId);
        $limit = "1000.0000000";

        $operation = new ChangeTrustOperation($asset, $limit);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(ChangeTrustOperation::class, get_class($parsed));
        assertEquals("USD", $parsed->getAsset()->getCode());
        assertEquals($issuerId, $parsed->getAsset()->getIssuer());
        assertEquals($limit, $parsed->getLimit());
    }

    public function testManageDataFromXdr()
    {
        $name = "test_key";
        $value = "test_value";

        $operation = new ManageDataOperation($name, $value);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(ManageDataOperation::class, get_class($parsed));
        assertEquals($name, $parsed->getKey());
        assertEquals($value, $parsed->getValue());
    }

    public function testManageDataDeleteFromXdr()
    {
        $name = "test_key";

        $operation = new ManageDataOperation($name, null);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(ManageDataOperation::class, get_class($parsed));
        assertEquals($name, $parsed->getKey());
        assertNull($parsed->getValue());
    }

    public function testBumpSequenceFromXdr()
    {
        $bumpTo = new BigInteger("123456789");

        $operation = new BumpSequenceOperation($bumpTo);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(BumpSequenceOperation::class, get_class($parsed));
        assertEquals($bumpTo->toString(), $parsed->getBumpTo()->toString());
    }

    public function testAccountMergeFromXdr()
    {
        $destinationId = "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO";
        $destination = MuxedAccount::fromAccountId($destinationId);

        $operation = new AccountMergeOperation($destination);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(AccountMergeOperation::class, get_class($parsed));
        assertEquals($destinationId, $parsed->getDestination()->getAccountId());
    }

    public function testOperationWithoutSourceAccount()
    {
        $destination = "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO";
        $operation = new CreateAccountOperation($destination, "100");

        $xdr = $operation->toXdr();
        assertNull($xdr->getSourceAccount());

        $parsed = AbstractOperation::fromXdr($xdr);
        assertNull($parsed->getSourceAccount());
    }

    public function testPathPaymentStrictReceiveFromXdr()
    {
        $sendAsset = Asset::createNonNativeAsset("USD", "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO");
        $sendMax = "100.0000000";
        $destinationId = "GDW6AUTBXTOC7FIKUO5BOO3OGLK4SF7ZPOBLMQHMZDI45J2Z6VXRB5NR";
        $destination = MuxedAccount::fromAccountId($destinationId);
        $destAsset = Asset::createNonNativeAsset("EUR", "GDW6AUTBXTOC7FIKUO5BOO3OGLK4SF7ZPOBLMQHMZDI45J2Z6VXRB5NR");
        $destAmount = "50.0000000";
        $path = [
            Asset::native(),
            Asset::createNonNativeAsset("BTC", "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO")
        ];

        $operation = new PathPaymentStrictReceiveOperation($sendAsset, $sendMax, $destination, $destAsset, $destAmount, $path);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(PathPaymentStrictReceiveOperation::class, get_class($parsed));
        assertEquals("USD", $parsed->getSendAsset()->getCode());
        assertEquals($sendMax, $parsed->getSendMax());
        assertEquals($destinationId, $parsed->getDestination()->getAccountId());
        assertEquals("EUR", $parsed->getDestAsset()->getCode());
        assertEquals($destAmount, $parsed->getDestAmount());
        assertCount(2, $parsed->getPath());
        assertEquals(Asset::TYPE_NATIVE, $parsed->getPath()[0]->getType());
        assertEquals("BTC", $parsed->getPath()[1]->getCode());
    }

    public function testPathPaymentStrictSendFromXdr()
    {
        $sendAsset = Asset::createNonNativeAsset("USD", "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO");
        $sendAmount = "100.0000000";
        $destinationId = "GDW6AUTBXTOC7FIKUO5BOO3OGLK4SF7ZPOBLMQHMZDI45J2Z6VXRB5NR";
        $destination = MuxedAccount::fromAccountId($destinationId);
        $destAsset = Asset::createNonNativeAsset("EUR", "GDW6AUTBXTOC7FIKUO5BOO3OGLK4SF7ZPOBLMQHMZDI45J2Z6VXRB5NR");
        $destMin = "50.0000000";
        $path = [Asset::native()];

        $operation = new PathPaymentStrictSendOperation($sendAsset, $sendAmount, $destination, $destAsset, $destMin, $path);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(PathPaymentStrictSendOperation::class, get_class($parsed));
        assertEquals("USD", $parsed->getSendAsset()->getCode());
        assertEquals($sendAmount, $parsed->getSendAmount());
        assertEquals($destinationId, $parsed->getDestination()->getAccountId());
        assertEquals("EUR", $parsed->getDestAsset()->getCode());
        assertEquals($destMin, $parsed->getDestMin());
        assertCount(1, $parsed->getPath());
        assertEquals(Asset::TYPE_NATIVE, $parsed->getPath()[0]->getType());
    }

    public function testManageSellOfferFromXdr()
    {
        $selling = Asset::native();
        $buying = Asset::createNonNativeAsset("USD", "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO");
        $amount = "100.0000000";
        $price = new Price(3, 2);
        $offerId = 12345;

        $operation = new ManageSellOfferOperation($selling, $buying, $amount, $price, $offerId);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(ManageSellOfferOperation::class, get_class($parsed));
        assertEquals(Asset::TYPE_NATIVE, $parsed->getSelling()->getType());
        assertEquals("USD", $parsed->getBuying()->getCode());
        assertEquals($amount, $parsed->getAmount());
        assertEquals(3, $parsed->getPrice()->getN());
        assertEquals(2, $parsed->getPrice()->getD());
        assertEquals($offerId, $parsed->getOfferId());
    }

    public function testManageBuyOfferFromXdr()
    {
        $selling = Asset::createNonNativeAsset("USD", "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO");
        $buying = Asset::native();
        $amount = "50.0000000";
        $price = new Price(5, 4);
        $offerId = 0;

        $operation = new ManageBuyOfferOperation($selling, $buying, $amount, $price, $offerId);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(ManageBuyOfferOperation::class, get_class($parsed));
        assertEquals("USD", $parsed->getSelling()->getCode());
        assertEquals(Asset::TYPE_NATIVE, $parsed->getBuying()->getType());
        assertEquals($amount, $parsed->getAmount());
        assertEquals(5, $parsed->getPrice()->getN());
        assertEquals(4, $parsed->getPrice()->getD());
        assertEquals(0, $parsed->getOfferId());
    }

    public function testCreatePassiveSellOfferFromXdr()
    {
        $selling = Asset::createNonNativeAsset("USD", "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO");
        $buying = Asset::createNonNativeAsset("EUR", "GDW6AUTBXTOC7FIKUO5BOO3OGLK4SF7ZPOBLMQHMZDI45J2Z6VXRB5NR");
        $amount = "1000.0000000";
        $price = new Price(10, 9);

        $operation = new CreatePassiveSellOfferOperation($selling, $buying, $amount, $price);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(CreatePassiveSellOfferOperation::class, get_class($parsed));
        assertEquals("USD", $parsed->getSelling()->getCode());
        assertEquals("EUR", $parsed->getBuying()->getCode());
        assertEquals($amount, $parsed->getAmount());
        assertEquals(10, $parsed->getPrice()->getN());
        assertEquals(9, $parsed->getPrice()->getD());
    }

    public function testAllowTrustFromXdr()
    {
        $trustorId = "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO";
        $assetCode = "USD";
        $authorize = true;
        $authorizeToMaintainLiabilities = false;

        $operation = new AllowTrustOperation($trustorId, $assetCode, $authorize, $authorizeToMaintainLiabilities);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(AllowTrustOperation::class, get_class($parsed));
        assertEquals($trustorId, $parsed->getTrustor());
        assertEquals($assetCode, $parsed->getAssetCode());
        assertEquals(true, $parsed->isAuthorize());
        assertEquals(false, $parsed->isAuthorizeToMaintainLiabilities());
    }

    public function testAllowTrustMaintainLiabilitiesFromXdr()
    {
        $trustorId = "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO";
        $assetCode = "ABCDEFGHIJKL";
        $authorize = false;
        $authorizeToMaintainLiabilities = true;

        $operation = new AllowTrustOperation($trustorId, $assetCode, $authorize, $authorizeToMaintainLiabilities);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(AllowTrustOperation::class, get_class($parsed));
        assertEquals($trustorId, $parsed->getTrustor());
        assertEquals($assetCode, $parsed->getAssetCode());
        assertEquals(false, $parsed->isAuthorize());
        assertEquals(true, $parsed->isAuthorizeToMaintainLiabilities());
    }

    public function testCreateClaimableBalanceFromXdr()
    {
        $asset = Asset::createNonNativeAsset("USD", "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO");
        $amount = "100.0000000";
        $claimant1 = new Claimant(
            "GDW6AUTBXTOC7FIKUO5BOO3OGLK4SF7ZPOBLMQHMZDI45J2Z6VXRB5NR",
            Claimant::predicateUnconditional()
        );
        $claimant2 = new Claimant(
            "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO",
            Claimant::predicateBeforeAbsoluteTime(1234567890)
        );
        $claimants = [$claimant1, $claimant2];

        $operation = new CreateClaimableBalanceOperation($claimants, $asset, $amount);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(CreateClaimableBalanceOperation::class, get_class($parsed));
        assertEquals("USD", $parsed->getAsset()->getCode());
        assertEquals($amount, $parsed->getAmount());
        assertCount(2, $parsed->getClaimants());
        assertEquals("GDW6AUTBXTOC7FIKUO5BOO3OGLK4SF7ZPOBLMQHMZDI45J2Z6VXRB5NR", $parsed->getClaimants()[0]->getDestination());
        assertEquals("GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO", $parsed->getClaimants()[1]->getDestination());
    }

    public function testClaimClaimableBalanceFromXdr()
    {
        $balanceId = "00000000da0d57da7d4850e7fc10d2a9d0ebc731f7afb40574c03395b17d49149b91f5be";

        $operation = new ClaimClaimableBalanceOperation($balanceId);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(ClaimClaimableBalanceOperation::class, get_class($parsed));
        assertEquals($balanceId, $parsed->getBalanceId());
    }

    public function testBeginSponsoringFutureReservesFromXdr()
    {
        $sponsoredId = "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO";

        $operation = new BeginSponsoringFutureReservesOperation($sponsoredId);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(BeginSponsoringFutureReservesOperation::class, get_class($parsed));
        assertEquals($sponsoredId, $parsed->getSponsoredId());
    }

    public function testEndSponsoringFutureReservesFromXdr()
    {
        $operation = new EndSponsoringFutureReservesOperation();

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(EndSponsoringFutureReservesOperation::class, get_class($parsed));
    }

    public function testRevokeSponsorshipAccountFromXdr()
    {
        $accountId = "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO";
        $ledgerKey = XdrLedgerKey::forAccountId($accountId);

        $operation = new RevokeSponsorshipOperation();
        $operation->setLedgerKey($ledgerKey);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(RevokeSponsorshipOperation::class, get_class($parsed));
        assertNotNull($parsed->getLedgerKey());
        assertNotNull($parsed->getLedgerKey()->getAccount());
        assertEquals($accountId, $parsed->getLedgerKey()->getAccount()->getAccountId()->getAccountId());
    }

    public function testClawbackFromXdr()
    {
        $asset = Asset::createNonNativeAsset("USD", "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO");
        $fromId = "GDW6AUTBXTOC7FIKUO5BOO3OGLK4SF7ZPOBLMQHMZDI45J2Z6VXRB5NR";
        $from = MuxedAccount::fromAccountId($fromId);
        $amount = "50.0000000";

        $operation = new ClawbackOperation($asset, $from, $amount);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(ClawbackOperation::class, get_class($parsed));
        assertEquals("USD", $parsed->getAsset()->getCode());
        assertEquals($fromId, $parsed->getFrom()->getAccountId());
        assertEquals($amount, $parsed->getAmount());
    }

    public function testClawbackClaimableBalanceFromXdr()
    {
        $balanceId = "00000000da0d57da7d4850e7fc10d2a9d0ebc731f7afb40574c03395b17d49149b91f5be";

        $operation = new ClawbackClaimableBalanceOperation($balanceId);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(ClawbackClaimableBalanceOperation::class, get_class($parsed));
        assertEquals($balanceId, $parsed->getBalanceId());
    }

    public function testSetTrustLineFlagsFromXdr()
    {
        $trustorId = "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO";
        $asset = Asset::createNonNativeAsset("USD", "GDW6AUTBXTOC7FIKUO5BOO3OGLK4SF7ZPOBLMQHMZDI45J2Z6VXRB5NR");
        $clearFlags = 2;
        $setFlags = 1;

        $operation = new SetTrustLineFlagsOperation($trustorId, $asset, $clearFlags, $setFlags);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(SetTrustLineFlagsOperation::class, get_class($parsed));
        assertEquals($trustorId, $parsed->getTrustorId());
        assertEquals("USD", $parsed->getAsset()->getCode());
        assertEquals($clearFlags, $parsed->getClearFlags());
        assertEquals($setFlags, $parsed->getSetFlags());
    }

    public function testLiquidityPoolDepositFromXdr()
    {
        $liquidityPoolId = "dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7";
        $maxAmountA = "100.0000000";
        $maxAmountB = "200.0000000";
        $minPrice = new Price(1, 2);
        $maxPrice = new Price(2, 1);

        $operation = new LiquidityPoolDepositOperation($liquidityPoolId, $maxAmountA, $maxAmountB, $minPrice, $maxPrice);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(LiquidityPoolDepositOperation::class, get_class($parsed));
        assertEquals($liquidityPoolId, $parsed->getLiqudityPoolId());
        assertEquals($maxAmountA, $parsed->getMaxAmountA());
        assertEquals($maxAmountB, $parsed->getMaxAmountB());
        assertEquals(1, $parsed->getMinPrice()->getN());
        assertEquals(2, $parsed->getMinPrice()->getD());
        assertEquals(2, $parsed->getMaxPrice()->getN());
        assertEquals(1, $parsed->getMaxPrice()->getD());
    }

    public function testLiquidityPoolWithdrawFromXdr()
    {
        $liquidityPoolId = "dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7";
        $amount = "50.0000000";
        $minAmountA = "25.0000000";
        $minAmountB = "25.0000000";

        $operation = new LiquidityPoolWithdrawOperation($liquidityPoolId, $amount, $minAmountA, $minAmountB);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(LiquidityPoolWithdrawOperation::class, get_class($parsed));
        assertEquals($liquidityPoolId, $parsed->getLiqudityPoolId());
        assertEquals($amount, $parsed->getAmount());
        assertEquals($minAmountA, $parsed->getMinAmountA());
        assertEquals($minAmountB, $parsed->getMinAmountB());
    }

    public function testExtendFootprintTTLFromXdr()
    {
        $extendTo = 1000;

        $operation = new ExtendFootprintTTLOperation($extendTo);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(ExtendFootprintTTLOperation::class, get_class($parsed));
        assertEquals($extendTo, $parsed->getExtendTo());
    }

    public function testRestoreFootprintFromXdr()
    {
        $operation = new RestoreFootprintOperation();

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(RestoreFootprintOperation::class, get_class($parsed));
    }

    public function testInvokeHostFunctionUploadWasmFromXdr()
    {
        // Create a simple WASM-like payload (just bytes for testing)
        $wasmBytes = pack('C*', 0x00, 0x61, 0x73, 0x6D, 0x01, 0x00, 0x00, 0x00);

        $hostFunction = new UploadContractWasmHostFunction($wasmBytes);
        $operation = new InvokeHostFunctionOperation($hostFunction);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(InvokeHostFunctionOperation::class, get_class($parsed));
        assertEquals(UploadContractWasmHostFunction::class, get_class($parsed->getFunction()));
        assertEquals($wasmBytes, $parsed->getFunction()->getContractCodeBytes());
    }

    public function testExtendFootprintTTLGetterSetter()
    {
        $operation = new ExtendFootprintTTLOperation(100);
        assertEquals(100, $operation->getExtendTo());

        $operation->setExtendTo(200);
        assertEquals(200, $operation->getExtendTo());
    }

    public function testInvokeHostFunctionGettersSetters()
    {
        $wasmBytes = "test_wasm_bytes";
        $hostFunction = new UploadContractWasmHostFunction($wasmBytes);
        $operation = new InvokeHostFunctionOperation($hostFunction);

        assertEquals($hostFunction, $operation->getFunction());
        assertEquals([], $operation->getAuth());

        $newHostFunction = new UploadContractWasmHostFunction("new_wasm");
        $operation->setFunction($newHostFunction);
        assertEquals($newHostFunction, $operation->getFunction());

        $operation->setAuth([]);
        assertEquals([], $operation->getAuth());
    }

    public function testInvokeContractHostFunctionFromXdr()
    {
        $contractId = "CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC";
        $functionName = "transfer";

        $arg1 = new XdrSCVal(new XdrSCValType(XdrSCValType::SCV_BOOL));
        $arg1->b = true;

        $hostFunction = new InvokeContractHostFunction($contractId, $functionName, [$arg1]);
        $operation = new InvokeHostFunctionOperation($hostFunction);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(InvokeHostFunctionOperation::class, get_class($parsed));
        assertEquals(InvokeContractHostFunction::class, get_class($parsed->getFunction()));
        assertEquals($functionName, $parsed->getFunction()->functionName);
    }

    public function testInvokeHostFunctionToOperationBody()
    {
        $wasmBytes = "test_wasm_bytes";
        $hostFunction = new UploadContractWasmHostFunction($wasmBytes);
        $operation = new InvokeHostFunctionOperation($hostFunction);

        $body = $operation->toOperationBody();

        assertNotNull($body);
        assertNotNull($body->getInvokeHostFunctionOperation());
    }

    public function testInvokeContractHostFunctionNoArgs()
    {
        $contractId = "CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC";
        $functionName = "get_balance";

        $hostFunction = new InvokeContractHostFunction($contractId, $functionName);
        $operation = new InvokeHostFunctionOperation($hostFunction);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(InvokeHostFunctionOperation::class, get_class($parsed));
        assertEquals(InvokeContractHostFunction::class, get_class($parsed->getFunction()));
        assertEquals($functionName, $parsed->getFunction()->functionName);
        assertEmpty($parsed->getFunction()->arguments);
    }

    public function testInvokeContractHostFunctionWithMultipleArgs()
    {
        $contractId = "CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC";
        $functionName = "complex_fn";

        $arg1 = new XdrSCVal(new XdrSCValType(XdrSCValType::SCV_BOOL));
        $arg1->b = true;
        $arg2 = new XdrSCVal(new XdrSCValType(XdrSCValType::SCV_BOOL));
        $arg2->b = false;

        $hostFunction = new InvokeContractHostFunction($contractId, $functionName, [$arg1, $arg2]);
        $operation = new InvokeHostFunctionOperation($hostFunction);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(InvokeHostFunctionOperation::class, get_class($parsed));
        assertCount(2, $parsed->getFunction()->arguments);
    }

    public function testInvokeHostFunctionWithSourceAccount()
    {
        $wasmBytes = "test_bytes";
        $hostFunction = new UploadContractWasmHostFunction($wasmBytes);
        $sourceAccount = MuxedAccount::fromAccountId("GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H");

        $operation = new InvokeHostFunctionOperation($hostFunction, [], $sourceAccount);

        assertNotNull($operation->getSourceAccount());
        assertEquals("GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H", $operation->getSourceAccount()->getAccountId());
    }
}
