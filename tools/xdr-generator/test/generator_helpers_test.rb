# frozen_string_literal: true

require 'minitest/autorun'
require 'xdrgen'
require_relative '../generator/generator'

# Adversarial assertions for private codegen helpers in Generator that do not
# fit cleanly into the snapshot or json_helpers test suites.
#
# Currently this exercises php_string_escape, which must produce
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

  private

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
