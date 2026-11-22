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

  # Every toJson() routes through XdrJsonHelper::encodeText, which holds the
  # nesting limit; a type that called json_encode directly would write text
  # the SDK refuses to read.
  def test_to_json_facade_routes_through_the_shared_encode_entry
    out = StringIO.new
    @gen.send(:render_to_json_facade, out)

    assert_includes out.string, 'return XdrJsonHelper::encodeText($this->toJsonValue());'
    refute_includes out.string, 'json_encode',
                    'toJson must not call json_encode directly; it bypasses the nesting limit'
  end

  # ------------------------------------------------------------------
  # Fixed vs variable opaque length checks in XDR-JSON and TxRep
  # ------------------------------------------------------------------

  def test_fixed_opaque_struct_field_emits_length_check_in_json_and_txrep
    struct = find_ast_definition(:struct, 'ShortHashSeed')
    fields = @gen.send(:collect_struct_fields, struct, 'XdrShortHashSeed')

    json_out = StringIO.new
    @gen.send(:render_struct_sep51_methods, json_out, 'XdrShortHashSeed', 'XdrShortHashSeed', fields)
    assert_includes json_out.string, 'XdrJsonHelper::bytesToHex(XdrEncoder::checkFixedLength($this->seed, 16))'
    assert_includes json_out.string, "$seed = XdrEncoder::checkFixedLength(XdrJsonHelper::hexToBytes($value['seed']), 16);"

    txrep_out = StringIO.new
    @gen.send(:render_struct_txrep_methods, txrep_out, 'XdrShortHashSeed', 'XdrShortHashSeed', fields)
    assert_includes txrep_out.string, 'TxRepHelper::bytesToHex(XdrEncoder::checkFixedLength($this->seed, 16))'
    assert_includes txrep_out.string, "$seed = XdrEncoder::checkFixedLength(TxRepHelper::hexToBytes(TxRepHelper::getValue($map, $prefix . '.seed') ?? ''), 16);"
  end

  def test_variable_opaque_struct_field_omits_fixed_length_check
    struct = find_ast_definition(:struct, 'DecoratedSignature')
    fields = @gen.send(:collect_struct_fields, struct, 'XdrDecoratedSignature')

    json_out = StringIO.new
    @gen.send(:render_struct_sep51_methods, json_out, 'XdrDecoratedSignature', 'XdrDecoratedSignatureBase', fields)
    assert_includes json_out.string, 'XdrJsonHelper::bytesToHex(XdrEncoder::checkFixedLength($this->hint, 4))'
    assert_includes json_out.string, "'signature' => XdrJsonHelper::bytesToHex($this->signature),"
    refute_includes json_out.string, 'checkFixedLength($this->signature'

    txrep_out = StringIO.new
    @gen.send(:render_struct_txrep_methods, txrep_out, 'XdrDecoratedSignature', 'XdrDecoratedSignatureBase', fields)
    assert_includes txrep_out.string, 'TxRepHelper::bytesToHex(XdrEncoder::checkFixedLength($this->hint, 4))'
    assert_includes txrep_out.string, "$lines[$prefix . '.signature'] = TxRepHelper::bytesToHex($this->signature);"
    assert_includes txrep_out.string, "$signature = TxRepHelper::hexToBytes(TxRepHelper::getValue($map, $prefix . '.signature') ?? '');"
    refute_includes txrep_out.string, 'checkFixedLength($this->signature'

    # Inline variable opaque<> field (SerializedBinaryFuseFilter.fingerprints) also omits checkFixedLength.
    bf = find_ast_definition(:struct, 'SerializedBinaryFuseFilter')
    bf_fields = @gen.send(:collect_struct_fields, bf, 'XdrSerializedBinaryFuseFilter')
    bf_json = StringIO.new
    @gen.send(:render_struct_sep51_methods, bf_json, 'XdrSerializedBinaryFuseFilter', 'XdrSerializedBinaryFuseFilter', bf_fields)
    assert_includes bf_json.string, "'fingerprints' => XdrJsonHelper::bytesToHex($this->fingerprints),"
    assert_includes bf_json.string, "$fingerprints = XdrJsonHelper::hexToBytes($value['fingerprints']);"
    refute_includes bf_json.string, 'checkFixedLength'

    bf_txrep = StringIO.new
    @gen.send(:render_struct_txrep_methods, bf_txrep, 'XdrSerializedBinaryFuseFilter', 'XdrSerializedBinaryFuseFilter', bf_fields)
    assert_includes bf_txrep.string, "$lines[$prefix . '.fingerprints'] = TxRepHelper::bytesToHex($this->fingerprints);"
    assert_includes bf_txrep.string, "$fingerprints = TxRepHelper::hexToBytes(TxRepHelper::getValue($map, $prefix . '.fingerprints') ?? '');"
    refute_includes bf_txrep.string, 'checkFixedLength'
  end

  def test_txrep_fixed_opaque_array_emits_element_length_checks
    # No generated TxRep type currently carries a fixed-opaque array; pin both
    # struct array fields and array typedefs against real AST declarations.
    struct = find_ast_definition(:struct, 'FreezeBypassTxs')
    fields = @gen.send(:collect_struct_fields, struct, 'XdrFreezeBypassTxs')
    out = StringIO.new
    @gen.send(:render_struct_txrep_methods, out, 'XdrFreezeBypassTxs', 'XdrFreezeBypassTxs', fields)
    assert_includes out.string, "$lines[$prefix . '.txHashes[' . $i . ']'] = TxRepHelper::bytesToHex(XdrEncoder::checkFixedLength($this->txHashes[$i], 32));"
    assert_includes out.string, "$txHashes[] = XdrEncoder::checkFixedLength(TxRepHelper::hexToBytes(TxRepHelper::getValue($map, $prefix . '.txHashes[' . $i . ']') ?? ''), 32);"

    td = find_ast_definition(:typedef, 'TxAdvertVector')
    td_out = StringIO.new
    @gen.send(:render_array_typedef_txrep_methods, td_out, 'XdrTxAdvertVector', 'XdrTxAdvertVector', 'XdrTxAdvertVector', 'txAdvertVector', 'string', td.declaration)
    assert_includes td_out.string, "$lines[$prefix . '[' . $i . ']'] = TxRepHelper::bytesToHex(XdrEncoder::checkFixedLength($this->txAdvertVector[$i], 32));"
    assert_includes td_out.string, "$txAdvertVector[] = XdrEncoder::checkFixedLength(TxRepHelper::hexToBytes(TxRepHelper::getValue($map, $prefix . '[' . $i . ']') ?? ''), 32);"
  end

  def test_fixed_opaque_info_for_typespec_follows_multi_hop_typedef_chains
    # PoolID -> Hash -> opaque[32] (two typedef hops).
    struct = find_ast_definition(:struct, 'LiquidityPoolDepositOp')
    field = @gen.send(:collect_struct_fields, struct, 'XdrLiquidityPoolDepositOperation').find { |f| f[:xdr_name] == 'liquidityPoolID' }
    assert_equal({ size: '32', padded: false }, @gen.send(:fixed_opaque_info_for_typespec, field[:decl].type))
  end

  def test_scalar_typedef_emits_fixed_length_check_when_fixed_and_omits_when_variable
    fixed_json = StringIO.new
    @gen.send(:render_scalar_typedef_sep51_methods, fixed_json, 'XdrFixedBlob', 'fixedBlob', :opaque, { max_length: nil }, { size: 16, padded: false })
    assert_includes fixed_json.string, 'return XdrJsonHelper::bytesToHex(XdrEncoder::checkFixedLength($this->fixedBlob, 16));'
    assert_includes fixed_json.string, 'return new static(XdrEncoder::checkFixedLength(XdrJsonHelper::hexToBytes($value), 16));'

    fixed_txrep = StringIO.new
    @gen.send(:render_scalar_typedef_txrep_methods, fixed_txrep, 'XdrFixedBlob', 'XdrFixedBlob', 'XdrFixedBlob', 'fixedBlob', :opaque, { max_length: nil }, { size: 16, padded: false })
    assert_includes fixed_txrep.string, '$lines[$prefix] = TxRepHelper::bytesToHex(XdrEncoder::checkFixedLength($this->fixedBlob, 16));'
    assert_includes fixed_txrep.string, "return new XdrFixedBlob(XdrEncoder::checkFixedLength(TxRepHelper::hexToBytes(TxRepHelper::getValue($map, $prefix) ?? ''), 16));"

    var_json = StringIO.new
    @gen.send(:render_scalar_typedef_sep51_methods, var_json, 'XdrVarBlob', 'varBlob', :opaque, { max_length: 64 }, nil)
    assert_includes var_json.string, 'return XdrJsonHelper::bytesToHex($this->varBlob);'
    refute_includes var_json.string, 'checkFixedLength'
  end

  private

  XDR_DIR = File.join(__dir__, '..', '..', '..', 'xdr')

  def self.ast
    @ast ||= begin
      deprecated = Warning[:deprecated]
      Warning[:deprecated] = false
      Xdrgen::Compilation.new(Dir.glob(File.join(XDR_DIR, '*.x'))).ast
    ensure
      Warning[:deprecated] = deprecated
    end
  end

  def find_ast_definition(kind, xdr_name)
    # Load the AST first so xdrgen requires treetop before AST::Definitions constants autoload.
    nodes = [self.class.ast]
    klass = kind == :struct ? Xdrgen::AST::Definitions::Struct : Xdrgen::AST::Definitions::Typedef
    until nodes.empty?
      node = nodes.shift
      match = node.definitions.find { |d| d.is_a?(klass) && d.name.to_s == xdr_name }
      return match if match
      nodes.concat(node.namespaces)
    end
    flunk "no #{klass} #{xdr_name} in #{XDR_DIR}"
  end

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
