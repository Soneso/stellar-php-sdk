# frozen_string_literal: true

# Field name and field type overrides for generated PHP XDR classes.
#
# FIELD_OVERRIDES remaps a field's property name when the PHP SDK uses a
# different name than the XDR specification.
#
# FIELD_TYPE_OVERRIDES substitutes the PHP type used for a specific field when
# the generated default type differs from the existing SDK implementation (e.g.
# when phpseclib3 BigInteger is used instead of a plain int for int64 fields).
#
# Structure for both maps:
#   "<PhpClassName>" => {
#     "<fieldName>" => "<overrideValue>",
#   }
#
# Phase 2 note: These are skeletons. A full field-by-field audit will be
# performed in Phase 2 to populate both maps completely.

# ---------------------------------------------------------------------------
# Field name overrides
# ---------------------------------------------------------------------------
FIELD_OVERRIDES = {
  # Batch 3: Field name differences between XDR spec and PHP SDK
  "XdrClaimOfferAtom" => {
    "sellerID" => "accountId",
    "offerID" => "offerId",
  },
  "XdrRevokeSponsorshipSigner" => {
    "accountID" => "accountId",
  },
  "XdrConfigSettingContractLedgerCostV0" => {
    "ledgerMaxDiskReadEntries" => "ledgerMaxDiskReadLedgerEntries",
  },
  "XdrInt256Parts" => {
    "hi_hi" => "hiHi",
    "hi_lo" => "hiLo",
    "lo_hi" => "loHi",
    "lo_lo" => "loLo",
  },
  "XdrUInt256Parts" => {
    "hi_hi" => "hiHi",
    "hi_lo" => "hiLo",
    "lo_hi" => "loHi",
    "lo_lo" => "loLo",
  },

  # Batch 5: Reserved-word field name preserved (PHP allows property names like $function)
  "XdrSorobanAuthorizedInvocation" => {
    "function" => "function",
  },

  # Batch 6: Field name differences between XDR spec and PHP SDK
  "XdrAllowTrustOperation" => {
    "authorize" => "authorized",
  },
  "XdrSetTrustLineFlagsOperation" => {
    "trustor" => "accountID",
  },
  "XdrSCMetaV0" => {
    "val" => "value",
  },

  # Batch 9: Field name differences for typedef array fields
  "XdrOperationMeta" => {
    "changes" => "ledgerEntryChanges",
  },
  "XdrTransactionMetaV1" => {
    "txChanges" => "ledgerEntryChanges",
  },

  # Batch 10+11: Union discriminant name overrides (XDR spec: "code", PHP SDK: "resultCode")
  "XdrClawbackResult" => { "code" => "resultCode" },
  "XdrClawbackClaimableBalanceResult" => { "code" => "resultCode" },
  "XdrRevokeSponsorshipResult" => { "code" => "resultCode" },
  "XdrAllowTrustResult" => { "code" => "resultCode" },
  "XdrBeginSponsoringFutureReservesResult" => { "code" => "resultCode" },
  "XdrBumpSequenceResult" => { "code" => "resultCode" },
  "XdrChangeTrustResult" => { "code" => "resultCode" },
  "XdrClaimClaimableBalanceResult" => { "code" => "resultCode" },
  "XdrCreateAccountResult" => { "code" => "resultCode" },
  "XdrEndSponsoringFutureReservesResult" => { "code" => "resultCode" },
  "XdrLiquidityPoolDepositResult" => { "code" => "resultCode" },
  "XdrLiquidityPoolWithdrawResult" => { "code" => "resultCode" },
  "XdrPaymentResult" => { "code" => "resultCode" },
  "XdrSetOptionsResult" => { "code" => "resultCode" },
  "XdrSetTrustLineFlagsResult" => { "code" => "resultCode" },

  # Batch 12: Union discriminant name overrides
  "XdrExtensionPoint" => { "v" => "discriminant" },
  "XdrAccountMergeResult" => { "code" => "resultCode" },
  "XdrInvokeHostFunctionResult" => { "code" => "type" },

  # Batch 13: Field name overrides for operations
  "XdrManageBuyOfferOperation" => {
    "buyAmount" => "amount",
    "offerID" => "offerId",
  },
  "XdrManageSellOfferOperation" => {
    "offerID" => "offerId",
  },

  # Batch 13: Union discriminant name overrides
  "XdrFeeBumpTransactionExt" => { "v" => "discriminant" },
  "XdrTransactionV0Ext" => { "v" => "discriminant" },

  # Batch 14: Union discriminant name overrides + field renames
  "XdrDataEntryExt" => { "v" => "discriminant" },
  "XdrOfferEntryExt" => { "v" => "discriminant" },
  "XdrLedgerEntryV1Ext" => { "v" => "discriminant" },
  "XdrTransactionExt" => { "v" => "discriminant", "sorobanData" => "sorobanTransactionData" },
  "XdrSorobanTransactionDataExt" => { "v" => "discriminant" },
}.freeze

# ---------------------------------------------------------------------------
# Field type overrides
# ---------------------------------------------------------------------------
FIELD_TYPE_OVERRIDES = {
  # Batch 3: BigInteger fields — the PHP SDK uses phpseclib3 BigInteger
  # instead of plain int for certain int64 fields (amounts, balances, etc.)
  "XdrOfferEntry" => { "offerID" => "BigInteger" },
  "XdrInflationPayout" => { "amount" => "BigInteger" },
  "XdrCreateAccountOperation" => { "startingBalance" => "BigInteger" },
  "XdrPaymentOperation" => { "amount" => "BigInteger" },
  "XdrCreatePassiveSellOfferOperation" => { "amount" => "BigInteger" },
  "XdrClaimOfferAtom" => {
    "amountSold" => "BigInteger",
    "amountBought" => "BigInteger",
  },
  "XdrClaimOfferAtomV0" => {
    "amountSold" => "BigInteger",
    "amountBought" => "BigInteger",
  },
  "XdrClaimLiquidityAtom" => {
    "amountSold" => "BigInteger",
    "amountBought" => "BigInteger",
  },
  "XdrSimplePaymentResult" => { "amount" => "BigInteger" },
  "XdrSequenceNumber" => { "sequenceNumber" => "BigInteger" },

  # Batch 5: BigInteger fields
  "XdrLiabilities" => {
    "buying" => "BigInteger",
    "selling" => "BigInteger",
  },

  # Batch 7: BigInteger fields
  "XdrClawbackOperation" => { "amount" => "BigInteger" },
  "XdrPathPaymentStrictReceiveOperation" => {
    "sendMax" => "BigInteger",
    "destAmount" => "BigInteger",
  },
  "XdrPathPaymentStrictSendOperation" => {
    "sendAmount" => "BigInteger",
    "destMin" => "BigInteger",
  },

  # Batch 9: BigInteger fields
  "XdrCreateClaimableBalanceOperation" => { "amount" => "BigInteger" },

  # Batch 12: BigInteger fields for union arms
  "XdrAccountMergeResult" => { "sourceAccountBalance" => "BigInteger" },

  # Batch 13: BigInteger fields for operations (keys are XDR field names, before rename)
  "XdrManageBuyOfferOperation" => { "buyAmount" => "BigInteger" },
  "XdrManageSellOfferOperation" => { "amount" => "BigInteger" },

}.freeze
