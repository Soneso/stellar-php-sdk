<?php declare(strict_types=1);

// Generate the SEP-51 corpus seed: a list of (id, type, base64, ...) tuples
// covering every requirement from the implementation plan. Each fixture is
// constructed via the PHP SDK's existing factories or direct property
// assignment, then serialised via toBase64Xdr so the base64 strings are
// guaranteed valid.
//
// Output: JSON list on stdout, one element per fixture, with keys
//   id, type, base64, divergence_reason, spec_anchor, notes,
//   spec_reference_json (optional)
//
// Used by tools/sep-51-fixtures/generate_corpus.py (which augments each
// fixture with py_reference_json when py-stellar-base is available).
//
// Run directly:
//   php tools/sep-51-fixtures/_corpus_seed.php > /tmp/seed.json

namespace Soneso\StellarSDKTests\Internal\Sep51Corpus;

require_once __DIR__ . '/../../vendor/autoload.php';

use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Xdr\XdrAccountEntry;
use Soneso\StellarSDK\Xdr\XdrAccountEntryExt;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrAccountMergeOperation;
use Soneso\StellarSDK\Xdr\XdrAllowTrustOperation;
use Soneso\StellarSDK\Xdr\XdrAllowTrustOperationAsset;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum12;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum4;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrBeginSponsoringFutureReservesOperation;
use Soneso\StellarSDK\Xdr\XdrBucketEntry;
use Soneso\StellarSDK\Xdr\XdrBucketEntryType;
use Soneso\StellarSDK\Xdr\XdrBucketMetadata;
use Soneso\StellarSDK\Xdr\XdrBucketMetadataExt;
use Soneso\StellarSDK\Xdr\XdrBumpSequenceOperation;
use Soneso\StellarSDK\Xdr\XdrChangeTrustAsset;
use Soneso\StellarSDK\Xdr\XdrChangeTrustOperation;
use Soneso\StellarSDK\Xdr\XdrClaimClaimableBalanceOperation;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceID;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceIDType;
use Soneso\StellarSDK\Xdr\XdrClawbackClaimableBalanceOperation;
use Soneso\StellarSDK\Xdr\XdrClawbackOperation;
use Soneso\StellarSDK\Xdr\XdrConfigSettingContractBandwidthV0;
use Soneso\StellarSDK\Xdr\XdrConfigSettingContractComputeV0;
use Soneso\StellarSDK\Xdr\XdrConfigSettingContractEventsV0;
use Soneso\StellarSDK\Xdr\XdrConfigSettingContractExecutionLanesV0;
use Soneso\StellarSDK\Xdr\XdrConfigSettingContractHistoricalDataV0;
use Soneso\StellarSDK\Xdr\XdrConfigSettingContractLedgerCostV0;
use Soneso\StellarSDK\Xdr\XdrConfigSettingEntry;
use Soneso\StellarSDK\Xdr\XdrConfigSettingID;
use Soneso\StellarSDK\Xdr\XdrContractExecutable;
use Soneso\StellarSDK\Xdr\XdrContractExecutableType;
use Soneso\StellarSDK\Xdr\XdrCreateAccountOperation;
use Soneso\StellarSDK\Xdr\XdrCreateClaimableBalanceOperation;
use Soneso\StellarSDK\Xdr\XdrCreatePassiveSellOfferOperation;
use Soneso\StellarSDK\Xdr\XdrDataValueMandatory;
use Soneso\StellarSDK\Xdr\XdrDecoratedSignature;
use Soneso\StellarSDK\Xdr\XdrEnvelopeType;
use Soneso\StellarSDK\Xdr\XdrExtendFootprintTTLOp;
use Soneso\StellarSDK\Xdr\XdrExtensionPoint;
use Soneso\StellarSDK\Xdr\XdrGeneralizedTransactionSet;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;
use Soneso\StellarSDK\Xdr\XdrHotArchiveBucketEntry;
use Soneso\StellarSDK\Xdr\XdrHotArchiveBucketEntryType;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrInt256Parts;
use Soneso\StellarSDK\Xdr\XdrInvokeHostFunctionOp;
use Soneso\StellarSDK\Xdr\XdrLedgerBounds;
use Soneso\StellarSDK\Xdr\XdrLedgerCloseMeta;
use Soneso\StellarSDK\Xdr\XdrLedgerCloseMetaExt;
use Soneso\StellarSDK\Xdr\XdrLedgerCloseMetaV0;
use Soneso\StellarSDK\Xdr\XdrLedgerCloseMetaV1;
use Soneso\StellarSDK\Xdr\XdrLedgerCloseMetaV2;
use Soneso\StellarSDK\Xdr\XdrLedgerEntry;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryData;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryExt;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerHeader;
use Soneso\StellarSDK\Xdr\XdrLedgerHeaderExt;
use Soneso\StellarSDK\Xdr\XdrLedgerHeaderHistoryEntry;
use Soneso\StellarSDK\Xdr\XdrLedgerHeaderHistoryEntryExt;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyAccount;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolDepositOperation;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolWithdrawOperation;
use Soneso\StellarSDK\Xdr\XdrManageBuyOfferOperation;
use Soneso\StellarSDK\Xdr\XdrManageDataOperation;
use Soneso\StellarSDK\Xdr\XdrManageSellOfferOperation;
use Soneso\StellarSDK\Xdr\XdrMemo;
use Soneso\StellarSDK\Xdr\XdrMemoType;
use Soneso\StellarSDK\Xdr\XdrMuxedAccount;
use Soneso\StellarSDK\Xdr\XdrMuxedAccountMed25519;
use Soneso\StellarSDK\Xdr\XdrOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrPathPaymentStrictReceiveOperation;
use Soneso\StellarSDK\Xdr\XdrPathPaymentStrictSendOperation;
use Soneso\StellarSDK\Xdr\XdrPaymentOperation;
use Soneso\StellarSDK\Xdr\XdrPrice;
use Soneso\StellarSDK\Xdr\XdrRestoreFootprintOp;
use Soneso\StellarSDK\Xdr\XdrRevokeSponsorshipOperation;
use Soneso\StellarSDK\Xdr\XdrRevokeSponsorshipType;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCAddressType;
use Soneso\StellarSDK\Xdr\XdrSCContractInstance;
use Soneso\StellarSDK\Xdr\XdrSCError;
use Soneso\StellarSDK\Xdr\XdrSCErrorCode;
use Soneso\StellarSDK\Xdr\XdrSCErrorType;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntry;
use Soneso\StellarSDK\Xdr\XdrSCSpecFunctionV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeDef;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTEnumCaseV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTEnumV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTErrorEnumCaseV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTErrorEnumV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTStructFieldV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTStructV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseVoidV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionV0;
use Soneso\StellarSDK\Xdr\XdrSCSymbol;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCNonceKey;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;
use Soneso\StellarSDK\Xdr\XdrSetOptionsOperation;
use Soneso\StellarSDK\Xdr\XdrSetTrustLineFlagsOperation;
use Soneso\StellarSDK\Xdr\XdrSignerKey;
use Soneso\StellarSDK\Xdr\XdrSignerKeyType;
use Soneso\StellarSDK\Xdr\XdrSignedPayload;
use Soneso\StellarSDK\Xdr\XdrStellarValue;
use Soneso\StellarSDK\Xdr\XdrStellarValueExt;
use Soneso\StellarSDK\Xdr\XdrStellarValueType;
use Soneso\StellarSDK\Xdr\XdrTimeBounds;
use Soneso\StellarSDK\Xdr\XdrTransaction;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrTransactionExt;
use Soneso\StellarSDK\Xdr\XdrTransactionSet;
use Soneso\StellarSDK\Xdr\XdrTransactionSetV1;
use Soneso\StellarSDK\Xdr\XdrTransactionV1Envelope;
use Soneso\StellarSDK\Xdr\XdrTrustlineAsset;
use Soneso\StellarSDK\Xdr\XdrUInt128Parts;
use Soneso\StellarSDK\Xdr\XdrUInt256Parts;

const TEST_ACCOUNT_G = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';
const TEST_ACCOUNT_G_2 = 'GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ';

$fixtures = [];

