# frozen_string_literal: true

# Codegen-time helpers for SEP-0051 (XDR-JSON) emission.
#
# This module groups Ruby-side computations the generator performs once at code
# generation: the discriminant prefix-stripping algorithm shared by enums and
# discriminated unions, plus helpers for naming int-cased union arms.
#
# The strings produced here are baked into the emitted PHP source as literal
# match-arm keys; downstream PHP code does not recompute them at runtime. The
# algorithm matches the canonical rs-stellar-xdr behaviour so that PHP, Rust and
# JS implementations share the same wire keys for every enum and union.
#
# Inputs are XDR identifier strings as they appear in the generated PHP source
# (after any MEMBER_OVERRIDES / MEMBER_PREFIX_STRIP rewrites): the algorithm
# operates on the visible PHP-side names so its output reflects the constants
# the generator will emit.

module XdrJsonHelpers
  module_function

  # Split an XDR identifier into lowercase snake_case tokens by splitting on
  # underscores only. Case-boundary transitions inside a token are NOT split:
  # this matches the rs-stellar-xdr canonical algorithm verified against
  # py-stellar-base v14.0.0 wire forms (e.g. "IPv4" tokenises to ["ipv4"], not
  # ["i", "pv4"]; "WasmInsnExec" tokenises to ["wasminsnexec"], not
  # ["wasm", "insn", "exec"]).
  #
  # The PHP SDK's identifiers cover three input shapes, all handled correctly
  # by underscore-only tokenisation:
  #   - ALL_CAPS_WITH_UNDERSCORES (e.g. "ASSET_TYPE_NATIVE")
  #   - CamelCase identifiers (e.g. "WasmInsnExec", "IPv4") -> single token
  #     after lowercasing.
  #   - already-snake_case (e.g. "scv_bool") -> tokens preserved as-is.
  #
  # Empty input returns an empty token list (caller decides whether that is
  # legal in the surrounding context).
  def tokenize_identifier(identifier)
    return [] if identifier.nil? || identifier.empty?

    identifier.downcase.split('_').reject(&:empty?)
  end

  # Find the longest leading-token sequence shared by every entry, with the
  # invariant that no entry's remaining-after-strip is empty.
  #
  # Returns the shared-prefix token list (possibly empty when there is no
  # shared prefix or when shrinking to satisfy the invariant emptied it).
  def longest_shared_prefix(token_lists)
    return [] if token_lists.empty?
    return [] if token_lists.any?(&:empty?)

    candidate = token_lists.first.dup
    token_lists.drop(1).each do |toks|
      # Trim candidate down to the LCP with this list.
      i = 0
      max = [candidate.length, toks.length].min
      while i < max && candidate[i] == toks[i]
        i += 1
      end
      candidate = candidate.first(i)
      break if candidate.empty?
    end

    # Enforce the no-empty-result-after-strip invariant by shrinking from the
    # right whenever stripping the candidate would leave any entry with no
    # tokens. Each iteration drops the trailing token; loop until the
    # invariant holds or the candidate becomes empty.
    loop do
      break if candidate.empty?
      offending = token_lists.any? { |toks| toks.length <= candidate.length }
      break unless offending
      candidate.pop
    end

    candidate
  end

  # Apply the SEP-51 / rs-stellar-xdr discriminant prefix-stripping algorithm
  # to a list of XDR identifiers.
  #
  # Returns a Hash mapping each original identifier (as supplied) to its
  # stripped lowercase snake_case form. The mapping preserves insertion order
  # so callers iterating it produce deterministic output.
  #
  # When the input contains duplicate identifiers, later entries overwrite
  # earlier ones in the returned hash; this is consistent with Ruby's standard
  # Hash semantics. Callers that require uniqueness should de-duplicate before
  # invoking.
  def strip_shared_prefix(identifiers)
    result = {}
    return result if identifiers.empty?

    token_lists = identifiers.map { |id| tokenize_identifier(id) }

    # When the input is a single identifier, there is no other entry to share
    # tokens with: the longest shared prefix is empty, and the wire form is the
    # full lowercase snake_case identifier. py-stellar-base v14.0.0 emits the
    # full identifier here too (e.g. "PUBLIC_KEY_TYPE_ED25519" ->
    # "public_key_type_ed25519"; "CLAIMABLE_BALANCE_ID_TYPE_V0" ->
    # "claimable_balance_id_type_v0"). The general path below produces the same
    # result via an empty prefix; the early return is retained for clarity.
    if identifiers.length == 1
      result[identifiers.first] = token_lists.first.join('_')
      return result
    end

    prefix = longest_shared_prefix(token_lists)
    identifiers.each_with_index do |id, idx|
      tokens = token_lists[idx]
      stripped = tokens.drop(prefix.length)
      result[id] = stripped.join('_')
    end
    result
  end

  # Compose the wire-form arm name for an int-cased discriminated union.
  #
  # SEP-51 emits int-cased arms as "<discriminant>0", "<discriminant>1", etc.,
  # with the discriminant variable name lowercased. ExtensionPoint's `switch
  # (int v)` produces "v0" for case 0, "v1" for case 1, and so on.
  #
  # The integer is rendered in base-10 with no zero-padding.
  def int_cased_arm_name(discriminant_var_name, integer)
    raise ArgumentError, 'discriminant_var_name must be a non-empty string' \
      if discriminant_var_name.nil? || discriminant_var_name.empty?
    raise ArgumentError, 'integer must be an Integer' unless integer.is_a?(Integer)

    "#{discriminant_var_name.downcase}#{integer}"
  end
end
