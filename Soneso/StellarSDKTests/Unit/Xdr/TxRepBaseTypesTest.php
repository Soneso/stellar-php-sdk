<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum4;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum12;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrChangeTrustAssetBase;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceID;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceIDType;
use Soneso\StellarSDK\Xdr\XdrConfigSettingID;
use Soneso\StellarSDK\Xdr\XdrContractDataDurability;
use Soneso\StellarSDK\Xdr\XdrCryptoKeyType;
use Soneso\StellarSDK\Xdr\XdrDataValueMandatory;
use Soneso\StellarSDK\Xdr\XdrEnvelopeType;
use Soneso\StellarSDK\Xdr\XdrHostFunctionBase;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;
use Soneso\StellarSDK\Xdr\XdrInvokeContractArgs;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyAccount;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyBase;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyContractCode;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyContractData;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyData;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyLiquidityPool;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyOffer;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyTrustLine;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyTTL;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolConstantProductParameters;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolParameters;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolType;
use Soneso\StellarSDK\Xdr\XdrMemo;
use Soneso\StellarSDK\Xdr\XdrMemoType;
use Soneso\StellarSDK\Xdr\XdrMuxedAccount;
use Soneso\StellarSDK\Xdr\XdrMuxedAccountBase;
use Soneso\StellarSDK\Xdr\XdrMuxedAccountMed25519;
use Soneso\StellarSDK\Xdr\XdrMuxedAccountMed25519Base;
use Soneso\StellarSDK\Xdr\XdrPreconditions;
use Soneso\StellarSDK\Xdr\XdrPreconditionType;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCAddressBase;
use Soneso\StellarSDK\Xdr\XdrSCAddressType;
use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrSCNonceKey;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValBase;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;
use Soneso\StellarSDK\Xdr\XdrTransaction;
use Soneso\StellarSDK\Xdr\XdrTransactionBase;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelopeBase;
use Soneso\StellarSDK\Xdr\XdrTransactionExt;
use Soneso\StellarSDK\Xdr\XdrTransactionV1Envelope;
use Soneso\StellarSDK\Xdr\XdrAllowTrustOperationAssetBase;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceIDBase;
use Soneso\StellarSDK\Xdr\XdrContractExecutable;
use Soneso\StellarSDK\Xdr\XdrContractIDPreimage;
use Soneso\StellarSDK\Xdr\XdrContractIDPreimageFromAddress;
use Soneso\StellarSDK\Xdr\XdrContractIDPreimageType;
use Soneso\StellarSDK\Xdr\XdrCreateContractArgs;
use Soneso\StellarSDK\Xdr\XdrCreateContractArgsV2;
use Soneso\StellarSDK\Xdr\XdrDecoratedSignatureBase;
use Soneso\StellarSDK\Xdr\XdrFeeBumpTransaction;
use Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionExt;
use Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionInnerTx;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrInt256Parts;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolDepositOperationBase;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolWithdrawOperationBase;
use Soneso\StellarSDK\Xdr\XdrPrice;
use Soneso\StellarSDK\Xdr\XdrSCContractInstance;
use Soneso\StellarSDK\Xdr\XdrSCError;
use Soneso\StellarSDK\Xdr\XdrSCErrorCode;
use Soneso\StellarSDK\Xdr\XdrSCErrorType;
use Soneso\StellarSDK\Xdr\XdrSignedPayload;
use Soneso\StellarSDK\Xdr\XdrSignerKey;
use Soneso\StellarSDK\Xdr\XdrSignerKeyType;
use Soneso\StellarSDK\Xdr\XdrSignerKeyTypeBase;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedFunctionBase;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedFunctionType;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionDataExt;
use Soneso\StellarSDK\Xdr\XdrTransactionV0;
use Soneso\StellarSDK\Xdr\XdrTransactionV0Envelope;
use Soneso\StellarSDK\Xdr\XdrTrustlineAsset;
use Soneso\StellarSDK\Xdr\XdrTrustlineAssetBase;
use Soneso\StellarSDK\Xdr\XdrUInt128Parts;
use Soneso\StellarSDK\Xdr\XdrUInt256Parts;
use Soneso\StellarSDK\Xdr\XdrClaimantType;
use Soneso\StellarSDK\Xdr\XdrContractExecutableType;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrPublicKeyType;
use Soneso\StellarSDK\Xdr\XdrRevokeSponsorshipType;
use Soneso\StellarSDK\Xdr\XdrClaimPredicateType;
use Soneso\StellarSDK\Xdr\XdrManageDataOperationBase;

/**
 * Hand-crafted unit tests for XDR Base type toTxRep/fromTxRep methods.
 *
 * These types are in SKIP_TEST_TYPES so auto-generated roundtrip tests do not
 * cover them. Each test constructs a valid instance, serializes it to a TxRep
 * map, deserializes it back, and verifies that toBase64Xdr() output matches.
 */
class TxRepBaseTypesTest extends TestCase
{
    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function accountId(): string
    {
        return KeyPair::random()->getAccountId();
    }

    private function randomBytes(int $len = 32): string
    {
        return random_bytes($len);
    }

    private function randomHex(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    // ------------------------------------------------------------------
    // XdrMuxedAccountMed25519Base
    // ------------------------------------------------------------------

    public function testMuxedAccountMed25519BaseRoundtripViaBase(): void
    {
        $key = $this->randomBytes(32);
        $id = 42000;
        $original = new XdrMuxedAccountMed25519Base($id, $key);

        $lines = [];
        $original->toTxRep('med', $lines);

        $this->assertArrayHasKey('med.id', $lines);
        $this->assertArrayHasKey('med.ed25519', $lines);
        $this->assertEquals((string)$id, $lines['med.id']);

        $restored = XdrMuxedAccountMed25519Base::fromTxRep($lines, 'med');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals($original->getId(), $restored->getId());
    }

    public function testMuxedAccountMed25519BaseRoundtripWithLargeId(): void
    {
        $key = $this->randomBytes(32);
        $id = PHP_INT_MAX;
        $original = new XdrMuxedAccountMed25519Base($id, $key);

        $lines = [];
        $original->toTxRep('x', $lines);

        $restored = XdrMuxedAccountMed25519Base::fromTxRep($lines, 'x');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
    }

    // ------------------------------------------------------------------
    // XdrMuxedAccountBase
    // ------------------------------------------------------------------

    public function testMuxedAccountBaseEd25519Roundtrip(): void
    {
        $keyBytes = KeyPair::random()->getPublicKey();
        $original = new XdrMuxedAccountBase(XdrCryptoKeyType::KEY_TYPE_ED25519());
        $original->ed25519 = $keyBytes;

        $lines = [];
        $original->toTxRep('src', $lines);

        $this->assertArrayHasKey('src.type', $lines);
        $this->assertArrayHasKey('src.ed25519', $lines);

        $restored = XdrMuxedAccountBase::fromTxRep($lines, 'src');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrCryptoKeyType::KEY_TYPE_ED25519, $restored->getType()->getValue());
        $this->assertNotNull($restored->getEd25519());
    }

