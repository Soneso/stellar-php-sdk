# frozen_string_literal: true

require 'minitest/autorun'
require 'xdrgen'
require_relative '../generator/generator'

# FIELD_STORAGE_OVERRIDES emission. Each listed field is held as text by the
# wrapper and as 32 bytes by the base, so every generated writer has to read it
# through the emitted resolver and every generated reader has to store it in
# the form of the called class. A writer left on the field itself would encode
# hexadecimal text as bytes, and a reader left on the raw default would hand
# the wrapper bytes.
class FieldStorageOverridesTest < Minitest::Test
  OUTPUT_DIR = File.join(__dir__, '..', '..', '..', 'Soneso', 'StellarSDK', 'Xdr')
  XDR_DIR = File.join(__dir__, '..', '..', '..', 'xdr')

  def setup
    @gen = Generator.new(nil, nil)
  end

  # Minitest turns on deprecation warnings, which treetop 1.5.3 prints while it
  # parses, so they are off for the parse.
  def self.ast
    @ast ||= begin
      deprecated = Warning[:deprecated]
      Warning[:deprecated] = false
      Xdrgen::Compilation.new(Dir.glob(File.join(XDR_DIR, '*.x'))).ast
    ensure
      Warning[:deprecated] = deprecated
    end
  end

  # One field of a struct of the pinned XDR definitions, as the generator collects it.
  def collected_field(xdr_struct, member)
    nodes = [self.class.ast]
    until nodes.empty?
      node = nodes.shift
      struct = node.definitions.find { |d| d.is_a?(Xdrgen::AST::Definitions::Struct) && d.name.to_s == xdr_struct }
      return @gen.send(:collect_struct_fields, struct, "Xdr#{xdr_struct}").find { |f| f[:xdr_name] == member } if struct

      nodes.concat(node.namespaces)
    end
    flunk "no struct #{xdr_struct} in #{XDR_DIR}"
  end

  def method_body(source, signature)
    source[/^    #{Regexp.escape(signature)}.*?^    \}/m].to_s
  end

  def test_every_entry_is_resolved_in_every_writer_and_stored_by_every_reader
    FIELD_STORAGE_OVERRIDES.each do |(struct, field), entry|
      base = File.read(File.join(OUTPUT_DIR, "#{struct}Base.php"))
      file = "#{struct}Base.php"
      resolver = "$this->#{field}Bytes()"

      assert_includes base, "return is_a(static::class, #{struct}::class, true);", "#{file} must name its wrapper"
      body = method_body(base, "private function #{field}Bytes(): string {")
      refute_empty body, "#{file} must define #{field}Bytes()"
      assert_includes body, "return self::heldHashBytes($value, '#{entry[:label]}', ", "#{file} #{field}Bytes()"
      if entry[:strkey]
        strkey = FIELD_STORAGE_STRKEYS.fetch(entry[:strkey])
        # 56 is the length of a C or L strkey that carries 32 bytes, kept as a fixed value so
        # the test does not read the generator's own constant.
        assert_includes body, "strlen($value) === 56 && $value[0] === '#{strkey[:letter]}'", "#{file} #{field}Bytes()"
        assert_includes body, "return StrKey::#{strkey[:decoder]}($value);", "#{file} #{field}Bytes()"
      else
        refute_includes body, 'StrKey::', "#{file} #{field}Bytes() must take hexadecimal only"
      end

      assert_includes method_body(base, 'public function encode(): string {'),
                      "XdrEncoder::opaqueFixed(#{resolver}, 32)", "#{file} encode()"
      assert_includes method_body(base, 'public static function decode(XdrBuffer $xdr): static {'),
                      "$#{field} = self::heldHashForm($xdr->readOpaqueFixed(32));", "#{file} decode()"
      assert_includes method_body(base, 'public function toJsonValue(): array {'), resolver, "#{file} toJsonValue()"
      assert_includes method_body(base, 'public static function fromJsonValue(mixed $value): static {'),
                      "$#{field} = self::heldHashForm($#{field});", "#{file} fromJsonValue()"
      if base.include?('public function toTxRep(')
        assert_includes method_body(base, 'public function toTxRep(string $prefix, array &$lines): void {'),
                        "TxRepHelper::bytesToHex(#{resolver})", "#{file} toTxRep()"
        assert_match(/\$#{field} = TxRepHelper::getValue\(\$map, \$prefix \. '\.\w+'\) \?\? '';\s+if \(!self::holdsHashesAsText\(\)\) \{\s+\$#{field} = TxRepHelper::hexToBytes\(\$#{field}\);/,
                     base, "#{file} fromTxRep() must decode the line only where the class holds bytes")
      end
      refute_match(/\(\$this->#{field}\b/, base, "#{file} must not pass the field to a writer unresolved")

      wrapper = File.read(File.join(OUTPUT_DIR, "#{struct}.php"))
      %w[encode decode toTxRep fromTxRep toJsonValue fromJsonValue].each do |method|
        refute_match(/function #{method}\(/, wrapper, "#{struct}.php must inherit #{method}()")
      end
    end
  end

  def test_a_seed_is_named_by_its_kind_first_and_every_preview_is_redacted
    members = FIELD_STORAGE_CLASS_MEMBERS
    seed_check = members.index('SecretSeedRedaction::seedKind($value)')
    preview = members.index('XdrJsonHelper::safePreview(SecretSeedRedaction::redactSecretSeeds($value))')
    assert seed_check && preview && seed_check < preview, 'heldHashBytes() must check for a seed before it previews'
    FIELD_STORAGE_OVERRIDES.keys.map(&:first).uniq.each do |struct|
      base = File.read(File.join(OUTPUT_DIR, "#{struct}Base.php"))
      refute_match(/safePreview\((?!SecretSeedRedaction::redactSecretSeeds\(\$value\)\))/, base,
                   "#{struct}Base.php must redact secret seeds in every preview")
    end
  end

  def test_only_the_listed_types_get_the_members
    holders = Dir.glob(File.join(OUTPUT_DIR, '*.php')).select { |f| File.read(f).include?('holdsHashesAsText') }
    expected = FIELD_STORAGE_OVERRIDES.keys.map { |(struct, _)| "#{struct}Base.php" }.uniq.sort
    assert_equal expected, holders.map { |f| File.basename(f) }.sort
  end

  def test_a_required_32_byte_field_of_a_base_wrapper_struct_is_accepted
    field = collected_field('ConfigUpgradeSetKey', 'contractID')
    @gen.send(:check_field_storage, 'XdrConfigUpgradeSetKey', true, [field])
    assert_equal 'Contract id', field[:storage][:label]
  end

  # Each case breaks one condition of the field declaration and keeps the others.
  def test_a_field_the_storage_cannot_hold_is_refused
    valid = collected_field('ConfigUpgradeSetKey', 'contractID')
    {
      'not a base' => ['XdrConfigUpgradeSetKey', false, valid],
      'own constructor' => ['XdrTransaction', true, valid],
      'optional' => ['XdrConfigUpgradeSetKey', true, valid.merge(is_optional: true)],
      'array' => ['XdrConfigUpgradeSetKey', true, valid.merge(is_array: true)],
      'not 32 bytes' => ['XdrConfigUpgradeSetKey', true, collected_field('Price', 'n').merge(storage: valid[:storage])],
    }.each do |name, (struct, is_base, field)|
      error = assert_raises(RuntimeError, name) { @gen.send(:check_field_storage, struct, is_base, [field]) }
      assert_includes error.message, "#{struct}.#{field[:name]}", name
    end
  end
end