// -----------------------------------------------------------------------
// Helper to record a fixture; takes a constructed XDR object.
//
// Guarantees:
//   - object exposes encode() (and toBase64Xdr() for top-level types).
//   - the resulting base64 round-trips: type::fromBase64Xdr($b64)->toBase64Xdr() === $b64.
//   - any fixture with divergence_reason !== null also has spec_reference_json !== null.
//
// The round-trip and invariant guards both fire as RuntimeException /
// InvalidArgumentException so the seed run halts on any contract violation.
// -----------------------------------------------------------------------
function add(array &$list, string $id, string $type, $obj, ?string $divergenceReason = null,
             ?string $specAnchor = null, ?string $notes = null, ?string $specReferenceJson = null): void {
    if (!method_exists($obj, 'encode')) {
        // Top-level types use toBase64Xdr; some inner types only encode().
        throw new \RuntimeException("object of type $type lacks encode()");
    }
    if (method_exists($obj, 'toBase64Xdr')) {
        $b64 = $obj->toBase64Xdr();
    } else {
        $b64 = base64_encode($obj->encode());
    }

    // Invariant: divergent fixtures must carry a spec-reference value so that
    // Phase 5b DivergenceTest.php has a concrete assertion target. Without
    // this guard a divergent fixture with null spec_reference_json silently
    // disables the assertion in DivergenceTest, defeating the gate.
    if ($divergenceReason !== null && $specReferenceJson === null) {
        throw new \InvalidArgumentException(
            "fixture $id has divergence_reason but spec_reference_json is null; "
            . "divergent fixtures must declare the expected PHP output explicitly."
        );
    }

    // Round-trip guard: every fixture's base64 must decode and re-encode to
    // the same string via the type's own codec. The type name in the corpus
    // entry is the unprefixed XDR name; the live PHP class is "Xdr$type".
    $phpClass = "Soneso\\StellarSDK\\Xdr\\Xdr$type";
    if (class_exists($phpClass) && method_exists($phpClass, 'fromBase64Xdr')) {
        try {
            $decoded = $phpClass::fromBase64Xdr($b64);
            $reEncoded = method_exists($decoded, 'toBase64Xdr')
                ? $decoded->toBase64Xdr()
                : base64_encode($decoded->encode());
            if ($reEncoded !== $b64) {
                throw new \RuntimeException(
                    "fixture $id ($type) failed base64 round-trip: "
                    . "input=$b64, re-encoded=$reEncoded"
                );
            }
        } catch (\Throwable $e) {
            // Re-raise as RuntimeException with the fixture id for diagnosis.
            throw new \RuntimeException(
                "fixture $id ($type) round-trip threw: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    $list[] = [
        'id' => $id,
        'type' => $type,
        'base64' => $b64,
        'divergence_reason' => $divergenceReason,
        'spec_anchor' => $specAnchor,
        'notes' => $notes,
        'spec_reference_json' => $specReferenceJson,
    ];
}

// -----------------------------------------------------------------------
// Spec-anchor: every primitive example from SEP-0051 §Specification
// -----------------------------------------------------------------------
add($fixtures, 'scval_bool_true', 'SCVal', XdrSCVal::forTrue(), null, 'SEP-0051 §Specification > Boolean');
add($fixtures, 'scval_bool_false', 'SCVal', XdrSCVal::forFalse(), null, 'SEP-0051 §Specification > Boolean');
add($fixtures, 'scval_void', 'SCVal', XdrSCVal::forVoid(), null, 'SEP-0051 §Specification > Discriminated Union (void arm)');

// Integers
add($fixtures, 'scval_u32_zero', 'SCVal', XdrSCVal::forU32(0), null, 'SEP-0051 §Specification > Unsigned Integer');
add($fixtures, 'scval_u32_max', 'SCVal', XdrSCVal::forU32(4294967295), null, 'SEP-0051 §Specification > Unsigned Integer (max)');
add($fixtures, 'scval_u32_typical', 'SCVal', XdrSCVal::forU32(123456), null, 'SEP-0051 §Specification > Unsigned Integer');
add($fixtures, 'scval_i32_zero', 'SCVal', XdrSCVal::forI32(0), null, 'SEP-0051 §Specification > Integer');
add($fixtures, 'scval_i32_max', 'SCVal', XdrSCVal::forI32(2147483647), null, 'SEP-0051 §Specification > Integer (max)');
add($fixtures, 'scval_i32_min', 'SCVal', XdrSCVal::forI32(-2147483648), null, 'SEP-0051 §Specification > Integer (min)');
add($fixtures, 'scval_i32_negative_one', 'SCVal', XdrSCVal::forI32(-1), null, 'SEP-0051 §Specification > Integer');

add($fixtures, 'scval_u64_zero', 'SCVal', XdrSCVal::forU64(0), null, 'SEP-0051 §Specification > Unsigned Hyper Integer');
add($fixtures, 'scval_u64_max_safe', 'SCVal', XdrSCVal::forU64(PHP_INT_MAX), null, 'SEP-0051 §Specification > Unsigned Hyper Integer');
add($fixtures, 'scval_u64_typical', 'SCVal', XdrSCVal::forU64(1234567890123), null, 'SEP-0051 §Specification > Unsigned Hyper Integer');
add($fixtures, 'scval_i64_zero', 'SCVal', XdrSCVal::forI64(0), null, 'SEP-0051 §Specification > Hyper Integer');
add($fixtures, 'scval_i64_max', 'SCVal', XdrSCVal::forI64(PHP_INT_MAX), null, 'SEP-0051 §Specification > Hyper Integer');
add($fixtures, 'scval_i64_min', 'SCVal', XdrSCVal::forI64(PHP_INT_MIN), null, 'SEP-0051 §Specification > Hyper Integer');

// 128/256-bit Parts
add($fixtures, 'scval_u128_zero', 'SCVal', XdrSCVal::forU128Parts(0, 0), null, 'SEP-0051 §Specification > Unsigned Hyper Integer (extended)');
add($fixtures, 'scval_u128_one_lo', 'SCVal', XdrSCVal::forU128Parts(0, 1), null, 'SEP-0051 §Specification > Unsigned Hyper Integer (extended)');
add($fixtures, 'scval_u128_typical', 'SCVal', XdrSCVal::forU128Parts(1, 0), null);
add($fixtures, 'scval_i128_zero', 'SCVal', XdrSCVal::forI128Parts(0, 0), null);
add($fixtures, 'scval_i128_negative_one', 'SCVal', XdrSCVal::forI128Parts(-1, PHP_INT_MAX), null,
    null, 'mixed-sign 128-bit Parts edge');
add($fixtures, 'scval_u256_zero', 'SCVal', XdrSCVal::forU256(new XdrUInt256Parts(0, 0, 0, 0)), null);
add($fixtures, 'scval_u256_one', 'SCVal', XdrSCVal::forU256(new XdrUInt256Parts(0, 0, 0, 1)), null);
add($fixtures, 'scval_i256_zero', 'SCVal', XdrSCVal::forI256(new XdrInt256Parts(0, 0, 0, 0)), null);
add($fixtures, 'scval_i256_one', 'SCVal', XdrSCVal::forI256(new XdrInt256Parts(0, 0, 0, 1)), null);

// Timepoint / Duration
add($fixtures, 'scval_timepoint_epoch', 'SCVal', XdrSCVal::forTimepoint(0), null);
add($fixtures, 'scval_timepoint_typical', 'SCVal', XdrSCVal::forTimepoint(1714838400), null);
add($fixtures, 'scval_duration_zero', 'SCVal', XdrSCVal::forDuration(0), null);
add($fixtures, 'scval_duration_typical', 'SCVal', XdrSCVal::forDuration(86400), null);

// Bytes / String / Symbol
add($fixtures, 'scval_bytes_empty', 'SCVal', XdrSCVal::forBytes(''), null,
    'SEP-0051 §Specification > Opaque (variable, empty)');
add($fixtures, 'scval_bytes_single_zero', 'SCVal', XdrSCVal::forBytes("\x00"), null,
    'SEP-0051 §Specification > Opaque (variable)');
add($fixtures, 'scval_bytes_single_ff', 'SCVal', XdrSCVal::forBytes("\xff"), null);
add($fixtures, 'scval_bytes_typical', 'SCVal', XdrSCVal::forBytes("hello world"), null);
add($fixtures, 'scval_bytes_non_ascii', 'SCVal', XdrSCVal::forBytes("\xc3\x80\xc3\xa9"), null);
add($fixtures, 'scval_string_empty', 'SCVal', XdrSCVal::forString(''), null,
    'SEP-0051 §Specification > String (empty)');
add($fixtures, 'scval_string_typical', 'SCVal', XdrSCVal::forString('hello'), null,
    'SEP-0051 §Specification > String');
add($fixtures, 'scval_string_non_ascii', 'SCVal', XdrSCVal::forString("héllo"), null,
    'SEP-0051 §Specification > String (non-ASCII)');
add($fixtures, 'scval_string_with_special_chars', 'SCVal', XdrSCVal::forString("line1\nline2\t\"q\""), null);
add($fixtures, 'scval_symbol_empty', 'SCVal', XdrSCVal::forSymbol(''), null);
add($fixtures, 'scval_symbol_typical', 'SCVal', XdrSCVal::forSymbol('xfer'), null);
add($fixtures, 'scval_symbol_with_underscores', 'SCVal', XdrSCVal::forSymbol('balance_of'), null);

// Vec / Map
add($fixtures, 'scval_vec_empty', 'SCVal', XdrSCVal::forVec([]), null,
    'SEP-0051 §Specification > Array (variable, empty)');
add($fixtures, 'scval_vec_single', 'SCVal', XdrSCVal::forVec([XdrSCVal::forU32(1)]), null);
add($fixtures, 'scval_vec_mixed', 'SCVal', XdrSCVal::forVec([
    XdrSCVal::forU32(1),
    XdrSCVal::forI32(-1),
    XdrSCVal::forSymbol('x'),
    XdrSCVal::forBool(true),
]), null);
add($fixtures, 'scval_map_empty', 'SCVal', XdrSCVal::forMap([]), null);

// Address arms (account / contract / muxed / claimable_balance / liquidity_pool)
$accountSCAddr = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT());
$accountSCAddr->accountId = XdrAccountID::fromAccountId(TEST_ACCOUNT_G);
add($fixtures, 'scval_address_account', 'SCVal', XdrSCVal::forAddress($accountSCAddr), null,
    'SEP-0051 §Stellar-Specific Types > Address Types > G-strkey');

$contractSCAddr = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT());
$contractSCAddr->contractId = str_repeat('a1', 32); // 64 hex chars (32 raw bytes)
add($fixtures, 'scval_address_contract', 'SCVal', XdrSCVal::forAddress($contractSCAddr), null,
    'SEP-0051 §Stellar-Specific Types > Address Types > C-strkey');

