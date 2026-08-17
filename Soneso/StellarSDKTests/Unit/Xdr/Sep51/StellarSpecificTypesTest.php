<?php declare(strict_types=1);

namespace Soneso\StellarSDKTests\Unit\Xdr\Sep51;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrAllowTrustOperationAsset;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum12;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum4;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceID;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceIDType;
use Soneso\StellarSDK\Xdr\XdrCryptoKeyType;
use Soneso\StellarSDK\Xdr\XdrDataValue;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrInt256Parts;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyLiquidityPool;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolDepositOperation;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolWithdrawOperation;
use Soneso\StellarSDK\Xdr\XdrManageDataOperation;
use Soneso\StellarSDK\Xdr\XdrMemo;
use Soneso\StellarSDK\Xdr\XdrMemoType;
use Soneso\StellarSDK\Xdr\XdrMuxedAccount;
use Soneso\StellarSDK\Xdr\XdrMuxedAccountMed25519;
use Soneso\StellarSDK\Xdr\XdrNodeID;
use Soneso\StellarSDK\Xdr\XdrPublicKey;
use Soneso\StellarSDK\Xdr\XdrPublicKeyType;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCAddressType;
use Soneso\StellarSDK\Xdr\XdrSignedPayload;
use Soneso\StellarSDK\Xdr\XdrSignerKey;
use Soneso\StellarSDK\Xdr\XdrSignerKeyType;
use Soneso\StellarSDK\Xdr\XdrTimeBounds;
use Soneso\StellarSDK\Xdr\XdrTransaction;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrTransactionV0;
use Soneso\StellarSDK\Xdr\XdrUInt128Parts;
use Soneso\StellarSDK\Xdr\XdrUInt256Parts;
use phpseclib3\Math\BigInteger;

/**
 * SEP-51 round-trip tests for Stellar-specific types whose JSON wire form
 * diverges from the generator's default enum/struct/union templates.
 *
 * Covers:
 *   - Bespoke-shape types: XdrPublicKey, XdrNodeID, XdrSignerKey,
 *     XdrSignedPayload, XdrAsset, XdrMemo, XdrUInt128Parts /
 *     XdrInt128Parts / XdrUInt256Parts / XdrInt256Parts.
 *   - Bespoke types backed by a hand-written wrapper: XdrAccountID,
 *     XdrClaimableBalanceID, XdrSCAddress, XdrMuxedAccount,
 *     XdrMuxedAccountMed25519.
 *   - Wrapper types with field-name overrides: XdrAssetAlphaNum4,
 *     XdrAssetAlphaNum12, XdrAllowTrustOperationAsset,
 *     XdrLiquidityPoolDepositOperation, XdrLiquidityPoolWithdrawOperation,
 *     XdrLedgerKeyLiquidityPool.
 *   - Object-shaped bespoke types: XdrTimeBounds, XdrManageDataOperation,
 *     XdrTransaction, XdrTransactionV0.
 *   - Hand-written XdrDataValue.
 *
 * Each test verifies (a) the toJsonValue wire form matches the SEP-51
 * specification, (b) the round-trip via fromJsonValue reproduces an
 * equal instance, and (c) the JSON facade (toJson/fromJson) preserves
 * the same wire form when serialising via JSON_THROW_ON_ERROR |
 * JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE.
 */
class StellarSpecificTypesTest extends TestCase
{
    /**
     * A G-strkey whose payload is 37 bytes rather than the 32 an ed25519 key
     * has. Its 64 characters carry a correct checksum; the encoded-length rule
     * (56 characters for G) is what stands between it and a caller.
     */
    private const G_STRKEY_WITH_37_BYTE_PAYLOAD =
        'GABAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAE4FK';

    // -----------------------------------------------------------------
    // XdrPublicKey / XdrNodeID — G-strkey
    // -----------------------------------------------------------------

