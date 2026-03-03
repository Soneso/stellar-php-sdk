# frozen_string_literal: true

# Type resolution overrides for the PHP XDR generator.
#
# TYPE_OVERRIDES: Maps typedef-resolved class names to the actual PHP types
# the SDK uses. Applied in php_type_for_typespec() when resolving Simple types.
#
# BASE_WRAPPER_TYPES: Types that generate *Base.php files. The hand-written
# wrapper file extends the generated base class.
#
# SELF_REFERENCING_BASE_TYPES: Base types that reference their own wrapper type
# in field declarations (e.g., XdrSCValBase has array of XdrSCVal fields).
#
# SKIP_TYPES: Types the generator must NOT produce. Initially all existing types;
# shrink by removing batches as generation is verified.
#
# EXTENSION_POINT_FIELDS: Struct fields that are void-only extension unions,
# simplified to `public int $fieldName = 0`.

# ---------------------------------------------------------------------------
# TYPE_OVERRIDES
# Maps generated typedef names to the types the SDK actually uses.
# Phase 2: Populate with typedef resolution rules as types are audited.
# ---------------------------------------------------------------------------
TYPE_OVERRIDES = {
  # Integer typedefs — SDK uses plain int
  "XdrInt32" => "int",
  "XdrUint32" => "int",
  "XdrInt64" => "int",
  "XdrUint64" => "int",
  "XdrTimePoint" => "int",
  "XdrDuration" => "int",

  # Fixed-opaque typedefs — SDK uses string (binary)
  "XdrHash" => "string",
  "XdrUint256" => "string",
  "XdrPoolID" => "string",
  "XdrAssetCode4" => "string",
  "XdrAssetCode12" => "string",

  # String typedefs — SDK uses string
  "XdrString64" => "string",
  "XdrString32" => "string",
  "XdrString256" => "string",
  "XdrString1000" => "string",

  # Symbol typedef — SDK uses string
  "XdrSCSymbol" => "string",

  # Typedef-array — SDK inlines as array (no wrapper class)
  "XdrLedgerEntryChanges" => "array",
}.freeze

# ---------------------------------------------------------------------------
# BASE_WRAPPER_TYPES
# Types that generate *Base.php files. The hand-written wrapper extends the base.
# Phase 1: Populated when wrapper types are identified and created.
# ---------------------------------------------------------------------------
BASE_WRAPPER_TYPES = %w[
  XdrAccountID
  XdrClaimableBalanceID
  XdrContractExecutable
  XdrContractIDPreimage
  XdrDecoratedSignature
  XdrHostFunction
  XdrLedgerKey
  XdrMuxedAccount
  XdrMuxedAccountMed25519
  XdrSCAddress
  XdrSCVal
  XdrTransaction
].freeze

# ---------------------------------------------------------------------------
# SELF_REFERENCING_BASE_TYPES
# Base types that import their own wrapper (e.g., XdrSCValBase has List<XdrSCVal>).
# ---------------------------------------------------------------------------
SELF_REFERENCING_BASE_TYPES = %w[
  XdrSCVal
].freeze

# ---------------------------------------------------------------------------
# SKIP_TYPES
#
# Every existing PHP XDR class. The generator will not emit a file for any
# type whose resolved name matches an entry here.
#
# Names must match what the generator's name() method returns (with Xdr prefix).
# To regenerate a specific type, remove it from this list and re-run.
# Batches will progressively remove entries as types are verified.
# ---------------------------------------------------------------------------
SKIP_TYPES = %w[
  XdrAccountEntry
  XdrAccountEntryV2
  XdrAccountID
  XdrAccountMergeOperation
  XdrAllowTrustOperationAsset
  XdrAsset
  XdrAssetAlphaNum12
  XdrAssetAlphaNum4

  XdrBuffer
  XdrChangeTrustAsset
  XdrChangeTrustOperation
  XdrClaimAtom
  XdrClaimOfferAtomV0
  XdrClaimPredicate

  XdrClaimableBalanceEntry
  XdrClaimableBalanceEntryExtV1
  XdrClaimableBalanceID
  XdrClaimant

  XdrConfigSettingEntry
  XdrConstantProduct
  XdrConfigUpgradeSetKey
  XdrContractCodeEntry
  XdrContractCodeEntryExt
  XdrContractCodeEntryExtV1
  XdrContractCostParams
  XdrContractEvent
  XdrContractEventBody
  XdrContractExecutable
  XdrContractIDPreimage
  XdrCreateClaimableBalanceResult
  XdrDataEntry
  XdrDataValue
  XdrDataValueMandatory
  XdrDecoder
  XdrDecoratedSignature
  XdrEncoder
  XdrFeeBumpTransaction
  XdrFeeBumpTransactionInnerTx
  XdrHashIDPreimage
  XdrHostFunction
  XdrInnerTransactionResult
  XdrInnerTransactionResultPair
  XdrInnerTransactionResultResult
  XdrLedgerEntry
  XdrLedgerEntryChange
  XdrLedgerEntryData
  XdrLedgerEntryV1
  XdrLedgerKey
  XdrLedgerKeyAccount
  XdrLiquidityPoolBody
  XdrLiquidityPoolDepositOperation
  XdrLiquidityPoolEntry
  XdrLiquidityPoolParameters

  XdrLiquidityPoolWithdrawOperation
  XdrManageDataOperation
  XdrManageOfferResult
  XdrManageOfferSuccessResult
  XdrManageOfferSuccessResultOffer
  XdrMemo

  XdrMuxedAccount
  XdrMuxedAccountMed25519
  XdrOfferEntry
  XdrOperation
  XdrOperationBody
  XdrOperationResult
  XdrOperationResultTr
  XdrPathPaymentResultSuccess
  XdrPathPaymentStrictReceiveResult
  XdrPathPaymentStrictSendResult

  XdrPreconditions
  XdrPreconditionsV2
  XdrRevokeSponsorshipOperation

  XdrSCAddress
  XdrSCContractInstance
  XdrSCEnvMetaEntry
  XdrSCError
  XdrSCMetaEntry
  XdrSCSpecEntry
  XdrSCSpecTypeDef
  XdrSCSpecUDTUnionCaseV0
  XdrSCVal
  XdrSetOptionsOperation
  XdrSignedPayload
  XdrSignerKey
  XdrSignerKeyType
  XdrSorobanAuthorizedFunction
  XdrSorobanCredentials
  XdrSorobanTransactionData
  XdrSorobanTransactionMeta
  XdrSorobanTransactionMetaV2
  XdrTimeBounds
  XdrTransaction
  XdrTransactionEnvelope
  XdrTransactionMeta
  XdrTransactionMetaV3
  XdrTransactionMetaV4
  XdrTransactionResult
  XdrTransactionResultResult
  XdrTransactionV0
  XdrTrustLineEntry
  XdrTrustLineEntryExtensionV2
  XdrTrustlineAsset
].freeze

# ---------------------------------------------------------------------------
# EXTENSION_POINT_FIELDS
# Maps struct names to field names that are void-only extension unions.
# These are simplified to `public int $fieldName = 0` instead of full unions.
# Phase 2: Populate as extension point fields are identified during audit.
# ---------------------------------------------------------------------------
EXTENSION_POINT_FIELDS = {
  # "XdrDataEntry" => ["ext"],
  # "XdrTransaction" => ["ext"],
}.freeze