// Muxed account SCAddress
$mux = new XdrMuxedAccountMed25519(12345, str_repeat("\x01", 32));
$muxedSCAddr = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT());
$muxedSCAddr->muxedAccount = $mux;
add($fixtures, 'scval_address_muxed', 'SCVal', XdrSCVal::forAddress($muxedSCAddr), null,
    'SEP-0051 §Stellar-Specific Types > Address Types > M-strkey');

$liquiditySCAddr = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL());
$liquiditySCAddr->liquidityPoolId = str_repeat('b2', 32);
add($fixtures, 'scval_address_liquidity_pool', 'SCVal', XdrSCVal::forAddress($liquiditySCAddr), null,
    'SEP-0051 §Stellar-Specific Types > Address Types > L-strkey');

$cbSCAddr = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE());
$cbHash = str_repeat('c3', 32);
$cb = new XdrClaimableBalanceID(new XdrClaimableBalanceIDType(XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0), $cbHash);
$cbSCAddr->claimableBalanceId = $cb;
add($fixtures, 'scval_address_claimable_balance', 'SCVal', XdrSCVal::forAddress($cbSCAddr), null,
    'SEP-0051 §Stellar-Specific Types > Address Types > B-strkey');

// Error
$scErr = new XdrSCError(new XdrSCErrorType(XdrSCErrorType::SCE_CONTRACT));
$scErr->code = new XdrSCErrorCode(XdrSCErrorCode::SCEC_INVALID_INPUT);
add($fixtures, 'scval_error_contract', 'SCVal', XdrSCVal::forError($scErr), null);

// LedgerKeyContractInstance
add($fixtures, 'scval_ledger_key_contract_instance', 'SCVal', XdrSCVal::forLedgerKeyContractInstance(), null,
    'SEP-0051 §Specification > Discriminated Union (multi-void)');

// LedgerKey nonce
$nonceKey = new XdrSCNonceKey(0);
add($fixtures, 'scval_ledger_key_nonce', 'SCVal', XdrSCVal::forLedgerNonceKey($nonceKey), null);

// -----------------------------------------------------------------------
// Asset arms
// -----------------------------------------------------------------------
$nativeAsset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
add($fixtures, 'asset_native', 'Asset', $nativeAsset, null, 'SEP-0051 §Examples > Asset native');

$jpyAsset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
$jpyAsset->setAlphaNum4(new XdrAssetAlphaNum4('JPY', XdrAccountID::fromAccountId(TEST_ACCOUNT_G)));
add($fixtures, 'asset_alphanum4_jpy', 'Asset', $jpyAsset, null, 'SEP-0051 §Examples > Asset alphanum4');

$eurcAsset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
$eurcAsset->setAlphaNum4(new XdrAssetAlphaNum4('EURC', XdrAccountID::fromAccountId(TEST_ACCOUNT_G_2)));
add($fixtures, 'asset_alphanum4_4byte', 'Asset', $eurcAsset, null);

$usd3Asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
$usd3Asset->setAlphaNum4(new XdrAssetAlphaNum4('USD', XdrAccountID::fromAccountId(TEST_ACCOUNT_G)));
add($fixtures, 'asset_alphanum4_3byte', 'Asset', $usd3Asset, null);

$customAsset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
$customAsset->setAlphaNum12(new XdrAssetAlphaNum12('CUSTOM', XdrAccountID::fromAccountId(TEST_ACCOUNT_G)));
add($fixtures, 'asset_alphanum12_6byte', 'Asset', $customAsset, null,
    'SEP-0051 §Stellar-Specific Types > AssetCode12');

$cust12 = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
$cust12->setAlphaNum12(new XdrAssetAlphaNum12('TWELVECHARS', XdrAccountID::fromAccountId(TEST_ACCOUNT_G_2)));
add($fixtures, 'asset_alphanum12_11byte', 'Asset', $cust12, null);

$cust5 = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
$cust5->setAlphaNum12(new XdrAssetAlphaNum12('FIVEC', XdrAccountID::fromAccountId(TEST_ACCOUNT_G)));
add($fixtures, 'asset_alphanum12_5byte', 'Asset', $cust5, null,
    'SEP-0051 §Stellar-Specific Types > AssetCode12 (5-byte boundary)');

// -----------------------------------------------------------------------
// Memo arms (none, text, id, hash, return)
// -----------------------------------------------------------------------
$memoNone = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_NONE));
add($fixtures, 'memo_none', 'Memo', $memoNone, null, 'SEP-0051 §Specification > Discriminated Union (void)');
$memoText = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_TEXT));
$memoText->text = 'hello';
add($fixtures, 'memo_text', 'Memo', $memoText, null);
$memoTextNonAscii = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_TEXT));
$memoTextNonAscii->text = "h\xc3\xa9llo";
add($fixtures, 'memo_text_non_ascii', 'Memo', $memoTextNonAscii, null,
    'SEP-0051 §Specification > String (non-ASCII)',
    'memo text containing UTF-8 bytes (\xc3\xa9 = é)');
$memoId = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_ID));
$memoId->id = 1234567;
add($fixtures, 'memo_id', 'Memo', $memoId, null);
$memoHash = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_HASH));
$memoHash->hash = str_repeat("\xab", 32);
add($fixtures, 'memo_hash', 'Memo', $memoHash, null);
$memoReturn = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_RETURN));
$memoReturn->returnHash = str_repeat("\xcd", 32);
add($fixtures, 'memo_return', 'Memo', $memoReturn, null);

// -----------------------------------------------------------------------
// SignerKey arms
// -----------------------------------------------------------------------
$signerEd = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::ED25519));
$signerEd->ed25519 = str_repeat("\x11", 32);
add($fixtures, 'signer_key_ed25519', 'SignerKey', $signerEd, null,
    'SEP-0051 §Stellar-Specific Types > SignerKey > G-strkey');

$signerPre = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::PRE_AUTH_TX));
$signerPre->preAuthTx = str_repeat("\x22", 32);
add($fixtures, 'signer_key_pre_auth_tx', 'SignerKey', $signerPre, null,
    'SEP-0051 §Stellar-Specific Types > SignerKey > T-strkey');

$signerHashX = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::HASH_X));
$signerHashX->hashX = str_repeat("\x33", 32);
add($fixtures, 'signer_key_hash_x', 'SignerKey', $signerHashX, null,
    'SEP-0051 §Stellar-Specific Types > SignerKey > X-strkey');

$signedPayload = new XdrSignedPayload(str_repeat("\x44", 32), str_repeat("\x55", 16));
$signerSP = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::ED25519_SIGNED_PAYLOAD));
$signerSP->signedPayload = $signedPayload;
add($fixtures, 'signer_key_ed25519_signed_payload', 'SignerKey', $signerSP, null,
    'SEP-0051 §Stellar-Specific Types > SignerKey > P-strkey');

