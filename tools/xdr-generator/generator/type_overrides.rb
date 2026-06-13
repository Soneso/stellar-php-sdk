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
# Entries are added when a typedef's default resolution does not match the
# type the existing PHP SDK exposes for that name.
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
  "XdrSignatureHint" => "string",
  "XdrSignature" => "string",
  "XdrThresholds" => "string",

  # String typedefs — SDK uses string
  "XdrString64" => "string",
  "XdrString32" => "string",
  "XdrString256" => "string",
  "XdrString1000" => "string",

  # Symbol typedef — SDK uses string
  "XdrSCSymbol" => "string",

  # Hash-based typedefs — SDK uses string (binary)
  "XdrContractID" => "string",

  # Opaque/string typedefs — SDK uses string (binary or text)
  "XdrSCBytes" => "string",   # typedef opaque SCBytes<>
  "XdrSCString" => "string",  # typedef string SCString<SCVAL_LIMIT>
  "XdrDataValue" => "string", # typedef opaque DataValue<64>

  # Typedef-array — SDK inlines as array (no wrapper class)
  "XdrLedgerEntryChanges" => "array",
  "XdrSCMap" => "array",
  "XdrSCVec" => "array",      # typedef SCVal SCVec<>
}.freeze

# ---------------------------------------------------------------------------
# BASE_WRAPPER_TYPES
# Types that generate *Base.php files. The hand-written wrapper extends the
# base. Listed here when a hand-maintained wrapper provides factory methods
# or other helpers that cannot be derived from the XDR spec alone.
# ---------------------------------------------------------------------------
BASE_WRAPPER_TYPES = %w[
  XdrAccountID
  XdrClaimableBalanceID
  XdrContractExecutable
  XdrContractIDPreimage
  XdrDecoratedSignature
  XdrHostFunction
  XdrLedgerKey
  XdrLedgerKeyAccount
  XdrMuxedAccount
  XdrMuxedAccountMed25519
  XdrSCAddress
  XdrSCVal
  XdrSorobanAuthorizedFunction
  XdrSorobanCredentials
  XdrTransaction
  XdrSCSpecEntry
  XdrSCSpecTypeDef
  XdrSignerKeyType
  XdrAllowTrustOperationAsset
  XdrTransactionEnvelope
  XdrConfigUpgradeSetKey
  XdrLiquidityPoolDepositOperation
  XdrLiquidityPoolWithdrawOperation
  XdrAssetAlphaNum4
  XdrAssetAlphaNum12
  XdrChangeTrustOperation
  XdrInnerTransactionResultPair
  XdrTimeBounds
  XdrOperationResultTr
  XdrManageDataOperation
  XdrTransactionV0
  XdrChangeTrustAsset
  XdrTrustlineAsset
  XdrSCSpecUDTUnionCaseV0
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
  XdrDataValue
].freeze

# ---------------------------------------------------------------------------
# RECURSIVE_STRUCT_TYPES
# Struct types whose XDR decode method recurses into itself (directly or via a
# field of the same type). The generator wraps the decode body with
# XdrBuffer::enterRecursion / leaveRecursion so that hostile deeply-nested data
# received from the network cannot overflow the PHP call stack.
#
# Each entry triggers an enterRecursion() at the start of decode() and a
# leaveRecursion() in a finally block before the return. The cap is
# XdrBuffer::RECURSION_LIMIT (128).
# ---------------------------------------------------------------------------
RECURSIVE_STRUCT_TYPES = %w[
  XdrSorobanDelegateSignature
].freeze

# ---------------------------------------------------------------------------
# EXTENSION_POINT_FIELDS
# Maps struct names to field names that are void-only extension unions.
# These are simplified to `public int $fieldName = 0` instead of full unions.
# Entries are added when a struct introduces such a void-only extension.
# ---------------------------------------------------------------------------
EXTENSION_POINT_FIELDS = {
  # Batch 16: Struct fields that are void-only ext unions, simplified to int
  "XdrClaimableBalanceEntryExtV1" => ["ext"],
  "XdrTrustLineEntryExtensionV2" => ["ext"],
}.freeze
