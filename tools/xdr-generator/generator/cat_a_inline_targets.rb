# frozen_string_literal: true

# Closed list of XDR types whose SEP-51 methods are emitted inline by the generator
# (Category A in the SEP-51 implementation's three-category model). These types
# receive Stellar-specific JSON shape that diverges from the generator's default
# emission for their syntactic shape (enum/struct/union); the inline emission
# encodes things like StrKey output, GMP integer reassembly, and prefix-stripped
# discriminant strings.
#
# The list is frozen and must be kept in sync with the baseline mirror at
# tools/baselines/sep-51-cat-a-targets.txt (consumed by the generator's entry
# gate to detect drift between this list and its committed snapshot).

CAT_A_INLINE_TARGETS = %w[
  XdrAsset
  XdrInt128Parts
  XdrInt256Parts
  XdrLedgerBounds
  XdrLiquidityPoolEntry
  XdrMemo
  XdrNodeID
  XdrPreconditions
  XdrPreconditionsV2
  XdrPublicKey
  XdrSignedPayload
  XdrSignerKey
  XdrSorobanResources
  XdrUInt128Parts
  XdrUInt256Parts
].sort.freeze