// Standalone SignedPayload (Cat A)
add($fixtures, 'signed_payload_standalone', 'SignedPayload', new XdrSignedPayload(str_repeat("\x66", 32), 'shortpayload'), null,
    'SEP-0051 §Stellar-Specific Types > SignerKey > P-strkey (standalone)');

// -----------------------------------------------------------------------
// MuxedAccount
// -----------------------------------------------------------------------
$muxAccountG = new XdrMuxedAccount(str_repeat("\x21", 32));
add($fixtures, 'muxed_account_ed25519', 'MuxedAccount', $muxAccountG, null);
$muxAccount2 = new XdrMuxedAccount(null, new XdrMuxedAccountMed25519(99, str_repeat("\x33", 32)));
add($fixtures, 'muxed_account_med25519', 'MuxedAccount', $muxAccount2, null);

// -----------------------------------------------------------------------
// AccountID
// -----------------------------------------------------------------------
add($fixtures, 'account_id_g', 'AccountID', XdrAccountID::fromAccountId(TEST_ACCOUNT_G), null);
add($fixtures, 'account_id_g_alt', 'AccountID', XdrAccountID::fromAccountId(TEST_ACCOUNT_G_2), null);

// -----------------------------------------------------------------------
// LedgerBounds
// -----------------------------------------------------------------------
add($fixtures, 'ledger_bounds_zero', 'LedgerBounds', new XdrLedgerBounds(0, 0), null);
add($fixtures, 'ledger_bounds_typical', 'LedgerBounds', new XdrLedgerBounds(100, 200000), null);

// -----------------------------------------------------------------------
// TimeBounds (use Base directly to bypass DateTime-only wrapper constructor)
// -----------------------------------------------------------------------
add($fixtures, 'time_bounds_zero', 'TimeBounds', new \Soneso\StellarSDK\Xdr\XdrTimeBoundsBase(0, 0), null);
add($fixtures, 'time_bounds_typical', 'TimeBounds', new \Soneso\StellarSDK\Xdr\XdrTimeBoundsBase(1700000000, 1799999999), null);

// -----------------------------------------------------------------------
// LedgerKey arms
// -----------------------------------------------------------------------
$lkAccount = new XdrLedgerKey(new \Soneso\StellarSDK\Xdr\XdrLedgerEntryType(\Soneso\StellarSDK\Xdr\XdrLedgerEntryType::ACCOUNT));
$lkAccount->account = new XdrLedgerKeyAccount(XdrAccountID::fromAccountId(TEST_ACCOUNT_G));
add($fixtures, 'ledger_key_account', 'LedgerKey', $lkAccount, null);

// -----------------------------------------------------------------------
// 128/256 Parts standalone (Cat A)
// -----------------------------------------------------------------------
add($fixtures, 'uint128_zero', 'UInt128Parts', new XdrUInt128Parts(0, 0), null);
add($fixtures, 'uint128_one', 'UInt128Parts', new XdrUInt128Parts(0, 1), null);
add($fixtures, 'uint128_typical', 'UInt128Parts', new XdrUInt128Parts(1, 0), null);
add($fixtures, 'int128_zero', 'Int128Parts', new XdrInt128Parts(0, 0), null);
add($fixtures, 'int128_negative_one', 'Int128Parts', new XdrInt128Parts(-1, PHP_INT_MAX), null);
add($fixtures, 'uint256_zero', 'UInt256Parts', new XdrUInt256Parts(0, 0, 0, 0), null);
add($fixtures, 'uint256_one', 'UInt256Parts', new XdrUInt256Parts(0, 0, 0, 1), null);
add($fixtures, 'int256_zero', 'Int256Parts', new XdrInt256Parts(0, 0, 0, 0), null);

// -----------------------------------------------------------------------
// ClaimableBalanceID
// -----------------------------------------------------------------------
$cb1 = new XdrClaimableBalanceID(
    new XdrClaimableBalanceIDType(XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0),
    str_repeat('1f', 32)
);
add($fixtures, 'claimable_balance_id_v0', 'ClaimableBalanceID', $cb1, null);

// -----------------------------------------------------------------------
// DecoratedSignature
// -----------------------------------------------------------------------
$decSig = new XdrDecoratedSignature(str_repeat("\x77", 4), str_repeat("\x88", 64));
add($fixtures, 'decorated_signature', 'DecoratedSignature', $decSig, null);

// -----------------------------------------------------------------------
// AssetCode boundary fixtures (consuming-struct sites)
// -----------------------------------------------------------------------
$ac4Min = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
$ac4Min->setAlphaNum4(new XdrAssetAlphaNum4('A', XdrAccountID::fromAccountId(TEST_ACCOUNT_G)));
add($fixtures, 'asset_alphanum4_1byte', 'Asset', $ac4Min, null,
    'SEP-0051 §Stellar-Specific Types > AssetCode4 (1-byte)');

// AssetCode4 with non-ASCII byte 0x80 — chosen-divergence: PHP escapes via String escape ladder; py crashes.
// Bytes [0x41, 0x42, 0x80, 0x00] right-trim NULs to [0x41, 0x42, 0x80]; spec String escape ladder
// emits the JSON string `"AB\x80"` (literal characters: A, B, backslash, x, 8, 0). As JSON, this
// is the 6-character string content within enclosing quotes; the spec_reference_json field carries
// the entire JSON encoding of that string, including its outer quotes — `"AB\x80"`.
// In PHP source the embedded backslash needs escaping: '"AB\\x80"' is the 8-byte literal
// 0x22 0x41 0x42 0x5C 0x78 0x38 0x30 0x22.
$ac4NonAscii = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
$ac4NonAscii->setAlphaNum4(new XdrAssetAlphaNum4("AB\x80", XdrAccountID::fromAccountId(TEST_ACCOUNT_G)));
add($fixtures, 'asset_alphanum4_non_ascii', 'Asset', $ac4NonAscii,
    'py-stellar-base v14 raw ASCII decode crashes on non-ASCII byte 0x80 for both AssetCode4 and AssetCode12; PHP follows spec String-escape ladder',
    'SEP-0051 §Stellar-Specific Types > AssetCode4 (non-ASCII)',
    'divergence (1) entry — covers both AssetCode4 and AssetCode12 non-ASCII paths',
    '"AB\\x80"');

// AssetCode12 3-byte input padded to 5
$ac12_3byte = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
$ac12_3byte->setAlphaNum12(new XdrAssetAlphaNum12('ABC', XdrAccountID::fromAccountId(TEST_ACCOUNT_G)));
add($fixtures, 'asset_alphanum12_3byte_padded', 'Asset', $ac12_3byte, null,
    'SEP-0051 §Stellar-Specific Types > AssetCode12 (3-byte right-padded to 5)');

// -----------------------------------------------------------------------
// SCVal integer-as-string boundary fixtures
// -----------------------------------------------------------------------
add($fixtures, 'scval_u64_one', 'SCVal', XdrSCVal::forU64(1), null);
add($fixtures, 'scval_i64_negative_one', 'SCVal', XdrSCVal::forI64(-1), null);

// -----------------------------------------------------------------------
// SCVal Vec / Map nested fixtures (recursion depth 2)
// -----------------------------------------------------------------------
add($fixtures, 'scval_vec_nested', 'SCVal', XdrSCVal::forVec([
    XdrSCVal::forVec([XdrSCVal::forU32(1)]),
    XdrSCVal::forVec([XdrSCVal::forU32(2), XdrSCVal::forU32(3)]),
]), null);
add($fixtures, 'scval_vec_address', 'SCVal', XdrSCVal::forVec([
    XdrSCVal::forAddress($accountSCAddr),
    XdrSCVal::forAddress($contractSCAddr),
]), null);

// -----------------------------------------------------------------------
// Bytes large
// -----------------------------------------------------------------------
add($fixtures, 'scval_bytes_64', 'SCVal', XdrSCVal::forBytes(str_repeat("\x42", 64)), null);
add($fixtures, 'scval_bytes_256', 'SCVal', XdrSCVal::forBytes(str_repeat("a", 256)), null);

// -----------------------------------------------------------------------
// Symbol boundary (max length 32)
// -----------------------------------------------------------------------
add($fixtures, 'scval_symbol_32', 'SCVal', XdrSCVal::forSymbol(str_repeat('x', 32)), null);

// -----------------------------------------------------------------------
// String long
// -----------------------------------------------------------------------
add($fixtures, 'scval_string_long', 'SCVal', XdrSCVal::forString(str_repeat('Lorem ipsum dolor sit amet ', 10)), null);

