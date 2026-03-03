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
  XdrAccountEntryExt
  XdrAccountEntryV1
  XdrAccountEntryV1Ext
  XdrAccountEntryV2
  XdrAccountEntryV2Ext
  XdrAccountEntryV3
  XdrAccountID
  XdrAccountMergeOperation
  XdrAccountMergeResult
  XdrAllowTrustOperation
  XdrAllowTrustOperationAsset
  XdrAllowTrustResult
  XdrAsset
  XdrAssetAlphaNum12
  XdrAssetAlphaNum4

  XdrBeginSponsoringFutureReservesResult
  XdrBuffer
  XdrBumpSequenceOperation
  XdrBumpSequenceResult
  XdrChangeTrustAsset
  XdrChangeTrustOperation
  XdrChangeTrustResult
  XdrClaimAtom
  XdrClaimClaimableBalanceOperation
  XdrClaimClaimableBalanceResult
  XdrClaimOfferAtomV0
  XdrClaimPredicate

  XdrClaimableBalanceEntry
  XdrClaimableBalanceEntryExt
  XdrClaimableBalanceEntryExtV1
  XdrClaimableBalanceID
  XdrClaimant

  XdrClawbackClaimableBalanceOperation
  XdrClawbackClaimableBalanceResult
  XdrClawbackOperation
  XdrClawbackResult
  XdrConfigSettingEntry
  XdrConfigUpgradeSetKey
  XdrConstantProduct
  XdrContractCodeEntry
  XdrContractCodeEntryExt
  XdrContractCodeEntryExtV1
  XdrContractCostParams
  XdrContractCostType
  XdrContractDataEntry
  XdrContractEvent
  XdrContractEventBody
  XdrContractExecutable
  XdrContractIDPreimage
  XdrCreateAccountResult
  XdrCreateClaimableBalanceOperation
  XdrCreateClaimableBalanceResult
  XdrDataEntry
  XdrDataEntryExt
  XdrDataValue
  XdrDataValueMandatory
  XdrDecoder
  XdrDecoratedSignature
  XdrEncoder
  XdrEndSponsoringFutureReservesResult
  XdrEvictionIterator
  XdrExtendFootprintTTLResult
  XdrExtensionPoint
  XdrFeeBumpTransaction
  XdrFeeBumpTransactionExt
  XdrFeeBumpTransactionInnerTx
  XdrHashIDPreimage
  XdrHostFunction
  XdrInflationResult
  XdrInnerTransactionResult
  XdrInnerTransactionResultPair
  XdrInnerTransactionResultResult
  XdrInvokeHostFunctionOp
  XdrInvokeHostFunctionResult
  XdrInvokeHostFunctionResultCode
  XdrInvokeHostFunctionSuccessPreImage
  XdrLedgerEntry
  XdrLedgerEntryChange
  XdrLedgerEntryData
  XdrLedgerEntryExt
  XdrLedgerEntryV1
  XdrLedgerEntryV1Ext
  XdrLedgerKey
  XdrLedgerKeyAccount
  XdrLiquidityPoolBody
  XdrLiquidityPoolConstantProductParameters
  XdrLiquidityPoolDepositOperation
  XdrLiquidityPoolDepositResult
  XdrLiquidityPoolEntry
  XdrLiquidityPoolParameters

  XdrLiquidityPoolWithdrawOperation
  XdrLiquidityPoolWithdrawResult
  XdrManageBuyOfferOperation
  XdrManageDataOperation
  XdrManageDataResult
  XdrManageOfferResult
  XdrManageOfferSuccessResult
  XdrManageOfferSuccessResultOffer
  XdrManageSellOfferOperation
  XdrMemo

  XdrMuxedAccount
  XdrMuxedAccountMed25519
  XdrOfferEntry
  XdrOfferEntryExt
  XdrOperation
  XdrOperationBody
  XdrOperationMeta
  XdrOperationMetaV2
  XdrOperationResult
  XdrOperationResultTr
  XdrPathPaymentResultSuccess
  XdrPathPaymentStrictReceiveOperation
  XdrPathPaymentStrictReceiveResult
  XdrPathPaymentStrictSendOperation
  XdrPathPaymentStrictSendResult
  XdrPaymentResult

  XdrPreconditions
  XdrPreconditionsV2
  XdrRestoreFootprintResult
  XdrRevokeSponsorshipOperation
  XdrRevokeSponsorshipResult

  XdrSCAddress
  XdrSCContractInstance
  XdrSCEnvMetaEntry
  XdrSCEnvMetaKind
  XdrSCError
  XdrSCMetaEntry
  XdrSCMetaKind
  XdrSCMetaV0
  XdrSCSpecEntry
  XdrSCSpecEntryKind
  XdrSCSpecEventDataFormat
  XdrSCSpecEventParamLocationV0
  XdrSCSpecEventParamV0
  XdrSCSpecEventV0
  XdrSCSpecFunctionInputV0
  XdrSCSpecFunctionV0
  XdrSCSpecTypeDef
  XdrSCSpecTypeTuple
  XdrSCSpecUDTEnumCaseV0
  XdrSCSpecUDTEnumV0
  XdrSCSpecUDTErrorEnumCaseV0
  XdrSCSpecUDTErrorEnumV0
  XdrSCSpecUDTStructFieldV0
  XdrSCSpecUDTStructV0
  XdrSCSpecUDTUnionCaseTupleV0
  XdrSCSpecUDTUnionCaseV0
  XdrSCSpecUDTUnionCaseV0Kind
  XdrSCSpecUDTUnionCaseVoidV0
  XdrSCSpecUDTUnionV0
  XdrSCVal
  XdrSetOptionsOperation
  XdrSetOptionsResult
  XdrSetTrustLineFlagsOperation
  XdrSetTrustLineFlagsResult
  XdrSignedPayload
  XdrSignerKey
  XdrSignerKeyType
  XdrSequenceNumber
  XdrSorobanAuthorizationEntry
  XdrSorobanAuthorizedFunction
  XdrSorobanAuthorizedFunctionType
  XdrSorobanCredentials
  XdrSorobanCredentialsType
  XdrSorobanResourcesExtV0
  XdrSorobanTransactionData
  XdrSorobanTransactionDataExt
  XdrSorobanTransactionMeta
  XdrSorobanTransactionMetaExt
  XdrSorobanTransactionMetaExtV1
  XdrSorobanTransactionMetaV2
  XdrTimeBounds
  XdrTransaction
  XdrTransactionEnvelope
  XdrTransactionEvent
  XdrTransactionEventStage
  XdrTransactionExt
  XdrTransactionMeta
  XdrTransactionMetaV1
  XdrTransactionMetaV2
  XdrTransactionMetaV3
  XdrTransactionMetaV4
  XdrTransactionResult
  XdrTransactionResultExt
  XdrTransactionResultResult
  XdrTransactionV0
  XdrTransactionV0Ext
  XdrTrustLineEntry
  XdrTrustLineEntryExt
  XdrTrustLineEntryExtensionV2
  XdrTrustLineEntryV1
  XdrTrustLineEntryV1Ext
  XdrTrustLineFlags
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
