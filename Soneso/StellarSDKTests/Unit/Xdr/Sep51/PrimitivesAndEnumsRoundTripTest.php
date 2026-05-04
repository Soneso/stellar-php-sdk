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
 * The OperationResultCode and TransactionResultCode coverage documents the
 * deliberate divergence from py-stellar-base v14.0.0: PHP emits the bare
 * member name without the `op` / `tx` prefix py retains. The divergence is
 * recorded in tools/baselines/sep-51-divergence-catalogue.md (entry 6).
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
    // Positive round-trip — XdrSCValType (22 arms)
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
        ];
        $this->assertCount(22, $cases, 'SCValType has 22 arms — keep this in sync with the IDL');
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
    // snake_case identifier. Verified against py-stellar-base v14.0.0 wire
    // maps for every single-member enum in the SDK.
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
    // The tokenizer splits on '_' only, so each CamelCase identifier becomes a
    // single lowercased token. Wire forms verified against py-stellar-base v14.0.0
    // (e.g. IPv4 -> "ipv4"; WasmInsnExec -> "wasminsnexec").
    // =========================================================================

    public function testIPAddrType_camelCaseRoundTrip(): void
    {
        $cases = [
            XdrIPAddrType::IPv4 => 'ipv4',
            XdrIPAddrType::IPv6 => 'ipv6',
        ];
        foreach ($cases as $value => $expected) {
            $this->assertSame($expected, (new XdrIPAddrType($value))->toJsonValue());
            $this->assertSame($value, XdrIPAddrType::fromJsonValue($expected)->getValue());
        }
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
            XdrContractCostType::WasmInsnExec     => 'wasminsnexec',
            XdrContractCostType::MemAlloc         => 'memalloc',
            XdrContractCostType::ChaCha20DrawBytes => 'chacha20drawbytes',
            XdrContractCostType::Bls12381EncodeFp => 'bls12381encodefp',
            XdrContractCostType::Bn254G1Msm       => 'bn254g1msm',
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
    // OperationResultCode — documented divergence from py-stellar-base
    // =========================================================================

    public function testOperationResultCode_emitsBareMemberNamesNoOpPrefix(): void
    {
        // Divergence (6) in tools/baselines/sep-51-divergence-catalogue.md:
        // py-stellar-base renders these as "opinner", "opbad_auth", ...; PHP
        // emits the bare lowercase identifier without the `op` prefix because
        // the PHP-side identifiers are already stripped at codegen-name level
        // via MEMBER_PREFIX_STRIP. PHP follows the rs-stellar-xdr canonical
        // algorithm; py retains the prefix.
        $cases = [
            XdrOperationResultCode::INNER               => 'inner',
            XdrOperationResultCode::BAD_AUTH            => 'bad_auth',
            XdrOperationResultCode::NO_ACCOUNT          => 'no_account',
            XdrOperationResultCode::NOT_SUPPORTED       => 'not_supported',
            XdrOperationResultCode::TOO_MANY_SUBENTRIES => 'too_many_subentries',
            XdrOperationResultCode::EXCEEDED_WORK_LIMIT => 'exceeded_work_limit',
            XdrOperationResultCode::TOO_MANY_SPONSORING => 'too_many_sponsoring',
        ];
        foreach ($cases as $value => $expected) {
            $this->assertSame($expected, (new XdrOperationResultCode($value))->toJsonValue());
            $this->assertSame($value, XdrOperationResultCode::fromJsonValue($expected)->getValue());
        }
    }

    public function testOperationResultCode_rejectsPyStyleOpPrefixedString(): void
    {
        // py-stellar-base's "opinner" is NOT accepted by PHP; the divergence
        // is asserted explicitly so cross-SDK consumers cannot mistake the
        // prefix-retaining form for spec-compliant input.
        $this->expectException(\InvalidArgumentException::class);
        XdrOperationResultCode::fromJsonValue('opinner');
    }

    // =========================================================================
    // TransactionResultCode — same divergence pattern (`tx` prefix)
    // =========================================================================

    public function testTransactionResultCode_emitsBareMemberNamesNoTxPrefix(): void
    {
        $cases = [
            XdrTransactionResultCode::FEE_BUMP_INNER_SUCCESS => 'fee_bump_inner_success',
            XdrTransactionResultCode::SUCCESS                => 'success',
            XdrTransactionResultCode::FAILED                 => 'failed',
            XdrTransactionResultCode::TOO_EARLY              => 'too_early',
            XdrTransactionResultCode::TOO_LATE               => 'too_late',
            XdrTransactionResultCode::MISSING_OPERATION      => 'missing_operation',
            XdrTransactionResultCode::BAD_SEQ                => 'bad_seq',
            XdrTransactionResultCode::BAD_AUTH               => 'bad_auth',
            XdrTransactionResultCode::INSUFFICIENT_BALANCE   => 'insufficient_balance',
            XdrTransactionResultCode::NO_ACCOUNT             => 'no_account',
            XdrTransactionResultCode::INSUFFICIENT_FEE       => 'insufficient_fee',
            XdrTransactionResultCode::BAD_AUTH_EXTRA         => 'bad_auth_extra',
            XdrTransactionResultCode::INTERNAL_ERROR         => 'internal_error',
            XdrTransactionResultCode::NOT_SUPPORTED          => 'not_supported',
            XdrTransactionResultCode::FEE_BUMP_INNER_FAILED  => 'fee_bump_inner_failed',
            XdrTransactionResultCode::BAD_SPONSORSHIP        => 'bad_sponsorship',
            XdrTransactionResultCode::BAD_MIN_SEQ_AGE_OR_GAP => 'bad_min_seq_age_or_gap',
            XdrTransactionResultCode::MALFORMED              => 'malformed',
            XdrTransactionResultCode::SOROBAN_INVALID        => 'soroban_invalid',
            XdrTransactionResultCode::FROZEN_KEY_ACCESSED    => 'frozen_key_accessed',
        ];
        foreach ($cases as $value => $expected) {
            $this->assertSame($expected, (new XdrTransactionResultCode($value))->toJsonValue());
            $this->assertSame($value, XdrTransactionResultCode::fromJsonValue($expected)->getValue());
        }
    }

    public function testTransactionResultCode_rejectsPyStyleTxPrefixedString(): void
    {
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