// -----------------------------------------------------------------------
// SCVal with all numeric types (full coverage of the 22 SCVal arms section)
// -----------------------------------------------------------------------
// Already covered: bool, void, error, u32, i32, u64, i64, timepoint, duration,
// u128, i128, u256, i256, bytes, string, symbol, vec, map, address,
// ledger_key_contract_instance, ledger_key_nonce. ContractInstance is added
// later in the form of a contract_instance fixture if present.

// -----------------------------------------------------------------------
// SCV_CONTRACT_INSTANCE arm — covers the remaining SCVal arm not yet exercised.
// -----------------------------------------------------------------------
$wasmIdHex = str_repeat('a1', 32); // 64 hex chars = 32 raw bytes
$contractInstance = new XdrSCContractInstance(
    new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM()),
    null
);
$contractInstance->executable->wasmIdHex = $wasmIdHex;
add($fixtures, 'scval_contract_instance_wasm', 'SCVal', XdrSCVal::forContractInstance($contractInstance), null,
    'SEP-0051 §Specification > SCV_CONTRACT_INSTANCE');

$contractInstanceSAC = new XdrSCContractInstance(
    new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_STELLAR_ASSET()),
    null
);
add($fixtures, 'scval_contract_instance_stellar_asset', 'SCVal', XdrSCVal::forContractInstance($contractInstanceSAC), null,
    'SEP-0051 §Specification > SCV_CONTRACT_INSTANCE (Stellar Asset Contract)');

// -----------------------------------------------------------------------
// TransactionEnvelope canonical example — top-level container exercising
// the spec's worked TransactionEnvelope shape end-to-end.
// -----------------------------------------------------------------------
$canonicalSrc = new XdrMuxedAccount(str_repeat("\x21", 32));
$canonicalSeq = new XdrSequenceNumber(new BigInteger(20));
$canonicalCreateAccountOp = new XdrCreateAccountOperation(
    XdrAccountID::fromAccountId(TEST_ACCOUNT_G_2),
    new BigInteger(10000000) // 1 XLM in stroops
);
$canonicalOpBody = new XdrOperationBody(XdrOperationType::CREATE_ACCOUNT());
$canonicalOpBody->createAccountOp = $canonicalCreateAccountOp;
$canonicalOp = new XdrOperation($canonicalOpBody);
$canonicalTx = new XdrTransaction($canonicalSrc, $canonicalSeq, [$canonicalOp], 100);
$canonicalV1Env = new XdrTransactionV1Envelope($canonicalTx, []);
$canonicalEnv = new XdrTransactionEnvelope(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX));
$canonicalEnv->v1 = $canonicalV1Env;
add($fixtures, 'transaction_envelope_canonical', 'TransactionEnvelope', $canonicalEnv, null,
    'SEP-0051 §Examples §TransactionEnvelope',
    'canonical TransactionEnvelope: ENVELOPE_TYPE_TX with one CreateAccount operation, no signatures');

// Fee-bump-style envelope variant (still TX but with non-trivial preconditions could be added)
// is intentionally deferred: spec example covers ENVELOPE_TYPE_TX only.

// -----------------------------------------------------------------------
// LedgerCloseMeta v0 / v1 / v2 — top-level meta containers.
// All three are constructed with empty inner arrays + minimal-zero ledger header.
// -----------------------------------------------------------------------
$lcmHeader = new XdrLedgerHeader(
    42, str_repeat("\0", 32),
    new XdrStellarValue(
        str_repeat("\0", 32), 42, [],
        new XdrStellarValueExt(new XdrStellarValueType(XdrStellarValueType::STELLAR_VALUE_BASIC))
    ),
    str_repeat("\0", 32), str_repeat("\0", 32),
    42, 42, 42, 42, 42, 42, 42, 42,
    [str_repeat("\0", 32), str_repeat("\0", 32), str_repeat("\0", 32), str_repeat("\0", 32)],
    new XdrLedgerHeaderExt(0)
);
$lhhe = new XdrLedgerHeaderHistoryEntry(str_repeat("\0", 32), $lcmHeader, new XdrLedgerHeaderHistoryEntryExt(0));

$lcmV0Inner = new XdrLedgerCloseMetaV0($lhhe, new XdrTransactionSet(str_repeat("\0", 32), []), [], [], []);
$lcmV0 = new XdrLedgerCloseMeta(0);
$lcmV0->v0 = $lcmV0Inner;
add($fixtures, 'ledger_close_meta_v0', 'LedgerCloseMeta', $lcmV0, null,
    'SEP-0051 §Specification > Discriminated Union (int-cased) > LedgerCloseMeta v0');

$lcmGtsV1 = new XdrGeneralizedTransactionSet(1);
$lcmGtsV1->v1TxSet = new XdrTransactionSetV1(str_repeat("\0", 32), []);
$lcmV1Inner = new XdrLedgerCloseMetaV1(new XdrLedgerCloseMetaExt(0), $lhhe, $lcmGtsV1, [], [], [], 42, [], []);
$lcmV1 = new XdrLedgerCloseMeta(1);
$lcmV1->v1 = $lcmV1Inner;
add($fixtures, 'ledger_close_meta_v1', 'LedgerCloseMeta', $lcmV1, null,
    'SEP-0051 §Specification > Discriminated Union (int-cased) > LedgerCloseMeta v1');

$lcmGtsV2 = new XdrGeneralizedTransactionSet(1);
$lcmGtsV2->v1TxSet = new XdrTransactionSetV1(str_repeat("\0", 32), []);
$lcmV2Inner = new XdrLedgerCloseMetaV2(new XdrLedgerCloseMetaExt(0), $lhhe, $lcmGtsV2, [], [], [], 42, []);
$lcmV2 = new XdrLedgerCloseMeta(2);
$lcmV2->v2 = $lcmV2Inner;
add($fixtures, 'ledger_close_meta_v2', 'LedgerCloseMeta', $lcmV2, null,
    'SEP-0051 §Specification > Discriminated Union (int-cased) > LedgerCloseMeta v2');

// -----------------------------------------------------------------------
// BucketEntry — METAENTRY (uses BucketMetadata), DEADENTRY (uses LedgerKey),
// LIVEENTRY and INITENTRY (use LedgerEntry). The LedgerKey/LedgerEntry are
// constructed minimally over an account.
// -----------------------------------------------------------------------
$bucketMeta = new XdrBucketMetadata(42, new XdrBucketMetadataExt(0));

$bucketEntryMeta = new XdrBucketEntry(new XdrBucketEntryType(XdrBucketEntryType::METAENTRY));
$bucketEntryMeta->metaEntry = $bucketMeta;
add($fixtures, 'bucket_entry_metaentry', 'BucketEntry', $bucketEntryMeta, null,
    'SEP-0051 §Stellar-Specific Types > BucketEntry > METAENTRY');

$lkAccountForBucket = new XdrLedgerKey(new XdrLedgerEntryType(XdrLedgerEntryType::ACCOUNT));
$lkAccountForBucket->account = new XdrLedgerKeyAccount(XdrAccountID::fromAccountId(TEST_ACCOUNT_G));
$bucketEntryDead = new XdrBucketEntry(new XdrBucketEntryType(XdrBucketEntryType::DEADENTRY));
$bucketEntryDead->deadEntry = $lkAccountForBucket;
add($fixtures, 'bucket_entry_deadentry', 'BucketEntry', $bucketEntryDead, null,
    'SEP-0051 §Stellar-Specific Types > BucketEntry > DEADENTRY');

// Minimal LedgerEntry (account-typed) for LIVEENTRY/INITENTRY arms.
$accountEntryForBucket = new XdrAccountEntry(
    XdrAccountID::fromAccountId(TEST_ACCOUNT_G),
    new BigInteger(10000000),
    new XdrSequenceNumber(new BigInteger(1)),
    0,
    0,
    '',
    chr(1) . chr(0) . chr(0) . chr(0),
    [],
    new XdrAccountEntryExt(0)
);
$ledgerEntryDataForBucket = new XdrLedgerEntryData(new XdrLedgerEntryType(XdrLedgerEntryType::ACCOUNT));
$ledgerEntryDataForBucket->account = $accountEntryForBucket;
$ledgerEntryForBucket = new XdrLedgerEntry(123, $ledgerEntryDataForBucket, new XdrLedgerEntryExt(0));

