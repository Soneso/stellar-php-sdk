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
  # Example (disabled):
  # "XdrSignerKey" => {
  #   "ed25519SignedPayload" => "signedPayload",
  # },
}.freeze

# ---------------------------------------------------------------------------
# Field type overrides
# ---------------------------------------------------------------------------
FIELD_TYPE_OVERRIDES = {
  # XdrOfferEntry.offerID is represented as a phpseclib3 BigInteger in the
  # PHP SDK rather than a plain int64.
  "XdrOfferEntry" => { "offerID" => "BigInteger" },

  # More BigInteger and other type overrides will be added during Phase 2 audit.
}.freeze
