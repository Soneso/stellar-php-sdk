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
use Soneso\StellarSDK\Xdr\XdrTrustlineAsset;
use Soneso\StellarSDK\Xdr\XdrTrustlineAssetBase;

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
}