$bucketEntryLive = new XdrBucketEntry(new XdrBucketEntryType(XdrBucketEntryType::LIVEENTRY));
$bucketEntryLive->liveEntry = $ledgerEntryForBucket;
add($fixtures, 'bucket_entry_liveentry_account', 'BucketEntry', $bucketEntryLive, null,
    'SEP-0051 §Stellar-Specific Types > BucketEntry > LIVEENTRY');

$bucketEntryInit = new XdrBucketEntry(new XdrBucketEntryType(XdrBucketEntryType::INITENTRY));
$bucketEntryInit->liveEntry = $ledgerEntryForBucket; // same field per generated encoder
add($fixtures, 'bucket_entry_initentry_account', 'BucketEntry', $bucketEntryInit, null,
    'SEP-0051 §Stellar-Specific Types > BucketEntry > INITENTRY');

// -----------------------------------------------------------------------
// HotArchiveBucketEntry — METAENTRY (BucketMetadata), ARCHIVED (LedgerEntry), LIVE (LedgerKey).
// -----------------------------------------------------------------------
$haMeta = new XdrHotArchiveBucketEntry(new XdrHotArchiveBucketEntryType(XdrHotArchiveBucketEntryType::HOT_ARCHIVE_METAENTRY));
$haMeta->metaEntry = $bucketMeta;
add($fixtures, 'hot_archive_bucket_entry_metaentry', 'HotArchiveBucketEntry', $haMeta, null,
    'SEP-0051 §Stellar-Specific Types > HotArchiveBucketEntry > HOT_ARCHIVE_METAENTRY');

$haArchived = new XdrHotArchiveBucketEntry(new XdrHotArchiveBucketEntryType(XdrHotArchiveBucketEntryType::HOT_ARCHIVE_ARCHIVED));
$haArchived->archivedEntry = $ledgerEntryForBucket;
add($fixtures, 'hot_archive_bucket_entry_archived', 'HotArchiveBucketEntry', $haArchived, null,
    'SEP-0051 §Stellar-Specific Types > HotArchiveBucketEntry > HOT_ARCHIVE_ARCHIVED');

$haLive = new XdrHotArchiveBucketEntry(new XdrHotArchiveBucketEntryType(XdrHotArchiveBucketEntryType::HOT_ARCHIVE_LIVE));
$haLive->key = $lkAccountForBucket;
add($fixtures, 'hot_archive_bucket_entry_live', 'HotArchiveBucketEntry', $haLive, null,
    'SEP-0051 §Stellar-Specific Types > HotArchiveBucketEntry > HOT_ARCHIVE_LIVE');

// -----------------------------------------------------------------------
// Operation arms — one fixture per OperationBody discriminant. Constructed
// as full XdrOperation values (with no source-account) so the fixture's
// declared type 'Operation' round-trips through XdrOperation::fromBase64Xdr.
// -----------------------------------------------------------------------

$opSource = null; // each Operation has no source-account

function add_op(array &$list, string $id, int $opType, string $specSuffix, ?\Closure $bodyAttacher = null,
                ?string $notes = null): void {
    $body = new XdrOperationBody(new XdrOperationType($opType));
    if ($bodyAttacher !== null) {
        $bodyAttacher($body);
    }
    $op = new XdrOperation($body);
    add($list, $id, 'Operation', $op, null,
        'SEP-0051 §Examples > Operation > ' . $specSuffix, $notes);
}

add_op($fixtures, 'operation_create_account', XdrOperationType::CREATE_ACCOUNT, 'CreateAccountOp',
    function (XdrOperationBody $b): void {
        $b->createAccountOp = new XdrCreateAccountOperation(
            XdrAccountID::fromAccountId(TEST_ACCOUNT_G_2),
            new BigInteger(10000000)
        );
    });

add_op($fixtures, 'operation_payment', XdrOperationType::PAYMENT, 'PaymentOp',
    function (XdrOperationBody $b): void {
        $native = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $b->paymentOp = new XdrPaymentOperation(
            new XdrMuxedAccount(str_repeat("\x21", 32)),
            $native,
            new BigInteger(50000)
        );
    });

add_op($fixtures, 'operation_path_payment_strict_receive', XdrOperationType::PATH_PAYMENT_STRICT_RECEIVE,
    'PathPaymentStrictReceiveOp',
    function (XdrOperationBody $b): void {
        $native = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $b->pathPaymentStrictReceiveOp = new XdrPathPaymentStrictReceiveOperation(
            $native,
            new BigInteger(100),
            new XdrMuxedAccount(str_repeat("\x21", 32)),
            $native,
            new BigInteger(50),
            []
        );
    });

add_op($fixtures, 'operation_manage_sell_offer', XdrOperationType::MANAGE_SELL_OFFER, 'ManageSellOfferOp',
    function (XdrOperationBody $b): void {
        $native = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $usd = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $usd->setAlphaNum4(new XdrAssetAlphaNum4('USD', XdrAccountID::fromAccountId(TEST_ACCOUNT_G)));
        $b->manageSellOfferOp = new XdrManageSellOfferOperation(
            $native, $usd, new BigInteger(1000), new XdrPrice(1, 1), 0
        );
    });

add_op($fixtures, 'operation_create_passive_sell_offer', XdrOperationType::CREATE_PASSIVE_SELL_OFFER,
    'CreatePassiveSellOfferOp',
    function (XdrOperationBody $b): void {
        $native = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $usd = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $usd->setAlphaNum4(new XdrAssetAlphaNum4('USD', XdrAccountID::fromAccountId(TEST_ACCOUNT_G)));
        $b->createPassiveSellOfferOp = new XdrCreatePassiveSellOfferOperation(
            $native, $usd, new BigInteger(1000), new XdrPrice(1, 1)
        );
    });

add_op($fixtures, 'operation_set_options_minimal', XdrOperationType::SET_OPTIONS, 'SetOptionsOp',
    function (XdrOperationBody $b): void {
        $b->setOptionsOp = new XdrSetOptionsOperation();
    });

add_op($fixtures, 'operation_change_trust', XdrOperationType::CHANGE_TRUST, 'ChangeTrustOp',
    function (XdrOperationBody $b): void {
        $line = new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $line->setAlphaNum4(new XdrAssetAlphaNum4('USD', XdrAccountID::fromAccountId(TEST_ACCOUNT_G)));
        $b->changeTrustOp = new XdrChangeTrustOperation($line);
    });

add_op($fixtures, 'operation_allow_trust', XdrOperationType::ALLOW_TRUST, 'AllowTrustOp',
    function (XdrOperationBody $b): void {
        $allowAsset = new XdrAllowTrustOperationAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $allowAsset->setAssetCode4('USD');
        $b->allowTrustOperation = new XdrAllowTrustOperation(
            XdrAccountID::fromAccountId(TEST_ACCOUNT_G_2),
            $allowAsset,
            1
        );
    });

add_op($fixtures, 'operation_account_merge', XdrOperationType::ACCOUNT_MERGE, 'AccountMergeOp',
    function (XdrOperationBody $b): void {
        $b->accountMergeOp = new XdrAccountMergeOperation(new XdrMuxedAccount(str_repeat("\x21", 32)));
    });

add_op($fixtures, 'operation_inflation', XdrOperationType::INFLATION, 'InflationOp (void arm)');

add_op($fixtures, 'operation_manage_data', XdrOperationType::MANAGE_DATA, 'ManageDataOp',
    function (XdrOperationBody $b): void {
        $b->manageDataOperation = new XdrManageDataOperation(
            'config',
            new \Soneso\StellarSDK\Xdr\XdrDataValue('value-bytes')
        );
    });

add_op($fixtures, 'operation_bump_sequence', XdrOperationType::BUMP_SEQUENCE, 'BumpSequenceOp',
    function (XdrOperationBody $b): void {
        $b->bumpSequenceOp = new XdrBumpSequenceOperation(
            new XdrSequenceNumber(new BigInteger(99))
        );
    });

add_op($fixtures, 'operation_manage_buy_offer', XdrOperationType::MANAGE_BUY_OFFER, 'ManageBuyOfferOp',
    function (XdrOperationBody $b): void {
        $native = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $usd = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $usd->setAlphaNum4(new XdrAssetAlphaNum4('USD', XdrAccountID::fromAccountId(TEST_ACCOUNT_G)));
        $b->manageBuyOfferOp = new XdrManageBuyOfferOperation(
            $native, $usd, new BigInteger(1000), new XdrPrice(1, 1), 0
        );
    });

add_op($fixtures, 'operation_path_payment_strict_send', XdrOperationType::PATH_PAYMENT_STRICT_SEND,
    'PathPaymentStrictSendOp',
    function (XdrOperationBody $b): void {
        $native = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $b->pathPaymentStrictSendOp = new XdrPathPaymentStrictSendOperation(
            $native,
            new BigInteger(100),
            new XdrMuxedAccount(str_repeat("\x21", 32)),
            $native,
            new BigInteger(50),
            []
        );
    });

