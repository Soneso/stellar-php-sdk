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
#   { strkey: :liquidity_pool | :contract | :claimable_balance }
#       Emit the binary-form StrKey call (encodeXxxId / decodeXxxId). The
#       field holds the raw 32 bytes; a field in FIELD_STORAGE_OVERRIDES
#       (field_storage_overrides.rb) reaches the override as the 32 bytes
#       its resolver returns.
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

SEP51_FIELD_OVERRIDES = {
  # PoolID -> L-strkey (7 sites verified by reading Soneso/StellarSDK/Xdr/).
  # XdrLedgerKey.liquidityPoolID is intentionally absent: the LIQUIDITY_POOL
  # arm of XdrLedgerKey delegates to XdrLedgerKeyLiquidityPool whose
  # liquidityPoolID is registered above.
  ['XdrLiquidityPoolDepositOperationBase',  'liquidityPoolID']  => { strkey: :liquidity_pool },
  ['XdrLiquidityPoolWithdrawOperationBase', 'liquidityPoolID']  => { strkey: :liquidity_pool },
  ['XdrTrustlineAssetBase',                 'liquidityPoolID']  => { strkey: :liquidity_pool },
  ['XdrLedgerKeyLiquidityPool',             'liquidityPoolID']  => { strkey: :liquidity_pool },
  ['XdrLiquidityPoolEntry',                 'liquidityPoolID']  => { strkey: :liquidity_pool },
  ['XdrClaimLiquidityAtom',                 'liquidityPoolID']  => { strkey: :liquidity_pool },
  ['XdrHashIDPreimageRevokeID',             'liquidityPoolID']  => { strkey: :liquidity_pool },

  # ContractID -> C-strkey (sites outside SCAddress). XdrConfigUpgradeSetKey's
  # contract id reaches the override resolved to 32 bytes; XdrContractEvent stores the
  # optional contractID (PHP property `hash`) as raw 32 bytes. Both emit the
  # canonical C-strkey, matching rs-stellar-xdr's ContractId serialisation.
  ['XdrConfigUpgradeSetKeyBase',            'contractID']       => { strkey: :contract },
  ['XdrContractEvent',                      'hash']             => { strkey: :contract },

  # ContractExecutable wasm hash. The hand-written XdrContractExecutable
  # wrapper stores the hash as a 64-character hex string (decode applies
  # bin2hex), so the SEP-51 methods emitted on the Base must pass the stored
  # hex through unchanged in both directions; hex-encoding the storage again
  # would double-encode. Both directions defer the shape of the hexadecimal to
  # the canonical accessor ARM_STORAGE_HELPERS emits on the same class, so the
  # JSON pair and the XDR and TxRep readers hold the field to one rule. The
  # incoming JSON value is checked for being a string first, because the
  # accessor's parameter is typed and a non-string would fail as a TypeError.
  ['XdrContractExecutableBase',             'wasmIdHex']        => {
    proc: {
      to: lambda do |_accessor|
        '$this->getCanonicalWasmIdHex()'
      end,
      from: lambda do |source|
        "(static function ($v) { " \
        "if (!is_string($v)) { " \
        "throw new InvalidArgumentException('Expected string JSON value for SEP-51 field, got ' . get_debug_type($v)); } " \
        "return self::canonicalWasmIdHex($v); })(#{source})"
      end,
    },
  },

  # AssetCode4 / AssetCode12 trim-pad-escape inline at consuming-field sites.
  # XdrAssetCode does not exist as a class; both consuming-struct sites
  # (XdrAssetAlphaNum4Base.assetCode and XdrAssetAlphaNum12Base.assetCode)
  # emit a bare string rather than a sub-object envelope. The AllowTrust
  # AssetCode union is handled whole-body by StellarJsonOverrides
  # (XdrAllowTrustOperationAsset), which emits the bare length-discriminated
  # string form.
  ['XdrAssetAlphaNum4Base',                 'assetCode']        => { asset_code: 4 },
  ['XdrAssetAlphaNum12Base',                'assetCode']        => { asset_code: 12 },
}.freeze
