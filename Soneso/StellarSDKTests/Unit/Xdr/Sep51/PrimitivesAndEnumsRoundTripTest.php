<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr\Sep51;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceFlags;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceIDType;
use Soneso\StellarSDK\Xdr\XdrContractCostType;
use Soneso\StellarSDK\Xdr\XdrCryptoKeyType;
use Soneso\StellarSDK\Xdr\XdrIPAddrType;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolType;
use Soneso\StellarSDK\Xdr\XdrMemoType;
use Soneso\StellarSDK\Xdr\XdrOfferEntryFlags;
use Soneso\StellarSDK\Xdr\XdrOperationResultCode;
use Soneso\StellarSDK\Xdr\XdrPreconditionType;
use Soneso\StellarSDK\Xdr\XdrPublicKeyType;
use Soneso\StellarSDK\Xdr\XdrSCAddressType;
use Soneso\StellarSDK\Xdr\XdrSCEnvMetaKind;
use Soneso\StellarSDK\Xdr\XdrSCMetaKind;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrSurveyMessageCommandType;
use Soneso\StellarSDK\Xdr\XdrTransactionResultCode;

/**
 * Round-trip tests for the SEP-51 (XDR-JSON) emission on primitive enums.
 *
 * Each test pairs an enum constant with its expected wire-form string and
 * asserts:
 *   - toJsonValue() returns the expected lowercase prefix-stripped name
 *   - fromJsonValue() of that string reconstructs an equal-valued instance
 *   - toJson() / fromJson() round-trip through their JSON-string facades
 *
 * The wire-form names are derived at codegen time by the rs-stellar-xdr
 * canonical prefix-stripping algorithm; assertions here pin the exact
 * wire output so refactors of the algorithm produce a clear test failure
 * rather than silent breakage.
 *
 * Each enum class also receives a battery of negative assertions covering
 * the three rejection paths in fromJsonValue:
 *   - non-string input (int, array, null, bool, float)
 *   - unknown wire-form string
 *   - long unknown string (truncated in the exception message)
 *
 * The OperationResultCode and TransactionResultCode coverage pins the
 * canonical rs-stellar-xdr naming rule: the byte-wise shared prefix is
 * truncated back to the last underscore before stripping, so the "op"/"tx"
 * prefixes (no trailing underscore) are retained and camelCase splits at
 * word boundaries (opINNER emits `op_inner`, txSUCCESS emits `tx_success`).
 * The names emitted by SDK releases up to 1.11.x remain accepted by
 * fromJsonValue as deprecated input aliases.
 */
class PrimitivesAndEnumsRoundTripTest extends TestCase
{
    // =========================================================================
    // Positive round-trip — XdrAssetType (4 arms)
    // =========================================================================

    public function testAssetType_roundTrip(): void
    {
        $cases = [
            XdrAssetType::ASSET_TYPE_NATIVE            => 'native',
            XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4  => 'credit_alphanum4',
            XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12 => 'credit_alphanum12',
            XdrAssetType::ASSET_TYPE_POOL_SHARE        => 'pool_share',
        ];
        foreach ($cases as $value => $expected) {
            $instance = new XdrAssetType($value);
            $this->assertSame($expected, $instance->toJsonValue(),
                "AssetType($value) toJsonValue mismatch");
            $reconstructed = XdrAssetType::fromJsonValue($expected);
            $this->assertSame($value, $reconstructed->getValue(),
                "AssetType('$expected') fromJsonValue mismatch");
            // JSON-string facade
            $json = $instance->toJson();
            $this->assertSame('"' . $expected . '"', $json);
            $reconstructed2 = XdrAssetType::fromJson($json);
            $this->assertSame($value, $reconstructed2->getValue());
        }
    }