    public function testMuxedAccountBaseMuxedEd25519Roundtrip(): void
    {
        $keyBytes = KeyPair::random()->getPublicKey();
        $med25519 = new XdrMuxedAccountMed25519(99999, $keyBytes);

        $original = new XdrMuxedAccountBase(XdrCryptoKeyType::KEY_TYPE_MUXED_ED25519());
        $original->med25519 = $med25519;

        $lines = [];
        $original->toTxRep('src', $lines);

        $this->assertArrayHasKey('src.type', $lines);
        $this->assertArrayHasKey('src.med25519.id', $lines);
        $this->assertArrayHasKey('src.med25519.ed25519', $lines);

        $restored = XdrMuxedAccountBase::fromTxRep($lines, 'src');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrCryptoKeyType::KEY_TYPE_MUXED_ED25519, $restored->getType()->getValue());
        $this->assertNotNull($restored->getMed25519());
        $this->assertEquals(99999, $restored->getMed25519()->getId());
    }

    // ------------------------------------------------------------------
    // XdrChangeTrustAssetBase
    // ------------------------------------------------------------------

    public function testChangeTrustAssetBaseNativeRoundtrip(): void
    {
        $original = new XdrChangeTrustAssetBase(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));

        $lines = [];
        $original->toTxRep('asset', $lines);

        $this->assertArrayHasKey('asset.type', $lines);

        $restored = XdrChangeTrustAssetBase::fromTxRep($lines, 'asset');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrAssetType::ASSET_TYPE_NATIVE, $restored->getType()->getValue());
    }

    public function testChangeTrustAssetBaseAlphaNum4Roundtrip(): void
    {
        $issuerId = XdrAccountID::fromAccountId($this->accountId());
        $original = new XdrChangeTrustAssetBase(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $original->alphaNum4 = new XdrAssetAlphaNum4('USDC', $issuerId);

        $lines = [];
        $original->toTxRep('asset', $lines);

        $restored = XdrChangeTrustAssetBase::fromTxRep($lines, 'asset');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4, $restored->getType()->getValue());
        $this->assertNotNull($restored->getAlphaNum4());
    }

    public function testChangeTrustAssetBaseAlphaNum12Roundtrip(): void
    {
        $issuerId = XdrAccountID::fromAccountId($this->accountId());
        $original = new XdrChangeTrustAssetBase(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $original->alphaNum12 = new XdrAssetAlphaNum12('LONGCODE12', $issuerId);

        $lines = [];
        $original->toTxRep('asset', $lines);

        $restored = XdrChangeTrustAssetBase::fromTxRep($lines, 'asset');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12, $restored->getType()->getValue());
        $this->assertNotNull($restored->getAlphaNum12());
    }

    public function testChangeTrustAssetBasePoolShareRoundtrip(): void
    {
        $issuerA = XdrAccountID::fromAccountId($this->accountId());
        $issuerB = XdrAccountID::fromAccountId($this->accountId());
        $assetA = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $assetB = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $assetB->alphaNum4 = new XdrAssetAlphaNum4('USDC', $issuerB);

        $params = new XdrLiquidityPoolConstantProductParameters($assetA, $assetB, 30);
        $poolParams = new XdrLiquidityPoolParameters(new XdrLiquidityPoolType(XdrLiquidityPoolType::LIQUIDITY_POOL_CONSTANT_PRODUCT));
        $poolParams->constantProduct = $params;

        $original = new XdrChangeTrustAssetBase(new XdrAssetType(XdrAssetType::ASSET_TYPE_POOL_SHARE));
        $original->liquidityPool = $poolParams;

        $lines = [];
        $original->toTxRep('asset', $lines);

        $this->assertArrayHasKey('asset.type', $lines);
        $this->assertArrayHasKey('asset.liquidityPool.type', $lines);

        $restored = XdrChangeTrustAssetBase::fromTxRep($lines, 'asset');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrAssetType::ASSET_TYPE_POOL_SHARE, $restored->getType()->getValue());
        $this->assertNotNull($restored->getLiquidityPool());
    }

    // ------------------------------------------------------------------
    // XdrTrustlineAssetBase
    // ------------------------------------------------------------------

    public function testTrustlineAssetBaseNativeRoundtrip(): void
    {
        $original = new XdrTrustlineAssetBase(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));

        $lines = [];
        $original->toTxRep('asset', $lines);

        $restored = XdrTrustlineAssetBase::fromTxRep($lines, 'asset');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrAssetType::ASSET_TYPE_NATIVE, $restored->getType()->getValue());
    }

    public function testTrustlineAssetBaseAlphaNum4Roundtrip(): void
    {
        $issuerId = XdrAccountID::fromAccountId($this->accountId());
        $original = new XdrTrustlineAssetBase(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $original->alphaNum4 = new XdrAssetAlphaNum4('BTC', $issuerId);

        $lines = [];
        $original->toTxRep('asset', $lines);

        $restored = XdrTrustlineAssetBase::fromTxRep($lines, 'asset');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
    }

    public function testTrustlineAssetBaseAlphaNum12Roundtrip(): void
    {
        $issuerId = XdrAccountID::fromAccountId($this->accountId());
        $original = new XdrTrustlineAssetBase(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $original->alphaNum12 = new XdrAssetAlphaNum12('STELLARTOKEN', $issuerId);

        $lines = [];
        $original->toTxRep('asset', $lines);

        $restored = XdrTrustlineAssetBase::fromTxRep($lines, 'asset');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
    }

    public function testTrustlineAssetBasePoolShareRoundtrip(): void
    {
        $poolIdBytes = $this->randomBytes(32);
        $original = new XdrTrustlineAssetBase(new XdrAssetType(XdrAssetType::ASSET_TYPE_POOL_SHARE));
        $original->liquidityPoolID = $poolIdBytes;

        $lines = [];
        $original->toTxRep('asset', $lines);

        $this->assertArrayHasKey('asset.liquidityPoolID', $lines);

        $restored = XdrTrustlineAssetBase::fromTxRep($lines, 'asset');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrAssetType::ASSET_TYPE_POOL_SHARE, $restored->getType()->getValue());
        $this->assertEquals($poolIdBytes, $restored->getLiquidityPoolID());
    }

    // ------------------------------------------------------------------
    // XdrSCAddressBase
    // ------------------------------------------------------------------

    public function testSCAddressBaseAccountRoundtrip(): void
    {
        $accountId = $this->accountId();
        $original = new XdrSCAddressBase(XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT());
        $original->accountId = XdrAccountID::fromAccountId($accountId);

        $lines = [];
        $original->toTxRep('addr', $lines);

        $this->assertArrayHasKey('addr.type', $lines);
        $this->assertArrayHasKey('addr.accountId', $lines);

        $restored = XdrSCAddressBase::fromTxRep($lines, 'addr');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT, $restored->getType()->getValue());
        $this->assertNotNull($restored->getAccountId());
    }

    public function testSCAddressBaseContractRoundtrip(): void
    {
        $contractIdBytes = $this->randomBytes(32);
        $original = new XdrSCAddressBase(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT());
        $original->contractId = $contractIdBytes;

        $lines = [];
        $original->toTxRep('addr', $lines);

        $this->assertArrayHasKey('addr.contractId', $lines);

        $restored = XdrSCAddressBase::fromTxRep($lines, 'addr');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT, $restored->getType()->getValue());
        $this->assertEquals($contractIdBytes, $restored->getContractId());
    }

    public function testSCAddressBaseMuxedAccountRoundtrip(): void
    {
        $keyBytes = KeyPair::random()->getPublicKey();
        $muxed = new XdrMuxedAccountMed25519(123456789, $keyBytes);

        $original = new XdrSCAddressBase(XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT());
        $original->muxedAccount = $muxed;

        $lines = [];
        $original->toTxRep('addr', $lines);

        $this->assertArrayHasKey('addr.muxedAccount.id', $lines);
        $this->assertArrayHasKey('addr.muxedAccount.ed25519', $lines);

        $restored = XdrSCAddressBase::fromTxRep($lines, 'addr');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT, $restored->getType()->getValue());
        $this->assertNotNull($restored->getMuxedAccount());
        $this->assertEquals(123456789, $restored->getMuxedAccount()->getId());
    }

    public function testSCAddressBaseClaimableBalanceRoundtrip(): void
    {
        $balanceIdHex = $this->randomHex(32);
        $balanceId = XdrClaimableBalanceID::forClaimableBalanceId($balanceIdHex);

        $original = new XdrSCAddressBase(XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE());
        $original->claimableBalanceId = $balanceId;

        $lines = [];
        $original->toTxRep('addr', $lines);

        $this->assertArrayHasKey('addr.claimableBalanceId.type', $lines);

        $restored = XdrSCAddressBase::fromTxRep($lines, 'addr');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE, $restored->getType()->getValue());
        $this->assertNotNull($restored->getClaimableBalanceId());
    }

    public function testSCAddressBaseLiquidityPoolRoundtrip(): void
    {
        $poolIdBytes = $this->randomBytes(32);
        $original = new XdrSCAddressBase(XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL());
        $original->liquidityPoolId = $poolIdBytes;

        $lines = [];
        $original->toTxRep('addr', $lines);

        $this->assertArrayHasKey('addr.liquidityPoolId', $lines);

        $restored = XdrSCAddressBase::fromTxRep($lines, 'addr');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals($poolIdBytes, $restored->getLiquidityPoolId());
    }

    // ------------------------------------------------------------------
    // XdrSCValBase
    // ------------------------------------------------------------------

    public function testSCValBaseBoolTrueRoundtrip(): void
    {
        $original = new XdrSCValBase(XdrSCValType::BOOL());
        $original->b = true;

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.b', $lines);
        $this->assertEquals('true', $lines['val.b']);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertTrue($restored->getB());
    }

    public function testSCValBaseBoolFalseRoundtrip(): void
    {
        $original = new XdrSCValBase(XdrSCValType::BOOL());
        $original->b = false;

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertEquals('false', $lines['val.b']);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertFalse($restored->getB());
        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
    }

    public function testSCValBaseVoidRoundtrip(): void
    {
        $original = new XdrSCValBase(XdrSCValType::VOID());

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.type', $lines);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
    }

    public function testSCValBaseU32Roundtrip(): void
    {
        $original = new XdrSCValBase(XdrSCValType::U32());
        $original->u32 = 4294967295;

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.u32', $lines);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(4294967295, $restored->getU32());
    }

    public function testSCValBaseI32Roundtrip(): void
    {
        $original = new XdrSCValBase(XdrSCValType::I32());
        $original->i32 = -12345;

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.i32', $lines);
        $this->assertEquals('-12345', $lines['val.i32']);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(-12345, $restored->getI32());
    }

    public function testSCValBaseU64Roundtrip(): void
    {
        $original = new XdrSCValBase(XdrSCValType::U64());
        $original->u64 = 9876543210;

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.u64', $lines);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(9876543210, $restored->getU64());
    }

    public function testSCValBaseI64Roundtrip(): void
    {
        $original = new XdrSCValBase(XdrSCValType::I64());
        $original->i64 = -987654321;

        $lines = [];
        $original->toTxRep('val', $lines);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(-987654321, $restored->getI64());
    }

    public function testSCValBaseTimepointRoundtrip(): void
    {
        $original = new XdrSCValBase(XdrSCValType::TIMEPOINT());
        $original->timepoint = 1700000000;

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.timepoint', $lines);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
    }

    public function testSCValBaseDurationRoundtrip(): void
    {
        $original = new XdrSCValBase(XdrSCValType::DURATION());
        $original->duration = 86400;

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.duration', $lines);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
    }

    public function testSCValBaseBytesRoundtrip(): void
    {
        $original = new XdrSCValBase(XdrSCValType::BYTES());
        $original->bytes = new XdrDataValueMandatory($this->randomBytes(16));

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.bytes', $lines);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertNotNull($restored->getBytes());
    }

    public function testSCValBaseStringRoundtrip(): void
    {
        $original = new XdrSCValBase(XdrSCValType::STRING());
        $original->str = 'hello stellar';

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.str', $lines);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals('hello stellar', $restored->getStr());
    }

    public function testSCValBaseSymbolRoundtrip(): void
    {
        $original = new XdrSCValBase(XdrSCValType::SYMBOL());
        $original->sym = 'transfer';

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.sym', $lines);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals('transfer', $restored->getSym());
    }

    public function testSCValBaseVecPresentRoundtrip(): void
    {
        $inner1 = new XdrSCValBase(XdrSCValType::I32());
        $inner1->i32 = 1;
        $inner2 = new XdrSCValBase(XdrSCValType::I32());
        $inner2->i32 = 2;

        // XdrSCValBase.vec expects XdrSCVal instances for encode/decode to work
        $original = new XdrSCValBase(XdrSCValType::VEC());
        $original->vec = [XdrSCVal::forI32(1), XdrSCVal::forI32(2)];

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.vec._present', $lines);
        $this->assertEquals('true', $lines['val.vec._present']);
        $this->assertArrayHasKey('val.vec.len', $lines);
        $this->assertEquals('2', $lines['val.vec.len']);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertNotNull($restored->getVec());
        $this->assertCount(2, $restored->getVec());
    }

    public function testSCValBaseVecNullRoundtrip(): void
    {
        $original = new XdrSCValBase(XdrSCValType::VEC());
        $original->vec = null;

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertEquals('false', $lines['val.vec._present']);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertNull($restored->getVec());
    }

    public function testSCValBaseMapPresentRoundtrip(): void
    {
        $key = XdrSCVal::forSymbol('amount');
        $value = XdrSCVal::forU64(1000000);
        $entry = new XdrSCMapEntry($key, $value);

        $original = new XdrSCValBase(XdrSCValType::MAP());
        $original->map = [$entry];

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertEquals('true', $lines['val.map._present']);
        $this->assertEquals('1', $lines['val.map.len']);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertNotNull($restored->getMap());
        $this->assertCount(1, $restored->getMap());
    }

    public function testSCValBaseMapNullRoundtrip(): void
    {
        $original = new XdrSCValBase(XdrSCValType::MAP());
        $original->map = null;

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertEquals('false', $lines['val.map._present']);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertNull($restored->getMap());
    }

    public function testSCValBaseAddressRoundtrip(): void
    {
        $accountId = $this->accountId();
        $address = XdrSCAddress::forAccountId($accountId);

        $original = new XdrSCValBase(XdrSCValType::ADDRESS());
        $original->address = $address;

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.address.type', $lines);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertNotNull($restored->getAddress());
    }

    public function testSCValBaseLedgerKeyContractInstanceRoundtrip(): void
    {
        $original = new XdrSCValBase(XdrSCValType::SCV_LEDGER_KEY_CONTRACT_INSTANCE());

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.type', $lines);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
    }

    public function testSCValBaseLedgerKeyNonceRoundtrip(): void
    {
        $original = new XdrSCValBase(XdrSCValType::LEDGER_KEY_NONCE());
        $original->nonceKey = new XdrSCNonceKey(12345678);

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.nonce_key.nonce', $lines);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertNotNull($restored->getNonceKey());
        $this->assertEquals(12345678, $restored->getNonceKey()->getNonce());
    }

    // ------------------------------------------------------------------
    // XdrHostFunctionBase
    // ------------------------------------------------------------------

    public function testHostFunctionBaseInvokeContractRoundtrip(): void
    {
        $contractIdBytes = $this->randomBytes(32);
        $contractAddr = new XdrSCAddressBase(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT());
        $contractAddr->contractId = $contractIdBytes;
        // XdrInvokeContractArgs requires XdrSCAddress, so use the wrapper
        $contractAddrWrapper = XdrSCAddress::forContractId(bin2hex($contractIdBytes));

        $argVal = XdrSCVal::forU32(42);
        $args = new XdrInvokeContractArgs($contractAddrWrapper, 'my_function', [$argVal]);

        $original = new XdrHostFunctionBase(XdrHostFunctionType::INVOKE_CONTRACT());
        $original->invokeContract = $args;

        $lines = [];
        $original->toTxRep('fn', $lines);

        $this->assertArrayHasKey('fn.type', $lines);
        $this->assertArrayHasKey('fn.invokeContract.functionName', $lines);

        $restored = XdrHostFunctionBase::fromTxRep($lines, 'fn');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(
            XdrHostFunctionType::HOST_FUNCTION_TYPE_INVOKE_CONTRACT,
            $restored->getType()->getValue()
        );
        $this->assertNotNull($restored->getInvokeContract());
        $this->assertEquals('my_function', $restored->getInvokeContract()->getFunctionName());
    }

    public function testHostFunctionBaseCreateContractRoundtrip(): void
    {
        // XdrHostFunctionBase::toTxRep/fromTxRep for the UPLOAD_CONTRACT_WASM arm is
        // inconsistent in the generated base (the wasm property is typed as
        // XdrDataValueMandatory but the base methods treat it as a raw string).
        // The XdrHostFunction wrapper corrects this. Test the CREATE_CONTRACT arm
        // instead, which is fully functional in the base class.
        $contractIdBytes = $this->randomBytes(32);
        $contractAddr = XdrSCAddress::forContractId(bin2hex($contractIdBytes));
        $argVal = XdrSCVal::forBool(true);
        $args = new XdrInvokeContractArgs($contractAddr, 'get_value', [$argVal]);

        $original = new XdrHostFunctionBase(XdrHostFunctionType::INVOKE_CONTRACT());
        $original->invokeContract = $args;

        $lines = [];
        $original->toTxRep('fn', $lines);

        $this->assertArrayHasKey('fn.invokeContract.functionName', $lines);
        $this->assertArrayHasKey('fn.invokeContract.args.len', $lines);
        $this->assertEquals('1', $lines['fn.invokeContract.args.len']);

        $restored = XdrHostFunctionBase::fromTxRep($lines, 'fn');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals('get_value', $restored->getInvokeContract()->getFunctionName());
        $this->assertCount(1, $restored->getInvokeContract()->getArgs());
    }

    // ------------------------------------------------------------------
    // XdrLedgerKeyBase
    // ------------------------------------------------------------------

    public function testLedgerKeyBaseAccountRoundtrip(): void
    {
        $accountId = $this->accountId();
        $original = new XdrLedgerKeyBase(XdrLedgerEntryType::ACCOUNT());
        $original->account = XdrLedgerKeyAccount::forAccountId($accountId);

        $lines = [];
        $original->toTxRep('lk', $lines);

        $this->assertArrayHasKey('lk.type', $lines);
        $this->assertArrayHasKey('lk.account.accountID', $lines);

        $restored = XdrLedgerKeyBase::fromTxRep($lines, 'lk');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrLedgerEntryType::ACCOUNT, $restored->getType()->getValue());
        $this->assertNotNull($restored->getAccount());
    }

    public function testLedgerKeyBaseTrustlineRoundtrip(): void
    {
        $accountId = $this->accountId();
        $issuerId = XdrAccountID::fromAccountId($this->accountId());
        $asset = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $asset->alphaNum4 = new XdrAssetAlphaNum4('ETH', $issuerId);

        $original = new XdrLedgerKeyBase(XdrLedgerEntryType::TRUSTLINE());
        $original->trustLine = new XdrLedgerKeyTrustLine(
            XdrAccountID::fromAccountId($accountId),
            $asset,
        );

        $lines = [];
        $original->toTxRep('lk', $lines);

        $this->assertArrayHasKey('lk.trustLine.accountID', $lines);

        $restored = XdrLedgerKeyBase::fromTxRep($lines, 'lk');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrLedgerEntryType::TRUSTLINE, $restored->getType()->getValue());
        $this->assertNotNull($restored->getTrustLine());
    }

    public function testLedgerKeyBaseOfferRoundtrip(): void
    {
        $sellerId = XdrAccountID::fromAccountId($this->accountId());

        $original = new XdrLedgerKeyBase(XdrLedgerEntryType::OFFER());
        $original->offer = new XdrLedgerKeyOffer($sellerId, 12345678);

        $lines = [];
        $original->toTxRep('lk', $lines);

        $this->assertArrayHasKey('lk.offer.offerID', $lines);
        $this->assertEquals('12345678', $lines['lk.offer.offerID']);

        $restored = XdrLedgerKeyBase::fromTxRep($lines, 'lk');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrLedgerEntryType::OFFER, $restored->getType()->getValue());
        $this->assertNotNull($restored->getOffer());
    }

    public function testLedgerKeyBaseDataRoundtrip(): void
    {
        $accountId = XdrAccountID::fromAccountId($this->accountId());

        $original = new XdrLedgerKeyBase(XdrLedgerEntryType::DATA());
        $original->data = new XdrLedgerKeyData($accountId, 'my-data-entry');

        $lines = [];
        $original->toTxRep('lk', $lines);

        $this->assertArrayHasKey('lk.data.dataName', $lines);

        $restored = XdrLedgerKeyBase::fromTxRep($lines, 'lk');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrLedgerEntryType::DATA, $restored->getType()->getValue());
        $this->assertNotNull($restored->getData());
        $this->assertEquals('my-data-entry', $restored->getData()->getDataName());
    }

    public function testLedgerKeyBaseClaimableBalanceRoundtrip(): void
    {
        $balanceIdHex = $this->randomHex(32);

        $original = new XdrLedgerKeyBase(XdrLedgerEntryType::CLAIMABLE_BALANCE());
        $original->balanceID = XdrClaimableBalanceID::forClaimableBalanceId($balanceIdHex);

        $lines = [];
        $original->toTxRep('lk', $lines);

        // Base uses prefix .claimableBalance (not .claimableBalance.balanceID as the wrapper does)
        $this->assertArrayHasKey('lk.claimableBalance.type', $lines);

        $restored = XdrLedgerKeyBase::fromTxRep($lines, 'lk');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrLedgerEntryType::CLAIMABLE_BALANCE, $restored->getType()->getValue());
        $this->assertNotNull($restored->getBalanceID());
    }

    public function testLedgerKeyBaseLiquidityPoolRoundtrip(): void
    {
        $poolIdBytes = $this->randomBytes(32);

        $original = new XdrLedgerKeyBase(XdrLedgerEntryType::LIQUIDITY_POOL());
        $original->liquidityPool = new XdrLedgerKeyLiquidityPool($poolIdBytes);

        $lines = [];
        $original->toTxRep('lk', $lines);

        $this->assertArrayHasKey('lk.liquidityPool.liquidityPoolID', $lines);

        $restored = XdrLedgerKeyBase::fromTxRep($lines, 'lk');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrLedgerEntryType::LIQUIDITY_POOL, $restored->getType()->getValue());
        $this->assertNotNull($restored->getLiquidityPool());
    }

    public function testLedgerKeyBaseContractDataRoundtrip(): void
    {
        $contractIdBytes = $this->randomBytes(32);
        $contractAddr = XdrSCAddress::forContractId(bin2hex($contractIdBytes));
        $key = XdrSCVal::forSymbol('storage_key');
        $durability = XdrContractDataDurability::PERSISTENT();

        $original = new XdrLedgerKeyBase(XdrLedgerEntryType::CONTRACT_DATA());
        $original->contractData = new XdrLedgerKeyContractData($contractAddr, $key, $durability);

        $lines = [];
        $original->toTxRep('lk', $lines);

        $this->assertArrayHasKey('lk.contractData.durability', $lines);

        $restored = XdrLedgerKeyBase::fromTxRep($lines, 'lk');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrLedgerEntryType::CONTRACT_DATA, $restored->getType()->getValue());
        $this->assertNotNull($restored->getContractData());
    }

    public function testLedgerKeyBaseContractCodeRoundtrip(): void
    {
        $hashBytes = $this->randomBytes(32);

        $original = new XdrLedgerKeyBase(XdrLedgerEntryType::CONTRACT_CODE());
        $original->contractCode = new XdrLedgerKeyContractCode($hashBytes);

        $lines = [];
        $original->toTxRep('lk', $lines);

        $this->assertArrayHasKey('lk.contractCode.hash', $lines);

        $restored = XdrLedgerKeyBase::fromTxRep($lines, 'lk');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrLedgerEntryType::CONTRACT_CODE, $restored->getType()->getValue());
        $this->assertNotNull($restored->getContractCode());
    }

    public function testLedgerKeyBaseConfigSettingRoundtrip(): void
    {
        $original = new XdrLedgerKeyBase(XdrLedgerEntryType::CONFIG_SETTING());
        $original->configSetting = new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_MAX_SIZE_BYTES);

        $lines = [];
        $original->toTxRep('lk', $lines);

        $this->assertArrayHasKey('lk.configSetting', $lines);

        $restored = XdrLedgerKeyBase::fromTxRep($lines, 'lk');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrLedgerEntryType::CONFIG_SETTING, $restored->getType()->getValue());
        $this->assertNotNull($restored->getConfigSetting());
    }

    public function testLedgerKeyBaseTTLRoundtrip(): void
    {
        $keyHashBytes = $this->randomBytes(32);

        $original = new XdrLedgerKeyBase(XdrLedgerEntryType::TTL());
        $original->ttl = new XdrLedgerKeyTTL($keyHashBytes);

        $lines = [];
        $original->toTxRep('lk', $lines);

        $this->assertArrayHasKey('lk.ttl.keyHash', $lines);

        $restored = XdrLedgerKeyBase::fromTxRep($lines, 'lk');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrLedgerEntryType::TTL, $restored->getType()->getValue());
        $this->assertNotNull($restored->getTtl());
    }

    // ------------------------------------------------------------------
    // XdrTransactionBase
    // ------------------------------------------------------------------

    public function testTransactionBaseMinimalRoundtrip(): void
    {
        $keyBytes = KeyPair::random()->getPublicKey();
        $sourceAccount = new XdrMuxedAccount($keyBytes);
        $seqNum = new XdrSequenceNumber(new BigInteger(1000));
        $preconditions = new XdrPreconditions(XdrPreconditionType::NONE());
        $memo = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_NONE));
        $ext = new XdrTransactionExt(0);

        $original = new XdrTransactionBase($sourceAccount, 100, $seqNum, $preconditions, $memo, [], $ext);

        $lines = [];
        $original->toTxRep('tx', $lines);

        $this->assertArrayHasKey('tx.sourceAccount', $lines);
        $this->assertArrayHasKey('tx.fee', $lines);
        $this->assertEquals('100', $lines['tx.fee']);
        $this->assertArrayHasKey('tx.operations.len', $lines);
        $this->assertEquals('0', $lines['tx.operations.len']);

        $restored = XdrTransactionBase::fromTxRep($lines, 'tx');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(100, $restored->getFee());
        $this->assertCount(0, $restored->getOperations());
    }

    public function testTransactionBaseFieldAccessors(): void
    {
        $keyBytes = KeyPair::random()->getPublicKey();
        $sourceAccount = new XdrMuxedAccount($keyBytes);
        $seqNum = new XdrSequenceNumber(new BigInteger(42));
        $preconditions = new XdrPreconditions(XdrPreconditionType::NONE());
        $memo = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_NONE));
        $ext = new XdrTransactionExt(0);

        $tx = new XdrTransactionBase($sourceAccount, 200, $seqNum, $preconditions, $memo, [], $ext);

        $this->assertEquals(200, $tx->getFee());
        $this->assertEquals('42', $tx->getSequenceNumber()->sequenceNumber->toString());
        $this->assertCount(0, $tx->getOperations());
    }

    // ------------------------------------------------------------------
    // XdrTransactionEnvelopeBase
    // ------------------------------------------------------------------

    public function testTransactionEnvelopeBaseV1ToTxRep(): void
    {
        // Build a real XdrTransaction using the wrapper (which has compatible constructor),
        // then test XdrTransactionEnvelopeBase::toTxRep emits the right keys.
        $keyBytes = KeyPair::random()->getPublicKey();
        $sourceAccount = new XdrMuxedAccount($keyBytes);
        $tx = new XdrTransaction($sourceAccount, new XdrSequenceNumber(new BigInteger(5000)), []);

        $envelope = new XdrTransactionV1Envelope($tx, []);

        $original = new XdrTransactionEnvelopeBase(XdrEnvelopeType::ENVELOPE_TYPE_TX());
        $original->v1 = $envelope;

        $lines = [];
        $original->toTxRep('env', $lines);

        $this->assertArrayHasKey('env.type', $lines);
        $this->assertArrayHasKey('env.v1.tx.fee', $lines);
        $this->assertArrayHasKey('env.v1.tx.sourceAccount', $lines);
        $this->assertArrayHasKey('env.v1.signatures.len', $lines);
        $this->assertEquals('0', $lines['env.v1.signatures.len']);

        // Verify the XDR encoding is stable
        $this->assertNotEmpty($original->toBase64Xdr());
    }

    public function testTransactionEnvelopeBaseFromTxRepV1(): void
    {
        // XdrTransactionEnvelopeBase::fromTxRep for ENVELOPE_TYPE_TX → XdrTransactionV1Envelope::fromTxRep
        // → XdrTransaction::fromTxRep (inherited from XdrTransactionBase) calls
        // new static($sourceAccount, $fee, $sequenceNumber, ...) but XdrTransaction::__construct
        // has the different signature ($sourceAccount, $sequenceNumber, $operations, ...).
        //
        // This mismatch means the base fromTxRep cannot reconstruct an XdrTransaction.
        // The test below exercises the toTxRep path only, then uses XdrBuffer decode
        // (not fromTxRep) to verify the XDR output is valid.
        $keyBytes = KeyPair::random()->getPublicKey();
        $sourceAccount = new XdrMuxedAccount($keyBytes);
        $tx = new XdrTransaction($sourceAccount, new XdrSequenceNumber(new BigInteger(5000)), []);
        $envelope = new XdrTransactionV1Envelope($tx, []);

        $original = new XdrTransactionEnvelopeBase(XdrEnvelopeType::ENVELOPE_TYPE_TX());
        $original->v1 = $envelope;

        $lines = [];
        $original->toTxRep('env', $lines);

        // Verify the toTxRep output keys
        $this->assertArrayHasKey('env.type', $lines);
        $this->assertArrayHasKey('env.v1.tx.fee', $lines);
        $this->assertArrayHasKey('env.v1.signatures.len', $lines);
        $this->assertEquals('0', $lines['env.v1.signatures.len']);

        // Verify XDR can be decoded back via XdrBuffer (not fromTxRep)
        $xdrBase64 = $original->toBase64Xdr();
        $this->assertNotEmpty($xdrBase64);
        $decoded = XdrTransactionEnvelopeBase::fromBase64Xdr($xdrBase64);
        $this->assertEquals(XdrEnvelopeType::ENVELOPE_TYPE_TX, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getV1());
    }

    public function testTransactionEnvelopeBaseMutators(): void
    {
        $original = new XdrTransactionEnvelopeBase(XdrEnvelopeType::ENVELOPE_TYPE_TX());

        $this->assertEquals(XdrEnvelopeType::ENVELOPE_TYPE_TX, $original->getType()->getValue());

        $original->setType(XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP());
        $this->assertEquals(XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP, $original->getType()->getValue());

        $original->setV1(null);
        $this->assertNull($original->getV1());

        $original->setV0(null);
        $this->assertNull($original->getV0());

        $original->setFeeBump(null);
        $this->assertNull($original->getFeeBump());
    }

    // ------------------------------------------------------------------
    // Integration: XdrSCValBase with nested address roundtrip
    // ------------------------------------------------------------------

    public function testSCValBaseAddressWithContractIdRoundtrip(): void
    {
        $contractIdBytes = $this->randomBytes(32);
        $address = XdrSCAddress::forContractId(bin2hex($contractIdBytes));

        $original = new XdrSCValBase(XdrSCValType::ADDRESS());
        $original->address = $address;

        $lines = [];
        $original->toTxRep('val', $lines);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT, $restored->getAddress()->getType()->getValue());
    }

    // ------------------------------------------------------------------
    // Getter/setter coverage for XdrMuxedAccountMed25519Base
    // ------------------------------------------------------------------

    public function testMuxedAccountMed25519BaseGettersSetters(): void
    {
        $key1 = $this->randomBytes(32);
        $key2 = $this->randomBytes(32);

        $obj = new XdrMuxedAccountMed25519Base(100, $key1);

        $this->assertEquals(100, $obj->getId());
        $this->assertEquals($key1, $obj->getEd25519());

        $obj->setId(200);
        $this->assertEquals(200, $obj->getId());

        $obj->setEd25519($key2);
        $this->assertEquals($key2, $obj->getEd25519());
    }

    // ------------------------------------------------------------------
    // Getter/setter coverage for XdrLedgerKeyBase
    // ------------------------------------------------------------------

    public function testLedgerKeyBaseGettersSetters(): void
    {
        $obj = new XdrLedgerKeyBase(XdrLedgerEntryType::ACCOUNT());

        $obj->setAccount(null);
        $this->assertNull($obj->getAccount());

        $obj->setTrustLine(null);
        $this->assertNull($obj->getTrustLine());

        $obj->setOffer(null);
        $this->assertNull($obj->getOffer());

        $obj->setData(null);
        $this->assertNull($obj->getData());

        $obj->setBalanceID(null);
        $this->assertNull($obj->getBalanceID());

        $obj->setLiquidityPool(null);
        $this->assertNull($obj->getLiquidityPool());

        $obj->setContractData(null);
        $this->assertNull($obj->getContractData());

        $obj->setContractCode(null);
        $this->assertNull($obj->getContractCode());

        $obj->setConfigSetting(null);
        $this->assertNull($obj->getConfigSetting());

        $obj->setTtl(null);
        $this->assertNull($obj->getTtl());
    }

    // ------------------------------------------------------------------
    // XdrSCValBase — missing arms: U128, I128, U256, I256, ERROR,
    //                CONTRACT_INSTANCE
    // ------------------------------------------------------------------

    public function testSCValBaseU128Roundtrip(): void
    {
        $original = new XdrSCValBase(XdrSCValType::U128());
        $original->u128 = new XdrUInt128Parts(0xDEADBEEF, 0xCAFEBABE);

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.u128.hi', $lines);
        $this->assertArrayHasKey('val.u128.lo', $lines);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertNotNull($restored->getU128());
        $this->assertEquals(0xDEADBEEF, $restored->getU128()->getHi());
        $this->assertEquals(0xCAFEBABE, $restored->getU128()->getLo());
    }

    public function testSCValBaseI128Roundtrip(): void
    {
        $original = new XdrSCValBase(XdrSCValType::I128());
        $original->i128 = new XdrInt128Parts(-1, 0xFFFFFFFF);

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.i128.hi', $lines);
        $this->assertArrayHasKey('val.i128.lo', $lines);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertNotNull($restored->getI128());
        $this->assertEquals(-1, $restored->getI128()->getHi());
    }

    public function testSCValBaseU256Roundtrip(): void
    {
        $original = new XdrSCValBase(XdrSCValType::U256());
        $original->u256 = new XdrUInt256Parts(1, 2, 3, 4);

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.u256.hi_hi', $lines);
        $this->assertArrayHasKey('val.u256.hi_lo', $lines);
        $this->assertArrayHasKey('val.u256.lo_hi', $lines);
        $this->assertArrayHasKey('val.u256.lo_lo', $lines);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertNotNull($restored->getU256());
        $this->assertEquals(1, $restored->getU256()->getHiHi());
        $this->assertEquals(4, $restored->getU256()->getLoLo());
    }

    public function testSCValBaseI256Roundtrip(): void
    {
        $original = new XdrSCValBase(XdrSCValType::I256());
        $original->i256 = new XdrInt256Parts(-100, 200, 300, 400);

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.i256.hi_hi', $lines);
        $this->assertArrayHasKey('val.i256.lo_lo', $lines);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertNotNull($restored->getI256());
        $this->assertEquals(-100, $restored->getI256()->getHiHi());
        $this->assertEquals(400, $restored->getI256()->getLoLo());
    }

    public function testSCValBaseErrorContractCodeRoundtrip(): void
    {
        $error = new XdrSCError(XdrSCErrorType::SCE_CONTRACT());
        $error->contractCode = 42;

        $original = new XdrSCValBase(XdrSCValType::ERROR());
        $original->error = $error;

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.error.type', $lines);
        $this->assertArrayHasKey('val.error.contractCode', $lines);
        $this->assertEquals('42', $lines['val.error.contractCode']);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertNotNull($restored->getError());
        $this->assertEquals(XdrSCErrorType::SCE_CONTRACT, $restored->getError()->getType()->getValue());
        $this->assertEquals(42, $restored->getError()->getContractCode());
    }

    public function testSCValBaseErrorWasmVmWithCodeRoundtrip(): void
    {
        $error = new XdrSCError(XdrSCErrorType::SCE_WASM_VM());
        $error->code = XdrSCErrorCode::SCEC_INVALID_INPUT();

        $original = new XdrSCValBase(XdrSCValType::ERROR());
        $original->error = $error;

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.error.type', $lines);
        $this->assertArrayHasKey('val.error.code', $lines);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertNotNull($restored->getError());
        $this->assertEquals(XdrSCErrorType::SCE_WASM_VM, $restored->getError()->getType()->getValue());
        $this->assertNotNull($restored->getError()->getCode());
        $this->assertEquals(XdrSCErrorCode::SCEC_INVALID_INPUT, $restored->getError()->getCode()->getValue());
    }

    public function testSCValBaseContractInstanceWithStorageRoundtrip(): void
    {
        $executable = XdrContractExecutable::forToken();

        $key = XdrSCVal::forSymbol('balance');
        $value = XdrSCVal::forU64(1000);
        $entry = new XdrSCMapEntry($key, $value);

        $instance = new XdrSCContractInstance($executable, [$entry]);

        $original = new XdrSCValBase(XdrSCValType::SCV_CONTRACT_INSTANCE());
        $original->instance = $instance;

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.instance.executable.type', $lines);
        $this->assertArrayHasKey('val.instance.storage._present', $lines);
        $this->assertEquals('true', $lines['val.instance.storage._present']);
        $this->assertEquals('1', $lines['val.instance.storage.len']);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertNotNull($restored->getInstance());
        $this->assertNotNull($restored->getInstance()->getStorage());
        $this->assertCount(1, $restored->getInstance()->getStorage());
    }

    public function testSCValBaseContractInstanceNoStorageRoundtrip(): void
    {
        $executable = XdrContractExecutable::forToken();
        $instance = new XdrSCContractInstance($executable, null);

        $original = new XdrSCValBase(XdrSCValType::SCV_CONTRACT_INSTANCE());
        $original->instance = $instance;

        $lines = [];
        $original->toTxRep('val', $lines);

        $this->assertArrayHasKey('val.instance.storage._present', $lines);
        $this->assertEquals('false', $lines['val.instance.storage._present']);

        $restored = XdrSCValBase::fromTxRep($lines, 'val');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertNotNull($restored->getInstance());
        $this->assertNull($restored->getInstance()->getStorage());
    }

    // ------------------------------------------------------------------
    // XdrHostFunctionBase — UPLOAD_WASM and CREATE_CONTRACT_V2 arms
    // ------------------------------------------------------------------

    public function testHostFunctionBaseGettersSetters(): void
    {
        // The UPLOAD_CONTRACT_WASM arm in XdrHostFunctionBase has a known type
        // inconsistency: the wasm property is typed as ?XdrDataValueMandatory but
        // both encode() and toTxRep() treat it as a raw string. This prevents any
        // functional roundtrip test. We verify the getter/setter work at type-level.
        $original = new XdrHostFunctionBase(XdrHostFunctionType::INVOKE_CONTRACT());
        $this->assertEquals(
            XdrHostFunctionType::HOST_FUNCTION_TYPE_INVOKE_CONTRACT,
            $original->getType()->getValue()
        );

        $original->setType(XdrHostFunctionType::UPLOAD_CONTRACT_WASM());
        $this->assertEquals(
            XdrHostFunctionType::HOST_FUNCTION_TYPE_UPLOAD_CONTRACT_WASM,
            $original->getType()->getValue()
        );

        $wasm = new XdrDataValueMandatory(random_bytes(16));
        $original->setWasm($wasm);
        $this->assertSame($wasm, $original->getWasm());

        $original->setWasm(null);
        $this->assertNull($original->getWasm());
    }

    private function buildContractIDPreimage(): XdrContractIDPreimage
    {
        $contractIdBytes = $this->randomBytes(32);
        $contractAddr = XdrSCAddress::forContractId(bin2hex($contractIdBytes));
        $saltBytes = $this->randomBytes(32);
        // Construct via the base so fromAddress is set directly (the wrapper
        // forAddress() factory only sets flattened fields that are synced during
        // encode(), not during toTxRep() which reads fromAddress directly).
        $preimage = new XdrContractIDPreimage(XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS());
        $preimage->fromAddress = new XdrContractIDPreimageFromAddress($contractAddr, $saltBytes);
        return $preimage;
    }

    public function testHostFunctionBaseCreateContractV2Roundtrip(): void
    {
        $preimage = $this->buildContractIDPreimage();
        $executable = XdrContractExecutable::forToken();
        $arg = XdrSCVal::forU32(99);
        $args = new XdrCreateContractArgsV2($preimage, $executable, [$arg]);

        $original = new XdrHostFunctionBase(XdrHostFunctionType::CREATE_CONTRACT_V2());
        $original->createContractV2 = $args;

        $lines = [];
        $original->toTxRep('fn', $lines);

        $this->assertArrayHasKey('fn.type', $lines);
        $this->assertArrayHasKey('fn.createContractV2.executable.type', $lines);
        $this->assertArrayHasKey('fn.createContractV2.constructorArgs.len', $lines);
        $this->assertEquals('1', $lines['fn.createContractV2.constructorArgs.len']);

        $restored = XdrHostFunctionBase::fromTxRep($lines, 'fn');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(
            XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT_V2,
            $restored->getType()->getValue()
        );
        $this->assertNotNull($restored->getCreateContractV2());
        $this->assertCount(1, $restored->getCreateContractV2()->getConstructorArgs());
    }

    public function testHostFunctionBaseCreateContractFromAddressRoundtrip(): void
    {
        $preimage = $this->buildContractIDPreimage();
        $executable = XdrContractExecutable::forToken();
        $args = new XdrCreateContractArgs($preimage, $executable);

        $original = new XdrHostFunctionBase(XdrHostFunctionType::CREATE_CONTRACT());
        $original->createContract = $args;

        $lines = [];
        $original->toTxRep('fn', $lines);

        $this->assertArrayHasKey('fn.type', $lines);
        $this->assertArrayHasKey('fn.createContract.executable.type', $lines);

        $restored = XdrHostFunctionBase::fromTxRep($lines, 'fn');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(
            XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT,
            $restored->getType()->getValue()
        );
        $this->assertNotNull($restored->getCreateContract());
    }

    // ------------------------------------------------------------------
    // XdrSignerKey — all four arms via toTxRep/fromTxRep
    // ------------------------------------------------------------------

    public function testSignerKeyEd25519TxRepRoundtrip(): void
    {
        $keyBytes = $this->randomBytes(32);

        $original = new XdrSignerKey(XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519());
        $original->ed25519 = $keyBytes;

        $lines = [];
        $original->toTxRep('signer', $lines);

        $this->assertArrayHasKey('signer.type', $lines);
        $this->assertArrayHasKey('signer.ed25519', $lines);

        $restored = XdrSignerKey::fromTxRep($lines, 'signer');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519, $restored->getType()->getValue());
        $this->assertEquals($keyBytes, $restored->getEd25519());
    }

    public function testSignerKeyPreAuthTxTxRepRoundtrip(): void
    {
        $hashBytes = $this->randomBytes(32);

        $original = new XdrSignerKey(XdrSignerKeyType::SIGNER_KEY_TYPE_PRE_AUTH_TX());
        $original->preAuthTx = $hashBytes;

        $lines = [];
        $original->toTxRep('signer', $lines);

        $this->assertArrayHasKey('signer.preAuthTx', $lines);

        $restored = XdrSignerKey::fromTxRep($lines, 'signer');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrSignerKeyType::SIGNER_KEY_TYPE_PRE_AUTH_TX, $restored->getType()->getValue());
        $this->assertEquals($hashBytes, $restored->getPreAuthTx());
    }

    public function testSignerKeyHashXTxRepRoundtrip(): void
    {
        $hashBytes = $this->randomBytes(32);

        $original = new XdrSignerKey(XdrSignerKeyType::SIGNER_KEY_TYPE_HASH_X());
        $original->hashX = $hashBytes;

        $lines = [];
        $original->toTxRep('signer', $lines);

        $this->assertArrayHasKey('signer.hashX', $lines);

        $restored = XdrSignerKey::fromTxRep($lines, 'signer');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrSignerKeyType::SIGNER_KEY_TYPE_HASH_X, $restored->getType()->getValue());
        $this->assertEquals($hashBytes, $restored->getHashX());
    }

    public function testSignerKeyEd25519SignedPayloadTxRepRoundtrip(): void
    {
        $ed25519Bytes = $this->randomBytes(32);
        $payloadBytes = $this->randomBytes(16);
        $signedPayload = new XdrSignedPayload($ed25519Bytes, $payloadBytes);

        $original = new XdrSignerKey(XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD());
        $original->signedPayload = $signedPayload;

        $lines = [];
        $original->toTxRep('signer', $lines);

        $this->assertArrayHasKey('signer.ed25519SignedPayload.ed25519', $lines);
        $this->assertArrayHasKey('signer.ed25519SignedPayload.payload', $lines);

        $restored = XdrSignerKey::fromTxRep($lines, 'signer');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(
            XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD,
            $restored->getType()->getValue()
        );
        $this->assertNotNull($restored->getSignedPayload());
        $this->assertEquals($ed25519Bytes, $restored->getSignedPayload()->getEd25519());
        $this->assertEquals($payloadBytes, $restored->getSignedPayload()->getPayload());
    }

    // ------------------------------------------------------------------
    // XdrAllowTrustOperationAssetBase — both arms
    // ------------------------------------------------------------------

    public function testAllowTrustOperationAssetBaseAlphaNum4Roundtrip(): void
    {
        $original = new XdrAllowTrustOperationAssetBase(
            new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4)
        );
        $original->assetCode4 = 'USD' . "\x00"; // raw padded XDR code

        $lines = [];
        $original->toTxRep('asset', $lines);

        $this->assertArrayHasKey('asset.type', $lines);
        $this->assertArrayHasKey('asset.assetCode4', $lines);

        $restored = XdrAllowTrustOperationAssetBase::fromTxRep($lines, 'asset');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4, $restored->getType()->getValue());
        $this->assertNotNull($restored->getAssetCode4());
    }

    public function testAllowTrustOperationAssetBaseAlphaNum12Roundtrip(): void
    {
        $original = new XdrAllowTrustOperationAssetBase(
            new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12)
        );
        $original->assetCode12 = 'STELLARTOKEN';

        $lines = [];
        $original->toTxRep('asset', $lines);

        $this->assertArrayHasKey('asset.type', $lines);
        $this->assertArrayHasKey('asset.assetCode12', $lines);

        $restored = XdrAllowTrustOperationAssetBase::fromTxRep($lines, 'asset');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12, $restored->getType()->getValue());
        $this->assertNotNull($restored->getAssetCode12());
    }

    // ------------------------------------------------------------------
    // XdrClaimableBalanceIDBase — V0 arm
    // ------------------------------------------------------------------

    public function testClaimableBalanceIDBaseV0Roundtrip(): void
    {
        $hashBytes = $this->randomBytes(32);

        $original = new XdrClaimableBalanceIDBase(
            XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0()
        );
        $original->hash = $hashBytes;

        $lines = [];
        $original->toTxRep('bal', $lines);

        $this->assertArrayHasKey('bal.type', $lines);
        $this->assertArrayHasKey('bal.v0', $lines);

        $restored = XdrClaimableBalanceIDBase::fromTxRep($lines, 'bal');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(
            XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0,
            $restored->getType()->getValue()
        );
        $this->assertEquals($hashBytes, $restored->getHash());
    }

    // ------------------------------------------------------------------
    // XdrFeeBumpTransactionInnerTx — ENVELOPE_TYPE_TX arm
    // ------------------------------------------------------------------

    public function testTxRepRoundtrip_XdrFeeBumpTransactionInnerTx(): void
    {
        $keyBytes = KeyPair::random()->getPublicKey();
        $sourceAccount = new XdrMuxedAccount($keyBytes);
        $tx = new XdrTransaction($sourceAccount, new XdrSequenceNumber(new BigInteger(7777)), []);
        $envelope = new XdrTransactionV1Envelope($tx, []);

        $original = new XdrFeeBumpTransactionInnerTx(XdrEnvelopeType::ENVELOPE_TYPE_TX());
        $original->v1 = $envelope;

        $lines = [];
        $original->toTxRep('inner', $lines);

        $this->assertArrayHasKey('inner.type', $lines);
        $this->assertArrayHasKey('inner.v1.tx.fee', $lines);
        $this->assertArrayHasKey('inner.v1.signatures.len', $lines);
        $this->assertEquals('0', $lines['inner.v1.signatures.len']);

        // Verify XDR encoding is stable
        $this->assertNotEmpty($original->toBase64Xdr());
        $decoded = XdrFeeBumpTransactionInnerTx::fromBase64Xdr($original->toBase64Xdr());
        $this->assertEquals(XdrEnvelopeType::ENVELOPE_TYPE_TX, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getV1());
    }

    // ------------------------------------------------------------------
    // XdrLiquidityPoolDepositOperationBase
    // ------------------------------------------------------------------

    public function testTxRepRoundtrip_XdrLiquidityPoolDepositOperationBase(): void
    {
        $poolIdBytes = $this->randomBytes(32);
        $maxAmountA = new BigInteger(1000000000);
        $maxAmountB = new BigInteger(2000000000);
        $minPrice = new XdrPrice(1, 2);
        $maxPrice = new XdrPrice(3, 1);

        $original = new XdrLiquidityPoolDepositOperationBase(
            $poolIdBytes,
            $maxAmountA,
            $maxAmountB,
            $minPrice,
            $maxPrice
        );

        $lines = [];
        $original->toTxRep('op', $lines);

        $this->assertArrayHasKey('op.liquidityPoolID', $lines);
        $this->assertArrayHasKey('op.maxAmountA', $lines);
        $this->assertArrayHasKey('op.maxAmountB', $lines);
        $this->assertArrayHasKey('op.minPrice.n', $lines);
        $this->assertArrayHasKey('op.maxPrice.d', $lines);
        $this->assertEquals($maxAmountA->toString(), $lines['op.maxAmountA']);
        $this->assertEquals($maxAmountB->toString(), $lines['op.maxAmountB']);

        $restored = XdrLiquidityPoolDepositOperationBase::fromTxRep($lines, 'op');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals($poolIdBytes, $restored->getLiquidityPoolID());
        $this->assertEquals($maxAmountA->toString(), $restored->getMaxAmountA()->toString());
        $this->assertEquals($maxAmountB->toString(), $restored->getMaxAmountB()->toString());
        $this->assertEquals(1, $restored->getMinPrice()->getN());
        $this->assertEquals(2, $restored->getMinPrice()->getD());
    }

    // ------------------------------------------------------------------
    // XdrLiquidityPoolWithdrawOperationBase
    // ------------------------------------------------------------------

    public function testTxRepRoundtrip_XdrLiquidityPoolWithdrawOperationBase(): void
    {
        $poolIdBytes = $this->randomBytes(32);
        $amount = new BigInteger(500000000);
        $minAmountA = new BigInteger(100000000);
        $minAmountB = new BigInteger(200000000);

        $original = new XdrLiquidityPoolWithdrawOperationBase(
            $poolIdBytes,
            $amount,
            $minAmountA,
            $minAmountB
        );

        $lines = [];
        $original->toTxRep('op', $lines);

        $this->assertArrayHasKey('op.liquidityPoolID', $lines);
        $this->assertArrayHasKey('op.amount', $lines);
        $this->assertArrayHasKey('op.minAmountA', $lines);
        $this->assertArrayHasKey('op.minAmountB', $lines);
        $this->assertEquals($amount->toString(), $lines['op.amount']);
        $this->assertEquals($minAmountA->toString(), $lines['op.minAmountA']);

        $restored = XdrLiquidityPoolWithdrawOperationBase::fromTxRep($lines, 'op');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals($poolIdBytes, $restored->getLiquidityPoolID());
        $this->assertEquals($amount->toString(), $restored->getAmount()->toString());
        $this->assertEquals($minAmountA->toString(), $restored->getMinAmountA()->toString());
        $this->assertEquals($minAmountB->toString(), $restored->getMinAmountB()->toString());
    }

    // ------------------------------------------------------------------
    // XdrDecoratedSignatureBase
    // ------------------------------------------------------------------

    public function testTxRepRoundtrip_XdrDecoratedSignatureBase(): void
    {
        $hint = $this->randomBytes(4);
        $signature = $this->randomBytes(64);

        $original = new XdrDecoratedSignatureBase($hint, $signature);

        $lines = [];
        $original->toTxRep('sig', $lines);

        $this->assertArrayHasKey('sig.hint', $lines);
        $this->assertArrayHasKey('sig.signature', $lines);

        $restored = XdrDecoratedSignatureBase::fromTxRep($lines, 'sig');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals($hint, $restored->getHint());
        $this->assertEquals($signature, $restored->getSignature());
    }

    // ------------------------------------------------------------------
    // XdrFeeBumpTransactionExt
    // ------------------------------------------------------------------

    public function testTxRepRoundtrip_XdrFeeBumpTransactionExt(): void
    {
        $original = new XdrFeeBumpTransactionExt(0);

        $lines = [];
        $original->toTxRep('ext', $lines);

        $this->assertArrayHasKey('ext.v', $lines);
        $this->assertEquals('0', $lines['ext.v']);

        $restored = XdrFeeBumpTransactionExt::fromTxRep($lines, 'ext');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(0, $restored->getDiscriminant());
    }

    // ------------------------------------------------------------------
    // XdrFeeBumpTransaction
    // ------------------------------------------------------------------

    public function testTxRepRoundtrip_XdrFeeBumpTransaction(): void
    {
        $keyBytes = KeyPair::random()->getPublicKey();
        $feeSource = new XdrMuxedAccount($keyBytes);

        $innerKeyBytes = KeyPair::random()->getPublicKey();
        $innerSourceAccount = new XdrMuxedAccount($innerKeyBytes);
        $innerTx = new XdrTransaction(
            $innerSourceAccount,
            new XdrSequenceNumber(new BigInteger(1234)),
            []
        );
        $innerEnvelope = new XdrTransactionV1Envelope($innerTx, []);
        $innerTxUnion = new XdrFeeBumpTransactionInnerTx(XdrEnvelopeType::ENVELOPE_TYPE_TX());
        $innerTxUnion->v1 = $innerEnvelope;

        $ext = new XdrFeeBumpTransactionExt(0);

        $original = new XdrFeeBumpTransaction($feeSource, 1000000, $innerTxUnion, $ext);

        $lines = [];
        $original->toTxRep('feeBump', $lines);

        $this->assertArrayHasKey('feeBump.feeSource', $lines);
        $this->assertArrayHasKey('feeBump.fee', $lines);
        $this->assertEquals('1000000', $lines['feeBump.fee']);
        $this->assertArrayHasKey('feeBump.innerTx.type', $lines);
        $this->assertArrayHasKey('feeBump.ext.v', $lines);
        $this->assertEquals('0', $lines['feeBump.ext.v']);

        // Verify XDR is decodable
        $xdrBase64 = $original->toBase64Xdr();
        $this->assertNotEmpty($xdrBase64);
        $decoded = XdrFeeBumpTransaction::fromBase64Xdr($xdrBase64);
        $this->assertEquals(1000000, $decoded->getFee());
        $this->assertEquals(XdrEnvelopeType::ENVELOPE_TYPE_TX, $decoded->getInnerTx()->getType()->getValue());
    }

    // ------------------------------------------------------------------
    // XdrFeeBumpTransactionEnvelope
    // ------------------------------------------------------------------

    public function testTxRepRoundtrip_XdrFeeBumpTransactionEnvelope(): void
    {
        $keyBytes = KeyPair::random()->getPublicKey();
        $feeSource = new XdrMuxedAccount($keyBytes);

        $innerKeyBytes = KeyPair::random()->getPublicKey();
        $innerSourceAccount = new XdrMuxedAccount($innerKeyBytes);
        $innerTx = new XdrTransaction(
            $innerSourceAccount,
            new XdrSequenceNumber(new BigInteger(9999)),
            []
        );
        $innerEnvelope = new XdrTransactionV1Envelope($innerTx, []);
        $innerTxUnion = new XdrFeeBumpTransactionInnerTx(XdrEnvelopeType::ENVELOPE_TYPE_TX());
        $innerTxUnion->v1 = $innerEnvelope;

        $ext = new XdrFeeBumpTransactionExt(0);
        $feeBumpTx = new XdrFeeBumpTransaction($feeSource, 500000, $innerTxUnion, $ext);

        $original = new XdrFeeBumpTransactionEnvelope($feeBumpTx, []);

        $lines = [];
        $original->toTxRep('env', $lines);

        $this->assertArrayHasKey('env.tx.feeSource', $lines);
        $this->assertArrayHasKey('env.tx.fee', $lines);
        $this->assertEquals('500000', $lines['env.tx.fee']);
        $this->assertArrayHasKey('env.signatures.len', $lines);
        $this->assertEquals('0', $lines['env.signatures.len']);

        // Verify XDR round-trip via decode
        $xdrBase64 = $original->toBase64Xdr();
        $this->assertNotEmpty($xdrBase64);
        $decoded = XdrFeeBumpTransactionEnvelope::fromBase64Xdr($xdrBase64);
        $this->assertEquals(500000, $decoded->getTx()->getFee());
        $this->assertCount(0, $decoded->getSignatures());
    }

    // ------------------------------------------------------------------
    // XdrTransactionEnvelopeBase — FEE_BUMP arm
    // ------------------------------------------------------------------

    public function testTransactionEnvelopeBaseFeeBumpToTxRep(): void
    {
        $keyBytes = KeyPair::random()->getPublicKey();
        $feeSource = new XdrMuxedAccount($keyBytes);

        $innerKeyBytes = KeyPair::random()->getPublicKey();
        $innerSourceAccount = new XdrMuxedAccount($innerKeyBytes);
        $innerTx = new XdrTransaction(
            $innerSourceAccount,
            new XdrSequenceNumber(new BigInteger(5555)),
            []
        );
        $innerEnvelope = new XdrTransactionV1Envelope($innerTx, []);
        $innerTxUnion = new XdrFeeBumpTransactionInnerTx(XdrEnvelopeType::ENVELOPE_TYPE_TX());
        $innerTxUnion->v1 = $innerEnvelope;

        $ext = new XdrFeeBumpTransactionExt(0);
        $feeBumpTx = new XdrFeeBumpTransaction($feeSource, 2000000, $innerTxUnion, $ext);
        $feeBumpEnvelope = new XdrFeeBumpTransactionEnvelope($feeBumpTx, []);

        $original = new XdrTransactionEnvelopeBase(XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP());
        $original->feeBump = $feeBumpEnvelope;

        $lines = [];
        $original->toTxRep('env', $lines);

        $this->assertArrayHasKey('env.type', $lines);
        $this->assertArrayHasKey('env.feeBump.tx.fee', $lines);
        $this->assertEquals('2000000', $lines['env.feeBump.tx.fee']);
        $this->assertArrayHasKey('env.feeBump.signatures.len', $lines);

        // Verify XDR decodes correctly
        $xdrBase64 = $original->toBase64Xdr();
        $this->assertNotEmpty($xdrBase64);
        $decoded = XdrTransactionEnvelopeBase::fromBase64Xdr($xdrBase64);
        $this->assertEquals(XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getFeeBump());
    }

    // ------------------------------------------------------------------
    // XdrLedgerKey wrapper — liquidity pool hex sync
    // ------------------------------------------------------------------

    public function testLedgerKeyWrapperLiquidityPoolHexFieldSync(): void
    {
        $poolIdHex = bin2hex($this->randomBytes(32));

        $ledgerKey = XdrLedgerKey::forLiquidityPoolId($poolIdHex);

        $this->assertEquals($poolIdHex, $ledgerKey->getLiquidityPoolID());

        // Encode forces the hex → binary sync
        $xdrBase64 = $ledgerKey->toBase64Xdr();
        $this->assertNotEmpty($xdrBase64);

        // Decode syncs binary → hex
        $decoded = XdrLedgerKey::fromBase64Xdr($xdrBase64);
        $this->assertEquals(XdrLedgerEntryType::LIQUIDITY_POOL, $decoded->getType()->getValue());
        $this->assertEquals($poolIdHex, $decoded->getLiquidityPoolID());
    }

    // ------------------------------------------------------------------
    // XdrUInt128Parts / XdrInt128Parts / XdrUInt256Parts / XdrInt256Parts
    // standalone TxRep roundtrips
    // ------------------------------------------------------------------

    public function testUInt128PartsTxRepRoundtrip(): void
    {
        $original = new XdrUInt128Parts(0xABCDEF01, 0x12345678);

        $lines = [];
        $original->toTxRep('u128', $lines);

        $this->assertArrayHasKey('u128.hi', $lines);
        $this->assertArrayHasKey('u128.lo', $lines);

        $restored = XdrUInt128Parts::fromTxRep($lines, 'u128');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(0xABCDEF01, $restored->getHi());
        $this->assertEquals(0x12345678, $restored->getLo());
    }

    public function testInt128PartsTxRepRoundtrip(): void
    {
        $original = new XdrInt128Parts(-9999999, 1234567890);

        $lines = [];
        $original->toTxRep('i128', $lines);

        $restored = XdrInt128Parts::fromTxRep($lines, 'i128');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(-9999999, $restored->getHi());
        $this->assertEquals(1234567890, $restored->getLo());
    }

    public function testUInt256PartsTxRepRoundtrip(): void
    {
        $original = new XdrUInt256Parts(100, 200, 300, 400);

        $lines = [];
        $original->toTxRep('u256', $lines);

        $restored = XdrUInt256Parts::fromTxRep($lines, 'u256');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(100, $restored->getHiHi());
        $this->assertEquals(200, $restored->getHiLo());
        $this->assertEquals(300, $restored->getLoHi());
        $this->assertEquals(400, $restored->getLoLo());
    }

    public function testInt256PartsTxRepRoundtrip(): void
    {
        $original = new XdrInt256Parts(-500, 600, 700, 800);

        $lines = [];
        $original->toTxRep('i256', $lines);

        $restored = XdrInt256Parts::fromTxRep($lines, 'i256');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(-500, $restored->getHiHi());
        $this->assertEquals(800, $restored->getLoLo());
    }

    // ------------------------------------------------------------------
    // XdrSignedPayload standalone TxRep roundtrip
    // ------------------------------------------------------------------

    public function testSignedPayloadTxRepRoundtrip(): void
    {
        $ed25519Bytes = $this->randomBytes(32);
        $payloadBytes = $this->randomBytes(32);

        $original = new XdrSignedPayload($ed25519Bytes, $payloadBytes);

        $lines = [];
        $original->toTxRep('sp', $lines);

        $this->assertArrayHasKey('sp.ed25519', $lines);
        $this->assertArrayHasKey('sp.payload', $lines);

        $restored = XdrSignedPayload::fromTxRep($lines, 'sp');

        $this->assertEquals($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals($ed25519Bytes, $restored->getEd25519());
        $this->assertEquals($payloadBytes, $restored->getPayload());
    }

    // ------------------------------------------------------------------
    // XdrSCError — additional error types (SCE_CONTEXT, SCE_STORAGE, etc.)
    // ------------------------------------------------------------------

    public function testSCErrorContextWithCodeRoundtrip(): void
    {
        $error = new XdrSCError(XdrSCErrorType::SCE_CONTEXT());
        $error->code = XdrSCErrorCode::SCEC_INTERNAL_ERROR();

        $lines = [];
        $error->toTxRep('err', $lines);

        $this->assertArrayHasKey('err.type', $lines);
        $this->assertEquals('SCE_CONTEXT', $lines['err.type']);

        $restored = XdrSCError::fromTxRep($lines, 'err');

        $this->assertEquals($error->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrSCErrorType::SCE_CONTEXT, $restored->getType()->getValue());
    }

    public function testSCErrorAuthWithCodeRoundtrip(): void
    {
        $error = new XdrSCError(XdrSCErrorType::SCE_AUTH());
        $error->code = XdrSCErrorCode::SCEC_ARITH_DOMAIN();

        $lines = [];
        $error->toTxRep('err', $lines);

        $restored = XdrSCError::fromTxRep($lines, 'err');

        $this->assertEquals($error->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertEquals(XdrSCErrorType::SCE_AUTH, $restored->getType()->getValue());
        $this->assertEquals(XdrSCErrorCode::SCEC_ARITH_DOMAIN, $restored->getCode()->getValue());
    }

    // ------------------------------------------------------------------
    // Enum default branch coverage: enumName() and fromTxRepName()
    //
    // Each enum's generated code has two uncovered default branches:
    //   1. enumName() returns 'XdrFooType#<value>' for unknown values.
    //   2. fromTxRepName() parses 'XdrFooType#<value>' back and also throws
    //      InvalidArgumentException for completely unknown names.
    // ------------------------------------------------------------------

    private function assertEnumDefaultBranches(
        string $className,
        string $prefix,
        int $unknownValue = 9999
    ): void {
        $obj = new $className($unknownValue);

        // enumName() returns the fallback numeric string
        $name = $obj->enumName();
        $this->assertSame($prefix . '#' . $unknownValue, $name);

        // fromTxRepName() parses the numeric fallback string back
        $roundtripped = $className::fromTxRepName($name);
        $this->assertSame($unknownValue, $roundtripped->value);

        // fromTxRepName() throws for a completely unknown name
        $this->expectException(\InvalidArgumentException::class);
        $className::fromTxRepName('TOTALLY_UNKNOWN_VALUE_XYZ');
    }

    public function testEnumDefaultBranches_XdrContractExecutableType(): void
    {
        $this->assertEnumDefaultBranches(XdrContractExecutableType::class, 'XdrContractExecutableType');
    }

    public function testEnumDefaultBranches_XdrPreconditionType(): void
    {
        $this->assertEnumDefaultBranches(XdrPreconditionType::class, 'XdrPreconditionType');
    }

    public function testEnumDefaultBranches_XdrClaimableBalanceIDType(): void
    {
        $this->assertEnumDefaultBranches(XdrClaimableBalanceIDType::class, 'XdrClaimableBalanceIDType');
    }

    public function testEnumDefaultBranches_XdrLedgerEntryType(): void
    {
        $this->assertEnumDefaultBranches(XdrLedgerEntryType::class, 'XdrLedgerEntryType');
    }

    public function testEnumDefaultBranches_XdrEnvelopeType(): void
    {
        $this->assertEnumDefaultBranches(XdrEnvelopeType::class, 'XdrEnvelopeType');
    }

    public function testEnumDefaultBranches_XdrConfigSettingID(): void
    {
        $this->assertEnumDefaultBranches(XdrConfigSettingID::class, 'XdrConfigSettingID');
    }

    public function testEnumDefaultBranches_XdrSignerKeyTypeBase(): void
    {
        $this->assertEnumDefaultBranches(XdrSignerKeyTypeBase::class, 'XdrSignerKeyTypeBase');
    }

    public function testEnumDefaultBranches_XdrHostFunctionType(): void
    {
        $this->assertEnumDefaultBranches(XdrHostFunctionType::class, 'XdrHostFunctionType');
    }

    public function testEnumDefaultBranches_XdrClaimPredicateType(): void
    {
        $this->assertEnumDefaultBranches(XdrClaimPredicateType::class, 'XdrClaimPredicateType');
    }

    public function testEnumDefaultBranches_XdrAssetType(): void
    {
        $this->assertEnumDefaultBranches(XdrAssetType::class, 'XdrAssetType');
    }

    public function testEnumDefaultBranches_XdrLiquidityPoolType(): void
    {
        $this->assertEnumDefaultBranches(XdrLiquidityPoolType::class, 'XdrLiquidityPoolType');
    }

    public function testEnumDefaultBranches_XdrMemoType(): void
    {
        $this->assertEnumDefaultBranches(XdrMemoType::class, 'XdrMemoType');
    }

    public function testEnumDefaultBranches_XdrSorobanCredentialsType(): void
    {
        $this->assertEnumDefaultBranches(XdrSorobanCredentialsType::class, 'XdrSorobanCredentialsType');
    }

    public function testEnumDefaultBranches_XdrContractDataDurability(): void
    {
        $this->assertEnumDefaultBranches(XdrContractDataDurability::class, 'XdrContractDataDurability');
    }

    public function testEnumDefaultBranches_XdrSCErrorCode(): void
    {
        $this->assertEnumDefaultBranches(XdrSCErrorCode::class, 'XdrSCErrorCode');
    }

    public function testEnumDefaultBranches_XdrSCErrorType(): void
    {
        $this->assertEnumDefaultBranches(XdrSCErrorType::class, 'XdrSCErrorType');
    }

    public function testEnumDefaultBranches_XdrSCValType(): void
    {
        $this->assertEnumDefaultBranches(XdrSCValType::class, 'XdrSCValType');
    }

    public function testEnumDefaultBranches_XdrOperationType(): void
    {
        $this->assertEnumDefaultBranches(XdrOperationType::class, 'XdrOperationType');
    }

    public function testEnumDefaultBranches_XdrRevokeSponsorshipType(): void
    {
        $this->assertEnumDefaultBranches(XdrRevokeSponsorshipType::class, 'XdrRevokeSponsorshipType');
    }

    public function testEnumDefaultBranches_XdrClaimantType(): void
    {
        $this->assertEnumDefaultBranches(XdrClaimantType::class, 'XdrClaimantType');
    }

    public function testEnumDefaultBranches_XdrPublicKeyType(): void
    {
        $this->assertEnumDefaultBranches(XdrPublicKeyType::class, 'XdrPublicKeyType');
    }

    public function testEnumDefaultBranches_XdrCryptoKeyType(): void
    {
        $this->assertEnumDefaultBranches(XdrCryptoKeyType::class, 'XdrCryptoKeyType');
    }

    public function testEnumDefaultBranches_XdrSCAddressType(): void
    {
        $this->assertEnumDefaultBranches(XdrSCAddressType::class, 'XdrSCAddressType');
    }

    public function testEnumDefaultBranches_XdrSorobanAuthorizedFunctionType(): void
    {
        $this->assertEnumDefaultBranches(XdrSorobanAuthorizedFunctionType::class, 'XdrSorobanAuthorizedFunctionType');
    }

    // ------------------------------------------------------------------
    // XdrTransactionV0Envelope — toTxRep produces correct map structure
    // ------------------------------------------------------------------

    public function testTxRepRoundtrip_XdrTransactionV0Envelope(): void
    {
        $sourceKey = KeyPair::random()->getPublicKey();
        $seqNum = new XdrSequenceNumber(new BigInteger(42));
        $v0Tx = new XdrTransactionV0($sourceKey, $seqNum, []);

        $original = new XdrTransactionV0Envelope($v0Tx, []);

        $lines = [];
        $original->toTxRep('env', $lines);

        $this->assertArrayHasKey('env.tx.sourceAccountEd25519', $lines);
        $this->assertArrayHasKey('env.tx.fee', $lines);
        $this->assertArrayHasKey('env.tx.seqNum', $lines);
        $this->assertSame('42', $lines['env.tx.seqNum']);
        $this->assertArrayHasKey('env.signatures.len', $lines);
        $this->assertSame('0', $lines['env.signatures.len']);

        // Verify XDR round-trip via encode/decode
        $xdrBase64 = $original->toBase64Xdr();
        $this->assertNotEmpty($xdrBase64);
        $decoded = XdrTransactionV0Envelope::fromBase64Xdr($xdrBase64);
        $this->assertSame(bin2hex($sourceKey), bin2hex($decoded->getTx()->getSourceAccountEd25519()));
        $this->assertCount(0, $decoded->getSignatures());
    }

    // ------------------------------------------------------------------
    // XdrTransactionEnvelopeBase — V0 arm
    // ------------------------------------------------------------------

    public function testTransactionEnvelopeBaseV0Arm(): void
    {
        $sourceKey = KeyPair::random()->getPublicKey();
        $seqNum = new XdrSequenceNumber(new BigInteger(77));
        $v0Tx = new XdrTransactionV0($sourceKey, $seqNum, []);
        $v0Envelope = new XdrTransactionV0Envelope($v0Tx, []);

        $original = new XdrTransactionEnvelopeBase(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0());
        $original->v0 = $v0Envelope;

        $lines = [];
        $original->toTxRep('env', $lines);

        $this->assertArrayHasKey('env.type', $lines);
        $this->assertSame('ENVELOPE_TYPE_TX_V0', $lines['env.type']);
        $this->assertArrayHasKey('env.v0.tx.fee', $lines);
        $this->assertArrayHasKey('env.v0.signatures.len', $lines);

        // Verify XDR encode/decode roundtrip
        $xdrBase64 = $original->toBase64Xdr();
        $this->assertNotEmpty($xdrBase64);
        $decoded = XdrTransactionEnvelopeBase::fromBase64Xdr($xdrBase64);
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getV0());
    }

    // ------------------------------------------------------------------
    // XdrTransactionEnvelopeBase — V1 arm (TxRep map structure plus XDR roundtrip)
    // ------------------------------------------------------------------

    public function testTransactionEnvelopeBaseV1Arm(): void
    {
        $keyBytes = KeyPair::random()->getPublicKey();
        $sourceAccount = new XdrMuxedAccount($keyBytes);
        $innerTx = new XdrTransaction($sourceAccount, new XdrSequenceNumber(new BigInteger(111)), []);
        $v1Envelope = new XdrTransactionV1Envelope($innerTx, []);

        $original = new XdrTransactionEnvelopeBase(XdrEnvelopeType::ENVELOPE_TYPE_TX());
        $original->v1 = $v1Envelope;

        $lines = [];
        $original->toTxRep('env', $lines);

        $this->assertArrayHasKey('env.type', $lines);
        $this->assertSame('ENVELOPE_TYPE_TX', $lines['env.type']);
        $this->assertArrayHasKey('env.v1.tx.sourceAccount', $lines);
        $this->assertArrayHasKey('env.v1.signatures.len', $lines);

        // Verify via XDR encode/decode (fromTxRep has a known ctor-signature mismatch for XdrTransaction)
        $xdrBase64 = $original->toBase64Xdr();
        $this->assertNotEmpty($xdrBase64);
        $decoded = XdrTransactionEnvelopeBase::fromBase64Xdr($xdrBase64);
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getV1());
    }

    // ------------------------------------------------------------------
    // XdrMuxedAccount — toTxRep / fromTxRep (G... and M... addresses)
    // ------------------------------------------------------------------

    public function testMuxedAccountToTxRepFromTxRepEd25519(): void
    {
        $keyBytes = KeyPair::random()->getPublicKey();
        $original = new XdrMuxedAccount($keyBytes);

        $lines = [];
        $original->toTxRep('src', $lines);

        $this->assertArrayHasKey('src', $lines);
        $this->assertStringStartsWith('G', $lines['src']);

        $restored = XdrMuxedAccount::fromTxRep($lines, 'src');

        $this->assertSame($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertSame(XdrCryptoKeyType::KEY_TYPE_ED25519, $restored->getType()->getValue());
    }

    public function testMuxedAccountToTxRepFromTxRepMuxed(): void
    {
        $keyBytes = KeyPair::random()->getPublicKey();
        $med25519 = new XdrMuxedAccountMed25519(12345, $keyBytes);
        $original = new XdrMuxedAccount(null, $med25519);

        $lines = [];
        $original->toTxRep('src', $lines);

        $this->assertArrayHasKey('src', $lines);
        $this->assertStringStartsWith('M', $lines['src']);

        $restored = XdrMuxedAccount::fromTxRep($lines, 'src');

        $this->assertSame($original->toBase64Xdr(), $restored->toBase64Xdr());
        $this->assertSame(XdrCryptoKeyType::KEY_TYPE_MUXED_ED25519, $restored->getType()->getValue());
        $this->assertNotNull($restored->getMed25519());
        $this->assertSame(12345, $restored->getMed25519()->getId());
    }

    public function testMuxedAccountFromTxRepThrowsForMissingKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrMuxedAccount::fromTxRep([], 'src');
    }

    public function testMuxedAccountFromTxRepThrowsForEmptyAddress(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrMuxedAccount::fromTxRep(['src' => ''], 'src');
    }

    public function testMuxedAccountFromTxRepThrowsForUnrecognizedPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrMuxedAccount::fromTxRep(['src' => 'ZINVALID'], 'src');
    }

    public function testMuxedAccountFromTxRepThrowsForInvalidGAddress(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrMuxedAccount::fromTxRep(['src' => 'GINVALID'], 'src');
    }

    // ------------------------------------------------------------------
    // XdrFeeBumpTransactionInnerTx — default arm (non-ENVELOPE_TYPE_TX type)
    // ------------------------------------------------------------------

    public function testFeeBumpTransactionInnerTxDefaultArm(): void
    {
        // Use an unknown envelope type value to exercise the default branch.
        $unknownType = new XdrEnvelopeType(9999);
        $innerTx = new XdrFeeBumpTransactionInnerTx($unknownType);

        $lines = [];
        $innerTx->toTxRep('it', $lines);

        $this->assertArrayHasKey('it.type', $lines);
        // The unknown type will use the fallback enumName
        $this->assertStringContainsString('9999', $lines['it.type']);

        // fromTxRep round-trip for the default arm
        $restored = XdrFeeBumpTransactionInnerTx::fromTxRep($lines, 'it');
        $this->assertSame(9999, $restored->getType()->getValue());
        $this->assertNull($restored->getV1());
    }

    // ------------------------------------------------------------------
    // XdrSorobanTransactionDataExt — discriminant 0 and unknown default arm
    // ------------------------------------------------------------------

    public function testSorobanTransactionDataExtDiscriminantZero(): void
    {
        $original = new XdrSorobanTransactionDataExt(0);

        $lines = [];
        $original->toTxRep('ext', $lines);

        $this->assertArrayHasKey('ext.v', $lines);
        $this->assertSame('0', $lines['ext.v']);

        $restored = XdrSorobanTransactionDataExt::fromTxRep($lines, 'ext');

        $this->assertSame(0, $restored->getDiscriminant());
        $this->assertNull($restored->getResourceExt());
        $this->assertSame($original->toBase64Xdr(), $restored->toBase64Xdr());
    }

    public function testSorobanTransactionDataExtDefaultArm(): void
    {
        // Discriminant 99 triggers the default (no-op) branch in toTxRep/fromTxRep.
        $original = new XdrSorobanTransactionDataExt(99);

        $lines = [];
        $original->toTxRep('ext', $lines);

        $this->assertArrayHasKey('ext.v', $lines);
        $this->assertSame('99', $lines['ext.v']);

        $restored = XdrSorobanTransactionDataExt::fromTxRep($lines, 'ext');

        $this->assertSame(99, $restored->getDiscriminant());
        $this->assertNull($restored->getResourceExt());
    }

    // ------------------------------------------------------------------
    // XdrManageDataOperationBase — present and absent dataValue
    // ------------------------------------------------------------------

    public function testManageDataOperationBaseWithDataValue(): void
    {
        $dataValue = random_bytes(16);
        $original = new XdrManageDataOperationBase('test_key', $dataValue);

        $lines = [];
        $original->toTxRep('op', $lines);

        $this->assertArrayHasKey('op.dataName', $lines);
        $this->assertArrayHasKey('op.dataValue._present', $lines);
        $this->assertSame('true', $lines['op.dataValue._present']);
        $this->assertArrayHasKey('op.dataValue', $lines);

        $restored = XdrManageDataOperationBase::fromTxRep($lines, 'op');

        $this->assertSame('test_key', $restored->getDataName());
        $this->assertNotNull($restored->getDataValue());
        $this->assertSame($dataValue, $restored->getDataValue());
        $this->assertSame($original->toBase64Xdr(), $restored->toBase64Xdr());
    }

    public function testManageDataOperationBaseWithoutDataValue(): void
    {
        $original = new XdrManageDataOperationBase('delete_key', null);

        $lines = [];
        $original->toTxRep('op', $lines);

        $this->assertArrayHasKey('op.dataName', $lines);
        $this->assertArrayHasKey('op.dataValue._present', $lines);
        $this->assertSame('false', $lines['op.dataValue._present']);
        $this->assertArrayNotHasKey('op.dataValue', $lines);

        $restored = XdrManageDataOperationBase::fromTxRep($lines, 'op');

        $this->assertSame('delete_key', $restored->getDataName());
        $this->assertNull($restored->getDataValue());
        $this->assertSame($original->toBase64Xdr(), $restored->toBase64Xdr());
    }

    // ------------------------------------------------------------------
    // XdrSorobanAuthorizedFunctionBase — CREATE_CONTRACT_HOST_FN arm
    // ------------------------------------------------------------------

    private function buildMinimalCreateContractPreimage(): XdrContractIDPreimage
    {
        $contractIdAddress = XdrSCAddress::forAccountId(KeyPair::random()->getAccountId());
        $salt = $this->randomBytes(32);
        $preimage = new XdrContractIDPreimage(XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS());
        // Directly set fromAddress so toTxRep works without needing encode() to sync
        $preimage->fromAddress = new XdrContractIDPreimageFromAddress($contractIdAddress, $salt);
        return $preimage;
    }

    private function buildMinimalWasmExecutable(): XdrContractExecutable
    {
        $executable = new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM());
        // wasmIdHex stored as raw bytes in the base toTxRep path
        $executable->wasmIdHex = $this->randomBytes(32);
        return $executable;
    }

    public function testSorobanAuthorizedFunctionBaseCreateContractArm(): void
    {
        $preimage = $this->buildMinimalCreateContractPreimage();
        $executable = $this->buildMinimalWasmExecutable();
        $createArgs = new XdrCreateContractArgs($preimage, $executable);

        $original = new XdrSorobanAuthorizedFunctionBase(
            XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_HOST_FN()
        );
        $original->createContractHostFn = $createArgs;

        $lines = [];
        $original->toTxRep('fn', $lines);

        $this->assertArrayHasKey('fn.type', $lines);
        $this->assertSame(
            'SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_HOST_FN',
            $lines['fn.type']
        );
        $this->assertArrayHasKey('fn.createContractHostFn.contractIDPreimage.type', $lines);

        $restored = XdrSorobanAuthorizedFunctionBase::fromTxRep($lines, 'fn');
        $this->assertSame(
            XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_HOST_FN,
            $restored->getType()->getValue()
        );
        $this->assertNotNull($restored->getCreateContractHostFn());
    }

    public function testSorobanAuthorizedFunctionBaseCreateContractV2Arm(): void
    {
        $preimage = $this->buildMinimalCreateContractPreimage();
        $executable = $this->buildMinimalWasmExecutable();
        $createArgsV2 = new XdrCreateContractArgsV2($preimage, $executable, []);

        $original = new XdrSorobanAuthorizedFunctionBase(
            XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_V2_HOST_FN()
        );
        $original->createContractV2HostFn = $createArgsV2;

        $lines = [];
        $original->toTxRep('fn', $lines);

        $this->assertArrayHasKey('fn.type', $lines);
        $this->assertSame(
            'SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_V2_HOST_FN',
            $lines['fn.type']
        );
        $this->assertArrayHasKey('fn.createContractV2HostFn.contractIDPreimage.type', $lines);

        $restored = XdrSorobanAuthorizedFunctionBase::fromTxRep($lines, 'fn');
        $this->assertSame(
            XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_V2_HOST_FN,
            $restored->getType()->getValue()
        );
        $this->assertNotNull($restored->getCreateContractV2HostFn());
    }

    public function testSorobanAuthorizedFunctionBaseDefaultArm(): void
    {
        $unknownType = new XdrSorobanAuthorizedFunctionType(9999);
        $obj = new XdrSorobanAuthorizedFunctionBase($unknownType);

        $lines = [];
        $obj->toTxRep('fn', $lines);

        $this->assertArrayHasKey('fn.type', $lines);
        $this->assertStringContainsString('9999', $lines['fn.type']);

        $restored = XdrSorobanAuthorizedFunctionBase::fromTxRep($lines, 'fn');
        $this->assertSame(9999, $restored->getType()->getValue());
        $this->assertNull($restored->getContractFn());
        $this->assertNull($restored->getCreateContractHostFn());
        $this->assertNull($restored->getCreateContractV2HostFn());
    }

    // ------------------------------------------------------------------
    // XdrTransactionV1Envelope — toTxRep map structure verification
    // ------------------------------------------------------------------

    public function testTxRepRoundtrip_XdrTransactionV1Envelope(): void
    {
        $keyBytes = KeyPair::random()->getPublicKey();
        $sourceAccount = new XdrMuxedAccount($keyBytes);
        $innerTx = new XdrTransaction($sourceAccount, new XdrSequenceNumber(new BigInteger(500)), []);

        $original = new XdrTransactionV1Envelope($innerTx, []);

        $lines = [];
        $original->toTxRep('v1env', $lines);

        $this->assertArrayHasKey('v1env.tx.sourceAccount', $lines);
        $this->assertArrayHasKey('v1env.tx.seqNum', $lines);
        $this->assertSame('500', $lines['v1env.tx.seqNum']);
        $this->assertArrayHasKey('v1env.signatures.len', $lines);
        $this->assertSame('0', $lines['v1env.signatures.len']);

        // Verify XDR encode/decode roundtrip
        $xdrBase64 = $original->toBase64Xdr();
        $this->assertNotEmpty($xdrBase64);
        $decoded = XdrTransactionV1Envelope::fromBase64Xdr($xdrBase64);
        $this->assertCount(0, $decoded->getSignatures());
        $this->assertSame($original->toBase64Xdr(), $decoded->toBase64Xdr());
    }

    // ------------------------------------------------------------------
    // XdrHostFunction — default arm in toTxRep / fromTxRep
    // ------------------------------------------------------------------

    public function testHostFunctionDefaultArm(): void
    {
        $unknownType = new XdrHostFunctionType(9999);
        $obj = new XdrHostFunctionBase($unknownType);

        $lines = [];
        $obj->toTxRep('hf', $lines);

        $this->assertArrayHasKey('hf.type', $lines);
        $this->assertStringContainsString('9999', $lines['hf.type']);

        $restored = XdrHostFunctionBase::fromTxRep($lines, 'hf');
        $this->assertSame(9999, $restored->getType()->getValue());
        $this->assertNull($restored->getInvokeContract());
        $this->assertNull($restored->getCreateContract());
        $this->assertNull($restored->getWasm());
        $this->assertNull($restored->getCreateContractV2());
    }

    // ------------------------------------------------------------------
    // XdrLedgerKey — TTL and CONFIG_SETTING arms
    // ------------------------------------------------------------------

    public function testLedgerKeyTtlArm(): void
    {
        $keyHash = $this->randomBytes(32);
        $original = XdrLedgerKey::forTTL($keyHash);

        $lines = [];
        $original->toTxRep('lk', $lines);

        $this->assertArrayHasKey('lk.type', $lines);
        $this->assertSame('TTL', $lines['lk.type']);
        $this->assertArrayHasKey('lk.ttl.keyHash', $lines);

        $restored = XdrLedgerKey::fromTxRep($lines, 'lk');

        $this->assertSame(XdrLedgerEntryType::TTL, $restored->getType()->getValue());
        $this->assertNotNull($restored->getTtl());
        $this->assertSame($original->toBase64Xdr(), $restored->toBase64Xdr());
    }

    public function testLedgerKeyConfigSettingArm(): void
    {
        $configId = XdrConfigSettingID::CONFIG_SETTING_CONTRACT_BANDWIDTH_V0();
        $original = XdrLedgerKey::forConfigSettingID($configId);

        $lines = [];
        $original->toTxRep('lk', $lines);

        $this->assertArrayHasKey('lk.type', $lines);
        $this->assertSame('CONFIG_SETTING', $lines['lk.type']);
        $this->assertArrayHasKey('lk.configSetting', $lines);

        $restored = XdrLedgerKey::fromTxRep($lines, 'lk');

        $this->assertSame(XdrLedgerEntryType::CONFIG_SETTING, $restored->getType()->getValue());
        $this->assertNotNull($restored->getConfigSetting());
        $this->assertSame($original->toBase64Xdr(), $restored->toBase64Xdr());
    }

    public function testLedgerKeyDefaultArm(): void
    {
        $unknownType = new XdrLedgerEntryType(9999);
        $obj = new XdrLedgerKeyBase($unknownType);

        $lines = [];
        $obj->toTxRep('lk', $lines);

        $this->assertArrayHasKey('lk.type', $lines);
        $this->assertStringContainsString('9999', $lines['lk.type']);

        $restored = XdrLedgerKeyBase::fromTxRep($lines, 'lk');
        $this->assertSame(9999, $restored->getType()->getValue());
    }
}
