# frozen_string_literal: true

# Maps enum member names and union arm names where the PHP SDK uses a different
# constant name than the XDR specification defines.
#
# Structure:
#   MEMBER_OVERRIDES = {
#     "<PhpClassName>" => {
#       "<xdr_member_name>" => "<php_member_name>",
#     },
#   }
#
# Populated incrementally during batch processing.

MEMBER_OVERRIDES = {
  # Batch 1: Simple enums — SDK strips the type prefix from constant names
  "XdrClaimantType" => {
    "CLAIMANT_TYPE_V0" => "V0",
  },
  "XdrClaimPredicateType" => {
    "CLAIM_PREDICATE_UNCONDITIONAL" => "UNCONDITIONAL",
    "CLAIM_PREDICATE_AND" => "AND",
    "CLAIM_PREDICATE_OR" => "OR",
    "CLAIM_PREDICATE_NOT" => "NOT",
    "CLAIM_PREDICATE_BEFORE_ABSOLUTE_TIME" => "BEFORE_ABSOLUTE_TIME",
    "CLAIM_PREDICATE_BEFORE_RELATIVE_TIME" => "BEFORE_RELATIVE_TIME",
  },
  "XdrPreconditionType" => {
    "PRECOND_NONE" => "NONE",
    "PRECOND_TIME" => "TIME",
    "PRECOND_V2" => "V2",
  },
  "XdrRevokeSponsorshipType" => {
    "REVOKE_SPONSORSHIP_LEDGER_ENTRY" => "LEDGER_ENTRY",
    "REVOKE_SPONSORSHIP_SIGNER" => "SIGNER",
  },

  # Batch 2: Individual overrides for constants that don't follow prefix-strip
  "XdrClawbackResultCode" => {
    "CLAWBACK_NOT_CLAWBACK_ENABLED" => "NOT_ENABLED",
  },
  "XdrCreateAccountResultCode" => {
    "CREATE_ACCOUNT_ALREADY_EXIST" => "ACCOUNT_ALREADY_EXIST",
  },
}.freeze

# ---------------------------------------------------------------------------
# MEMBER_PREFIX_STRIP
# Maps PHP class names to the XDR constant prefix that the SDK strips.
# Applied in resolve_member_name() when no individual MEMBER_OVERRIDE exists.
# ---------------------------------------------------------------------------
MEMBER_PREFIX_STRIP = {
  # Batch 2: Result codes and simple type enums
  "XdrAccountMergeResultCode" => "ACCOUNT_MERGE_",
  "XdrAllowTrustResultCode" => "ALLOW_TRUST_",
  "XdrBeginSponsoringFutureReservesResultCode" => "BEGIN_SPONSORING_FUTURE_RESERVES_",
  "XdrBumpSequenceResultCode" => "BUMP_SEQUENCE_",
  "XdrChangeTrustResultCode" => "CHANGE_TRUST_",
  "XdrClaimAtomType" => "CLAIM_ATOM_TYPE_",
  "XdrClaimClaimableBalanceResultCode" => "CLAIM_CLAIMABLE_BALANCE_",
  "XdrClawbackClaimableBalanceResultCode" => "CLAWBACK_CLAIMABLE_BALANCE_",
  "XdrClawbackResultCode" => "CLAWBACK_",
  "XdrCreateAccountResultCode" => "CREATE_ACCOUNT_",
  "XdrCreateClaimableBalanceResultCode" => "CREATE_CLAIMABLE_BALANCE_",
  "XdrEndSponsoringFutureReservesResultCode" => "END_SPONSORING_FUTURE_RESERVES_",
  "XdrInflationResultCode" => "INFLATION_",
  "XdrLiquidityPoolDepositResultCode" => "LIQUIDITY_POOL_DEPOSIT_",
  "XdrLiquidityPoolWithdrawResultCode" => "LIQUIDITY_POOL_WITHDRAW_",
  "XdrManageDataResultCode" => "MANAGE_DATA_",
  "XdrManageOfferResultCode" => "MANAGE_SELL_OFFER_",
  "XdrOperationResultCode" => "op",
  "XdrPathPaymentStrictReceiveResultCode" => "PATH_PAYMENT_STRICT_RECEIVE_",
  "XdrPathPaymentStrictSendResultCode" => "PATH_PAYMENT_STRICT_SEND_",
  "XdrPaymentResultCode" => "PAYMENT_",
  "XdrRevokeSponsorshipResultCode" => "REVOKE_SPONSORSHIP_",
  "XdrSetOptionsResultCode" => "SET_OPTIONS_",
  "XdrSetTrustLineFlagsResultCode" => "SET_TRUST_LINE_FLAGS_",
  "XdrTransactionResultCode" => "tx",
}.freeze
