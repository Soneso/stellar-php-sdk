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

  # ------------------------------------------------------------------
  # Phase 3 AST-shape assertions for structs and unions
  # ------------------------------------------------------------------

  SEP51_STRUCT_TO_JSON_VALUE_SIGNATURE =
    /public\s+function\s+toJsonValue\(\)\s*:\s*array/.freeze

  SEP51_UNION_TO_JSON_VALUE_SIGNATURE =
    /public\s+function\s+toJsonValue\(\)\s*:\s*mixed/.freeze

  SEP51_FROM_JSON_FACADE_SIGNATURE =
    /public\s+static\s+function\s+fromJson\(string\s+\$json\)\s*:\s*static/.freeze

  SEP51_TO_JSON_FACADE_SIGNATURE =
    /public\s+function\s+toJson\(\)\s*:\s*string/.freeze

  SEP51_SCHEMA_STRIP =
    /array_key_exists\('\$schema',\s*\$value\)/.freeze

  def test_sep51_xdr_sc_val_is_cat_b_skipped
    # XdrSCVal is in BASE_WRAPPER_TYPES and Cat-B; Phase 4 owns its emission.
    # Phase 3 must not emit on the wrapper, and the Base file must not yet
    # contain SEP-51 methods either (Phase 4 will add them on the *Base.php).
    %w[XdrSCVal.php XdrSCValBase.php].each do |fname|
      path = File.join(OUTPUT_DIR, fname)
      next unless File.exist?(path)
      contents = File.read(path)
      refute_match SEP51_UNION_TO_JSON_VALUE_SIGNATURE, contents,
        "#{fname} (Cat-B) must not have SEP-51 toJsonValue until Phase 4"
      refute_match SEP51_STRUCT_TO_JSON_VALUE_SIGNATURE, contents,
        "#{fname} (Cat-B) must not have SEP-51 toJsonValue until Phase 4"
    end
  end

  def test_sep51_xdr_asset_cat_a_skipped
    # XdrAsset, XdrAssetAlphaNum4, XdrAssetAlphaNum12, XdrAssetCode (typedef),
    # and XdrSCAddress are Cat-A inline targets / Cat-B wrappers; Phase 3 must
    # not emit on them. XdrAssetCode is not even a class — the typedef is
    # collapsed to string at PHP level — so the file does not exist.
    %w[XdrAsset.php XdrAssetAlphaNum4.php XdrAssetAlphaNum12.php
       XdrAssetAlphaNum4Base.php XdrAssetAlphaNum12Base.php XdrSCAddress.php
       XdrSCAddressBase.php].each do |fname|
      path = File.join(OUTPUT_DIR, fname)
      next unless File.exist?(path)
      contents = File.read(path)
      refute_match SEP51_TO_JSON_VALUE_SIGNATURE, contents,
        "#{fname} is a Cat-A/Cat-B target; SEP-51 must not be emitted until Phase 4"
      refute_match SEP51_STRUCT_TO_JSON_VALUE_SIGNATURE, contents,
        "#{fname} is a Cat-A/Cat-B target; SEP-51 must not be emitted until Phase 4"
      refute_match SEP51_UNION_TO_JSON_VALUE_SIGNATURE, contents,
        "#{fname} is a Cat-A/Cat-B target; SEP-51 must not be emitted until Phase 4"
    end
  end

  def test_sep51_xdr_memo_cat_a_skipped
    # XdrMemo is in CAT_A_INLINE_TARGETS; Phase 4 emits its bespoke shape.
    contents = File.read(File.join(OUTPUT_DIR, "XdrMemo.php"))
    refute_match SEP51_UNION_TO_JSON_VALUE_SIGNATURE, contents
  end

  def test_sep51_xdr_preconditions_cat_a_skipped
    # XdrPreconditions and XdrPreconditionsV2 are Cat-A.
    %w[XdrPreconditions.php XdrPreconditionsV2.php].each do |fname|
      contents = File.read(File.join(OUTPUT_DIR, fname))
      refute_match SEP51_UNION_TO_JSON_VALUE_SIGNATURE, contents,
        "#{fname} is Cat-A; SEP-51 must not be emitted until Phase 4"
      refute_match SEP51_STRUCT_TO_JSON_VALUE_SIGNATURE, contents,
        "#{fname} is Cat-A; SEP-51 must not be emitted until Phase 4"
    end
  end

  def test_sep51_xdr_signer_key_cat_a_skipped
    # XdrSignerKey is a Cat-A inline target; Phase 4 emits its bespoke
    # P-strkey-bearing shape. Phase 3 must not emit.
    contents = File.read(File.join(OUTPUT_DIR, "XdrSignerKey.php"))
    refute_match SEP51_UNION_TO_JSON_VALUE_SIGNATURE, contents,
      "XdrSignerKey.php is Cat-A; SEP-51 must not be emitted until Phase 4"
  end

  def test_sep51_xdr_ledger_key_cat_b_skipped
    # XdrLedgerKey is in BASE_WRAPPER_TYPES; Phase 4 emits.
    %w[XdrLedgerKey.php XdrLedgerKeyBase.php].each do |fname|
      path = File.join(OUTPUT_DIR, fname)
      next unless File.exist?(path)
      contents = File.read(path)
      refute_match SEP51_UNION_TO_JSON_VALUE_SIGNATURE, contents,
        "#{fname} is Cat-B; SEP-51 must not be emitted until Phase 4"
    end
  end

  def test_sep51_xdr_transaction_envelope_cat_b_skipped
    # XdrTransactionEnvelope is in BASE_WRAPPER_TYPES; Phase 4 emits.
    %w[XdrTransactionEnvelope.php XdrTransactionEnvelopeBase.php].each do |fname|
      path = File.join(OUTPUT_DIR, fname)
      next unless File.exist?(path)
      contents = File.read(path)
      refute_match SEP51_UNION_TO_JSON_VALUE_SIGNATURE, contents,
        "#{fname} is Cat-B; SEP-51 must not be emitted until Phase 4"
    end
  end

  def test_sep51_xdr_transaction_v1_envelope_struct_emitted
    assert_sep51_struct_shape(
      "XdrTransactionV1Envelope.php",
      class_name: "XdrTransactionV1Envelope",
      expected_keys: %w[tx signatures]
    )
  end

  def test_sep51_xdr_fee_bump_transaction_struct_emitted
    assert_sep51_struct_shape(
      "XdrFeeBumpTransaction.php",
      class_name: "XdrFeeBumpTransaction",
      expected_keys: %w[fee_source fee inner_tx ext]
    )
  end

  def test_sep51_xdr_ledger_entry_struct_emitted
    assert_sep51_struct_shape(
      "XdrLedgerEntry.php",
      class_name: "XdrLedgerEntry",
      expected_keys: %w[last_modified_ledger_seq data ext]
    )
  end

  def test_sep51_xdr_ledger_entry_data_union_emitted_non_void
    assert_sep51_union_shape(
      "XdrLedgerEntryData.php",
      class_name: "XdrLedgerEntryData",
      shape: "non_void",
      expected_arm_keys: %w[account trustline offer data claimable_balance liquidity_pool contract_data contract_code config_setting ttl]
    )
  end

  def test_sep51_xdr_account_entry_struct_emitted
    assert_sep51_struct_shape(
      "XdrAccountEntry.php",
      class_name: "XdrAccountEntry",
      expected_keys: %w[account_id balance seq_num num_sub_entries inflation_dest flags home_domain thresholds signers ext]
    )
  end

  def test_sep51_xdr_operation_struct_emitted
    assert_sep51_struct_shape(
      "XdrOperation.php",
      class_name: "XdrOperation",
      expected_keys: %w[source_account body]
    )
  end

  def test_sep51_xdr_account_entry_v1_ext_int_cased_mixed
    # AccountEntryExtensionV1Ext has case 0 (void) and case 2 (V2 struct):
    # int-cased; per plan rule 10 precedence (1) -> shape=int_cased.
    assert_sep51_union_shape(
      "XdrAccountEntryV1Ext.php",
      class_name: "XdrAccountEntryV1Ext",
      shape: "int_cased",
      expected_arm_keys: %w[v0 v2]
    )
  end

  def test_sep51_xdr_extension_point_void_only_int_cased
    # ExtensionPoint has only case 0 (void); int-cased; shape=int_cased.
    contents = File.read(File.join(OUTPUT_DIR, "XdrExtensionPoint.php"))
    assert_match(/@sep51-union\s+XdrExtensionPoint\s+shape=int_cased/, contents,
      "XdrExtensionPoint.php missing int_cased marker")
    assert_match(/=>\s*'v0'/, contents)
    assert_match(/'v0'\s*=>/, contents)
  end

  def test_sep51_xdr_transaction_meta_int_cased_non_void_v0_to_v4
    # TransactionMeta is int-cased with v0..v4; every arm is non-void.
    contents = File.read(File.join(OUTPUT_DIR, "XdrTransactionMeta.php"))
    assert_match(/@sep51-union\s+XdrTransactionMeta\s+shape=int_cased/, contents,
      "XdrTransactionMeta.php missing int_cased marker")
    %w[v0 v1 v2 v3 v4].each do |arm|
      assert_match Regexp.new("=>\\s*\\['#{arm}'\\s*=>"), contents,
        "XdrTransactionMeta.php: missing to-side arm key '#{arm}'"
      assert_match Regexp.new("'#{arm}'\\s*=>"), contents,
        "XdrTransactionMeta.php: missing from-side arm key '#{arm}'"
    end
  end

  def test_sep51_xdr_ledger_close_meta_int_cased
    contents = File.read(File.join(OUTPUT_DIR, "XdrLedgerCloseMeta.php"))
    assert_match(/@sep51-union\s+XdrLedgerCloseMeta\s+shape=int_cased/, contents,
      "XdrLedgerCloseMeta.php missing int_cased marker")
    %w[v0 v1 v2].each do |arm|
      assert_match Regexp.new("'#{arm}'\\s*=>"), contents,
        "XdrLedgerCloseMeta.php: missing arm '#{arm}'"
    end
  end

  def test_sep51_xdr_soroban_transaction_meta_ext_int_cased_non_void
    contents = File.read(File.join(OUTPUT_DIR, "XdrSorobanTransactionMetaExt.php"))
    assert_match(/@sep51-union\s+XdrSorobanTransactionMetaExt\s+shape=int_cased/, contents,
      "XdrSorobanTransactionMetaExt.php missing int_cased marker")
    %w[v0 v1].each do |arm|
      assert_match Regexp.new("'#{arm}'"), contents,
        "XdrSorobanTransactionMetaExt.php: missing arm '#{arm}'"
    end
  end

  def test_sep51_xdr_claimable_balance_entry_ext_v1_extension_point
    # XdrClaimableBalanceEntryExtV1 is a struct whose `ext` field is collapsed
    # via EXTENSION_POINT_FIELDS to int. The wire form for `ext` is the bare
    # int-cased void union arm string "v0".
    contents = File.read(File.join(OUTPUT_DIR, "XdrClaimableBalanceEntryExtV1.php"))
    assert_match SEP51_STRUCT_TO_JSON_VALUE_SIGNATURE, contents
    assert_match(/'ext'\s*=>\s*'v0'/, contents,
      "XdrClaimableBalanceEntryExtV1.php missing 'ext' => 'v0' emission")
    assert_match(/!== 'v0'/, contents,
      "XdrClaimableBalanceEntryExtV1.php missing strict v0 validation on input")
    assert_match SEP51_SCHEMA_STRIP, contents
  end

  def test_sep51_xdr_trust_line_entry_extension_v2_extension_point
    contents = File.read(File.join(OUTPUT_DIR, "XdrTrustLineEntryExtensionV2.php"))
    assert_match SEP51_STRUCT_TO_JSON_VALUE_SIGNATURE, contents
    assert_match(/'ext'\s*=>\s*'v0'/, contents,
      "XdrTrustLineEntryExtensionV2.php missing 'ext' => 'v0' emission")
    assert_match(/!== 'v0'/, contents,
      "XdrTrustLineEntryExtensionV2.php missing strict v0 validation on input")
  end

  def test_sep51_xdr_trust_line_entry_struct_emitted
    contents = File.read(File.join(OUTPUT_DIR, "XdrTrustLineEntry.php"))
    assert_match SEP51_STRUCT_TO_JSON_VALUE_SIGNATURE, contents
    assert_match SEP51_SCHEMA_STRIP, contents
  end

  def test_sep51_xdr_claimable_balance_entry_struct_emitted
    contents = File.read(File.join(OUTPUT_DIR, "XdrClaimableBalanceEntry.php"))
    assert_match SEP51_STRUCT_TO_JSON_VALUE_SIGNATURE, contents
    assert_match SEP51_SCHEMA_STRIP, contents
  end

  private

  # Assert that a struct file was emitted with the expected SEP-51 shape:
  # toJsonValue() : array, fromJsonValue, $schema strip, and each expected
  # JSON key.
  def assert_sep51_struct_shape(filename, class_name:, expected_keys:)
    path = File.join(OUTPUT_DIR, filename)
    assert File.exist?(path), "Generated file not found: #{path}"
    contents = File.read(path)

    assert_match SEP51_STRUCT_TO_JSON_VALUE_SIGNATURE, contents,
      "#{filename} missing toJsonValue() : array signature"
    assert_match SEP51_FROM_JSON_VALUE_SIGNATURE, contents,
      "#{filename} missing fromJsonValue signature"
    assert_match SEP51_TO_JSON_FACADE_SIGNATURE, contents,
      "#{filename} missing toJson facade signature"
    assert_match SEP51_TO_JSON_FACADE_BODY, contents,
      "#{filename} missing JSON encoder flag triple"
    assert_match SEP51_FROM_JSON_FACADE_SIGNATURE, contents,
      "#{filename} missing fromJson facade signature"
    assert_match SEP51_SCHEMA_STRIP, contents,
      "#{filename} missing $schema strip in fromJsonValue"

    expected_keys.each do |key|
      assert_match Regexp.new("'#{Regexp.escape(key)}'\\s*=>"), contents,
        "#{filename}: missing JSON key '#{key}'"
    end
  end

  # Assert that a union file was emitted with the expected SEP-51 shape:
  # the @sep51-union shape marker, the schema strip, and each expected
  # arm key on both the to-side and from-side.
  def assert_sep51_union_shape(filename, class_name:, shape:, expected_arm_keys:)
    path = File.join(OUTPUT_DIR, filename)
    assert File.exist?(path), "Generated file not found: #{path}"
    contents = File.read(path)

    assert_match SEP51_UNION_TO_JSON_VALUE_SIGNATURE, contents,
      "#{filename} missing toJsonValue() : mixed signature"
    assert_match SEP51_FROM_JSON_VALUE_SIGNATURE, contents,
      "#{filename} missing fromJsonValue signature"
    assert_match SEP51_TO_JSON_FACADE_BODY, contents,
      "#{filename} missing JSON encoder flag triple"
    assert_match SEP51_SCHEMA_STRIP, contents,
      "#{filename} missing $schema strip in fromJsonValue"

    marker_re = Regexp.new("@sep51-union\\s+#{Regexp.escape(class_name)}\\s+shape=#{shape}")
    assert_match marker_re, contents,
      "#{filename} missing or wrong @sep51-union marker (expected shape=#{shape})"

    # The marker MUST appear on the FIRST line of the fromJsonValue body so
    # that negative_gate.sh's shape-detection regex can locate it.
    body_match = contents.match(/public static function fromJsonValue\(mixed \$value\): static \{\n([^\n]*)/m)
    assert body_match, "#{filename}: could not locate fromJsonValue body"
    first_line = body_match[1].to_s.strip
    assert_match marker_re, first_line,
      "#{filename}: @sep51-union marker not on first line of fromJsonValue body"

    expected_arm_keys.each do |arm|
      assert_match Regexp.new("'#{Regexp.escape(arm)}'\\s*=>"), contents,
        "#{filename}: missing from-side arm '#{arm}'"
    end
  end

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
