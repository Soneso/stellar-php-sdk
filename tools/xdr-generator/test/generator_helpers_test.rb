# frozen_string_literal: true

require 'minitest/autorun'
require 'stringio'
require 'xdrgen'
require_relative '../generator/generator'

# Adversarial assertions for private codegen helpers in Generator that do not
# fit cleanly into the snapshot or json_helpers test suites.
#
# php_string_escape must produce
# single-quoted-PHP-literal-safe output for inputs containing backslashes,
# single quotes, or both. Ruby's two-argument String#gsub re-parses the
# replacement string for back-references, so a naive implementation
# (`s.gsub('\\', '\\\\').gsub("'", "\\\\'")`) does the wrong thing on inputs
# containing backslash bytes; the block form of gsub is required.
class GeneratorHelpersTest < Minitest::Test
  def setup
    @gen = Generator.new(nil, nil)
  end

  # The helper takes a literal backslash byte ('\\' inside a double-quoted
  # Ruby string) and must double it to two backslash bytes — the byte
  # sequence "\\\\" inside a Ruby string literal. PHP single-quoted literals
  # treat \\ as a single backslash, so two backslashes in source represent
  # one byte after PHP parses the literal.
  def test_php_string_escape_single_backslash
    result = @gen.send(:php_string_escape, "a\\b")
    assert_equal "a\\\\b", result
  end

  # A single quote inside a single-quoted PHP literal must be escaped as \'.
  # The Ruby string "c\\'d" represents the four bytes: c, \, ', d.
  def test_php_string_escape_single_quote
    result = @gen.send(:php_string_escape, "c'd")
    assert_equal "c\\'d", result
  end

  # The combined case is the real adversarial input: a backslash followed
  # by a single quote. The two passes must each see the right input — first
  # pass doubles the backslash (one byte -> two bytes); second pass escapes
  # the single quote with a leading backslash. Final form: e \ \ \ ' f.
  def test_php_string_escape_backslash_then_quote
    result = @gen.send(:php_string_escape, "e\\'f")
    assert_equal "e\\\\\\'f", result
  end

  # Verify the result is safe to embed inside a single-quoted PHP literal
  # by round-tripping through PHP's own string-literal grammar. A
  # single-quoted PHP literal recognises only the escape sequences \' and
  # \\; everything else is left verbatim. Reverse the escaping with the
  # same two rules to confirm we recover the original.
  def test_php_string_escape_round_trips_via_php_grammar
    inputs = ["a\\b", "c'd", "e\\'f", '', 'plain', "back\\\\slash", "'quote'"]
    inputs.each do |input|
      escaped = @gen.send(:php_string_escape, input)
      decoded = decode_php_single_quoted_literal(escaped)
      assert_equal input, decoded, "round-trip failed for input: #{input.inspect}"
    end
  end

  # ------------------------------------------------------------------
  # fromJson facade emission
  #
  # Every fromJson(string) entry point routes through
  # XdrJsonHelper::decodeText, which is where the SEP-0051 text-level input
  # rules live — currently the duplicate-object-key rejection. A type that
  # called json_decode directly would silently accept a document the rest of
  # the SDK refuses, so the template is pinned here rather than left to the
  # per-type snapshots alone.
  # ------------------------------------------------------------------

  def test_from_json_facade_routes_through_the_shared_decode_entry
    php = emit_from_json_facade

    assert_includes php, 'return static::fromJsonValue(XdrJsonHelper::decodeText($json));'
    refute_includes php, 'json_decode',
                    'fromJson must not call json_decode directly; it bypasses the input rules'
  end

  def test_from_json_facade_documents_both_rejection_paths
    php = emit_from_json_facade

    assert_includes php, '@throws JsonException If $json is not syntactically valid JSON.'
    assert_includes php, '@throws InvalidArgumentException If an object in $json repeats a key, or if'
  end

  private

  # Emit the generator's fromJson facade as PHP source.
  def emit_from_json_facade
    out = StringIO.new
    @gen.send(:render_from_json_facade, out)
    out.string
  end

  # Reverse the escaping a PHP single-quoted literal applies: \\ -> \, \' -> '.
  # Bytes that are not part of these two sequences are returned verbatim.
  def decode_php_single_quoted_literal(escaped)
    out = String.new
    i = 0
    while i < escaped.length
      ch = escaped[i]
      if ch == '\\' && i + 1 < escaped.length && (escaped[i + 1] == '\\' || escaped[i + 1] == "'")
        out << escaped[i + 1]
        i += 2
      else
        out << ch
        i += 1
      end
    end
    out
  end
end
