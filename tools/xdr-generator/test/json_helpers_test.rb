# frozen_string_literal: true

require 'minitest/autorun'
require_relative '../generator/json_helpers'

# Unit tests for the codegen-time SEP-51 prefix-stripping algorithm and the
# int-cased arm-name helper.
#
# The algorithm operates on the PHP-side identifiers as they appear in the
# generated source (after MEMBER_OVERRIDES / MEMBER_PREFIX_STRIP rewrites).
# Each test asserts the exact stripped lowercase wire form against the
# expected output baked into emitted PHP.
class JsonHelpersTest < Minitest::Test
  # ------------------------------------------------------------------
  # tokenize_identifier
  # ------------------------------------------------------------------

  def test_tokenize_all_caps_with_underscores
    assert_equal %w[asset type native],
                 XdrJsonHelpers.tokenize_identifier('ASSET_TYPE_NATIVE')
  end

  def test_tokenize_camel_case_yields_single_token
    # Underscore-only tokenisation: a CamelCase identifier becomes ONE
    # lowercased token. Verified against py-stellar-base v14.0.0
    # (e.g. "WasmInsnExec" -> "wasminsnexec").
    assert_equal %w[assettypenative],
                 XdrJsonHelpers.tokenize_identifier('AssetTypeNative')
  end

  def test_tokenize_mixed_camel_underscore_splits_only_on_underscore
    assert_equal %w[asset type alphanum4],
                 XdrJsonHelpers.tokenize_identifier('ASSET_TYPE_AlphaNum4')
  end

  def test_tokenize_ipv4_yields_single_token
    # IPv4 / IPv6 / IPvNN: case-boundary tokenisation would produce nonsense
    # like ["i","pv4"]. Underscore-only tokenisation produces ["ipv4"], the
    # form py-stellar-base emits in IPAddrType wire mappings.
    assert_equal %w[ipv4], XdrJsonHelpers.tokenize_identifier('IPv4')
    assert_equal %w[ipv6], XdrJsonHelpers.tokenize_identifier('IPv6')
  end

  def test_tokenize_lowercase_already
    assert_equal %w[foo bar baz],
                 XdrJsonHelpers.tokenize_identifier('foo_bar_baz')
  end

  def test_tokenize_empty_returns_empty_list
    assert_equal [], XdrJsonHelpers.tokenize_identifier('')
  end

  def test_tokenize_nil_returns_empty_list
    assert_equal [], XdrJsonHelpers.tokenize_identifier(nil)
  end

  def test_tokenize_collapses_repeated_underscores
    assert_equal %w[a b], XdrJsonHelpers.tokenize_identifier('A__B')
  end

  # ------------------------------------------------------------------
  # strip_shared_prefix — real Stellar enum names
  # ------------------------------------------------------------------

  def test_strip_asset_type
    inputs = %w[
      ASSET_TYPE_NATIVE
      ASSET_TYPE_CREDIT_ALPHANUM4
      ASSET_TYPE_CREDIT_ALPHANUM12
      ASSET_TYPE_POOL_SHARE
    ]
    expected = {
      'ASSET_TYPE_NATIVE'             => 'native',
      'ASSET_TYPE_CREDIT_ALPHANUM4'   => 'credit_alphanum4',
      'ASSET_TYPE_CREDIT_ALPHANUM12'  => 'credit_alphanum12',
      'ASSET_TYPE_POOL_SHARE'         => 'pool_share',
    }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  def test_strip_scval_type_22_arms
    inputs = %w[
      SCV_BOOL SCV_VOID SCV_ERROR SCV_U32 SCV_I32 SCV_U64 SCV_I64
      SCV_TIMEPOINT SCV_DURATION SCV_U128 SCV_I128 SCV_U256 SCV_I256
      SCV_BYTES SCV_STRING SCV_SYMBOL SCV_VEC SCV_MAP SCV_ADDRESS
      SCV_CONTRACT_INSTANCE SCV_LEDGER_KEY_CONTRACT_INSTANCE
      SCV_LEDGER_KEY_NONCE
    ]
    expected_values = %w[
      bool void error u32 i32 u64 i64 timepoint duration u128 i128 u256 i256
      bytes string symbol vec map address contract_instance
      ledger_key_contract_instance ledger_key_nonce
    ]
    actual = XdrJsonHelpers.strip_shared_prefix(inputs)
    assert_equal inputs.length, actual.length
    inputs.each_with_index do |id, i|
      assert_equal expected_values[i], actual[id], "arm #{id}"
    end
  end

  def test_strip_memo_type
    # PHP-side member names after MEMO_ stripping is configured externally;
    # these are the raw XDR identifiers as they would appear in the IDL.
    inputs = %w[MEMO_NONE MEMO_TEXT MEMO_ID MEMO_HASH MEMO_RETURN]
    expected = {
      'MEMO_NONE'   => 'none',
      'MEMO_TEXT'   => 'text',
      'MEMO_ID'     => 'id',
      'MEMO_HASH'   => 'hash',
      'MEMO_RETURN' => 'return',
    }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  def test_strip_precondition_type
    inputs = %w[PRECOND_NONE PRECOND_TIME PRECOND_V2]
    expected = {
      'PRECOND_NONE' => 'none',
      'PRECOND_TIME' => 'time',
      'PRECOND_V2'   => 'v2',
    }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  def test_strip_scaddress_type
    inputs = %w[
      SC_ADDRESS_TYPE_ACCOUNT
      SC_ADDRESS_TYPE_CONTRACT
      SC_ADDRESS_TYPE_MUXED_ACCOUNT
      SC_ADDRESS_TYPE_CLAIMABLE_BALANCE
      SC_ADDRESS_TYPE_LIQUIDITY_POOL
    ]
    expected = {
      'SC_ADDRESS_TYPE_ACCOUNT'           => 'account',
      'SC_ADDRESS_TYPE_CONTRACT'          => 'contract',
      'SC_ADDRESS_TYPE_MUXED_ACCOUNT'     => 'muxed_account',
      'SC_ADDRESS_TYPE_CLAIMABLE_BALANCE' => 'claimable_balance',
      'SC_ADDRESS_TYPE_LIQUIDITY_POOL'    => 'liquidity_pool',
    }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  def test_strip_signer_key_type
    inputs = %w[
      SIGNER_KEY_TYPE_ED25519
      SIGNER_KEY_TYPE_PRE_AUTH_TX
      SIGNER_KEY_TYPE_HASH_X
      SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD
    ]
    expected = {
      'SIGNER_KEY_TYPE_ED25519'                => 'ed25519',
      'SIGNER_KEY_TYPE_PRE_AUTH_TX'            => 'pre_auth_tx',
      'SIGNER_KEY_TYPE_HASH_X'                 => 'hash_x',
      'SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD' => 'ed25519_signed_payload',
    }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  def test_strip_crypto_key_type
    inputs = %w[
      KEY_TYPE_ED25519
      KEY_TYPE_PRE_AUTH_TX
      KEY_TYPE_HASH_X
      KEY_TYPE_ED25519_SIGNED_PAYLOAD
      KEY_TYPE_MUXED_ED25519
    ]
    expected = {
      'KEY_TYPE_ED25519'                => 'ed25519',
      'KEY_TYPE_PRE_AUTH_TX'            => 'pre_auth_tx',
      'KEY_TYPE_HASH_X'                 => 'hash_x',
      'KEY_TYPE_ED25519_SIGNED_PAYLOAD' => 'ed25519_signed_payload',
      'KEY_TYPE_MUXED_ED25519'          => 'muxed_ed25519',
    }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  # ------------------------------------------------------------------
  # Documented PHP-side divergences from py-stellar-base — both
  # OperationResultCode and TransactionResultCode have their op/tx
  # prefix stripped at codegen-name level (see MEMBER_PREFIX_STRIP),
  # so the SEP-51 algorithm operates on the bare identifiers and emits
  # the bare lowercase form.
  # ------------------------------------------------------------------

  def test_strip_operation_result_code_php_side_inputs
    inputs = %w[
      INNER BAD_AUTH NO_ACCOUNT NOT_SUPPORTED TOO_MANY_SUBENTRIES
      EXCEEDED_WORK_LIMIT TOO_MANY_SPONSORING
    ]
    expected = {
      'INNER'                 => 'inner',
      'BAD_AUTH'              => 'bad_auth',
      'NO_ACCOUNT'            => 'no_account',
      'NOT_SUPPORTED'         => 'not_supported',
      'TOO_MANY_SUBENTRIES'   => 'too_many_subentries',
      'EXCEEDED_WORK_LIMIT'   => 'exceeded_work_limit',
      'TOO_MANY_SPONSORING'   => 'too_many_sponsoring',
    }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  def test_strip_transaction_result_code_php_side_inputs
    inputs = %w[
      FEE_BUMP_INNER_SUCCESS SUCCESS FAILED TOO_EARLY TOO_LATE
      MISSING_OPERATION BAD_SEQ BAD_AUTH INSUFFICIENT_BALANCE NO_ACCOUNT
      INSUFFICIENT_FEE BAD_AUTH_EXTRA INTERNAL_ERROR NOT_SUPPORTED
      FEE_BUMP_INNER_FAILED BAD_SPONSORSHIP BAD_MIN_SEQ_AGE_OR_GAP
      MALFORMED SOROBAN_INVALID FROZEN_KEY_ACCESSED
    ]
    actual = XdrJsonHelpers.strip_shared_prefix(inputs)
    # No shared prefix; each entry collapses to its own lowercase form.
    assert_equal 'fee_bump_inner_success', actual['FEE_BUMP_INNER_SUCCESS']
    assert_equal 'success', actual['SUCCESS']
    assert_equal 'failed',  actual['FAILED']
    assert_equal 'too_early', actual['TOO_EARLY']
    assert_equal 'frozen_key_accessed', actual['FROZEN_KEY_ACCESSED']
    assert_equal 'bad_min_seq_age_or_gap', actual['BAD_MIN_SEQ_AGE_OR_GAP']
  end

  # ------------------------------------------------------------------
  # Single-member edge case
  # ------------------------------------------------------------------

  # Single-member edge case: with only one identifier there is no other
  # entry to share tokens with, so the longest shared prefix is empty and
  # the wire form is the full lowercase snake_case identifier. Verified
  # against py-stellar-base v14.0.0 wire maps for every single-member enum.

  def test_strip_claimable_balance_id_type_single_member
    inputs = %w[CLAIMABLE_BALANCE_ID_TYPE_V0]
    expected = { 'CLAIMABLE_BALANCE_ID_TYPE_V0' => 'claimable_balance_id_type_v0' }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  def test_strip_public_key_type_single_member
    inputs = %w[PUBLIC_KEY_TYPE_ED25519]
    expected = { 'PUBLIC_KEY_TYPE_ED25519' => 'public_key_type_ed25519' }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  def test_strip_sc_env_meta_kind_single_member
    inputs = %w[SC_ENV_META_KIND_INTERFACE_VERSION]
    expected = {
      'SC_ENV_META_KIND_INTERFACE_VERSION' => 'sc_env_meta_kind_interface_version',
    }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  def test_strip_sc_meta_kind_single_member
    inputs = %w[SC_META_V0]
    expected = { 'SC_META_V0' => 'sc_meta_v0' }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  def test_strip_liquidity_pool_type_single_member
    inputs = %w[LIQUIDITY_POOL_CONSTANT_PRODUCT]
    expected = {
      'LIQUIDITY_POOL_CONSTANT_PRODUCT' => 'liquidity_pool_constant_product',
    }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  def test_strip_survey_message_command_type_single_member
    inputs = %w[TIME_SLICED_SURVEY_TOPOLOGY]
    expected = {
      'TIME_SLICED_SURVEY_TOPOLOGY' => 'time_sliced_survey_topology',
    }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  def test_strip_offer_entry_flags_single_member
    inputs = %w[PASSIVE_FLAG]
    expected = { 'PASSIVE_FLAG' => 'passive_flag' }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  def test_strip_claimable_balance_flags_single_member
    inputs = %w[CLAIMABLE_BALANCE_CLAWBACK_ENABLED_FLAG]
    expected = {
      'CLAIMABLE_BALANCE_CLAWBACK_ENABLED_FLAG' => 'claimable_balance_clawback_enabled_flag',
    }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  def test_strip_single_member_already_unprefixed_keeps_the_token
    inputs = %w[SOLO]
    expected = { 'SOLO' => 'solo' }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  def test_strip_single_member_with_multi_token_name_emits_full_identifier
    inputs = %w[FOO_BAR_BAZ]
    expected = { 'FOO_BAR_BAZ' => 'foo_bar_baz' }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  # ------------------------------------------------------------------
  # Synthetic edge cases from the algorithm specification
  # ------------------------------------------------------------------

  def test_strip_two_members_one_strict_prefix_of_other
    inputs = %w[FOO_BAR FOO_BAR_BAZ]
    expected = {
      'FOO_BAR'      => 'bar',
      'FOO_BAR_BAZ'  => 'bar_baz',
    }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  def test_strip_no_shared_prefix
    inputs = %w[ALPHA BRAVO CHARLIE]
    expected = {
      'ALPHA'   => 'alpha',
      'BRAVO'   => 'bravo',
      'CHARLIE' => 'charlie',
    }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  def test_strip_multi_token_shared_prefix_drops_everything_above_invariant
    inputs = %w[A_B_C_X A_B_C_Y A_B_C_Z]
    expected = {
      'A_B_C_X' => 'x',
      'A_B_C_Y' => 'y',
      'A_B_C_Z' => 'z',
    }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  def test_strip_empty_list_returns_empty_hash
    assert_equal({}, XdrJsonHelpers.strip_shared_prefix([]))
  end

  def test_strip_preserves_input_order_in_returned_hash
    inputs = %w[ASSET_TYPE_POOL_SHARE ASSET_TYPE_NATIVE]
    actual = XdrJsonHelpers.strip_shared_prefix(inputs)
    assert_equal inputs, actual.keys
  end

  # ------------------------------------------------------------------
  # CamelCase identifiers — XdrIPAddrType and XdrContractCostType
  # use CamelCase enum constants rather than ALL_CAPS_WITH_UNDERSCORES.
  # The tokeniser splits on '_' only, so each CamelCase identifier becomes
  # a single lowercased token. The shared-prefix step then trivially strips
  # nothing, and the wire form matches py-stellar-base v14.0.0.
  # ------------------------------------------------------------------

  def test_strip_ip_addr_type_camel_case
    inputs = %w[IPv4 IPv6]
    expected = { 'IPv4' => 'ipv4', 'IPv6' => 'ipv6' }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  def test_strip_contract_cost_type_first_three_camel_case
    inputs = %w[WasmInsnExec MemAlloc MemCpy]
    expected = {
      'WasmInsnExec' => 'wasminsnexec',
      'MemAlloc'     => 'memalloc',
      'MemCpy'       => 'memcpy',
    }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end

  def test_strip_camel_case_no_shared_prefix
    inputs = %w[Alpha BravoCharlie Delta]
    expected = {
      'Alpha'         => 'alpha',
      'BravoCharlie'  => 'bravocharlie',
      'Delta'         => 'delta',
    }
    assert_equal expected, XdrJsonHelpers.strip_shared_prefix(inputs)
  end


  # ------------------------------------------------------------------
  # int_cased_arm_name — int-cased union arm naming (ExtensionPoint)
  # ------------------------------------------------------------------

  def test_int_cased_arm_extension_point_case_zero
    assert_equal 'v0', XdrJsonHelpers.int_cased_arm_name('v', 0)
  end

  def test_int_cased_arm_extension_point_case_one
    assert_equal 'v1', XdrJsonHelpers.int_cased_arm_name('v', 1)
  end

  def test_int_cased_arm_uses_lowercase_discriminant_name
    assert_equal 'v2', XdrJsonHelpers.int_cased_arm_name('V', 2)
  end

  def test_int_cased_arm_two_digit_index
    assert_equal 'v42', XdrJsonHelpers.int_cased_arm_name('v', 42)
  end

  def test_int_cased_arm_rejects_empty_discriminant_name
    assert_raises(ArgumentError) do
      XdrJsonHelpers.int_cased_arm_name('', 0)
    end
  end

  def test_int_cased_arm_rejects_nil_discriminant_name
    assert_raises(ArgumentError) do
      XdrJsonHelpers.int_cased_arm_name(nil, 0)
    end
  end

  def test_int_cased_arm_rejects_non_integer
    assert_raises(ArgumentError) do
      XdrJsonHelpers.int_cased_arm_name('v', '0')
    end
  end
end