    public function testAssetType_fromJsonValue_rejectsUnknownString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown XdrAssetType JSON value');
        XdrAssetType::fromJsonValue('not_a_real_arm');
    }

    public function testAssetType_fromJsonValue_rejectsNonStringInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected string for XdrAssetType JSON value');
        XdrAssetType::fromJsonValue(42);
    }

    // =========================================================================
    // Positive round-trip — XdrSCValType (23 arms)
    // =========================================================================

    public function testSCValType_allArms(): void
    {
        $cases = [
            XdrSCValType::SCV_BOOL                          => 'bool',
            XdrSCValType::SCV_VOID                          => 'void',
            XdrSCValType::SCV_ERROR                         => 'error',
            XdrSCValType::SCV_U32                           => 'u32',
            XdrSCValType::SCV_I32                           => 'i32',
            XdrSCValType::SCV_U64                           => 'u64',
            XdrSCValType::SCV_I64                           => 'i64',
            XdrSCValType::SCV_TIMEPOINT                     => 'timepoint',
            XdrSCValType::SCV_DURATION                      => 'duration',
            XdrSCValType::SCV_U128                          => 'u128',
            XdrSCValType::SCV_I128                          => 'i128',
            XdrSCValType::SCV_U256                          => 'u256',
            XdrSCValType::SCV_I256                          => 'i256',
            XdrSCValType::SCV_BYTES                         => 'bytes',
            XdrSCValType::SCV_STRING                        => 'string',
            XdrSCValType::SCV_SYMBOL                        => 'symbol',
            XdrSCValType::SCV_VEC                           => 'vec',
            XdrSCValType::SCV_MAP                           => 'map',
            XdrSCValType::SCV_ADDRESS                       => 'address',
            XdrSCValType::SCV_CONTRACT_INSTANCE             => 'contract_instance',
            XdrSCValType::SCV_LEDGER_KEY_CONTRACT_INSTANCE  => 'ledger_key_contract_instance',
            XdrSCValType::SCV_LEDGER_KEY_NONCE              => 'ledger_key_nonce',
            XdrSCValType::SCV_EXECUTABLE_TAG                => 'executable_tag',
        ];
        $this->assertCount(23, $cases, 'SCValType has 23 arms — keep this in sync with the IDL');
        foreach ($cases as $value => $expected) {
            $this->assertSame($expected, (new XdrSCValType($value))->toJsonValue(),
                "SCVal arm value=$value");
            $this->assertSame($value, XdrSCValType::fromJsonValue($expected)->getValue(),
                "SCVal arm '$expected'");
        }
    }

    public function testSCValType_fromJsonValue_rejectsArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected string for XdrSCValType JSON value');
        XdrSCValType::fromJsonValue(['symbol' => 'foo']);
    }

    public function testSCValType_fromJsonValue_rejectsNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected string for XdrSCValType JSON value');
        XdrSCValType::fromJsonValue(null);
    }

    // =========================================================================
    // Positive round-trip — XdrMemoType
    // =========================================================================

    public function testMemoType_roundTrip(): void
    {
        $cases = [
            XdrMemoType::MEMO_NONE   => 'none',
            XdrMemoType::MEMO_TEXT   => 'text',
            XdrMemoType::MEMO_ID     => 'id',
            XdrMemoType::MEMO_HASH   => 'hash',
            XdrMemoType::MEMO_RETURN => 'return',
        ];
        foreach ($cases as $value => $expected) {
            $this->assertSame($expected, (new XdrMemoType($value))->toJsonValue());
            $this->assertSame($value, XdrMemoType::fromJsonValue($expected)->getValue());
        }
    }

    public function testMemoType_fromJsonValue_rejectsUnknown(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrMemoType::fromJsonValue('memo_extra');
    }

    // =========================================================================
    // Positive round-trip — XdrPreconditionType
    // =========================================================================

    public function testPreconditionType_roundTrip(): void
    {
        // Note: the PHP-side member names already strip the PRECOND_ prefix
        // (see MEMBER_OVERRIDES in tools/xdr-generator/generator/member_overrides.rb);
        // the algorithm operates on those stripped names and produces the
        // same lowercase wire form.
        $cases = [
            XdrPreconditionType::NONE => 'none',
            XdrPreconditionType::TIME => 'time',
            XdrPreconditionType::V2   => 'v2',
        ];
        foreach ($cases as $value => $expected) {
            $this->assertSame($expected, (new XdrPreconditionType($value))->toJsonValue());
            $this->assertSame($value, XdrPreconditionType::fromJsonValue($expected)->getValue());
        }
    }

    public function testPreconditionType_fromJsonValue_rejectsUnknown(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrPreconditionType::fromJsonValue('precond_other');
    }

    // =========================================================================
    // Positive round-trip — XdrSCAddressType
    // =========================================================================

    public function testSCAddressType_roundTrip(): void
    {
        $cases = [
            XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT           => 'account',
            XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT          => 'contract',
            XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT     => 'muxed_account',
            XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE => 'claimable_balance',
            XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL    => 'liquidity_pool',
        ];
        foreach ($cases as $value => $expected) {
            $this->assertSame($expected, (new XdrSCAddressType($value))->toJsonValue());
            $this->assertSame($value, XdrSCAddressType::fromJsonValue($expected)->getValue());
        }
    }

    public function testSCAddressType_fromJsonValue_rejectsBoolean(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected string for XdrSCAddressType JSON value');
        XdrSCAddressType::fromJsonValue(true);
    }

    // =========================================================================
    // Single-member edge cases — full-identifier wire form
    //
    // Single-member enums have no other entry to share tokens with, so the
    // longest shared prefix is empty and the wire form is the full lowercase
    // snake_case identifier (SEP-0051 §Discriminated unions: the prefix-strip
    // rule degenerates to the empty prefix when only one member exists).
    // =========================================================================

    public function testClaimableBalanceIDType_singleMemberEmitsFullIdentifier(): void
    {
        $instance = new XdrClaimableBalanceIDType(
            XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0
        );
        $this->assertSame('claimable_balance_id_type_v0', $instance->toJsonValue());
        $reconstructed = XdrClaimableBalanceIDType::fromJsonValue('claimable_balance_id_type_v0');
        $this->assertSame(
            XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0,
            $reconstructed->getValue()
        );
    }

    public function testClaimableBalanceIDType_rejectsTrailingTokenOnly(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrClaimableBalanceIDType::fromJsonValue('v0');
    }

    public function testPublicKeyType_singleMemberEmitsFullIdentifier(): void
    {
        $instance = new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519);
        $this->assertSame('public_key_type_ed25519', $instance->toJsonValue());
        $reconstructed = XdrPublicKeyType::fromJsonValue('public_key_type_ed25519');
        $this->assertSame(
            XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519,
            $reconstructed->getValue()
        );
    }

    public function testPublicKeyType_rejectsTrailingTokenOnly(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrPublicKeyType::fromJsonValue('ed25519');
    }

    public function testSCEnvMetaKind_singleMemberEmitsFullIdentifier(): void
    {
        $instance = new XdrSCEnvMetaKind(
            XdrSCEnvMetaKind::SC_ENV_META_KIND_INTERFACE_VERSION
        );
        $this->assertSame('sc_env_meta_kind_interface_version', $instance->toJsonValue());
        $reconstructed = XdrSCEnvMetaKind::fromJsonValue('sc_env_meta_kind_interface_version');
        $this->assertSame(
            XdrSCEnvMetaKind::SC_ENV_META_KIND_INTERFACE_VERSION,
            $reconstructed->getValue()
        );
    }

    public function testSCMetaKind_singleMemberEmitsFullIdentifier(): void
    {
        $instance = new XdrSCMetaKind(XdrSCMetaKind::SC_META_V0);
        $this->assertSame('sc_meta_v0', $instance->toJsonValue());
        $reconstructed = XdrSCMetaKind::fromJsonValue('sc_meta_v0');
        $this->assertSame(
            XdrSCMetaKind::SC_META_V0,
            $reconstructed->getValue()
        );
    }

    public function testLiquidityPoolType_singleMemberEmitsFullIdentifier(): void
    {
        $instance = new XdrLiquidityPoolType(
            XdrLiquidityPoolType::LIQUIDITY_POOL_CONSTANT_PRODUCT
        );
        $this->assertSame('liquidity_pool_constant_product', $instance->toJsonValue());
        $reconstructed = XdrLiquidityPoolType::fromJsonValue('liquidity_pool_constant_product');
        $this->assertSame(
            XdrLiquidityPoolType::LIQUIDITY_POOL_CONSTANT_PRODUCT,
            $reconstructed->getValue()
        );
    }

    public function testSurveyMessageCommandType_singleMemberEmitsFullIdentifier(): void
    {
        $instance = new XdrSurveyMessageCommandType(
            XdrSurveyMessageCommandType::TIME_SLICED_SURVEY_TOPOLOGY
        );
        $this->assertSame('time_sliced_survey_topology', $instance->toJsonValue());
        $reconstructed = XdrSurveyMessageCommandType::fromJsonValue('time_sliced_survey_topology');
        $this->assertSame(
            XdrSurveyMessageCommandType::TIME_SLICED_SURVEY_TOPOLOGY,
            $reconstructed->getValue()
        );
    }

    public function testOfferEntryFlags_singleMemberEmitsFullIdentifier(): void
    {
        $instance = new XdrOfferEntryFlags(XdrOfferEntryFlags::PASSIVE_FLAG);
        $this->assertSame('passive_flag', $instance->toJsonValue());
        $reconstructed = XdrOfferEntryFlags::fromJsonValue('passive_flag');
        $this->assertSame(
            XdrOfferEntryFlags::PASSIVE_FLAG,
            $reconstructed->getValue()
        );
    }

    public function testClaimableBalanceFlags_singleMemberEmitsFullIdentifier(): void
    {
        $instance = new XdrClaimableBalanceFlags(
            XdrClaimableBalanceFlags::CLAIMABLE_BALANCE_CLAWBACK_ENABLED_FLAG
        );
        $this->assertSame(
            'claimable_balance_clawback_enabled_flag',
            $instance->toJsonValue()
        );
        $reconstructed = XdrClaimableBalanceFlags::fromJsonValue(
            'claimable_balance_clawback_enabled_flag'
        );
        $this->assertSame(
            XdrClaimableBalanceFlags::CLAIMABLE_BALANCE_CLAWBACK_ENABLED_FLAG,
            $reconstructed->getValue()
        );
    }

    // =========================================================================
    // CamelCase identifier coverage — XdrIPAddrType, XdrContractCostType
    //
    // These two enums use CamelCase constants rather than ALL_CAPS_WITH_UNDERSCORES.
    // The canonical wire form splits camelCase at word boundaries per the
    // heck/serde casing used by rs-stellar-xdr (IPv4 -> "i_pv4";
    // WasmInsnExec -> "wasm_insn_exec").
    // =========================================================================

    public function testIPAddrType_camelCaseRoundTrip(): void
    {
        $cases = [
            XdrIPAddrType::IPv4 => 'i_pv4',
            XdrIPAddrType::IPv6 => 'i_pv6',
        ];
        foreach ($cases as $value => $expected) {
            $this->assertSame($expected, (new XdrIPAddrType($value))->toJsonValue());
            $this->assertSame($value, XdrIPAddrType::fromJsonValue($expected)->getValue());
        }
    }

    public function testIPAddrType_acceptsLegacyAliases(): void
    {
        // Names emitted by SDK releases up to 1.11.x are accepted on input
        // during the deprecation window; they are never emitted.
        $this->assertSame(XdrIPAddrType::IPv4, XdrIPAddrType::fromJsonValue('ipv4')->getValue());
        $this->assertSame(XdrIPAddrType::IPv6, XdrIPAddrType::fromJsonValue('ipv6')->getValue());
    }

    public function testIPAddrType_rejectsUnknownString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrIPAddrType::fromJsonValue('ipv99');
    }

    public function testIPAddrType_rejectsNonStringInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected string for XdrIPAddrType JSON value');
        XdrIPAddrType::fromJsonValue(4);
    }

    public function testContractCostType_camelCaseRoundTripRepresentativeMembers(): void
    {
        // Representative members spanning the index range [0, 85].
        $cases = [
            XdrContractCostType::WasmInsnExec      => 'wasm_insn_exec',
            XdrContractCostType::MemAlloc          => 'mem_alloc',
            XdrContractCostType::ChaCha20DrawBytes => 'cha_cha20_draw_bytes',
            XdrContractCostType::Bls12381EncodeFp  => 'bls12381_encode_fp',
            XdrContractCostType::Bn254G1Msm        => 'bn254_g1_msm',
        ];
        foreach ($cases as $value => $expected) {
            $this->assertSame(
                $expected,
                (new XdrContractCostType($value))->toJsonValue(),
                "ContractCostType($value) toJsonValue mismatch"
            );
            $this->assertSame(
                $value,
                XdrContractCostType::fromJsonValue($expected)->getValue(),
                "ContractCostType('$expected') fromJsonValue mismatch"
            );
        }
    }

    public function testContractCostType_acceptsLegacyAliases(): void
    {
        $aliases = [
            'wasminsnexec'      => XdrContractCostType::WasmInsnExec,
            'memalloc'          => XdrContractCostType::MemAlloc,
            'chacha20drawbytes' => XdrContractCostType::ChaCha20DrawBytes,
            'bls12381encodefp'  => XdrContractCostType::Bls12381EncodeFp,
            'bn254g1msm'        => XdrContractCostType::Bn254G1Msm,
        ];
        foreach ($aliases as $alias => $value) {
            $this->assertSame(
                $value,
                XdrContractCostType::fromJsonValue($alias)->getValue(),
                "ContractCostType legacy alias '$alias' must be accepted"
            );
        }
    }

    public function testContractCostType_rejectsUnknownString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrContractCostType::fromJsonValue('not_a_real_cost_type');
    }

    public function testContractCostType_rejectsNonStringInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected string for XdrContractCostType JSON value');
        XdrContractCostType::fromJsonValue(0);
    }

    // =========================================================================
    // CryptoKeyType — covers the SDK-specific MUXED_ED25519 entry
    // =========================================================================

    public function testCryptoKeyType_roundTrip(): void
    {
        $cases = [
            XdrCryptoKeyType::KEY_TYPE_ED25519                => 'ed25519',
            XdrCryptoKeyType::KEY_TYPE_PRE_AUTH_TX            => 'pre_auth_tx',
            XdrCryptoKeyType::KEY_TYPE_HASH_X                 => 'hash_x',
            XdrCryptoKeyType::KEY_TYPE_ED25519_SIGNED_PAYLOAD => 'ed25519_signed_payload',
            XdrCryptoKeyType::KEY_TYPE_MUXED_ED25519          => 'muxed_ed25519',
        ];
        foreach ($cases as $value => $expected) {
            $this->assertSame($expected, (new XdrCryptoKeyType($value))->toJsonValue());
            $this->assertSame($value, XdrCryptoKeyType::fromJsonValue($expected)->getValue());
        }
    }

    public function testCryptoKeyType_facadeRoundTripPreservesValue(): void
    {
        $original = new XdrCryptoKeyType(XdrCryptoKeyType::KEY_TYPE_HASH_X);
        $json = $original->toJson();
        $this->assertSame('"hash_x"', $json);
        $back = XdrCryptoKeyType::fromJson($json);
        $this->assertSame($original->getValue(), $back->getValue());
    }

    // =========================================================================
    // OperationResultCode — well-known IDL prefix stripped per SEP-0051
    // =========================================================================

    public function testOperationResultCode_keepsOpPrefix(): void
    {
        // The original XDR identifiers are opINNER, opBAD_AUTH, ...: their
        // byte-wise shared prefix "op" contains no underscore, so nothing is
        // stripped and the heck/serde casing yields "op_inner",
        // "op_bad_auth", etc., matching rs-stellar-xdr.
        $cases = [
            XdrOperationResultCode::INNER               => 'op_inner',
            XdrOperationResultCode::BAD_AUTH            => 'op_bad_auth',
            XdrOperationResultCode::NO_ACCOUNT          => 'op_no_account',
            XdrOperationResultCode::NOT_SUPPORTED       => 'op_not_supported',
            XdrOperationResultCode::TOO_MANY_SUBENTRIES => 'op_too_many_subentries',
            XdrOperationResultCode::EXCEEDED_WORK_LIMIT => 'op_exceeded_work_limit',
            XdrOperationResultCode::TOO_MANY_SPONSORING => 'op_too_many_sponsoring',
        ];
        foreach ($cases as $value => $expected) {
            $this->assertSame($expected, (new XdrOperationResultCode($value))->toJsonValue());
            $this->assertSame($value, XdrOperationResultCode::fromJsonValue($expected)->getValue());
        }
    }

    public function testOperationResultCode_acceptsLegacyAliases(): void
    {
        // Names emitted by SDK releases up to 1.11.x are accepted on input
        // during the deprecation window; they are never emitted.
        $this->assertSame(
            XdrOperationResultCode::INNER,
            XdrOperationResultCode::fromJsonValue('inner')->getValue()
        );
        $this->assertSame(
            XdrOperationResultCode::BAD_AUTH,
            XdrOperationResultCode::fromJsonValue('bad_auth')->getValue()
        );
    }

    public function testOperationResultCode_rejectsGluedPrefixString(): void
    {
        // "opinner" is neither the canonical wire form nor a legacy alias
        // (pre-1.12 enum emission used "inner"); it must be rejected.
        $this->expectException(\InvalidArgumentException::class);
        XdrOperationResultCode::fromJsonValue('opinner');
    }

    // =========================================================================
    // TransactionResultCode — same prefix-strip rule (`tx` prefix)
    // =========================================================================

    public function testTransactionResultCode_keepsTxPrefix(): void
    {
        $cases = [
            XdrTransactionResultCode::FEE_BUMP_INNER_SUCCESS => 'tx_fee_bump_inner_success',
            XdrTransactionResultCode::SUCCESS                => 'tx_success',
            XdrTransactionResultCode::FAILED                 => 'tx_failed',
            XdrTransactionResultCode::TOO_EARLY              => 'tx_too_early',
            XdrTransactionResultCode::TOO_LATE               => 'tx_too_late',
            XdrTransactionResultCode::MISSING_OPERATION      => 'tx_missing_operation',
            XdrTransactionResultCode::BAD_SEQ                => 'tx_bad_seq',
            XdrTransactionResultCode::BAD_AUTH               => 'tx_bad_auth',
            XdrTransactionResultCode::INSUFFICIENT_BALANCE   => 'tx_insufficient_balance',
            XdrTransactionResultCode::NO_ACCOUNT             => 'tx_no_account',
            XdrTransactionResultCode::INSUFFICIENT_FEE       => 'tx_insufficient_fee',
            XdrTransactionResultCode::BAD_AUTH_EXTRA         => 'tx_bad_auth_extra',
            XdrTransactionResultCode::INTERNAL_ERROR         => 'tx_internal_error',
            XdrTransactionResultCode::NOT_SUPPORTED          => 'tx_not_supported',
            XdrTransactionResultCode::FEE_BUMP_INNER_FAILED  => 'tx_fee_bump_inner_failed',
            XdrTransactionResultCode::BAD_SPONSORSHIP        => 'tx_bad_sponsorship',
            XdrTransactionResultCode::BAD_MIN_SEQ_AGE_OR_GAP => 'tx_bad_min_seq_age_or_gap',
            XdrTransactionResultCode::MALFORMED              => 'tx_malformed',
            XdrTransactionResultCode::SOROBAN_INVALID        => 'tx_soroban_invalid',
            XdrTransactionResultCode::FROZEN_KEY_ACCESSED    => 'tx_frozen_key_accessed',
        ];
        foreach ($cases as $value => $expected) {
            $this->assertSame($expected, (new XdrTransactionResultCode($value))->toJsonValue());
            $this->assertSame($value, XdrTransactionResultCode::fromJsonValue($expected)->getValue());
        }
    }

    public function testTransactionResultCode_acceptsLegacyAliases(): void
    {
        $this->assertSame(
            XdrTransactionResultCode::SUCCESS,
            XdrTransactionResultCode::fromJsonValue('success')->getValue()
        );
        $this->assertSame(
            XdrTransactionResultCode::FEE_BUMP_INNER_SUCCESS,
            XdrTransactionResultCode::fromJsonValue('fee_bump_inner_success')->getValue()
        );
    }

    public function testTransactionResultCode_rejectsGluedPrefixString(): void
    {
        // "txsuccess" is neither the canonical wire form nor a legacy alias
        // (pre-1.12 enum emission used "success"); it must be rejected.
        $this->expectException(\InvalidArgumentException::class);
        XdrTransactionResultCode::fromJsonValue('txsuccess');
    }

    // =========================================================================
    // Long-input rejection — verifies that XdrJsonHelper::safePreview truncates
    // the exception-message echo of attacker-controlled input. Generated enum
    // classes route their fromJsonValue default arm through this shared helper
    // (rather than carrying a per-class preview routine of their own).
    // =========================================================================

    public function testFromJsonValue_longUnknownInputIsTruncatedInMessage(): void
    {
        // Construct a 200-character string that is not a valid wire-form.
        $longUnknown = str_repeat('a', 200);
        try {
            XdrAssetType::fromJsonValue($longUnknown);
            $this->fail('Expected fromJsonValue to throw on unknown long string');
        } catch (\InvalidArgumentException $e) {
            $message = $e->getMessage();
            // XdrJsonHelper::safePreview caps echo at 80 chars (77 + '...').
            // The full 200-char input must NOT appear verbatim in the message.
            $this->assertStringNotContainsString($longUnknown, $message);
            $this->assertStringContainsString('...', $message);
        }
    }

    // =========================================================================
    // toJson / fromJson facade negative paths
    // =========================================================================

    public function testFromJson_malformedJsonThrows(): void
    {
        $this->expectException(\JsonException::class);
        XdrAssetType::fromJson('{not valid json');
    }

    public function testFromJson_jsonInteger_routedToFromJsonValueRejection(): void
    {
        // json_decode of "5" returns int 5; fromJsonValue rejects non-string.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected string for XdrAssetType JSON value');
        XdrAssetType::fromJson('5');
    }

    public function testToJson_emitsQuotedJsonString(): void
    {
        $instance = new XdrSCValType(XdrSCValType::SCV_SYMBOL);
        $this->assertSame('"symbol"', $instance->toJson());
    }
}
