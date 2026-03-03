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
}.freeze
