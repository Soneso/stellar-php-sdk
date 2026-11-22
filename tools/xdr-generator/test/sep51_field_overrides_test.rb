# frozen_string_literal: true

require 'minitest/autorun'
require 'xdrgen'
require_relative '../generator/sep51_field_overrides'
require_relative '../generator/stellar_json_overrides'
require_relative '../generator/generator'

# Tests for the SEP-51 per-field override registry.
#
# Two test categories:
#   1. Generation tests — assert the PHP code emitted for each override
#      kind (`:strkey :liquidity_pool`, `:strkey :contract`,
#      `:strkey :claimable_balance`, `:asset_code 4`, `:asset_code 12`,
#      `:proc`) matches the literal expected PHP source, byte for byte.
#   2. Round-trip tests — for each registered (parent_type, field_name),
#      build a hand-written XDR fixture, encode through the SEP-51 to-JSON
#      path, decode through the from-JSON path, assert the round-trip
#      reproduces the original value.
#
# The round-trip integration tests are exercised on the PHP side via
# StellarSpecificTypesTest.php; this Ruby test file focuses on the
# pure-codegen surface and registry-shape integrity.

class Sep51FieldOverridesTest < Minitest::Test
  def setup
    @gen = Generator.new(nil, nil)
  end

  # ------------------------------------------------------------------
  # Registry shape integrity
  # ------------------------------------------------------------------

  def test_registry_is_a_frozen_hash
    assert SEP51_FIELD_OVERRIDES.is_a?(Hash), 'SEP51_FIELD_OVERRIDES must be a Hash'
    assert SEP51_FIELD_OVERRIDES.frozen?, 'SEP51_FIELD_OVERRIDES must be frozen'
  end

  def test_registry_has_exactly_twelve_entries
    # Registry is exhaustive: 8 strkey rows + 4 asset_code rows.
    assert_equal 12, SEP51_FIELD_OVERRIDES.size,
                 "SEP51_FIELD_OVERRIDES must contain exactly 12 entries"
  end

  def test_registry_keys_are_two_string_arrays
    SEP51_FIELD_OVERRIDES.each_key do |key|
      assert_equal Array, key.class, "Key #{key.inspect} must be an Array"
      assert_equal 2, key.size, "Key #{key.inspect} must have exactly 2 elements"
      assert key.all? { |s| s.is_a?(String) }, "Key #{key.inspect} elements must be strings"
    end
  end

  def test_registry_strkey_values_name_only_a_known_kind
    SEP51_FIELD_OVERRIDES.each_value do |spec|
      next unless spec.key?(:strkey)
      assert_equal [:strkey], spec.keys, "strkey override takes only its kind: #{spec.inspect}"
      assert [:liquidity_pool, :contract, :claimable_balance].include?(spec[:strkey]),
             "Unknown strkey kind: #{spec[:strkey].inspect}"
    end
  end

  def test_registry_asset_code_values_have_width_four_or_twelve
    SEP51_FIELD_OVERRIDES.each_value do |spec|
      next unless spec.key?(:asset_code)
      assert [4, 12].include?(spec[:asset_code]),
             "asset_code width must be 4 or 12; got #{spec[:asset_code].inspect}"
    end
  end

  # ------------------------------------------------------------------
  # Generation tests — emitted PHP source per override kind
  # ------------------------------------------------------------------

  def test_strkey_liquidity_pool_raw_to_json_emits_raw_encoder
    override = { strkey: :liquidity_pool }
    php = @gen.send(:field_override_to_json_expr, override, '$this->liquidityPoolID')
    assert_equal 'StrKey::encodeLiquidityPoolId($this->liquidityPoolID)', php
  end

  def test_strkey_contract_raw_to_json_emits_raw_encoder
    override = { strkey: :contract }
    php = @gen.send(:field_override_to_json_expr, override, '$this->contractID')
    assert_equal 'StrKey::encodeContractId($this->contractID)', php
  end

  def test_strkey_claimable_balance_raw_to_json_emits_raw_encoder
    override = { strkey: :claimable_balance }
    php = @gen.send(:field_override_to_json_expr, override, '$this->balanceId')
    assert_equal 'StrKey::encodeClaimableBalanceId($this->balanceId)', php
  end

  def test_asset_code_4_to_json_emits_rtrim_then_escape
    override = { asset_code: 4 }
    php = @gen.send(:field_override_to_json_expr, override, '$this->assetCode')
    assert_equal "XdrJsonHelper::escapeString(rtrim(XdrEncoder::checkFixedLength($this->assetCode, 4, true), \"\\x00\"))", php
  end

  def test_asset_code_12_to_json_emits_trim_pad_to_5_then_escape
    override = { asset_code: 12 }
    php = @gen.send(:field_override_to_json_expr, override, '$this->assetCode')
    # The emitted body is a static IIFE that checks the raw width (at most
    # 12 bytes) before trimming, applies the AssetCode4-vs-AssetCode12
    # distinguishability pad, and escapes. An all-NUL code serialises as
    # five escaped NULs via the pad branch, matching the rs-stellar-xdr
    # reference.
    assert_includes php, 'rtrim(XdrEncoder::checkFixedLength($bytes, 12, true), "\\x00")'
    assert_includes php, 'str_pad($trimmed, 5, "\\x00", STR_PAD_RIGHT)'
    assert_includes php, 'XdrJsonHelper::escapeString($trimmed)'
    refute_includes php, 'throw', 'the emit path must serialise every AssetCode12 up to 12 bytes, including all-NUL'
  end

  def test_proc_kind_dispatches_to_lambda
    override = {
      proc: {
        to: ->(accessor) { "transformTo(#{accessor})" },
        from: ->(json) { "transformFrom(#{json})" },
      },
    }
    php_to = @gen.send(:field_override_to_json_expr, override, '$this->fieldName')
    assert_equal 'transformTo($this->fieldName)', php_to
  end

  # ------------------------------------------------------------------
  # From-side parser emission per kind
  # ------------------------------------------------------------------

  def test_strkey_liquidity_pool_from_json_checks_the_string_then_decodes
    out = StringIO.new
    override = { strkey: :liquidity_pool }
    @gen.send(:render_field_override_from_json, out, override, '$x', '$value["liquidity_pool_id"]', '        ')
    rendered = out.string
    assert_includes rendered, 'StrKey::decodeLiquidityPoolId($value["liquidity_pool_id"])'
    assert_includes rendered, 'if (!is_string($value["liquidity_pool_id"]))'
  end

  def test_strkey_contract_raw_from_json_emits_raw_decoder
    out = StringIO.new
    override = { strkey: :contract }
    @gen.send(:render_field_override_from_json, out, override, '$x', '$value["contract_id"]', '        ')
    rendered = out.string
    assert_includes rendered, 'StrKey::decodeContractId($value["contract_id"])'
  end

  def test_asset_code_4_from_json_emits_unescape_pad_reject_too_long
    out = StringIO.new
    override = { asset_code: 4 }
    @gen.send(:render_field_override_from_json, out, override, '$assetCode', '$value["asset_code"]', '        ')
    rendered = out.string
    assert_includes rendered, 'XdrJsonHelper::unescapeString($value["asset_code"])'
    assert_includes rendered, 'str_pad($decoded, 4, "\\x00", STR_PAD_RIGHT)'
    assert_includes rendered, 'AssetCode4 must not exceed 4 bytes'
  end

  def test_asset_code_12_from_json_emits_unescape_pad_reject_le_4_or_gt_12
    out = StringIO.new
    override = { asset_code: 12 }
    @gen.send(:render_field_override_from_json, out, override, '$assetCode', '$value["asset_code"]', '        ')
    rendered = out.string
    assert_includes rendered, 'XdrJsonHelper::unescapeString($value["asset_code"])'
    assert_includes rendered, 'str_pad($decoded, 12, "\\x00", STR_PAD_RIGHT)'
    assert_includes rendered, 'AssetCode12 must exceed 4 bytes'
    assert_includes rendered, 'AssetCode12 must not exceed 12 bytes'
  end

  # ------------------------------------------------------------------
  # Override-precedence-chain enforcement
  # ------------------------------------------------------------------

  def test_struct_wrapper_uses_base_field_overrides_and_wrapper_error_name
    registry = SEP51_FIELD_OVERRIDES
    Object.send(:remove_const, :SEP51_FIELD_OVERRIDES)
    Object.const_set(:SEP51_FIELD_OVERRIDES, registry.merge(
      ['XdrTransactionBase', 'fee'] => {
        proc: { to: ->(value) { "probeTo(#{value})" }, from: ->(value) { "probeFrom(#{value})" } }
      }
    ).freeze)
    field = { name: 'fee', xdr_name: 'fee', php_type: 'int', decl: nil,
              is_optional: false, is_array: false, is_ext_point: false }
    out = StringIO.new
    @gen.send(:render_struct_sep51_methods, out, 'XdrTransaction', 'XdrTransactionBase', [field])
    assert out.string.include?('probeTo($this->fee)'), 'Base-keyed override must apply to the JSON writer'
    assert out.string.include?("probeFrom($value['fee'])"), 'Base-keyed override must apply to the JSON reader'
    assert_includes out.string, 'Expected object for XdrTransaction JSON value'
  ensure
    Object.send(:remove_const, :SEP51_FIELD_OVERRIDES)
    Object.const_set(:SEP51_FIELD_OVERRIDES, registry)
  end

  def test_strkey_encoder_method_names_the_binary_method_per_kind_and_direction
    assert_equal 'encodeLiquidityPoolId', @gen.send(:strkey_encoder_method, :liquidity_pool, :encode)
    assert_equal 'decodeContractId', @gen.send(:strkey_encoder_method, :contract, :decode)
    assert_equal 'encodeClaimableBalanceId', @gen.send(:strkey_encoder_method, :claimable_balance, :encode)
    assert_equal 'decodeClaimableBalanceId', @gen.send(:strkey_encoder_method, :claimable_balance, :decode)
  end

  def test_strkey_encoder_method_rejects_unknown_kind
    assert_raises(RuntimeError) do
      @gen.send(:strkey_encoder_method, :unknown_kind, :encode)
    end
  end

  # ------------------------------------------------------------------
  # All nine strkey rows hit the registered field-override path
  # ------------------------------------------------------------------

  def test_all_nine_strkey_entries_resolve_to_a_strkey_override
    strkey_entries = SEP51_FIELD_OVERRIDES.select { |_, spec| spec.key?(:strkey) }
    assert_equal 9, strkey_entries.size,
                 'Expected exactly 9 strkey field-override entries (7 PoolID + 2 ContractID)'
  end

  def test_both_asset_code_entries_resolve_to_an_asset_code_override
    # The AllowTrust AssetCode union is handled whole-body by
    # StellarJsonOverrides, so only the two AlphaNum struct sites carry
    # per-field asset_code entries.
    ac_entries = SEP51_FIELD_OVERRIDES.select { |_, spec| spec.key?(:asset_code) }
    assert_equal 2, ac_entries.size,
                 'Expected exactly 2 asset_code field-override entries'
  end
end
