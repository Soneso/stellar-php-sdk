# frozen_string_literal: true

# Codegen-time helpers for SEP-0051 (XDR-JSON) emission.
#
# This module groups Ruby-side computations the generator performs once at code
# generation: the canonical enum-member naming algorithm shared by enums and
# discriminated unions, plus the legacy naming algorithm retained solely to
# emit deprecated input aliases.
#
# The strings produced here are baked into the emitted PHP source as literal
# match-arm keys; downstream PHP code does not recompute them at runtime.
#
# Canonical algorithm (matches rs-stellar-xdr v28.0.0, the SEP-0051 reference
# implementation; see xdr-parser/src/ast.rs and generator/src/naming.rs):
#
#   1. Inputs are the ORIGINAL XDR member identifiers exactly as they appear
#      in the .x IDL (e.g. "txSUCCESS", "opINNER", "IPv4"), never PHP-side
#      constant names.
#   2. Compute the byte-wise longest common prefix across all members of the
#      enum (case-sensitive). Single-member enums use an empty prefix.
#   3. Truncate the prefix back to the last underscore (inclusive). A prefix
#      containing no underscore becomes empty; this is why "opINNER" and
#      "txSUCCESS" keep their "op"/"tx" (the "op"/"tx" prefix has no trailing
#      underscore to truncate to).
#   4. Strip the prefix from each member. When the remainder starts with a
#      digit, prepend the first character of the stripped prefix
#      ("BINARY_FUSE_FILTER_8_BIT" -> "B8_BIT").
#   5. Convert to heck-style UpperCamelCase: words split on underscores, on
#      lowercase-or-digit to uppercase transitions, and before the final
#      uppercase of an uppercase run followed by lowercase
#      ("txSUCCESS" -> "TxSuccess", "IPv4" -> "IPv4").
#   6. Apply serde rename_all = "snake_case": an underscore before every
#      non-leading uppercase character, then lowercase
#      ("TxSuccess" -> "tx_success", "IPv4" -> "i_pv4", "B8Bit" -> "b8_bit").
#
# Union arm keys use the identical mapping of the discriminant enum's full
# member list, so enum value strings and union arm keys always agree.

module XdrJsonHelpers
  module_function

  # ---------------------------------------------------------------------------
  # Canonical naming (rs-stellar-xdr rule)
  # ---------------------------------------------------------------------------

  # Segment an ASCII identifier into heck-style words. Word boundaries are:
  # underscores (consumed), a lowercase letter or digit followed by an
  # uppercase letter, and the final uppercase of an uppercase run that is
  # followed by a lowercase letter (acronym end: "IPv4" -> ["I", "Pv4"]).
  def heck_words(identifier)
    words = []
    identifier.split('_').each do |segment|
      next if segment.empty?

      current = +''
      chars = segment.chars
      chars.each_with_index do |ch, i|
        unless current.empty?
          prev = current[-1]
          nxt = chars[i + 1] || ''
          if ch.match?(/[A-Z]/) &&
             (prev.match?(/[a-z0-9]/) ||
              (prev.match?(/[A-Z]/) && nxt.match?(/[a-z]/)))
            words << current
            current = +''
          end
        end
        current << ch
      end
      words << current unless current.empty?
    end
    words
  end

  # heck-style UpperCamelCase: each word capitalised on its first character
  # with the remainder lowercased ("txSUCCESS" -> "TxSuccess").
  def heck_upper_camel(identifier)
    heck_words(identifier).map { |w| w[0].upcase + w[1..].downcase }.join
  end

  # serde rename_all = "snake_case": underscore before every non-leading
  # uppercase character, then lowercase ("IPv4" -> "i_pv4").
  def serde_snake_case(camel)
    out = +''
    camel.each_char.with_index do |ch, i|
      out << '_' if i.positive? && ch.match?(/[A-Z]/)
      out << ch.downcase
    end
    out
  end

  # Byte-wise longest common prefix across all identifiers, truncated back to
  # the last underscore (inclusive). Returns '' when the common prefix
  # contains no underscore.
  def shared_byte_prefix(identifiers)
    prefix = identifiers.first.dup
    identifiers.drop(1).each do |id|
      prefix = prefix[0, [prefix.length, id.length].min] || ''
      prefix = prefix[0...-1] until id.start_with?(prefix)
    end
    cut = prefix.rindex('_')
    cut ? prefix[0..cut] : ''
  end

  # Canonical JSON wire names for a full enum member list.
  #
  # Returns a Hash mapping each original XDR identifier (as supplied) to its
  # wire-form name, preserving insertion order. The input must be the enum's
  # complete member list: the shared prefix is a property of the whole enum,
  # so passing a subset (e.g. only the cases of one union) would derive
  # different names than the reference implementation.
  def enum_json_names(identifiers)
    result = {}
    return result if identifiers.empty?

    prefix = identifiers.length == 1 ? '' : shared_byte_prefix(identifiers)
    identifiers.each do |id|
      rest = id[prefix.length..] || ''
      raise "Prefix strip emptied enum member #{id.inspect}" if rest.empty?

      if rest.match?(/\A[0-9]/)
        raise "Digit-leading member #{id.inspect} with empty prefix" if prefix.empty?

        rest = prefix[0] + rest
      end
      result[id] = serde_snake_case(heck_upper_camel(rest))
    end
    result
  end

  # ---------------------------------------------------------------------------
  # Legacy naming (deprecated input aliases only)
  # ---------------------------------------------------------------------------
  #
  # The functions below reproduce the wire names emitted by SDK releases up to
  # 1.11.x. They exist solely so the generator can emit fromJson input aliases
  # for those names during the deprecation window; toJson never emits them.
  # Enum classes feed PHP-side constant names (post MEMBER_OVERRIDES /
  # MEMBER_PREFIX_STRIP), unions feed original XDR case identifiers - exactly
  # the inputs each path used when those names were emitted.

  # Split an identifier into lowercase tokens on underscores only. Case
  # boundaries inside a token are not split ("IPv4" -> ["ipv4"]).
  def legacy_tokenize_identifier(identifier)
    return [] if identifier.nil? || identifier.empty?

    identifier.downcase.split('_').reject(&:empty?)
  end

  # Longest leading-token sequence shared by every entry, shrunk from the
  # right until no entry's remaining-after-strip is empty.
  def legacy_longest_shared_prefix(token_lists)
    return [] if token_lists.empty?
    return [] if token_lists.any?(&:empty?)

    candidate = token_lists.first.dup
    token_lists.drop(1).each do |toks|
      i = 0
      max = [candidate.length, toks.length].min
      i += 1 while i < max && candidate[i] == toks[i]
      candidate = candidate.first(i)
      break if candidate.empty?
    end

    loop do
      break if candidate.empty?
      break unless token_lists.any? { |toks| toks.length <= candidate.length }

      candidate.pop
    end

    candidate
  end

  # Legacy wire names: tokenize on underscores, strip the shared leading-token
  # prefix, join with underscores. Single identifiers pass through lowercased.
  def legacy_strip_shared_prefix(identifiers)
    result = {}
    return result if identifiers.empty?

    token_lists = identifiers.map { |id| legacy_tokenize_identifier(id) }

    if identifiers.length == 1
      result[identifiers.first] = token_lists.first.join('_')
      return result
    end

    prefix = legacy_longest_shared_prefix(token_lists)
    identifiers.each_with_index do |id, idx|
      result[id] = token_lists[idx].drop(prefix.length).join('_')
    end
    result
  end
end
