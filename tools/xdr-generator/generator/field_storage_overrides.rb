# frozen_string_literal: true

# FIELD_STORAGE_OVERRIDES, the struct twin of ARM_STORAGE_OVERRIDES: 32-byte id and
# hash fields that the hand-written wrapper and its subclasses hold as 64 hexadecimal
# characters, or as the "C..." or "L..." strkey where the entry names one, while the
# generated base holds the 32 bytes. The generator emits the members below into the
# base: its readers store a field in the form of the called class and its writers
# resolve it, so the wrapper inherits encode(), decode(), TxRep and SEP-51 JSON.
# SEP51_FIELD_OVERRIDES gives the JSON form of the resolved bytes.
#
# Keyed by [<PhpStructName>, <fieldName>]. label names the field in error messages;
# strkey is :contract or :liquidity_pool for a field with a strkey form, nil for a hash.
FIELD_STORAGE_OVERRIDES = {
  ['XdrConfigUpgradeSetKey', 'contractID'] => { label: 'Contract id', strkey: :contract },
  ['XdrConfigUpgradeSetKey', 'contentHash'] => { label: 'Content hash', strkey: nil },
  ['XdrInnerTransactionResultPair', 'transactionHash'] => { label: 'Transaction hash', strkey: nil },
  ['XdrLiquidityPoolDepositOperation', 'liquidityPoolID'] => { label: 'Liquidity pool id', strkey: :liquidity_pool },
  ['XdrLiquidityPoolWithdrawOperation', 'liquidityPoolID'] => { label: 'Liquidity pool id', strkey: :liquidity_pool },
}.freeze

# Version letter, article and StrKey decoder of each strkey form.
FIELD_STORAGE_STRKEYS = {
  contract: { letter: 'C', article: 'a', decoder: 'decodeContractId' },
  liquidity_pool: { letter: 'L', article: 'an', decoder: 'decodeLiquidityPoolId' },
}.freeze

# The length of a "C..." or "L..." strkey.
FIELD_STORAGE_STRKEY_LENGTH = 56

# A field's resolver is named <fieldName>Bytes. round_trip_emitter.rb matches this
# suffix to read the field's length from the writer call.
FIELD_STORAGE_RESOLVER_SUFFIX = 'Bytes'

# Members every type with stored fields shares. A value that starts with "S" or "s"
# may be a secret seed: heldHashBytes() names it by its kind, and every preview of a
# value redacts seeds, which may also sit inside a longer value.
FIELD_STORAGE_CLASS_MEMBERS = <<~PHP.chomp
  private static function heldHashForm(string $bytes): string {
      return self::holdsHashesAsText() ? bin2hex($bytes) : $bytes;
  }

  /**
   * Returns the 32 bytes of $value, a hash field in the form the called class holds.
   * Hexadecimal text is taken in either letter case.
   *
   * @throws InvalidArgumentException when $value is not 32 bytes in a class that holds
   * bytes, or not 64 hexadecimal characters in a class that holds text
   */
  private static function heldHashBytes(string $value, string $label, string $forms): string {
      if (!self::holdsHashesAsText()) {
          if (strlen($value) !== 32) {
              throw new InvalidArgumentException($label . ' must be 32 bytes; ' . strlen($value) . ' bytes given');
          }
          return $value;
      }
      $seedKind = SecretSeedRedaction::seedKind($value);
      if ($seedKind !== null) {
          throw new InvalidArgumentException($label . ' must be ' . $forms . ', ' . $seedKind . ' given');
      }
      if (strlen($value) !== 64 || !ctype_xdigit($value)) {
          $preview = XdrJsonHelper::safePreview(SecretSeedRedaction::redactSecretSeeds($value));
          throw new InvalidArgumentException(
              $label . ' must be ' . $forms . ', "' . $preview . '" (' . strlen($value) . ' characters) given'
          );
      }
      return hex2bin($value);
  }
PHP

def field_storage_resolver_name(field_name)
  "#{field_name}#{FIELD_STORAGE_RESOLVER_SUFFIX}"
end

# Returns the class members for the stored fields of a struct, or nil when it has none.
def field_storage_members(struct_name, fields)
  stored = fields.select { |f| f[:storage] }
  return nil if stored.empty?

  predicate = <<~PHP.chomp
    /**
     * True for #{struct_name} and its subclasses, which hold the hash fields as text:
     * 64 hexadecimal characters or the field's strkey. Other classes hold the 32 bytes.
     */
    private static function holdsHashesAsText(): bool {
        return is_a(static::class, #{struct_name}::class, true);
    }
  PHP
  [predicate, FIELD_STORAGE_CLASS_MEMBERS, *stored.map { |f| field_storage_resolver(f) }].join("\n\n")
end

def field_storage_resolver(field)
  entry = field[:storage]
  strkey = entry[:strkey] && FIELD_STORAGE_STRKEYS.fetch(entry[:strkey])
  forms = '64 hexadecimal characters'
  body = ["$value = $this->#{field[:name]};"]
  if strkey
    letter = strkey[:letter]
    forms = "#{strkey[:article]} \"#{letter}...\" strkey or #{forms}"
    error = <<~PHP.lines.map(&:chomp)
      throw new InvalidArgumentException(
          '#{entry[:label]} "' . XdrJsonHelper::safePreview(SecretSeedRedaction::redactSecretSeeds($value))
          . '" is not #{strkey[:article]} "#{letter}..." strkey: ' . $e->getMessage(),
          0,
          $e
      );
    PHP
    body << "if (self::holdsHashesAsText() && strlen($value) === #{FIELD_STORAGE_STRKEY_LENGTH} && $value[0] === '#{letter}') {"
    body.push('    try {', "        return StrKey::#{strkey[:decoder]}($value);", '    } catch (InvalidArgumentException $e) {')
    if letter.match?(/\A\h\z/)
      body << "        // #{letter} is a hexadecimal digit: a hexadecimal value that fails the strkey"
      body << '        // reading falls to the hexadecimal rule below, which names its length.'
      body.push('        if (!ctype_xdigit($value)) {', *error.map { |line| "            #{line}" }, '        }')
    else
      body.concat(error.map { |line| "        #{line}" })
    end
    body.push('    }', '}')
  end
  body << "return self::heldHashBytes($value, '#{entry[:label]}', '#{forms}');"
  [
    '/**',
    " * Returns the 32 bytes of the #{entry[:label].downcase} for every generated writer.",
    ' *',
    ' * @throws InvalidArgumentException when the field holds a value the called class does not take',
    ' */',
    "private function #{field_storage_resolver_name(field[:name])}(): string {",
    *body.map { |line| "    #{line}" },
    '}',
  ].join("\n")
end