    public function testXdrPublicKeyEd25519RoundTrip(): void
    {
        $rawEd25519 = str_repeat("\x11", 32);
        $expectedStrkey = StrKey::encodeAccountId($rawEd25519);

        $pk = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519));
        $pk->ed25519 = $rawEd25519;

        $this->assertSame($expectedStrkey, $pk->toJsonValue());
        $rt = XdrPublicKey::fromJsonValue($expectedStrkey);
        $this->assertSame($rawEd25519, $rt->ed25519);
    }

    public function testXdrPublicKeyJsonFacadeEmitsBareString(): void
    {
        $rawEd25519 = str_repeat("\x22", 32);
        $pk = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519));
        $pk->ed25519 = $rawEd25519;

        $json = $pk->toJson();
        $this->assertSame('"' . StrKey::encodeAccountId($rawEd25519) . '"', $json);
    }

    public function testXdrPublicKeyRejectsNonStringInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected string for XdrPublicKey JSON value');
        XdrPublicKey::fromJsonValue(['invalid' => 'shape']);
    }

    public function testXdrPublicKeyRejectsAnOversizedPayloadByItsEncodedLength(): void
    {
        // A 37-byte payload encodes to 64 characters, so the encoded-length
        // rule stops it before any payload byte is read.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('G-strkey must be 56 characters long, 64 characters given');
        XdrPublicKey::fromJsonValue(self::G_STRKEY_WITH_37_BYTE_PAYLOAD);
    }

    public function testXdrNodeIDRoundTrip(): void
    {
        $rawEd25519 = str_repeat("\x33", 32);
        $strkey = StrKey::encodeAccountId($rawEd25519);

        $pk = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519));
        $pk->ed25519 = $rawEd25519;
        $node = new XdrNodeID($pk);

        $this->assertSame($strkey, $node->toJsonValue());
        $rt = XdrNodeID::fromJsonValue($strkey);
        $this->assertSame($rawEd25519, $rt->nodeID->ed25519);
    }

    // -----------------------------------------------------------------
    // XdrAccountID — wrapper-backed G-strkey via inner XdrPublicKey
    // -----------------------------------------------------------------

    public function testXdrAccountIDRoundTrip(): void
    {
        $rawEd25519 = str_repeat("\x44", 32);
        $accountId = StrKey::encodeAccountId($rawEd25519);

        $xdrAccountId = new XdrAccountID($accountId);
        $this->assertSame($accountId, $xdrAccountId->toJsonValue());

        $rt = XdrAccountID::fromJsonValue($accountId);
        $this->assertSame($accountId, $rt->getAccountId());
    }

    // -----------------------------------------------------------------
    // XdrSignerKey — single-string strkey dispatch over 4 arms
    // -----------------------------------------------------------------

    public function testXdrSignerKeyEd25519ArmRoundTrip(): void
    {
        $raw = str_repeat("\x55", 32);
        $strkey = StrKey::encodeAccountId($raw);

        $key = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519));
        $key->ed25519 = $raw;

        $this->assertSame($strkey, $key->toJsonValue());
        $rt = XdrSignerKey::fromJsonValue($strkey);
        $this->assertSame(XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519, $rt->type->getValue());
        $this->assertSame($raw, $rt->ed25519);
    }

    public function testXdrSignerKeyPreAuthTxArmRoundTrip(): void
    {
        $raw = str_repeat("\x66", 32);
        $strkey = StrKey::encodePreAuthTx($raw);

        $key = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_PRE_AUTH_TX));
        $key->preAuthTx = $raw;

        $this->assertSame($strkey, $key->toJsonValue());
        $this->assertStringStartsWith('T', $strkey);
        $rt = XdrSignerKey::fromJsonValue($strkey);
        $this->assertSame(XdrSignerKeyType::SIGNER_KEY_TYPE_PRE_AUTH_TX, $rt->type->getValue());
        $this->assertSame($raw, $rt->preAuthTx);
    }

    public function testXdrSignerKeyHashXArmRoundTrip(): void
    {
        $raw = str_repeat("\x77", 32);
        $strkey = StrKey::encodeSha256Hash($raw);

        $key = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_HASH_X));
        $key->hashX = $raw;

        $this->assertSame($strkey, $key->toJsonValue());
        $this->assertStringStartsWith('X', $strkey);
        $rt = XdrSignerKey::fromJsonValue($strkey);
        $this->assertSame(XdrSignerKeyType::SIGNER_KEY_TYPE_HASH_X, $rt->type->getValue());
        $this->assertSame($raw, $rt->hashX);
    }

    public function testXdrSignerKeySignedPayloadArmRoundTrip(): void
    {
        $rawEd25519 = str_repeat("\x88", 32);
        $payload = "\x01\x02\x03\x04\x05";
        $signedPayload = new XdrSignedPayload($rawEd25519, $payload);
        $strkey = StrKey::encodeXdrSignedPayload($signedPayload);

        $key = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD));
        $key->signedPayload = $signedPayload;

        $this->assertSame($strkey, $key->toJsonValue());
        $this->assertStringStartsWith('P', $strkey);
        $rt = XdrSignerKey::fromJsonValue($strkey);
        $this->assertSame(XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD, $rt->type->getValue());
        $this->assertSame($rawEd25519, $rt->signedPayload->ed25519);
        $this->assertSame($payload, $rt->signedPayload->payload);
    }

    public function testXdrSignerKeyRejectsUnknownPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid XdrSignerKey strkey prefix');
        XdrSignerKey::fromJsonValue('Z' . str_repeat('A', 55));
    }

    public function testXdrSignerKeyRejectsZeroLengthSignedPayloadOnEmit(): void
    {
        // A zero-length payload is valid XDR but has no SEP-23 strkey the
        // ecosystem accepts (a signed payload carries 1 to 64 payload
        // bytes), so there is no spec-conformant JSON rendering.
        $key = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD));
        $key->signedPayload = new XdrSignedPayload(str_repeat("\x88", 32), '');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Zero-length signed payload has no SEP-23 strkey representation');
        $key->toJsonValue();
    }

    public function testXdrSignerKeyRejectsZeroLengthSignedPayloadOnParse(): void
    {
        // A structurally well-formed P-strkey (version byte, checksum, padding)
        // carrying a zero-length payload for the \x88-repeated signer key. A
        // hardcoded literal: StrKey refuses to encode a zero-length payload,
        // so the SDK cannot produce this string. Its 69 characters are inside
        // the SEP-23 length bounds, so what turns it away is the declared
        // payload length of zero rather than the length of the string.
        $strkey = 'PCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIQAAAAAAAAAAAAD33A';
        $this->assertSame(69, strlen($strkey));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Zero-length signed payload has no SEP-23 strkey representation');
        XdrSignerKey::fromJsonValue($strkey);
    }

    public function testXdrSignedPayloadRejectsZeroLengthPayloadBothDirections(): void
    {
        $zero = new XdrSignedPayload(str_repeat("\x99", 32), '');
        $threw = false;
        try {
            $zero->toJsonValue();
        } catch (\InvalidArgumentException $e) {
            $threw = true;
            $this->assertStringContainsString('Zero-length signed payload', $e->getMessage());
        }
        $this->assertTrue($threw, 'toJsonValue must reject a zero-length payload');

        // A structurally well-formed P-strkey carrying a zero-length payload
        // for the \x99-repeated signer key. A hardcoded literal: StrKey
        // refuses to encode a zero-length payload, so the SDK cannot produce
        // this string. Its 69 characters are inside the SEP-23 length bounds,
        // so what turns it away is the declared payload length of zero rather
        // than the length of the string.
        $strkey = 'PCMZTGMZTGMZTGMZTGMZTGMZTGMZTGMZTGMZTGMZTGMZTGMZTGMZSAAAAAAAAAAAACJ4K';
        $this->assertSame(69, strlen($strkey));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Zero-length signed payload has no SEP-23 strkey representation');
        XdrSignedPayload::fromJsonValue($strkey);
    }

    // -----------------------------------------------------------------
    // XdrSignedPayload — standalone P-strkey
    // -----------------------------------------------------------------

    public function testXdrSignedPayloadStandaloneRoundTrip(): void
    {
        $rawEd25519 = str_repeat("\x99", 32);
        $payload = "\xaa\xbb\xcc\xdd";
        $sp = new XdrSignedPayload($rawEd25519, $payload);

        $strkey = $sp->toJsonValue();
        $this->assertStringStartsWith('P', $strkey);

        $rt = XdrSignedPayload::fromJsonValue($strkey);
        $this->assertSame($rawEd25519, $rt->ed25519);
        $this->assertSame($payload, $rt->payload);
    }

    // -----------------------------------------------------------------
    // XdrClaimableBalanceID — B-strkey over 33-byte slice
    // -----------------------------------------------------------------

    public function testXdrClaimableBalanceIDRoundTrip(): void
    {
        $rawHash = str_repeat("\xab", 32);
        $hashHex = bin2hex($rawHash);
        $cbid = new XdrClaimableBalanceID(
            new XdrClaimableBalanceIDType(XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0),
            $hashHex
        );

        $strkey = $cbid->toJsonValue();
        $this->assertStringStartsWith('B', $strkey);

        // Verify the strkey decodes to the same 33-byte slice
        // (type byte 0x00 prefix + 32 hash bytes).
        $decoded = StrKey::decodeClaimableBalanceId($strkey);
        $this->assertSame(33, strlen($decoded));
        $this->assertSame("\x00", $decoded[0]);
        $this->assertSame($rawHash, substr($decoded, 1, 32));

        $rt = XdrClaimableBalanceID::fromJsonValue($strkey);
        $this->assertSame($hashHex, $rt->hash);
        $this->assertSame(XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0, $rt->type->getValue());
    }

    public function testXdrClaimableBalanceIDXdrRoundTripPreservesByteForm(): void
    {
        // Build a canonical fixture, encode to XDR, decode, JSON, decode JSON,
        // re-encode XDR and assert byte equality with the original XDR.
        $rawHash = "\xde\xad\xbe\xef" . str_repeat("\x00", 28);
        $hashHex = bin2hex($rawHash);
        $cbid = new XdrClaimableBalanceID(
            new XdrClaimableBalanceIDType(XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0),
            $hashHex
        );

        $xdrBytes = $cbid->encode();
        $jsonValue = $cbid->toJsonValue();
        $rt = XdrClaimableBalanceID::fromJsonValue($jsonValue);
        $this->assertSame($xdrBytes, $rt->encode());
    }

    // -----------------------------------------------------------------
    // XdrMuxedAccount — G/M-strkey dispatch
    // -----------------------------------------------------------------

    public function testXdrMuxedAccountEd25519ArmRoundTrip(): void
    {
        $rawEd25519 = str_repeat("\xcc", 32);
        $expectedStrkey = StrKey::encodeAccountId($rawEd25519);

        $muxed = new XdrMuxedAccount($rawEd25519);
        $this->assertSame($expectedStrkey, $muxed->toJsonValue());

        $rt = XdrMuxedAccount::fromJsonValue($expectedStrkey);
        $this->assertSame(XdrCryptoKeyType::KEY_TYPE_ED25519, $rt->type->getValue());
        $this->assertSame($rawEd25519, $rt->ed25519);
    }

    public function testXdrMuxedAccountMuxedEd25519ArmRoundTrip(): void
    {
        $rawEd25519 = str_repeat("\xdd", 32);
        $id = 0x0102030405060708;

        $med = new XdrMuxedAccountMed25519($id, $rawEd25519);
        $muxed = new XdrMuxedAccount(null, $med);

        $strkey = $muxed->toJsonValue();
        $this->assertStringStartsWith('M', $strkey);

        $rt = XdrMuxedAccount::fromJsonValue($strkey);
        $this->assertSame(XdrCryptoKeyType::KEY_TYPE_MUXED_ED25519, $rt->type->getValue());
        $this->assertSame($id, $rt->med25519->id);
        $this->assertSame($rawEd25519, $rt->med25519->ed25519);
    }

    // -----------------------------------------------------------------
    // XdrMuxedAccountMed25519 — standalone M-strkey
    // -----------------------------------------------------------------

    public function testXdrMuxedAccountMed25519PackOrderIsEd25519ThenIdBigEndian(): void
    {
        // Pin: the 40-byte pack is `ed25519 (32 bytes) || id (uint64 big-endian, 8 bytes)`,
        // matching the M-strkey payload layout from SEP-0023.
        $rawEd25519 = str_repeat("\xee", 32);
        $id = 0x1122334455667788;

        $med = new XdrMuxedAccountMed25519($id, $rawEd25519);
        $strkey = $med->toJsonValue();
        $decoded = StrKey::decodeMuxedAccountId($strkey);
        $this->assertSame(40, strlen($decoded));
        $this->assertSame($rawEd25519, substr($decoded, 0, 32));
        // Big-endian uint64 representation of $id.
        $this->assertSame("\x11\x22\x33\x44\x55\x66\x77\x88", substr($decoded, 32, 8));

        $rt = XdrMuxedAccountMed25519::fromJsonValue($strkey);
        $this->assertSame($id, $rt->id);
        $this->assertSame($rawEd25519, $rt->ed25519);
    }

    // -----------------------------------------------------------------
    // XdrSCAddress — 5-arm dispatch
    // -----------------------------------------------------------------

    public function testXdrSCAddressAccountArmRoundTrip(): void
    {
        $rawEd25519 = str_repeat("\x10", 32);
        $accountId = StrKey::encodeAccountId($rawEd25519);

        $addr = new XdrSCAddress(new XdrSCAddressType(XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT));
        $addr->accountId = new XdrAccountID($accountId);

        $this->assertSame($accountId, $addr->toJsonValue());
        $rt = XdrSCAddress::fromJsonValue($accountId);
        $this->assertSame(XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT, $rt->type->getValue());
        $this->assertSame($accountId, $rt->accountId->getAccountId());
    }

    public function testXdrSCAddressContractArmRoundTrip(): void
    {
        $rawContract = str_repeat("\x20", 32);
        $contractIdHex = bin2hex($rawContract);
        $contractStrKey = StrKey::encodeContractId($rawContract);

        $addr = new XdrSCAddress(new XdrSCAddressType(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT));
        $addr->contractId = $contractIdHex;

        $this->assertSame($contractStrKey, $addr->toJsonValue());
        $rt = XdrSCAddress::fromJsonValue($contractStrKey);
        $this->assertSame(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT, $rt->type->getValue());
        $this->assertSame($contractIdHex, $rt->contractId);
    }

    public function testXdrSCAddressMuxedAccountArmPackOrder(): void
    {
        // SC_ADDRESS muxed_account arm packs as ed25519 (32 bytes) || id (uint64 BE, 8 bytes),
        // matching the M-strkey payload layout from SEP-0023.
        $rawEd25519 = str_repeat("\x30", 32);
        $id = 42;

        $med = new XdrMuxedAccountMed25519($id, $rawEd25519);
        $addr = new XdrSCAddress(new XdrSCAddressType(XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT));
        $addr->muxedAccount = $med;

        $strkey = $addr->toJsonValue();
        $this->assertStringStartsWith('M', $strkey);
        $decoded = StrKey::decodeMuxedAccountId($strkey);
        $this->assertSame(40, strlen($decoded));
        $this->assertSame($rawEd25519, substr($decoded, 0, 32));

        $rt = XdrSCAddress::fromJsonValue($strkey);
        $this->assertSame(XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT, $rt->type->getValue());
        $this->assertSame($id, $rt->muxedAccount->id);
        $this->assertSame($rawEd25519, $rt->muxedAccount->ed25519);
    }

    public function testXdrSCAddressClaimableBalanceArmRoundTrip(): void
    {
        $rawHash = str_repeat("\x40", 32);
        $hashHex = bin2hex($rawHash);
        $cbid = new XdrClaimableBalanceID(
            new XdrClaimableBalanceIDType(XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0),
            $hashHex
        );

        $addr = new XdrSCAddress(new XdrSCAddressType(XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE));
        $addr->claimableBalanceId = $cbid;

        $strkey = $addr->toJsonValue();
        $this->assertStringStartsWith('B', $strkey);

        $rt = XdrSCAddress::fromJsonValue($strkey);
        $this->assertSame(XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE, $rt->type->getValue());
        $this->assertSame($hashHex, $rt->claimableBalanceId->hash);
    }

    public function testXdrSCAddressLiquidityPoolArmRoundTrip(): void
    {
        $rawPoolId = str_repeat("\x50", 32);
        $poolIdHex = bin2hex($rawPoolId);
        $expectedStrkey = StrKey::encodeLiquidityPoolId($rawPoolId);

        $addr = new XdrSCAddress(new XdrSCAddressType(XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL));
        $addr->liquidityPoolId = $poolIdHex;

        $this->assertSame($expectedStrkey, $addr->toJsonValue());
        $rt = XdrSCAddress::fromJsonValue($expectedStrkey);
        $this->assertSame(XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL, $rt->type->getValue());
        $this->assertSame($poolIdHex, $rt->liquidityPoolId);
    }

    public function testXdrSCAddressRejectsUnknownPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid XdrSCAddress strkey prefix');
        XdrSCAddress::fromJsonValue('Z' . str_repeat('A', 55));
    }

    // -----------------------------------------------------------------
    // XdrAsset — discriminated union with native bare-string + alphanum objects
    // -----------------------------------------------------------------

    public function testXdrAssetNativeArmEmitsBareString(): void
    {
        $native = new XdrAsset(XdrAssetType::ASSET_TYPE_NATIVE());
        $this->assertSame('native', $native->toJsonValue());

        $rt = XdrAsset::fromJsonValue('native');
        $this->assertSame(XdrAssetType::ASSET_TYPE_NATIVE, $rt->type->getValue());
    }

    public function testXdrAssetCreditAlphanum4ArmEmitsObject(): void
    {
        $rawIssuer = str_repeat("\x60", 32);
        $issuerStrKey = StrKey::encodeAccountId($rawIssuer);

        $alpha4 = new XdrAssetAlphaNum4("USD\x00", new XdrAccountID($issuerStrKey));
        $asset = new XdrAsset(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4());
        $asset->alphaNum4 = $alpha4;

        $value = $asset->toJsonValue();
        $this->assertIsArray($value);
        $this->assertArrayHasKey('credit_alphanum4', $value);
        $this->assertSame('USD', $value['credit_alphanum4']['asset_code']);
        $this->assertSame($issuerStrKey, $value['credit_alphanum4']['issuer']);

        $rt = XdrAsset::fromJsonValue($value);
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4, $rt->type->getValue());
        $this->assertSame("USD\x00", $rt->alphaNum4->assetCode);
        $this->assertSame($issuerStrKey, $rt->alphaNum4->issuer->getAccountId());
    }

    public function testXdrAssetCreditAlphanum12ArmEmitsObject(): void
    {
        $rawIssuer = str_repeat("\x70", 32);
        $issuerStrKey = StrKey::encodeAccountId($rawIssuer);

        // Pad "EURT" (4 bytes) up to 12 bytes for storage; SEP-51 emits the
        // 5-byte form per the AssetCode4-vs-AssetCode12 distinguishability
        // rule, and the trailing null byte renders as the SEP-51 \0 escape
        // (per sep-0051.md §"Asset Code Types": three-byte AssetCode12 emits
        // "ABC\\0\\0", confirming embedded nulls go through the String escape).
        $alpha12 = new XdrAssetAlphaNum12(str_pad('EURT', 12, "\x00"), new XdrAccountID($issuerStrKey));
        $asset = new XdrAsset(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12());
        $asset->alphaNum12 = $alpha12;

        $value = $asset->toJsonValue();
        $this->assertIsArray($value);
        $this->assertArrayHasKey('credit_alphanum12', $value);
        $this->assertSame('EURT\\0', $value['credit_alphanum12']['asset_code']);
        $this->assertSame($issuerStrKey, $value['credit_alphanum12']['issuer']);
    }

    public function testXdrAssetRejectsUnknownBareString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown XdrAsset bare string');
        XdrAsset::fromJsonValue('xlm');
    }

    public function testXdrAssetRejectsUnknownArmKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown arm key for XdrAsset');
        XdrAsset::fromJsonValue(['nonsense' => null]);
    }

    // -----------------------------------------------------------------
    // XdrAssetAlphaNum4 / XdrAssetAlphaNum12 trim-pad-escape semantics
    // -----------------------------------------------------------------

    public function testXdrAssetAlphaNum4TrimsTrailingNulls3ByteCode(): void
    {
        $rawIssuer = str_repeat("\x80", 32);
        $issuerStrKey = StrKey::encodeAccountId($rawIssuer);

        // 3-byte code "USD" stored as 4-byte buffer "USD\x00".
        $alpha = new XdrAssetAlphaNum4("USD\x00", new XdrAccountID($issuerStrKey));
        $value = $alpha->toJsonValue();
        $this->assertSame('USD', $value['asset_code']);

        $rt = XdrAssetAlphaNum4::fromJsonValue($value);
        $this->assertSame("USD\x00", $rt->assetCode);
    }

    public function testXdrAssetAlphaNum4FullCode(): void
    {
        $rawIssuer = str_repeat("\x90", 32);
        $issuerStrKey = StrKey::encodeAccountId($rawIssuer);

        $alpha = new XdrAssetAlphaNum4("USDC", new XdrAccountID($issuerStrKey));
        $value = $alpha->toJsonValue();
        $this->assertSame('USDC', $value['asset_code']);

        $rt = XdrAssetAlphaNum4::fromJsonValue($value);
        $this->assertSame('USDC', $rt->assetCode);
    }

    public function testXdrAssetAlphaNum4EscapesNonPrintable(): void
    {
        $rawIssuer = str_repeat("\xa0", 32);
        $issuerStrKey = StrKey::encodeAccountId($rawIssuer);

        // Code "U\x01" (one printable + one control char), stored as 4-byte buffer.
        $alpha = new XdrAssetAlphaNum4("U\x01\x00\x00", new XdrAccountID($issuerStrKey));
        $value = $alpha->toJsonValue();
        $this->assertSame('U\\x01', $value['asset_code']);

        $rt = XdrAssetAlphaNum4::fromJsonValue($value);
        $this->assertSame("U\x01\x00\x00", $rt->assetCode);
    }

    public function testXdrAssetAlphaNum12_3ByteCodeRightPadsTo5(): void
    {
        // 3-byte code "ABC" stored as 12-byte buffer; SEP-51 emits "ABC\\0\\0"
        // (right-padded to 5 to preserve AssetCode4-vs-AssetCode12 distinguishability).
        $rawIssuer = str_repeat("\xb0", 32);
        $issuerStrKey = StrKey::encodeAccountId($rawIssuer);

        $alpha = new XdrAssetAlphaNum12(str_pad('ABC', 12, "\x00"), new XdrAccountID($issuerStrKey));
        $value = $alpha->toJsonValue();
        $this->assertSame("ABC\\0\\0", $value['asset_code']);

        $rt = XdrAssetAlphaNum12::fromJsonValue($value);
        // Round-tripped value is right-padded to 12 bytes from the 5-byte
        // unescape of "ABC\0\0".
        $this->assertSame(12, strlen($rt->assetCode));
        $this->assertSame("ABC\x00\x00" . str_repeat("\x00", 7), $rt->assetCode);
    }

    public function testXdrAssetAlphaNum12_5ByteCodeUnchanged(): void
    {
        $rawIssuer = str_repeat("\xc0", 32);
        $issuerStrKey = StrKey::encodeAccountId($rawIssuer);

        $alpha = new XdrAssetAlphaNum12(str_pad('ABCDE', 12, "\x00"), new XdrAccountID($issuerStrKey));
        $value = $alpha->toJsonValue();
        $this->assertSame('ABCDE', $value['asset_code']);
    }

    public function testXdrAssetAlphaNum12_12ByteCodeUnchanged(): void
    {
        $rawIssuer = str_repeat("\xd0", 32);
        $issuerStrKey = StrKey::encodeAccountId($rawIssuer);

        $alpha = new XdrAssetAlphaNum12("ABCDEFGHIJKL", new XdrAccountID($issuerStrKey));
        $value = $alpha->toJsonValue();
        $this->assertSame('ABCDEFGHIJKL', $value['asset_code']);
    }

    public function testXdrAssetAlphaNum12AllNullCodeEmitsFiveEscapedNuls(): void
    {
        // An all-NUL AssetCode12 serialises as five escaped NULs and
        // round-trips back to 12 NUL bytes, matching the rs-stellar-xdr
        // reference implementation.
        $rawIssuer = str_repeat("\xe0", 32);
        $issuerStrKey = StrKey::encodeAccountId($rawIssuer);

        $alpha = new XdrAssetAlphaNum12(str_repeat("\x00", 12), new XdrAccountID($issuerStrKey));
        $value = $alpha->toJsonValue();
        $this->assertSame('\\0\\0\\0\\0\\0', $value['asset_code']);

        $back = XdrAssetAlphaNum12::fromJsonValue($value);
        $this->assertSame(str_repeat("\x00", 12), $back->getAssetCode());
    }

    public function testXdrAssetAlphaNum12RejectsLeFourByteCodeOnFromSide(): void
    {
        // 4-byte input must be rejected on the AlphaNum12 from-side
        // (it should be routed through AlphaNum4 instead).
        $rawIssuer = str_repeat("\xf0", 32);
        $issuerStrKey = StrKey::encodeAccountId($rawIssuer);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('AssetCode12 must exceed 4 bytes');
        XdrAssetAlphaNum12::fromJsonValue([
            'asset_code' => 'ABCD',
            'issuer' => $issuerStrKey,
        ]);
    }

    public function testXdrAssetAlphaNum12Rejects13ByteCodeOnFromSide(): void
    {
        $rawIssuer = str_repeat("\x05", 32);
        $issuerStrKey = StrKey::encodeAccountId($rawIssuer);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('AssetCode12 must not exceed 12 bytes');
        XdrAssetAlphaNum12::fromJsonValue([
            'asset_code' => 'ABCDEFGHIJKLM',
            'issuer' => $issuerStrKey,
        ]);
    }

    // -----------------------------------------------------------------
    // XdrAllowTrustOperationAsset — credit_alphanum4 / credit_alphanum12
    // arm keys with trim-pad-escape AssetCode semantics.
    //
    // The IDL field is `AssetCode` (a union typedef whose CREDIT_ALPHANUM4 /
    // CREDIT_ALPHANUM12 arms hold opaque[4] / opaque[12] respectively). The
    // Per SEP-0051 §"Asset Code Types" (and the rs-stellar-xdr reference), the
    // AssetCode union serialises as the BARE AssetCode4/AssetCode12 string
    // form - no arm-key envelope. The wire form rtrims trailing NUL on the
    // 4-byte arm and rtrim-then-pad-to-5 minimum on the 12-byte arm; the
    // parse side discriminates by decoded length (<= 4 bytes -> AssetCode4,
    // >= 5 -> AssetCode12). The override is registered in
    // stellar_json_overrides.rb.
    // -----------------------------------------------------------------

    public function testXdrAllowTrustOperationAssetCode4Path(): void
    {
        // fromAlphaNumAssetCode stores the asset code without null padding;
        // toJsonValue rtrims trailing NULs so the wire form is the bare code.
        $allow = XdrAllowTrustOperationAsset::fromAlphaNumAssetCode('USD');
        $this->assertSame('USD', $allow->toJsonValue());

        // Round-trip: 3 decoded bytes discriminate to the AssetCode4 arm and
        // the from-side right-pads to 4 bytes.
        $rt = XdrAllowTrustOperationAsset::fromJsonValue('USD');
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4, $rt->type->getValue());
        $this->assertSame("USD\x00", $rt->assetCode4);
    }

    public function testXdrAllowTrustOperationAssetCode12Path(): void
    {
        $allow = XdrAllowTrustOperationAsset::fromAlphaNumAssetCode('EURTOK');
        // 6-byte code is emitted as-is (above the 5-byte AssetCode12 floor).
        $this->assertSame('EURTOK', $allow->toJsonValue());

        // 6 decoded bytes discriminate to the AssetCode12 arm; the from-side
        // right-pads to 12 bytes.
        $rt = XdrAllowTrustOperationAsset::fromJsonValue('EURTOK');
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12, $rt->type->getValue());
        $this->assertSame(str_pad('EURTOK', 12, "\x00"), $rt->assetCode12);
    }

    public function testXdrAllowTrustOperationAssetCreditAlphanum4RoundTrip(): void
    {
        // 4-byte AlphaNum4 storage with trailing NUL is rtrimmed on the wire
        // so the rendered code is the bare "USD".
        $allow = new XdrAllowTrustOperationAsset(
            new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4)
        );
        $allow->assetCode4 = "USD\x00";

        $this->assertSame('USD', $allow->toJsonValue());

        // Round-trip via the JSON facade as well.
        $json = $allow->toJson();
        $this->assertSame('"USD"', $json);
        $rt = XdrAllowTrustOperationAsset::fromJson($json);
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4, $rt->type->getValue());
        $this->assertSame("USD\x00", $rt->assetCode4);
    }

    public function testXdrAllowTrustOperationAssetCreditAlphanum12RoundTrip(): void
    {
        // 12-byte buffer 'TESTTOKEN12\x00' has 11 non-NUL bytes; rtrim
        // trailing NUL leaves 'TESTTOKEN12' (11 bytes); above the 5-byte
        // floor so it is emitted as-is then escaped.
        $allow = new XdrAllowTrustOperationAsset(
            new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12)
        );
        $allow->assetCode12 = "TESTTOKEN12\x00";

        $this->assertSame('TESTTOKEN12', $allow->toJsonValue());

        // Round-trip via the JSON facade as well.
        $json = $allow->toJson();
        $rt = XdrAllowTrustOperationAsset::fromJson($json);
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12, $rt->type->getValue());
        $this->assertSame(str_pad('TESTTOKEN12', 12, "\x00"), $rt->assetCode12);
    }

    public function testXdrAllowTrustOperationAssetAcceptsLegacyObjectForms(): void
    {
        // SDK releases up to 1.11.x emitted single-key objects; they are
        // accepted on input during the deprecation window, never emitted.
        $rt4 = XdrAllowTrustOperationAsset::fromJsonValue(['credit_alphanum4' => 'USD']);
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4, $rt4->type->getValue());
        $this->assertSame("USD\x00", $rt4->assetCode4);

        $rt12 = XdrAllowTrustOperationAsset::fromJsonValue(['credit_alphanum12' => 'EURTOK']);
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12, $rt12->type->getValue());
        $this->assertSame(str_pad('EURTOK', 12, "\x00"), $rt12->assetCode12);
    }

    public function testXdrAllowTrustOperationAssetEmptyStringIsAllNulAssetCode4(): void
    {
        // The reference implementation parses "" as an all-NUL AssetCode4
        // (and emits "" for it); length discrimination places 0 decoded
        // bytes on the 4-byte arm.
        $rt = XdrAllowTrustOperationAsset::fromJsonValue('');
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4, $rt->type->getValue());
        $this->assertSame(str_repeat("\x00", 4), $rt->assetCode4);
    }

    // -----------------------------------------------------------------
    // XdrMemo — discriminated union with none bare-string + per-arm objects
    // -----------------------------------------------------------------

    public function testXdrMemoNoneArmEmitsBareString(): void
    {
        $memo = new XdrMemo(XdrMemoType::MEMO_NONE());
        $this->assertSame('none', $memo->toJsonValue());

        $rt = XdrMemo::fromJsonValue('none');
        $this->assertSame(XdrMemoType::MEMO_NONE, $rt->type->getValue());
    }

    public function testXdrMemoTextArmRoundTrip(): void
    {
        $memo = new XdrMemo(XdrMemoType::MEMO_TEXT());
        $memo->text = 'Hello SEP-51';
        $value = $memo->toJsonValue();
        $this->assertSame(['text' => 'Hello SEP-51'], $value);

        $rt = XdrMemo::fromJsonValue($value);
        $this->assertSame(XdrMemoType::MEMO_TEXT, $rt->type->getValue());
        $this->assertSame('Hello SEP-51', $rt->text);
    }

    public function testXdrMemoIdArmEmitsStringEncodedUint64(): void
    {
        $memo = new XdrMemo(XdrMemoType::MEMO_ID());
        $memo->id = 0x1122334455667788;
        $value = $memo->toJsonValue();
        $this->assertSame(['id' => '1234605616436508552'], $value);

        $rt = XdrMemo::fromJsonValue($value);
        $this->assertSame(XdrMemoType::MEMO_ID, $rt->type->getValue());
        $this->assertSame(0x1122334455667788, $rt->id);
    }

    public function testXdrMemoHashArmRoundTrip(): void
    {
        $hash = str_repeat("\x42", 32);
        $memo = new XdrMemo(XdrMemoType::MEMO_HASH());
        $memo->hash = $hash;

        $value = $memo->toJsonValue();
        $this->assertSame(['hash' => bin2hex($hash)], $value);

        $rt = XdrMemo::fromJsonValue($value);
        $this->assertSame(XdrMemoType::MEMO_HASH, $rt->type->getValue());
        $this->assertSame($hash, $rt->hash);
    }

    public function testXdrMemoReturnArmEmitsReturnArmKey(): void
    {
        // The arm wire key is "return" (prefix-stripped form of MEMO_RETURN),
        // not "ret_hash" or "return_hash" (SEP-0051 §Discriminated unions
        // strips the well-known IDL prefix and lowercases the remainder).
        $hash = str_repeat("\x77", 32);
        $memo = new XdrMemo(XdrMemoType::MEMO_RETURN());
        $memo->returnHash = $hash;

        $value = $memo->toJsonValue();
        $this->assertSame(['return' => bin2hex($hash)], $value);

        $rt = XdrMemo::fromJsonValue($value);
        $this->assertSame(XdrMemoType::MEMO_RETURN, $rt->type->getValue());
        $this->assertSame($hash, $rt->returnHash);
    }

    // -----------------------------------------------------------------
    // XdrUInt128Parts / XdrInt128Parts / XdrUInt256Parts / XdrInt256Parts — GMP
    // -----------------------------------------------------------------

    public function testXdrUInt128PartsRoundTripSmallValue(): void
    {
        $hi = 0;
        $lo = 42;
        $parts = new XdrUInt128Parts($hi, $lo);
        $this->assertSame('42', $parts->toJsonValue());

        $rt = XdrUInt128Parts::fromJsonValue('42');
        $this->assertSame(0, $rt->hi);
        $this->assertSame(42, $rt->lo);
    }

    public function testXdrInt128PartsRoundTripNegativeValue(): void
    {
        // -1 = (hi=-1, lo=0xFFFFFFFFFFFFFFFF as signed = -1)
        $parts = new XdrInt128Parts(-1, -1);
        $this->assertSame('-1', $parts->toJsonValue());

        $rt = XdrInt128Parts::fromJsonValue('-1');
        $this->assertSame(-1, $rt->hi);
        $this->assertSame(-1, $rt->lo);
    }

    public function testXdrUInt256PartsRoundTripLargeValue(): void
    {
        // Pick a value that requires all four limbs.
        $parts = new XdrUInt256Parts(1, 2, 3, 4);
        $value = $parts->toJsonValue();
        $this->assertNotEmpty($value);

        $rt = XdrUInt256Parts::fromJsonValue($value);
        $this->assertSame(1, $rt->hiHi);
        $this->assertSame(2, $rt->hiLo);
        $this->assertSame(3, $rt->loHi);
        $this->assertSame(4, $rt->loLo);
    }

    public function testXdrInt256PartsRoundTripNegativeValue(): void
    {
        $parts = new XdrInt256Parts(-1, -1, -1, -1);
        $this->assertSame('-1', $parts->toJsonValue());

        $rt = XdrInt256Parts::fromJsonValue('-1');
        $this->assertSame(-1, $rt->hiHi);
    }

    // -----------------------------------------------------------------
    // XdrLiquidityPoolDepositOperation / XdrLiquidityPoolWithdrawOperation —
    // hex storage form, L-strkey emission via field-override
    // -----------------------------------------------------------------

    public function testXdrLiquidityPoolDepositOperationFieldOverrideEmitsLStrKey(): void
    {
        $rawPoolId = str_repeat("\x88", 32);
        $poolIdHex = bin2hex($rawPoolId);
        $expectedStrKey = StrKey::encodeLiquidityPoolId($rawPoolId);

        // The wrapper accepts a hex string poolId; the SEP-51 field override
        // uses the hex-form encoder (encodeLiquidityPoolIdHex) per the
        // storage-form audit document.
        $price = new \Soneso\StellarSDK\Xdr\XdrPrice(1, 2);
        $op = new XdrLiquidityPoolDepositOperation(
            $poolIdHex,
            new BigInteger(100),
            new BigInteger(200),
            $price,
            $price
        );

        $value = $op->toJsonValue();
        $this->assertSame($expectedStrKey, $value['liquidity_pool_id']);

        $rt = XdrLiquidityPoolDepositOperation::fromJsonValue($value);
        $this->assertSame($poolIdHex, $rt->liquidityPoolID);
    }

    public function testXdrLiquidityPoolWithdrawOperationFieldOverrideEmitsLStrKey(): void
    {
        $rawPoolId = str_repeat("\x99", 32);
        $poolIdHex = bin2hex($rawPoolId);
        $expectedStrKey = StrKey::encodeLiquidityPoolId($rawPoolId);

        $op = new XdrLiquidityPoolWithdrawOperation(
            $poolIdHex,
            new BigInteger(1000),
            new BigInteger(50),
            new BigInteger(60)
        );

        $value = $op->toJsonValue();
        $this->assertSame($expectedStrKey, $value['liquidity_pool_id']);

        $rt = XdrLiquidityPoolWithdrawOperation::fromJsonValue($value);
        $this->assertSame($poolIdHex, $rt->liquidityPoolID);
    }

    public function testXdrLedgerKeyLiquidityPoolFieldOverrideEmitsLStrKey(): void
    {
        // XdrLedgerKeyLiquidityPool stores the pool id as RAW bytes (no
        // wrapper bin2hex); the field-override registry uses
        // `encoding: :raw` and emits StrKey::encodeLiquidityPoolId.
        $rawPoolId = str_repeat("\xaa", 32);
        $expectedStrKey = StrKey::encodeLiquidityPoolId($rawPoolId);

        $key = new XdrLedgerKeyLiquidityPool($rawPoolId);
        $value = $key->toJsonValue();
        $this->assertSame($expectedStrKey, $value['liquidity_pool_id']);

        $rt = XdrLedgerKeyLiquidityPool::fromJsonValue($value);
        $this->assertSame($rawPoolId, $rt->liquidityPoolID);
    }

    // -----------------------------------------------------------------
    // XdrDataValue — hand-written typedef wrapper
    // -----------------------------------------------------------------

    public function testXdrDataValueEmitsHexForNonNullBytes(): void
    {
        $bytes = "\x01\x02\xde\xad\xbe\xef";
        $dv = new XdrDataValue($bytes);
        $this->assertSame('0102deadbeef', $dv->toJsonValue());

        $rt = XdrDataValue::fromJsonValue('0102deadbeef');
        $this->assertSame($bytes, $rt->getValue());
    }

    public function testXdrDataValueEmitsNullForNullValue(): void
    {
        $dv = new XdrDataValue(null);
        $this->assertNull($dv->toJsonValue());

        $rt = XdrDataValue::fromJsonValue(null);
        $this->assertNull($rt->getValue());
    }

    public function testXdrDataValueRejectsNonStringNonNullInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected hex string or null for XdrDataValue');
        XdrDataValue::fromJsonValue(['nonsense' => 'bytes']);
    }

    // -----------------------------------------------------------------
    // XdrSCVal — wrapper-backed union end-to-end (covers SCAddress sub-arm)
    // -----------------------------------------------------------------

    public function testXdrSCValAddressArmRoundTripWithAccountSubArm(): void
    {
        $rawEd25519 = str_repeat("\x12", 32);
        $accountId = StrKey::encodeAccountId($rawEd25519);

        $addr = new XdrSCAddress(new XdrSCAddressType(XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT));
        $addr->accountId = new XdrAccountID($accountId);

        $scVal = new \Soneso\StellarSDK\Xdr\XdrSCVal(\Soneso\StellarSDK\Xdr\XdrSCValType::ADDRESS());
        $scVal->address = $addr;

        $json = $scVal->toJson();
        $rt = \Soneso\StellarSDK\Xdr\XdrSCVal::fromJson($json);
        $this->assertSame(\Soneso\StellarSDK\Xdr\XdrSCValType::SCV_ADDRESS, $rt->type->getValue());
        $this->assertSame($accountId, $rt->address->accountId->getAccountId());
    }

    // -----------------------------------------------------------------
    // XdrTransactionEnvelope — wrapper-backed int-cased union
    // -----------------------------------------------------------------

    public function testXdrTransactionEnvelopeIsSep51Wired(): void
    {
        // XdrTransactionEnvelope round-trips via the standard generator
        // template (no bespoke override). The wire form is an int-cased
        // single-key object. This test confirms the methods exist and
        // reject obvious malformed input. End-to-end XDR round-trip via
        // JSON is exercised by the snapshot corpus.
        $this->assertTrue(method_exists(\Soneso\StellarSDK\Xdr\XdrTransactionEnvelope::class, 'toJsonValue'));
        $this->assertTrue(method_exists(\Soneso\StellarSDK\Xdr\XdrTransactionEnvelope::class, 'fromJsonValue'));

        $this->expectException(\InvalidArgumentException::class);
        \Soneso\StellarSDK\Xdr\XdrTransactionEnvelope::fromJsonValue('not-an-object');
    }

    // -----------------------------------------------------------------
    // F4-S1: XdrAccountID eager strkey validation on the from-side.
    // -----------------------------------------------------------------

    public function testXdrAccountIDRejectsInvalidChecksum(): void
    {
        // A 56-character all-A buffer prefixed with G has the right shape
        // but a wrong checksum; eager validation must reject it at parse
        // time, before the wrapper stores the raw string.
        $this->expectException(\InvalidArgumentException::class);
        XdrAccountID::fromJsonValue('G' . str_repeat('A', 55));
    }

    public function testXdrAccountIDRejectsAnOversizedPayloadByItsEncodedLength(): void
    {
        // A G-strkey over a 37-byte payload with a correct checksum encodes to
        // 64 characters, so the encoded-length rule stops it before any payload
        // byte is read.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('G-strkey must be 56 characters long, 64 characters given');
        XdrAccountID::fromJsonValue(self::G_STRKEY_WITH_37_BYTE_PAYLOAD);
    }

    // -----------------------------------------------------------------
    // Per-registry-entry round-trip integration tests.
    //
    // The SEP51_FIELD_OVERRIDES registry holds 12 entries (8 strkey rows +
    // 4 asset_code rows). This block adds round-trip coverage for the
    // remaining sites beyond the existing DepositOp / WithdrawOp /
    // LedgerKeyLiquidityPool tests.
    // -----------------------------------------------------------------

    public function testXdrTrustlineAssetBasePoolIDRoundTrip(): void
    {
        // XdrTrustlineAsset's LIQUIDITY_POOL arm stores the pool id as raw
        // bytes (no wrapper bin2hex; encoding: :raw in the registry).
        $rawPoolId = str_repeat("\xa1", 32);
        $expectedStrKey = StrKey::encodeLiquidityPoolId($rawPoolId);

        $ta = new \Soneso\StellarSDK\Xdr\XdrTrustlineAsset(
            new XdrAssetType(XdrAssetType::ASSET_TYPE_POOL_SHARE)
        );
        $ta->liquidityPoolID = $rawPoolId;

        $value = $ta->toJsonValue();
        // The XdrTrustlineAsset union arm key is prefix-stripped from
        // ASSET_TYPE_POOL_SHARE; the longest shared prefix across the
        // four discriminants (NATIVE, CREDIT_ALPHANUM4, CREDIT_ALPHANUM12,
        // POOL_SHARE) is ASSET_TYPE_, so the arm key is `pool_share`.
        $this->assertSame(['pool_share' => $expectedStrKey], $value);

        $rt = \Soneso\StellarSDK\Xdr\XdrTrustlineAsset::fromJsonValue($value);
        $this->assertSame(\Soneso\StellarSDK\Xdr\XdrAssetType::ASSET_TYPE_POOL_SHARE, $rt->type->getValue());
        $this->assertSame($rawPoolId, $rt->liquidityPoolID);
    }

    public function testXdrLiquidityPoolEntryPoolIDRoundTrip(): void
    {
        // XdrLiquidityPoolEntry stores its pool id as raw bytes.
        $rawPoolId = str_repeat("\xa2", 32);
        $expectedStrKey = StrKey::encodeLiquidityPoolId($rawPoolId);

        $body = new \Soneso\StellarSDK\Xdr\XdrLiquidityPoolBody(
            new \Soneso\StellarSDK\Xdr\XdrLiquidityPoolType(
                \Soneso\StellarSDK\Xdr\XdrLiquidityPoolType::LIQUIDITY_POOL_CONSTANT_PRODUCT
            )
        );
        $alpha = new XdrAssetAlphaNum4(
            "AAA\x00",
            new XdrAccountID(StrKey::encodeAccountId(str_repeat("\x10", 32)))
        );
        $assetA = new XdrAsset(XdrAssetType::ASSET_TYPE_NATIVE());
        $assetB = new XdrAsset(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4());
        $assetB->alphaNum4 = $alpha;
        $params = new \Soneso\StellarSDK\Xdr\XdrLiquidityPoolConstantProductParameters(
            $assetA,
            $assetB,
            30
        );
        $cp = new \Soneso\StellarSDK\Xdr\XdrConstantProduct(
            $params,
            new BigInteger(0),
            new BigInteger(0),
            new BigInteger(0),
            0
        );
        $body->constantProduct = $cp;

        $entry = new \Soneso\StellarSDK\Xdr\XdrLiquidityPoolEntry($rawPoolId, $body);

        $value = $entry->toJsonValue();
        $this->assertSame($expectedStrKey, $value['liquidity_pool_id']);

        $rt = \Soneso\StellarSDK\Xdr\XdrLiquidityPoolEntry::fromJsonValue($value);
        $this->assertSame($rawPoolId, $rt->liquidityPoolID);
    }

    public function testXdrClaimLiquidityAtomPoolIDRoundTrip(): void
    {
        // XdrClaimLiquidityAtom stores its pool id as raw bytes.
        $rawPoolId = str_repeat("\xa3", 32);
        $expectedStrKey = StrKey::encodeLiquidityPoolId($rawPoolId);

        $assetSold = new XdrAsset(XdrAssetType::ASSET_TYPE_NATIVE());
        $assetBought = new XdrAsset(XdrAssetType::ASSET_TYPE_NATIVE());
        $atom = new \Soneso\StellarSDK\Xdr\XdrClaimLiquidityAtom(
            $rawPoolId,
            $assetSold,
            new BigInteger(100),
            $assetBought,
            new BigInteger(200)
        );

        $value = $atom->toJsonValue();
        $this->assertSame($expectedStrKey, $value['liquidity_pool_id']);

        $rt = \Soneso\StellarSDK\Xdr\XdrClaimLiquidityAtom::fromJsonValue($value);
        $this->assertSame($rawPoolId, $rt->liquidityPoolID);
    }

    public function testXdrHashIDPreimageRevokeIDPoolIDRoundTrip(): void
    {
        // XdrHashIDPreimageRevokeID stores its pool id as raw bytes.
        $rawPoolId = str_repeat("\xa4", 32);
        $expectedStrKey = StrKey::encodeLiquidityPoolId($rawPoolId);

        $rawSource = str_repeat("\x20", 32);
        $sourceAccountId = new XdrAccountID(StrKey::encodeAccountId($rawSource));
        $seqNum = new \Soneso\StellarSDK\Xdr\XdrSequenceNumber(new BigInteger(123));
        $asset = new XdrAsset(XdrAssetType::ASSET_TYPE_NATIVE());

        $revoke = new \Soneso\StellarSDK\Xdr\XdrHashIDPreimageRevokeID(
            $sourceAccountId,
            $seqNum,
            42,
            $rawPoolId,
            $asset
        );

        $value = $revoke->toJsonValue();
        $this->assertSame($expectedStrKey, $value['liquidity_pool_id']);

        $rt = \Soneso\StellarSDK\Xdr\XdrHashIDPreimageRevokeID::fromJsonValue($value);
        $this->assertSame($rawPoolId, $rt->liquidityPoolID);
    }

    public function testXdrConfigUpgradeSetKeyBaseContractIDRoundTrip(): void
    {
        // XdrConfigUpgradeSetKey stores its contract id as a 64-char hex
        // string (wrapper bin2hex; encoding: :hex in the registry).
        $rawContract = str_repeat("\xa5", 32);
        $contractIdHex = bin2hex($rawContract);
        $expectedStrKey = StrKey::encodeContractId($rawContract);

        $rawHash = str_repeat("\xa6", 32);
        $contentHashHex = bin2hex($rawHash);
        $cfg = new \Soneso\StellarSDK\Xdr\XdrConfigUpgradeSetKey($contractIdHex, $contentHashHex);

        $value = $cfg->toJsonValue();
        $this->assertSame($expectedStrKey, $value['contract_id']);

        $rt = \Soneso\StellarSDK\Xdr\XdrConfigUpgradeSetKey::fromJsonValue($value);
        $this->assertSame($contractIdHex, $rt->contractID);
    }

    public function testXdrAssetAlphaNum4FieldOverrideRoundTrip(): void
    {
        // Exercises the asset_code:4 SEP51_FIELD_OVERRIDES path on the
        // XdrAssetAlphaNum4Base.assetCode struct field. End-to-end:
        // construct, toJsonValue, fromJsonValue, assert equality.
        $rawIssuer = str_repeat("\xa7", 32);
        $issuerStrKey = StrKey::encodeAccountId($rawIssuer);

        $alpha = new XdrAssetAlphaNum4("USD\x00", new XdrAccountID($issuerStrKey));
        $value = $alpha->toJsonValue();
        $this->assertSame('USD', $value['asset_code']);
        $this->assertSame($issuerStrKey, $value['issuer']);

        $rt = XdrAssetAlphaNum4::fromJsonValue($value);
        $this->assertSame("USD\x00", $rt->assetCode);
        $this->assertSame($issuerStrKey, $rt->issuer->getAccountId());

        // Also via the JSON facade.
        $json = $alpha->toJson();
        $rt2 = XdrAssetAlphaNum4::fromJson($json);
        $this->assertSame("USD\x00", $rt2->assetCode);
    }

    public function testXdrAssetAlphaNum12FieldOverrideRoundTrip(): void
    {
        // Exercises the asset_code:12 SEP51_FIELD_OVERRIDES path on the
        // XdrAssetAlphaNum12Base.assetCode struct field. The 7-byte stored
        // code is above the 5-byte AssetCode12 distinguishability floor so
        // it round-trips through the wire form unchanged (modulo the from-
        // side right-pad to 12 bytes).
        $rawIssuer = str_repeat("\xa8", 32);
        $issuerStrKey = StrKey::encodeAccountId($rawIssuer);

        $stored = str_pad('EURTOKE', 12, "\x00");
        $alpha = new XdrAssetAlphaNum12($stored, new XdrAccountID($issuerStrKey));
        $value = $alpha->toJsonValue();
        $this->assertSame('EURTOKE', $value['asset_code']);
        $this->assertSame($issuerStrKey, $value['issuer']);

        $rt = XdrAssetAlphaNum12::fromJsonValue($value);
        $this->assertSame($stored, $rt->assetCode);

        // Also via the JSON facade.
        $json = $alpha->toJson();
        $rt2 = XdrAssetAlphaNum12::fromJson($json);
        $this->assertSame($stored, $rt2->assetCode);
    }

    // -----------------------------------------------------------------
    // Object-shaped bespoke types — struct-object key closure
    // -----------------------------------------------------------------
    //
    // XdrTimeBounds, XdrManageDataOperation, XdrTransaction and
    // XdrTransactionV0 receive bespoke from-side bodies rather than the
    // default struct template. They carry the same closure over their
    // declared keys, so an over-supplied document is rejected on these
    // paths too.

    public function testXdrTimeBoundsAcceptsExactlyItsDeclaredKeys(): void
    {
        $bounds = XdrTimeBounds::fromJsonValue(['min_time' => '0', 'max_time' => '100']);
        $this->assertSame(['min_time' => '0', 'max_time' => '100'], $bounds->toJsonValue());
    }

    public function testXdrTimeBoundsRejectsAnUnknownKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown field in JSON input for XdrTimeBounds: 'bogus'");
        XdrTimeBounds::fromJsonValue(['min_time' => '0', 'max_time' => '100', 'bogus' => 1]);
    }

    public function testXdrManageDataOperationAcceptsSchemaBesideItsDeclaredKeys(): void
    {
        $op = XdrManageDataOperation::fromJsonValue([
            '$schema' => 'https://schema',
            'data_name' => 'k',
            'data_value' => '00ff',
        ]);
        $this->assertSame(['data_name' => 'k', 'data_value' => '00ff'], $op->toJsonValue());
    }

    public function testXdrManageDataOperationRejectsAnUnknownKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Unknown field in JSON input for XdrManageDataOperation: 'bogus'"
        );
        XdrManageDataOperation::fromJsonValue([
            'data_name' => 'k',
            'data_value' => null,
            'bogus' => 1,
        ]);
    }

    /**
     * A signed v1 transaction envelope, used as the source of a real
     * XdrTransaction rather than a hand-typed key set: the fixture's own
     * toJsonValue supplies the declared keys, so the test cannot pass by
     * agreeing with a stale list.
     */
    private const TRANSACTION_ENVELOPE_V1_XDR =
        'AAAAAgAAAADmmSZkwY3163TMouB2TY8MljqXw2IxVYTGyvDrR6YtAAAqmmQAABpuAAAAAQAAAAAAAAAAAAAAAQ'
        . 'AAAAAAAAAYAAAAAQAAAAEAAAAAAAAAAQAAAAAAAAABAAAAAAAAAAAAAAABAAAABgAAAAHXkotywnA8z+r365/0'
        . '701QSlWouXn8m0UOoshCtNHOYQAAABQAAAABAAI9fQAAAAAAAAD4AAAAAAAqmgAAAAABR6YtAAAAAEArDtxbqU'
        . 'I+CsdkRmV0lFhVt0wyB7fyrmmkM6Fr35wpPcK8WKcXeKTl4BQ+akE14MZtpaea9LMdhXopaW3pJA0E';

    /** A v0 transaction with no optional fields set. */
    private const TRANSACTION_V0_XDR =
        'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAAAAA==';

    public function testXdrTransactionAcceptsExactlyItsDeclaredKeys(): void
    {
        $tx = XdrTransactionEnvelope::fromBase64Xdr(self::TRANSACTION_ENVELOPE_V1_XDR)
            ->getV1()->getTx();
        $jsonValue = $tx->toJsonValue();
        $this->assertSame(
            ['source_account', 'fee', 'seq_num', 'cond', 'memo', 'operations', 'ext'],
            array_keys($jsonValue)
        );
        $this->assertSame($jsonValue, XdrTransaction::fromJsonValue($jsonValue)->toJsonValue());
    }

    public function testXdrTransactionRejectsAnUnknownKey(): void
    {
        $jsonValue = XdrTransactionEnvelope::fromBase64Xdr(self::TRANSACTION_ENVELOPE_V1_XDR)
            ->getV1()->getTx()->toJsonValue();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown field in JSON input for XdrTransaction: 'bogus'");
        XdrTransaction::fromJsonValue($jsonValue + ['bogus' => 1]);
    }

    public function testXdrTransactionV0AcceptsExactlyItsDeclaredKeys(): void
    {
        $jsonValue = XdrTransactionV0::fromBase64Xdr(self::TRANSACTION_V0_XDR)->toJsonValue();
        $this->assertSame(
            ['source_account_ed25519', 'fee', 'seq_num', 'time_bounds', 'memo', 'operations', 'ext'],
            array_keys($jsonValue)
        );
        $this->assertSame($jsonValue, XdrTransactionV0::fromJsonValue($jsonValue)->toJsonValue());
    }

    public function testXdrTransactionV0RejectsAnUnknownKey(): void
    {
        $jsonValue = XdrTransactionV0::fromBase64Xdr(self::TRANSACTION_V0_XDR)->toJsonValue();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown field in JSON input for XdrTransactionV0: 'bogus'");
        XdrTransactionV0::fromJsonValue($jsonValue + ['bogus' => 1]);
    }
}