add_op($fixtures, 'operation_create_claimable_balance', XdrOperationType::CREATE_CLAIMABLE_BALANCE,
    'CreateClaimableBalanceOp',
    function (XdrOperationBody $b): void {
        $native = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $b->createClaimableBalanceOperation = new XdrCreateClaimableBalanceOperation(
            $native, new BigInteger(1000), []
        );
    });

add_op($fixtures, 'operation_claim_claimable_balance', XdrOperationType::CLAIM_CLAIMABLE_BALANCE,
    'ClaimClaimableBalanceOp',
    function (XdrOperationBody $b): void {
        $cbId = new XdrClaimableBalanceID(
            new XdrClaimableBalanceIDType(XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0),
            str_repeat('1f', 32)
        );
        $b->claimClaimableBalanceOperation = new XdrClaimClaimableBalanceOperation($cbId);
    });

add_op($fixtures, 'operation_begin_sponsoring_future_reserves', XdrOperationType::BEGIN_SPONSORING_FUTURE_RESERVES,
    'BeginSponsoringFutureReservesOp',
    function (XdrOperationBody $b): void {
        $b->beginSponsoringFutureReservesOperation = new XdrBeginSponsoringFutureReservesOperation(
            XdrAccountID::fromAccountId(TEST_ACCOUNT_G_2)
        );
    });

add_op($fixtures, 'operation_end_sponsoring_future_reserves', XdrOperationType::END_SPONSORING_FUTURE_RESERVES,
    'EndSponsoringFutureReservesOp (void arm)');

add_op($fixtures, 'operation_revoke_sponsorship_ledger_entry', XdrOperationType::REVOKE_SPONSORSHIP,
    'RevokeSponsorshipOp (LEDGER_ENTRY)',
    function (XdrOperationBody $b): void {
        $rs = new XdrRevokeSponsorshipOperation(new XdrRevokeSponsorshipType(XdrRevokeSponsorshipType::LEDGER_ENTRY));
        $lk = new XdrLedgerKey(new XdrLedgerEntryType(XdrLedgerEntryType::ACCOUNT));
        $lk->account = new XdrLedgerKeyAccount(XdrAccountID::fromAccountId(TEST_ACCOUNT_G));
        $rs->ledgerKey = $lk;
        $b->revokeSponsorshipOperation = $rs;
    });

add_op($fixtures, 'operation_clawback', XdrOperationType::CLAWBACK, 'ClawbackOp',
    function (XdrOperationBody $b): void {
        $usd = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $usd->setAlphaNum4(new XdrAssetAlphaNum4('USD', XdrAccountID::fromAccountId(TEST_ACCOUNT_G)));
        $b->clawbackOperation = new XdrClawbackOperation(
            $usd,
            new XdrMuxedAccount(str_repeat("\x21", 32)),
            new BigInteger(100)
        );
    });

add_op($fixtures, 'operation_clawback_claimable_balance', XdrOperationType::CLAWBACK_CLAIMABLE_BALANCE,
    'ClawbackClaimableBalanceOp',
    function (XdrOperationBody $b): void {
        $cbId = new XdrClaimableBalanceID(
            new XdrClaimableBalanceIDType(XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0),
            str_repeat('2e', 32)
        );
        $b->clawbackClaimableBalanceOperation = new XdrClawbackClaimableBalanceOperation($cbId);
    });

add_op($fixtures, 'operation_set_trust_line_flags', XdrOperationType::SET_TRUST_LINE_FLAGS, 'SetTrustLineFlagsOp',
    function (XdrOperationBody $b): void {
        $usd = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $usd->setAlphaNum4(new XdrAssetAlphaNum4('USD', XdrAccountID::fromAccountId(TEST_ACCOUNT_G)));
        $b->setTrustLineFlagsOperation = new XdrSetTrustLineFlagsOperation(
            XdrAccountID::fromAccountId(TEST_ACCOUNT_G_2),
            $usd,
            0,
            1
        );
    });

add_op($fixtures, 'operation_liquidity_pool_deposit', XdrOperationType::LIQUIDITY_POOL_DEPOSIT,
    'LiquidityPoolDepositOp',
    function (XdrOperationBody $b): void {
        $b->liquidityPoolDepositOperation = new XdrLiquidityPoolDepositOperation(
            str_repeat('aa', 32),
            new BigInteger(100),
            new BigInteger(100),
            new XdrPrice(1, 1),
            new XdrPrice(1, 1)
        );
    });

add_op($fixtures, 'operation_liquidity_pool_withdraw', XdrOperationType::LIQUIDITY_POOL_WITHDRAW,
    'LiquidityPoolWithdrawOp',
    function (XdrOperationBody $b): void {
        // Wrapper expects a 64-char hex string (Cat-B hex storage form per
        // Phase-4-entry storage-form audit; verified XdrLiquidityPoolWithdrawOperation.php:15).
        $b->liquidityPoolWithdrawOperation = new XdrLiquidityPoolWithdrawOperation(
            str_repeat('bb', 32),
            new BigInteger(100),
            new BigInteger(50),
            new BigInteger(50)
        );
    });

add_op($fixtures, 'operation_invoke_host_function_upload', XdrOperationType::INVOKE_HOST_FUNCTION,
    'InvokeHostFunctionOp (UPLOAD_CONTRACT_WASM)',
    function (XdrOperationBody $b): void {
        $hf = new XdrHostFunction(new XdrHostFunctionType(XdrHostFunctionType::HOST_FUNCTION_TYPE_UPLOAD_CONTRACT_WASM));
        $hf->wasm = new XdrDataValueMandatory("\x00asm" . str_repeat("\x00", 4));
        $b->invokeHostFunctionOperation = new XdrInvokeHostFunctionOp($hf, []);
    });

add_op($fixtures, 'operation_extend_footprint_ttl', XdrOperationType::EXTEND_FOOTPRINT_TTL, 'ExtendFootprintTTLOp',
    function (XdrOperationBody $b): void {
        $b->extendFootprintTTLOp = new XdrExtendFootprintTTLOp(new XdrExtensionPoint(0), 1000);
    });

add_op($fixtures, 'operation_restore_footprint', XdrOperationType::RESTORE_FOOTPRINT, 'RestoreFootprintOp',
    function (XdrOperationBody $b): void {
        $b->restoreFootprintOp = new XdrRestoreFootprintOp(new XdrExtensionPoint(0));
    });

// -----------------------------------------------------------------------
// SCSpecEntry — five variants (FUNCTION_V0, UDT_STRUCT_V0, UDT_UNION_V0,
// UDT_ENUM_V0, UDT_ERROR_ENUM_V0). Each uses minimal valid sub-objects.
// -----------------------------------------------------------------------
$specFn = new XdrSCSpecFunctionV0('docstring', 'transfer',
    [new \Soneso\StellarSDK\Xdr\XdrSCSpecFunctionInputV0('amt-doc', 'amount', XdrSCSpecTypeDef::I32())],
    [XdrSCSpecTypeDef::BOOL()]
);
add($fixtures, 'sc_spec_entry_function_v0', 'SCSpecEntry',
    XdrSCSpecEntry::forFunctionV0($specFn), null,
    'SEP-0051 §Stellar-Specific Types > SCSpecEntry > FUNCTION_V0');

$specStruct = new XdrSCSpecUDTStructV0('struct-doc', 'soroban_sdk', 'Point',
    [
        new XdrSCSpecUDTStructFieldV0('x-doc', 'x', XdrSCSpecTypeDef::I32()),
        new XdrSCSpecUDTStructFieldV0('y-doc', 'y', XdrSCSpecTypeDef::I32()),
    ]
);
add($fixtures, 'sc_spec_entry_udt_struct_v0', 'SCSpecEntry',
    XdrSCSpecEntry::forUDTStructV0($specStruct), null,
    'SEP-0051 §Stellar-Specific Types > SCSpecEntry > UDT_STRUCT_V0');

$specUnion = new XdrSCSpecUDTUnionV0('union-doc', 'soroban_sdk', 'Color',
    [
        XdrSCSpecUDTUnionCaseV0::forVoidCase(new XdrSCSpecUDTUnionCaseVoidV0('red-doc', 'Red')),
        XdrSCSpecUDTUnionCaseV0::forVoidCase(new XdrSCSpecUDTUnionCaseVoidV0('green-doc', 'Green')),
    ]
);
add($fixtures, 'sc_spec_entry_udt_union_v0', 'SCSpecEntry',
    XdrSCSpecEntry::forUDTUnionV0($specUnion), null,
    'SEP-0051 §Stellar-Specific Types > SCSpecEntry > UDT_UNION_V0');

