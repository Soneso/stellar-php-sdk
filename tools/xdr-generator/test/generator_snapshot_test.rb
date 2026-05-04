require 'minitest/autorun'
require 'xdrgen'
require_relative '../generator/generator'

class GeneratorSnapshotTest < Minitest::Test
  SNAPSHOT_DIR = File.join(__dir__, 'snapshots')
  OUTPUT_DIR = File.join(__dir__, '..', '..', '..', 'Soneso', 'StellarSDK', 'Xdr')
  XDR_DIR = File.join(__dir__, '..', '..', '..', 'xdr')

  # ------------------------------------------------------------------
  # Configuration sanity checks
  # ------------------------------------------------------------------

  def test_generator_loads
    assert defined?(Generator), "Generator class must be defined"
    assert Generator < Xdrgen::Generators::Base, "Generator must extend Xdrgen::Generators::Base"
  end

  def test_skip_types_populated
    assert SKIP_TYPES.is_a?(Array), "SKIP_TYPES must be an Array"
    assert SKIP_TYPES.size > 0, "SKIP_TYPES must not be empty during development"
  end

  def test_overrides_loaded
    assert defined?(NAME_OVERRIDES), "NAME_OVERRIDES must be defined"
    assert defined?(MEMBER_OVERRIDES), "MEMBER_OVERRIDES must be defined"
    assert defined?(FIELD_OVERRIDES), "FIELD_OVERRIDES must be defined"
    assert defined?(FIELD_TYPE_OVERRIDES), "FIELD_TYPE_OVERRIDES must be defined"
    assert defined?(TYPE_OVERRIDES), "TYPE_OVERRIDES must be defined"
    assert defined?(BASE_WRAPPER_TYPES), "BASE_WRAPPER_TYPES must be defined"
  end

  # ------------------------------------------------------------------
  # Snapshot comparison tests
  # ------------------------------------------------------------------

  def test_snapshot_xdr_asset_type
    assert_snapshot_match("XdrAssetType.php")
  end

  def test_snapshot_xdr_price
    assert_snapshot_match("XdrPrice.php")
  end

  def test_snapshot_xdr_asset
    assert_snapshot_match("XdrAsset.php")
  end

  def test_snapshot_xdr_sc_val_base
    assert_snapshot_match("XdrSCValBase.php")
  end

  def test_snapshot_xdr_account_entry_v1_ext
    assert_snapshot_match("XdrAccountEntryV1Ext.php")
  end

  def test_snapshot_xdr_ledger_entry_data
    assert_snapshot_match("XdrLedgerEntryData.php")
  end

  def test_snapshot_xdr_transaction_result_result
    assert_snapshot_match("XdrTransactionResultResult.php")
  end

  def test_snapshot_xdr_claimable_balance_entry_ext_v1
    assert_snapshot_match("XdrClaimableBalanceEntryExtV1.php")
  end

  # ------------------------------------------------------------------
  # SEP-51 emission AST-shape assertions
  #
  # The snapshot_match comparison above already pins the exact byte-form of
  # the emitted file. The assertions below provide phase-targeted regression
  # signals: they fail with a clear message when the SEP-51 method bodies
  # drift in shape independently of the broader file diff. Each generated
  # file under inspection is read live from the OUTPUT_DIR (the regenerated
  # source under Soneso/StellarSDK/Xdr/), not from the snapshot directory.
  # ------------------------------------------------------------------

  SEP51_INVARIANT_DEFAULT_THROW =
    /default\s*=>\s*throw\s+new\s+\\?InvalidArgumentException/.freeze

  SEP51_TO_JSON_VALUE_SIGNATURE =
    /public\s+function\s+toJsonValue\(\)\s*:\s*string/.freeze

  SEP51_FROM_JSON_VALUE_SIGNATURE =
    /public\s+static\s+function\s+fromJsonValue\(mixed\s+\$value\)\s*:\s*static/.freeze

  SEP51_TO_JSON_FACADE_BODY = /JSON_THROW_ON_ERROR\s*\|\s*JSON_UNESCAPED_SLASHES\s*\|\s*JSON_UNESCAPED_UNICODE/.freeze

  def test_sep51_xdr_asset_type_emits_required_methods
    assert_sep51_enum_shape("XdrAssetType.php", expected_arms: %w[
      native credit_alphanum4 credit_alphanum12 pool_share
    ])
  end

  def test_sep51_xdr_memo_type_emits_required_methods
    assert_sep51_enum_shape("XdrMemoType.php", expected_arms: %w[
      none text id hash return
    ])
  end

  def test_sep51_xdr_sc_val_type_emits_required_methods
    assert_sep51_enum_shape("XdrSCValType.php", expected_arms: %w[
      bool void error u32 i32 u64 i64 timepoint duration
      u128 i128 u256 i256 bytes string symbol vec map address
      contract_instance ledger_key_contract_instance ledger_key_nonce
    ])
  end

  def test_sep51_xdr_operation_result_code_emits_bare_member_names
    # Documented divergence from py-stellar-base: PHP emits the bare
    # lowercase identifier without the `op` prefix that py retains
    # (py renders "opinner" / "opbad_auth" etc; PHP renders "inner" /
    # "bad_auth"; see tools/baselines/sep-51-divergence-catalogue.md).
    assert_sep51_enum_shape("XdrOperationResultCode.php", expected_arms: %w[
      inner bad_auth no_account not_supported too_many_subentries
      exceeded_work_limit too_many_sponsoring
    ])
  end

  def test_sep51_xdr_claimable_balance_id_type_single_member
    # Single-member edge case: there is no other entry to share tokens with,
    # so the longest shared prefix is empty and the wire form is the full
    # lowercase snake_case identifier. Verified against py-stellar-base
    # v14.0.0 (CLAIMABLE_BALANCE_ID_TYPE_V0 -> "claimable_balance_id_type_v0").
    assert_sep51_enum_shape(
      "XdrClaimableBalanceIDType.php",
      expected_arms: %w[claimable_balance_id_type_v0]
    )
  end

  def test_sep51_signer_key_type_skipped_on_wrapper
    # XdrSignerKeyType is in BASE_WRAPPER_TYPES and is therefore owned by
    # the Stellar-specific phase that emits on the *Base.php class. The
    # wrapper file must not carry duplicate SEP-51 methods.
    path = File.join(OUTPUT_DIR, "XdrSignerKeyType.php")
    assert File.exist?(path), "expected #{path} to exist; check generator output cwd"
    contents = File.read(path)
    refute_match SEP51_TO_JSON_VALUE_SIGNATURE, contents,
      "XdrSignerKeyType is a Cat-B wrapper; SEP-51 must not be emitted on the wrapper"
    refute_match SEP51_FROM_JSON_VALUE_SIGNATURE, contents,
      "XdrSignerKeyType is a Cat-B wrapper; SEP-51 must not be emitted on the wrapper"
  end

  def test_sep51_emitted_for_camelcase_const_enums
    # XdrIPAddrType and XdrContractCostType use CamelCase constant names
    # (e.g. IPv4, IPv6, WasmInsnExec). The tokenizer splits on underscores
    # only, so each CamelCase identifier becomes a single lowercased token
    # and produces the same wire form as py-stellar-base v14.0.0
    # (e.g. ["ipv4","ipv6"]; ["wasminsnexec","memalloc",...]).
    %w[XdrIPAddrType.php XdrContractCostType.php].each do |fname|
      path = File.join(OUTPUT_DIR, fname)
      assert File.exist?(path), "expected #{path} to exist; check generator output cwd"
      contents = File.read(path)
      assert_match SEP51_TO_JSON_VALUE_SIGNATURE, contents,
        "#{fname} should receive SEP-51 enum emission for CamelCase identifiers"
    end
  end

  private

  def assert_sep51_enum_shape(filename, expected_arms:)
    path = File.join(OUTPUT_DIR, filename)
    assert File.exist?(path), "Generated file not found: #{path}"
    contents = File.read(path)

    assert_match SEP51_TO_JSON_VALUE_SIGNATURE, contents,
      "#{filename} missing toJsonValue signature"
    assert_match SEP51_FROM_JSON_VALUE_SIGNATURE, contents,
      "#{filename} missing fromJsonValue signature"
    assert_match SEP51_TO_JSON_FACADE_BODY, contents,
      "#{filename} missing JSON encoder flag triple in toJson facade"
    assert_match SEP51_INVARIANT_DEFAULT_THROW, contents,
      "#{filename} missing throw-on-default arm in fromJsonValue"

    expected_arms.each do |arm|
      # Each wire-form value appears at least once on the to-side
      # (`=> 'arm'`) and once on the from-side (`'arm' =>`).
      assert_match Regexp.new("=>\\s*'#{Regexp.escape(arm)}'"), contents,
        "#{filename}: missing to-side arm => '#{arm}'"
      assert_match Regexp.new("'#{Regexp.escape(arm)}'\\s*=>"), contents,
        "#{filename}: missing from-side arm '#{arm}' =>"
    end
  end

  def assert_snapshot_match(filename)
    unless Dir.exist?(SNAPSHOT_DIR) && !Dir.empty?(SNAPSHOT_DIR)
      if ENV['CI']
        flunk "Snapshot directory is missing or empty — snapshots must be committed for CI"
      else
        skip "Snapshots not yet created"
      end
    end

    generated = File.join(OUTPUT_DIR, filename)
    snapshot = File.join(SNAPSHOT_DIR, filename)

    assert File.exist?(generated), "Generated file not found: #{generated}"
    assert File.exist?(snapshot), "Snapshot file not found: #{snapshot}"

    generated_content = File.read(generated)
    snapshot_content = File.read(snapshot)

    assert_equal snapshot_content, generated_content,
      "Generated #{filename} does not match snapshot. " \
      "If the change is intentional, run: make xdr-generator-update-snapshots"
  end
end
