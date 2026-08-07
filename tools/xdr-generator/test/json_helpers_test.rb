# frozen_string_literal: true

require 'minitest/autorun'
require_relative '../generator/json_helpers'

# Unit tests for the codegen-time SEP-51 naming algorithms.
#
# The canonical algorithm (enum_json_names and its building blocks) operates
# on ORIGINAL XDR member identifiers and must reproduce the wire names of the
# rs-stellar-xdr reference implementation byte for byte; every expected value
# in the canonical section below was verified against the stellar-xdr CLI
# v28.0.0 oracle. The legacy_* functions reproduce the wire names emitted by
# SDK releases up to 1.11.x and exist solely to generate deprecated input
# aliases.
class JsonHelpersTest < Minitest::Test
  # ------------------------------------------------------------------
  # heck_words — word segmentation
  # ------------------------------------------------------------------

  def test_heck_words_splits_on_underscores
    assert_equal %w[ASSET TYPE NATIVE],
                 XdrJsonHelpers.heck_words('ASSET_TYPE_NATIVE')
  end

  def test_heck_words_splits_lower_to_upper
    assert_equal %w[Wasm Insn Exec], XdrJsonHelpers.heck_words('WasmInsnExec')
  end

  def test_heck_words_splits_before_last_upper_of_acronym_run
    # Uppercase run followed by lowercase splits before the final uppercase:
    # the acronym keeps its body, the next word starts at the capital that
    # begins the lowercase run.
    assert_equal %w[I Pv4], XdrJsonHelpers.heck_words('IPv4')
  end

  def test_heck_words_splits_lowercase_prefix_before_upper_run
    assert_equal %w[tx SUCCESS], XdrJsonHelpers.heck_words('txSUCCESS')
    assert_equal %w[op INNER], XdrJsonHelpers.heck_words('opINNER')
  end

  def test_heck_words_digits_stay_attached_and_split_before_upper
    assert_equal %w[Int256 Add Sub], XdrJsonHelpers.heck_words('Int256AddSub')
    assert_equal %w[Cha Cha20 Draw Bytes],
                 XdrJsonHelpers.heck_words('ChaCha20DrawBytes')
    assert_equal %w[Bls12381 Encode Fp],
                 XdrJsonHelpers.heck_words('Bls12381EncodeFp')
  end

  def test_heck_words_collapses_repeated_underscores
    assert_equal %w[A B], XdrJsonHelpers.heck_words('A__B')
  end

  # ------------------------------------------------------------------
  # heck_upper_camel / serde_snake_case
  # ------------------------------------------------------------------

  def test_heck_upper_camel
    assert_equal 'TxSuccess', XdrJsonHelpers.heck_upper_camel('txSUCCESS')
    assert_equal 'IPv4', XdrJsonHelpers.heck_upper_camel('IPv4')
    assert_equal 'B8Bit', XdrJsonHelpers.heck_upper_camel('B8_BIT')
    assert_equal 'Native', XdrJsonHelpers.heck_upper_camel('NATIVE')
  end

  def test_serde_snake_case_underscore_before_every_uppercase
    assert_equal 'tx_success', XdrJsonHelpers.serde_snake_case('TxSuccess')
    assert_equal 'i_pv4', XdrJsonHelpers.serde_snake_case('IPv4')
    assert_equal 'b8_bit', XdrJsonHelpers.serde_snake_case('B8Bit')
    assert_equal 'bls12381_encode_fp',
                 XdrJsonHelpers.serde_snake_case('Bls12381EncodeFp')
  end

  # ------------------------------------------------------------------
  # shared_byte_prefix
  # ------------------------------------------------------------------

  def test_shared_byte_prefix_truncates_to_last_underscore
    assert_equal 'ASSET_TYPE_',
                 XdrJsonHelpers.shared_byte_prefix(
                   %w[ASSET_TYPE_NATIVE ASSET_TYPE_POOL_SHARE]
                 )
  end

  def test_shared_byte_prefix_without_underscore_is_empty
    # "tx"/"op" prefixes contain no underscore, so nothing is stripped.
    assert_equal '', XdrJsonHelpers.shared_byte_prefix(%w[txSUCCESS txFAILED])
    assert_equal '', XdrJsonHelpers.shared_byte_prefix(%w[opINNER opBAD_AUTH])
  end

  def test_shared_byte_prefix_mid_token_lcp_backs_off_to_underscore
    # Byte-wise LCP of FOO_BAR/FOO_BAZ is "FOO_BA"; truncating to the last
    # underscore yields "FOO_".
    assert_equal 'FOO_', XdrJsonHelpers.shared_byte_prefix(%w[FOO_BAR FOO_BAZ])
  end

  def test_shared_byte_prefix_no_common_bytes
    assert_equal '', XdrJsonHelpers.shared_byte_prefix(%w[ALPHA BRAVO])
  end

  # ------------------------------------------------------------------
  # enum_json_names — real Stellar enums (oracle-verified)
  # ------------------------------------------------------------------

  def test_asset_type
    expected = {
      'ASSET_TYPE_NATIVE'            => 'native',
      'ASSET_TYPE_CREDIT_ALPHANUM4'  => 'credit_alphanum4',
      'ASSET_TYPE_CREDIT_ALPHANUM12' => 'credit_alphanum12',
      'ASSET_TYPE_POOL_SHARE'        => 'pool_share',
    }
    assert_equal expected, XdrJsonHelpers.enum_json_names(expected.keys)
  end

  def test_scval_type
    inputs = %w[
      SCV_BOOL SCV_VOID SCV_ERROR SCV_U32 SCV_I32 SCV_U64 SCV_I64
      SCV_TIMEPOINT SCV_DURATION SCV_U128 SCV_I128 SCV_U256 SCV_I256
      SCV_BYTES SCV_STRING SCV_SYMBOL SCV_VEC SCV_MAP SCV_ADDRESS
      SCV_CONTRACT_INSTANCE SCV_LEDGER_KEY_CONTRACT_INSTANCE
      SCV_LEDGER_KEY_NONCE SCV_EXECUTABLE_TAG
    ]
    expected_values = %w[
      bool void error u32 i32 u64 i64 timepoint duration u128 i128 u256 i256
      bytes string symbol vec map address contract_instance
      ledger_key_contract_instance ledger_key_nonce executable_tag
    ]
    actual = XdrJsonHelpers.enum_json_names(inputs)
    inputs.each_with_index do |id, i|
      assert_equal expected_values[i], actual[id], "member #{id}"
    end
  end

  def test_memo_type
    expected = {
      'MEMO_NONE'   => 'none',
      'MEMO_TEXT'   => 'text',
      'MEMO_ID'     => 'id',
      'MEMO_HASH'   => 'hash',
      'MEMO_RETURN' => 'return',
    }
    assert_equal expected, XdrJsonHelpers.enum_json_names(expected.keys)
  end

  def test_signer_key_type
    expected = {
      'SIGNER_KEY_TYPE_ED25519'                => 'ed25519',
      'SIGNER_KEY_TYPE_PRE_AUTH_TX'            => 'pre_auth_tx',
      'SIGNER_KEY_TYPE_HASH_X'                 => 'hash_x',
      'SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD' => 'ed25519_signed_payload',
    }
    assert_equal expected, XdrJsonHelpers.enum_json_names(expected.keys)
  end

  def test_ip_addr_type_camel_case_acronym
    expected = { 'IPv4' => 'i_pv4', 'IPv6' => 'i_pv6' }
    assert_equal expected, XdrJsonHelpers.enum_json_names(expected.keys)
  end

  def test_contract_cost_type_camel_case_members
    inputs = %w[
      WasmInsnExec MemAlloc MemCpy ChaCha20DrawBytes Int256AddSub
      Bls12381EncodeFp Sec1DecodePointUncompressed VerifyEcdsaSecp256r1Sig
    ]
    expected = {
      'WasmInsnExec'                => 'wasm_insn_exec',
      'MemAlloc'                    => 'mem_alloc',
      'MemCpy'                      => 'mem_cpy',
      'ChaCha20DrawBytes'           => 'cha_cha20_draw_bytes',
      'Int256AddSub'                => 'int256_add_sub',
      'Bls12381EncodeFp'            => 'bls12381_encode_fp',
      'Sec1DecodePointUncompressed' => 'sec1_decode_point_uncompressed',
      'VerifyEcdsaSecp256r1Sig'     => 'verify_ecdsa_secp256r1_sig',
    }
    assert_equal expected, XdrJsonHelpers.enum_json_names(inputs)
  end

  def test_operation_result_code_keeps_op_prefix
    inputs = %w[
      opINNER opBAD_AUTH opNO_ACCOUNT opNOT_SUPPORTED opTOO_MANY_SUBENTRIES
      opEXCEEDED_WORK_LIMIT opTOO_MANY_SPONSORING
    ]
    expected = {
      'opINNER'               => 'op_inner',
      'opBAD_AUTH'            => 'op_bad_auth',
      'opNO_ACCOUNT'          => 'op_no_account',
      'opNOT_SUPPORTED'       => 'op_not_supported',
      'opTOO_MANY_SUBENTRIES' => 'op_too_many_subentries',
      'opEXCEEDED_WORK_LIMIT' => 'op_exceeded_work_limit',
      'opTOO_MANY_SPONSORING' => 'op_too_many_sponsoring',
    }
    assert_equal expected, XdrJsonHelpers.enum_json_names(inputs)
  end

  def test_transaction_result_code_keeps_tx_prefix
    inputs = %w[
      txFEE_BUMP_INNER_SUCCESS txSUCCESS txFAILED txTOO_EARLY txTOO_LATE
      txMISSING_OPERATION txBAD_SEQ txBAD_AUTH txINSUFFICIENT_BALANCE
      txNO_ACCOUNT txINSUFFICIENT_FEE txBAD_AUTH_EXTRA txINTERNAL_ERROR
      txNOT_SUPPORTED txFEE_BUMP_INNER_FAILED txBAD_SPONSORSHIP
      txBAD_MIN_SEQ_AGE_OR_GAP txMALFORMED txSOROBAN_INVALID
    ]
    actual = XdrJsonHelpers.enum_json_names(inputs)
    assert_equal 'tx_fee_bump_inner_success', actual['txFEE_BUMP_INNER_SUCCESS']
    assert_equal 'tx_success', actual['txSUCCESS']
    assert_equal 'tx_failed', actual['txFAILED']
    assert_equal 'tx_bad_min_seq_age_or_gap', actual['txBAD_MIN_SEQ_AGE_OR_GAP']
    assert_equal 'tx_soroban_invalid', actual['txSOROBAN_INVALID']
  end

  def test_binary_fuse_filter_type_digit_leading_after_strip
    # Stripping BINARY_FUSE_FILTER_ leaves digit-leading remainders; the
    # first character of the stripped prefix is prepended before casing.
    expected = {
      'BINARY_FUSE_FILTER_8_BIT'  => 'b8_bit',
      'BINARY_FUSE_FILTER_16_BIT' => 'b16_bit',
      'BINARY_FUSE_FILTER_32_BIT' => 'b32_bit',
    }
    assert_equal expected, XdrJsonHelpers.enum_json_names(expected.keys)
  end

  def test_clawback_result_code_strips_only_leading_prefix
    # The prefix strip is positional: the second CLAWBACK inside
    # CLAWBACK_NOT_CLAWBACK_ENABLED is retained.
    inputs = %w[
      CLAWBACK_SUCCESS CLAWBACK_MALFORMED CLAWBACK_NOT_CLAWBACK_ENABLED
      CLAWBACK_NO_TRUST CLAWBACK_UNDERFUNDED
    ]
    actual = XdrJsonHelpers.enum_json_names(inputs)
    assert_equal 'not_clawback_enabled', actual['CLAWBACK_NOT_CLAWBACK_ENABLED']
    assert_equal 'success', actual['CLAWBACK_SUCCESS']
  end

  def test_create_account_result_code_strips_full_multi_token_prefix
    inputs = %w[
      CREATE_ACCOUNT_SUCCESS CREATE_ACCOUNT_MALFORMED
      CREATE_ACCOUNT_UNDERFUNDED CREATE_ACCOUNT_LOW_RESERVE
      CREATE_ACCOUNT_ALREADY_EXIST
    ]
    actual = XdrJsonHelpers.enum_json_names(inputs)
    assert_equal 'already_exist', actual['CREATE_ACCOUNT_ALREADY_EXIST']
    assert_equal 'low_reserve', actual['CREATE_ACCOUNT_LOW_RESERVE']
  end

  # ------------------------------------------------------------------
  # enum_json_names — single-member enums
  # ------------------------------------------------------------------

  def test_single_member_enums_emit_full_identifier
    {
      'CLAIMANT_TYPE_V0'                   => 'claimant_type_v0',
      'CLAIMABLE_BALANCE_ID_TYPE_V0'       => 'claimable_balance_id_type_v0',
      'PUBLIC_KEY_TYPE_ED25519'            => 'public_key_type_ed25519',
      'SC_ENV_META_KIND_INTERFACE_VERSION' => 'sc_env_meta_kind_interface_version',
      'LIQUIDITY_POOL_CONSTANT_PRODUCT'    => 'liquidity_pool_constant_product',
    }.each do |input, expected|
      assert_equal({ input => expected },
                   XdrJsonHelpers.enum_json_names([input]), "member #{input}")
    end
  end

  # ------------------------------------------------------------------
  # enum_json_names — synthetic edges
  # ------------------------------------------------------------------

  def test_one_member_is_byte_prefix_of_another
    # Byte LCP "FOO_BAR" truncates back to "FOO_".
    expected = { 'FOO_BAR' => 'bar', 'FOO_BAR_BAZ' => 'bar_baz' }
    assert_equal expected, XdrJsonHelpers.enum_json_names(expected.keys)
  end

  def test_no_shared_prefix
    expected = { 'ALPHA' => 'alpha', 'BRAVO' => 'bravo', 'CHARLIE' => 'charlie' }
    assert_equal expected, XdrJsonHelpers.enum_json_names(expected.keys)
  end

  def test_multi_token_shared_prefix
    expected = { 'A_B_C_X' => 'x', 'A_B_C_Y' => 'y', 'A_B_C_Z' => 'z' }
    assert_equal expected, XdrJsonHelpers.enum_json_names(expected.keys)
  end

  def test_empty_list_returns_empty_hash
    assert_equal({}, XdrJsonHelpers.enum_json_names([]))
  end

  def test_preserves_input_order
    inputs = %w[ASSET_TYPE_POOL_SHARE ASSET_TYPE_NATIVE]
    assert_equal inputs, XdrJsonHelpers.enum_json_names(inputs).keys
  end

  def test_raises_when_strip_would_empty_a_member
    assert_raises(RuntimeError) do
      XdrJsonHelpers.enum_json_names(%w[A_ A_B])
    end
  end

  # ------------------------------------------------------------------
  # legacy_* functions — wire names of SDK releases up to 1.11.x,
  # used solely to emit deprecated fromJson input aliases.
  # ------------------------------------------------------------------

  def test_legacy_tokenize_splits_on_underscores_only
    assert_equal %w[asset type native],
                 XdrJsonHelpers.legacy_tokenize_identifier('ASSET_TYPE_NATIVE')
    assert_equal %w[ipv4], XdrJsonHelpers.legacy_tokenize_identifier('IPv4')
    assert_equal %w[wasminsnexec],
                 XdrJsonHelpers.legacy_tokenize_identifier('WasmInsnExec')
    assert_equal [], XdrJsonHelpers.legacy_tokenize_identifier(nil)
    assert_equal [], XdrJsonHelpers.legacy_tokenize_identifier('')
  end

  def test_legacy_strip_reproduces_pre_hardening_enum_names
    # Enum classes fed PHP-side constant names (tx/op prefixes already
    # removed by MEMBER_PREFIX_STRIP); the legacy output is the alias the
    # regenerated fromJsonValue must keep accepting.
    actual = XdrJsonHelpers.legacy_strip_shared_prefix(
      %w[FEE_BUMP_INNER_SUCCESS SUCCESS FAILED TOO_EARLY]
    )
    assert_equal 'fee_bump_inner_success', actual['FEE_BUMP_INNER_SUCCESS']
    assert_equal 'success', actual['SUCCESS']
    assert_equal 'failed', actual['FAILED']
  end

  def test_legacy_strip_reproduces_pre_hardening_union_arm_keys
    # Unions fed original XDR case identifiers; camel/lower prefixes glue
    # into single tokens, so nothing is stripped and the legacy arm key is
    # the plain downcased identifier.
    actual = XdrJsonHelpers.legacy_strip_shared_prefix(
      %w[txFEE_BUMP_INNER_SUCCESS txSUCCESS txFAILED]
    )
    assert_equal 'txfee_bump_inner_success', actual['txFEE_BUMP_INNER_SUCCESS']
    assert_equal 'txsuccess', actual['txSUCCESS']
    assert_equal 'txfailed', actual['txFAILED']
  end

  def test_legacy_strip_single_member_emits_full_identifier
    assert_equal({ 'ENVELOPE_TYPE_TX' => 'envelope_type_tx' },
                 XdrJsonHelpers.legacy_strip_shared_prefix(%w[ENVELOPE_TYPE_TX]))
  end

  def test_legacy_strip_shrinks_prefix_to_keep_members_nonempty
    actual = XdrJsonHelpers.legacy_strip_shared_prefix(
      %w[ENVELOPE_TYPE_TX ENVELOPE_TYPE_TX_FEE_BUMP]
    )
    assert_equal 'tx', actual['ENVELOPE_TYPE_TX']
    assert_equal 'tx_fee_bump', actual['ENVELOPE_TYPE_TX_FEE_BUMP']
  end

  def test_legacy_strip_empty_list_returns_empty_hash
    assert_equal({}, XdrJsonHelpers.legacy_strip_shared_prefix([]))
  end

  # ------------------------------------------------------------------
  # unknown_field_guard — struct-object key closure
  # ------------------------------------------------------------------

  def test_unknown_field_guard_lists_keys_in_given_order
    assert_equal "XdrJsonHelper::rejectUnknownFields($value, ['n', 'd'], 'XdrPrice');",
                 XdrJsonHelpers.unknown_field_guard('XdrPrice', %w[n d])
  end

  def test_unknown_field_guard_with_single_key
    assert_equal "XdrJsonHelper::rejectUnknownFields($value, ['nonce'], 'XdrSCNonceKey');",
                 XdrJsonHelpers.unknown_field_guard('XdrSCNonceKey', %w[nonce])
  end

  def test_unknown_field_guard_with_no_declared_keys_closes_over_the_empty_set
    assert_equal "XdrJsonHelper::rejectUnknownFields($value, [], 'XdrEmpty');",
                 XdrJsonHelpers.unknown_field_guard('XdrEmpty', [])
  end

  def test_unknown_field_guard_escapes_php_single_quote_metacharacters
    assert_equal "XdrJsonHelper::rejectUnknownFields($value, ['a\\\\b', 'c\\'d'], 'Xdr\\'T');",
                 XdrJsonHelpers.unknown_field_guard("Xdr'T", ['a\\b', "c'd"])
  end

  # ------------------------------------------------------------------
  # field_alias_fold — input alias folded onto the canonical field key
  # ------------------------------------------------------------------

  def test_field_alias_fold_reassigns_value_from_the_helper
    assert_equal "$value = XdrJsonHelper::normalizeFieldAlias($value, 'type', 'type_', 'XdrDontHave');",
                 XdrJsonHelpers.field_alias_fold('XdrDontHave', 'type', 'type_')
  end

  def test_field_alias_fold_escapes_php_single_quote_metacharacters
    assert_equal "$value = XdrJsonHelper::normalizeFieldAlias($value, 'a\\'b', 'c\\\\d', 'Xdr\\'T');",
                 XdrJsonHelpers.field_alias_fold("Xdr'T", "a'b", 'c\\d')
  end
end
