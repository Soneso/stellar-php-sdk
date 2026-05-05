# frozen_string_literal: true

# Per-field SEP-51 (XDR-JSON) overrides.
#
# Maps `(parent_type, field_name)` pairs to override specs that change the
# generator's default toJsonValue / fromJsonValue emission for a single field
# of a single parent type. Used for typedef-collapsed identifiers where the
# typedef itself does not exist as a PHP class but the JSON contract diverges
# from the field's nominal PHP type.
#
# This registry is distinct from the pre-existing `field_overrides.rb` (which
# holds TxRep-related field-name and field-type remapping) and must not be
# merged with it. The Ruby constant exported by this file is
# `SEP51_FIELD_OVERRIDES` to avoid collision with `FIELD_OVERRIDES` from the
# TxRep file.
#
# Override-spec shapes:
#
#   { strkey: :liquidity_pool | :contract | :claimable_balance,
#     encoding: :hex | :raw }
#       Emit a StrKey encode/decode call. The :hex variant uses the hex-form
#       StrKey method (encodeXxxIdHex / decodeXxxIdHex) and assumes the
#       field's runtime storage form is a 64-char hex string. The :raw
#       variant uses the binary-form method (encodeXxxId / decodeXxxId) and
#       assumes the field's runtime storage form is a raw 32-byte buffer.
#       The storage form is verified per site in
#       `tools/baselines/sep-51-wrapper-storage-audit.md`.
#
#   { asset_code: 4 | 12 }
#       Emit AssetCode4 / AssetCode12 trim-pad-escape semantics inline at
#       the consuming-field site. There is no XdrAssetCode class; the
#       typedef collapses to PHP `string` and SEP-51 emits a single
#       string-shaped JSON value at every consuming site.
#
#   { proc: { to: ->(php_field_expr) {...}, from: ->(value_var) {...} } }
#       Escape hatch for cases the standard kinds cannot handle. The lambdas
#       receive the PHP field-access expression (e.g. "$this->fieldName")
#       and must return the PHP source string the generator emits.
#
# Per-site storage-form audit rules and the eight strkey rows are pinned in
# `tools/baselines/sep-51-wrapper-storage-audit.md`. Any change to a Cat-B
# wrapper's decode path that alters a field's storage form requires the
# audit document and this registry to be updated together.

SEP51_FIELD_OVERRIDES = {
  # PoolID -> L-strkey (7 sites verified by reading Soneso/StellarSDK/Xdr/).
  # XdrLedgerKey.liquidityPoolID is intentionally absent: the LIQUIDITY_POOL
  # arm of XdrLedgerKey delegates to XdrLedgerKeyLiquidityPool whose
  # liquidityPoolID is registered above.
  ['XdrLiquidityPoolDepositOperationBase',  'liquidityPoolID']  => { strkey: :liquidity_pool, encoding: :hex },
  ['XdrLiquidityPoolWithdrawOperationBase', 'liquidityPoolID']  => { strkey: :liquidity_pool, encoding: :hex },
  ['XdrTrustlineAssetBase',                 'liquidityPoolID']  => { strkey: :liquidity_pool, encoding: :raw },
  ['XdrLedgerKeyLiquidityPool',             'liquidityPoolID']  => { strkey: :liquidity_pool, encoding: :raw },
  ['XdrLiquidityPoolEntry',                 'liquidityPoolID']  => { strkey: :liquidity_pool, encoding: :raw },
  ['XdrClaimLiquidityAtom',                 'liquidityPoolID']  => { strkey: :liquidity_pool, encoding: :raw },
  ['XdrHashIDPreimageRevokeID',             'liquidityPoolID']  => { strkey: :liquidity_pool, encoding: :raw },

  # ContractID -> C-strkey (one site outside SCAddress; chosen divergence
  # from py-stellar-base which renders ContractID as a hex Hash at this
  # site. Documented in the SEP-51 divergence catalogue.)
  ['XdrConfigUpgradeSetKeyBase',            'contractID']       => { strkey: :contract, encoding: :hex },

  # AssetCode4 / AssetCode12 trim-pad-escape inline at consuming-field sites.
  # XdrAssetCode does not exist as a class; both consuming-struct sites
  # (XdrAssetAlphaNum4Base.assetCode and XdrAssetAlphaNum12Base.assetCode,
  # plus the two XdrAllowTrustOperationAssetBase fields) emit a bare string
  # rather than a sub-object envelope.
  ['XdrAssetAlphaNum4Base',                 'assetCode']        => { asset_code: 4 },
  ['XdrAssetAlphaNum12Base',                'assetCode']        => { asset_code: 12 },
  ['XdrAllowTrustOperationAssetBase',       'assetCode4']       => { asset_code: 4 },
  ['XdrAllowTrustOperationAssetBase',       'assetCode12']      => { asset_code: 12 },
}.freeze