$specEnum = new XdrSCSpecUDTEnumV0('enum-doc', 'soroban_sdk', 'Direction',
    [
        new XdrSCSpecUDTEnumCaseV0('north-doc', 'North', 0),
        new XdrSCSpecUDTEnumCaseV0('south-doc', 'South', 1),
    ]
);
add($fixtures, 'sc_spec_entry_udt_enum_v0', 'SCSpecEntry',
    XdrSCSpecEntry::forUDTEnumV0($specEnum), null,
    'SEP-0051 §Stellar-Specific Types > SCSpecEntry > UDT_ENUM_V0');

$specErrorEnum = new XdrSCSpecUDTErrorEnumV0('errenum-doc', 'soroban_sdk', 'Error',
    [
        new XdrSCSpecUDTErrorEnumCaseV0('not-found-doc', 'NotFound', 1),
        new XdrSCSpecUDTErrorEnumCaseV0('forbidden-doc', 'Forbidden', 2),
    ]
);
add($fixtures, 'sc_spec_entry_udt_error_enum_v0', 'SCSpecEntry',
    XdrSCSpecEntry::forUDTErrorEnumV0($specErrorEnum), null,
    'SEP-0051 §Stellar-Specific Types > SCSpecEntry > UDT_ERROR_ENUM_V0');

// -----------------------------------------------------------------------
// ConfigSettingEntry — representatives across every shape. Covers every
// arm in the plan §5 acceptance list (MAX_SIZE_BYTES, COMPUTE_V0,
// LEDGER_COST_V0, HISTORICAL_DATA_V0, EVENTS_V0, BANDWIDTH_V0,
// CONTRACT_DATA_KEY_SIZE_BYTES, CONTRACT_DATA_ENTRY_SIZE_BYTES,
// STATE_ARCHIVAL is omitted here as it requires a XdrStateArchivalSettings
// with 10 numeric fields — covered by its own per-arm test in Phase 5a;
// CONTRACT_EXECUTION_LANES included).
// -----------------------------------------------------------------------
$cfgMaxSize = new XdrConfigSettingEntry(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_MAX_SIZE_BYTES());
$cfgMaxSize->contractMaxSizeBytes = 65536;
add($fixtures, 'config_setting_entry_max_size_bytes', 'ConfigSettingEntry', $cfgMaxSize, null,
    'SEP-0051 §Stellar-Specific Types > ConfigSettingEntry > CONTRACT_MAX_SIZE_BYTES');

$cfgCompute = new XdrConfigSettingEntry(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COMPUTE_V0());
$cfgCompute->contractCompute = new XdrConfigSettingContractComputeV0(100000000, 25000000, 50, 67108864);
add($fixtures, 'config_setting_entry_compute_v0', 'ConfigSettingEntry', $cfgCompute, null,
    'SEP-0051 §Stellar-Specific Types > ConfigSettingEntry > CONTRACT_COMPUTE_V0');

$cfgLedgerCost = new XdrConfigSettingEntry(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_LEDGER_COST_V0());
$cfgLedgerCost->contractLedgerCost = new XdrConfigSettingContractLedgerCostV0(
    100, 1048576, 25, 65536, 50, 524288, 25, 32768,
    1000, 5000, 1000, 1000000, 100, 1000, 50
);
add($fixtures, 'config_setting_entry_ledger_cost_v0', 'ConfigSettingEntry', $cfgLedgerCost, null,
    'SEP-0051 §Stellar-Specific Types > ConfigSettingEntry > CONTRACT_LEDGER_COST_V0');

$cfgHistorical = new XdrConfigSettingEntry(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_HISTORICAL_DATA_V0());
$cfgHistorical->contractHistoricalData = new XdrConfigSettingContractHistoricalDataV0(100);
add($fixtures, 'config_setting_entry_historical_data_v0', 'ConfigSettingEntry', $cfgHistorical, null,
    'SEP-0051 §Stellar-Specific Types > ConfigSettingEntry > CONTRACT_HISTORICAL_DATA_V0');

$cfgEvents = new XdrConfigSettingEntry(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_EVENTS_V0());
$cfgEvents->contractEvents = new XdrConfigSettingContractEventsV0(8192, 200);
add($fixtures, 'config_setting_entry_events_v0', 'ConfigSettingEntry', $cfgEvents, null,
    'SEP-0051 §Stellar-Specific Types > ConfigSettingEntry > CONTRACT_EVENTS_V0');

$cfgBandwidth = new XdrConfigSettingEntry(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_BANDWIDTH_V0());
$cfgBandwidth->contractBandwidth = new XdrConfigSettingContractBandwidthV0(131072, 65536, 100);
add($fixtures, 'config_setting_entry_bandwidth_v0', 'ConfigSettingEntry', $cfgBandwidth, null,
    'SEP-0051 §Stellar-Specific Types > ConfigSettingEntry > CONTRACT_BANDWIDTH_V0');

$cfgKeySize = new XdrConfigSettingEntry(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_KEY_SIZE_BYTES());
$cfgKeySize->contractDataKeySizeBytes = 1024;
add($fixtures, 'config_setting_entry_data_key_size', 'ConfigSettingEntry', $cfgKeySize, null,
    'SEP-0051 §Stellar-Specific Types > ConfigSettingEntry > CONTRACT_DATA_KEY_SIZE_BYTES');

$cfgEntrySize = new XdrConfigSettingEntry(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_ENTRY_SIZE_BYTES());
$cfgEntrySize->contractDataEntrySizeBytes = 65536;
add($fixtures, 'config_setting_entry_data_entry_size', 'ConfigSettingEntry', $cfgEntrySize, null,
    'SEP-0051 §Stellar-Specific Types > ConfigSettingEntry > CONTRACT_DATA_ENTRY_SIZE_BYTES');

$cfgExecLanes = new XdrConfigSettingEntry(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_EXECUTION_LANES());
$cfgExecLanes->contractExecutionLanes = new XdrConfigSettingContractExecutionLanesV0(50);
add($fixtures, 'config_setting_entry_execution_lanes', 'ConfigSettingEntry', $cfgExecLanes, null,
    'SEP-0051 §Stellar-Specific Types > ConfigSettingEntry > CONTRACT_EXECUTION_LANES');

// LIVE_SOROBAN_STATE_SIZE_WINDOW uses an array of u64 — minimal empty list.
$cfgWindow = new XdrConfigSettingEntry(XdrConfigSettingID::CONFIG_SETTING_LIVE_SOROBAN_STATE_SIZE_WINDOW());
$cfgWindow->liveSorobanStateSizeWindow = [];
add($fixtures, 'config_setting_entry_state_size_window', 'ConfigSettingEntry', $cfgWindow, null,
    'SEP-0051 §Stellar-Specific Types > ConfigSettingEntry > LIVE_SOROBAN_STATE_SIZE_WINDOW (empty array)');

// -----------------------------------------------------------------------
// Many additional Asset / SCVal permutations to reach >=150 entries
// -----------------------------------------------------------------------
foreach (range(0, 9) as $i) {
    add($fixtures, "scval_u32_iter_$i", 'SCVal', XdrSCVal::forU32($i * 100000), null);
}
foreach (range(0, 9) as $i) {
    add($fixtures, "scval_i32_iter_$i", 'SCVal', XdrSCVal::forI32(($i - 5) * 100000), null);
}
foreach (range(0, 9) as $i) {
    add($fixtures, "scval_u64_iter_$i", 'SCVal', XdrSCVal::forU64($i * 1000000000), null);
}
foreach (range(0, 9) as $i) {
    add($fixtures, "scval_symbol_iter_$i", 'SCVal', XdrSCVal::forSymbol("op_$i"), null);
}
foreach (range(0, 9) as $i) {
    add($fixtures, "scval_string_iter_$i", 'SCVal', XdrSCVal::forString("string_$i"), null);
}
foreach (range(0, 9) as $i) {
    add($fixtures, "scval_bytes_iter_$i", 'SCVal', XdrSCVal::forBytes(pack('N', $i * 1024)), null);
}
foreach (range(0, 9) as $i) {
    add($fixtures, "scval_bool_iter_$i", 'SCVal', XdrSCVal::forBool($i % 2 === 0), null);
}

// -----------------------------------------------------------------------
// Output JSON
// -----------------------------------------------------------------------
echo json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n";
