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
# Phase 2 note: This is a skeleton. A full audit of all enum and union members
# across all 309 types will be performed in Phase 2 to populate this map.

MEMBER_OVERRIDES = {
  # Example (disabled):
  # "XdrMemoType" => {
  #   "MEMO_NONE" => "MEMO_TYPE_NONE",
  # },
}.freeze
