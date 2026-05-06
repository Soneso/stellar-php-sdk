# frozen_string_literal: true

# Per-type SEP-51 (XDR-JSON) emission overrides for Stellar-specific types.
#
# This registry maps an XDR type name (the PHP class name, e.g.
# "XdrAccountID") to a hash of `:to_body` / `:from_body` Ruby procs that
# emit the bespoke PHP method bodies for that type. The generator consults
# this registry before falling back to the default enum/struct/union
# emission. Per-field overrides in `sep51_field_overrides.rb` take
# precedence over this registry; the precedence chain is:
#
#   sep51_field_overrides[parent_type, field_name]
#     > stellar_json_overrides[type]
#     > generator default emission for the type's syntactic shape.
#
# The registry holds three categories of overrides:
#
# 1. Cat-A bespoke types: types in `cat_a_inline_targets.rb` whose JSON
#    wire form cannot be produced by the generator's default emission.
#    Examples: single-string strkey forms (XdrPublicKey, XdrSignerKey,
#    XdrAccountID-via-PublicKey, XdrSignedPayload), discriminated unions
#    that flatten to a single string (XdrAsset, XdrMemo as native/none
#    bare-strings), GMP integer reassembly (XdrInt128Parts/UInt128Parts/
#    Int256Parts/UInt256Parts).
#
# 2. Cat-B bespoke types: types in `BASE_WRAPPER_TYPES` whose Base file
#    receives bespoke emission (instead of the generator default). The
#    runtime instance is the wrapper subclass; the wrapper's decode path
#    determines the storage form `$this->fieldName` carries when SEP-51
#    runs. Examples: XdrAccountID (G-strkey via the wrapped XdrPublicKey
#    ed25519 field), XdrSCAddress (5-arm strkey dispatch), XdrMuxedAccount
#    (G/M-strkey dispatch), XdrMuxedAccountMed25519 (M-strkey over a
#    40-byte ed25519+id pack), XdrClaimableBalanceID (B-strkey over a
#    33-byte slice).
#
# 3. Wrapper carve-outs: hand-edited wrapper files registered here for
#    reviewer verifiability. The body recorded against the wrapper class
#    name is the literal PHP source the wrapper file commits.
#
# Each proc takes a context hash with keys (most are not used by every
# proc; the procs read the keys they need):
#   :class_name    -- the PHP class name being emitted into.
#   :indent        -- the leading indent string (default "    ").
#
# Each proc returns the FULL method body — i.e. the lines between the
# opening brace and the closing brace of the method, INCLUDING the
# closing brace's preceding indentation. The generator wraps the body in
# the standard method signature(s) (toJsonValue, fromJsonValue, toJson,
# fromJson). Bodies are emitted verbatim into the generated PHP file.

module StellarJsonOverrides
  module_function

  # Build the canonical toJson/fromJson facade as a single PHP source
  # string suitable for injection by callers that want the full
  # four-method block. Reproduces the generator's render_to_json_facade
  # and render_from_json_facade exactly.
  def facade_block
    <<~PHP.strip
      public function toJson(): string {
          return json_encode(
              $this->toJsonValue(),
              JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
          );
      }

      public static function fromJson(string $json): static {
          return static::fromJsonValue(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
      }
    PHP
  end

  # Registry of type-level overrides. Each entry maps an XDR type name to
  # a hash of:
  #   :to_value_signature   -- PHP signature of toJsonValue (default:
  #                            "public function toJsonValue(): mixed").
  #   :to_body              -- proc returning the body of toJsonValue.
  #   :from_body            -- proc returning the body of fromJsonValue.
  #
  # The procs return PHP source strings that include the body lines
  # between { and } but NOT the braces themselves. Each line in the
  # returned string is already indented from the method's opening brace
  # by 4 spaces (2 levels: 1 for the class, 1 for the method).
  REGISTRY = {

    # =====================================================================
    # Category A — bespoke single-string strkey emitters
    # =====================================================================

    # XdrAccountID — G-strkey over the wrapped XdrPublicKey's ed25519
    # field. Wrapper carve-out: the runtime instance is always XdrAccountID
    # (the hand-written wrapper), whose constructor stores the strkey
    # form in a private field accessible only via getAccountId(). The
    # inherited XdrPublicKey field's ed25519 byte buffer is set lazily by
    # the wrapper's encode(); SEP-51 must not depend on that side effect.
    # Both the to-side and from-side rely on the wrapper's hand-written
    # API rather than the base's raw fields.
    'XdrAccountID' => {
      to_value_signature: 'public function toJsonValue(): string',
      to_body: lambda do |_ctx|
        <<~PHP.chomp
                  return $this->getAccountId();
        PHP
      end,
      # Eagerly validate the strkey on the from-side. The wrapper's
      # constructor stores the raw input string and only resolves it via
      # StrKey::decodeAccountId(...) lazily inside encode(); SEP-51 input
      # arrives over the network trust boundary, so we must reject malformed
      # strkeys at the parse site rather than letting them propagate into a
      # stored field. Mirrors the eager-validation pattern used by the
      # sibling XdrPublicKey override.
      from_body: lambda do |_ctx|
        <<~PHP.chomp
                  if (!is_string($value)) {
                      throw new \\InvalidArgumentException(
                          'Expected string for XdrAccountID JSON value, got ' . get_debug_type($value)
                      );
                  }
                  StrKey::decodeAccountId($value);
                  return new XdrAccountID($value);
        PHP
      end,
    },

    # XdrPublicKey — G-strkey over the ed25519 union arm. Uniquely typed
    # so SEP-51 callers cannot confuse it with XdrAccountID's wrapper.
    'XdrPublicKey' => {
      to_value_signature: 'public function toJsonValue(): string',
      to_body: lambda do |_ctx|
        <<~PHP.chomp
                  if ($this->ed25519 === null) {
                      throw new \\InvalidArgumentException(
                          'XdrPublicKey ed25519 field is null; cannot encode strkey'
                      );
                  }
                  return StrKey::encodeAccountId($this->ed25519);
        PHP
      end,
      from_body: lambda do |_ctx|
        <<~PHP.chomp
                  if (!is_string($value)) {
                      throw new \\InvalidArgumentException(
                          'Expected string for XdrPublicKey JSON value, got ' . get_debug_type($value)
                      );
                  }
                  $result = new static(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519));
                  $result->ed25519 = StrKey::decodeAccountId($value);
                  return $result;
        PHP
      end,
    },

    # XdrNodeID — typedef PublicKey NodeID; identical wire form to
    # XdrPublicKey: G-strkey over ed25519. The PHP class wraps XdrPublicKey
    # under field name `nodeID`.
    'XdrNodeID' => {
      to_value_signature: 'public function toJsonValue(): string',
      to_body: lambda do |_ctx|
        <<~PHP.chomp
                  return $this->nodeID->toJsonValue();
        PHP
      end,
      from_body: lambda do |_ctx|
        <<~PHP.chomp
                  return new static(XdrPublicKey::fromJsonValue($value));
        PHP
      end,
    },

    # XdrSignerKey — single-string dispatch over the four discriminant
    # arms (G/T/X/P strkey). Bypasses the union-as-object emission.
    'XdrSignerKey' => {
      to_value_signature: 'public function toJsonValue(): string',
      to_body: lambda do |_ctx|
        <<~PHP.chomp
                  switch ($this->type->getValue()) {
                      case XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519:
                          if ($this->ed25519 === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrSignerKey ed25519 field is null'
                              );
                          }
                          return StrKey::encodeAccountId($this->ed25519);
                      case XdrSignerKeyType::SIGNER_KEY_TYPE_PRE_AUTH_TX:
                          if ($this->preAuthTx === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrSignerKey preAuthTx field is null'
                              );
                          }
                          return StrKey::encodePreAuthTx($this->preAuthTx);
                      case XdrSignerKeyType::SIGNER_KEY_TYPE_HASH_X:
                          if ($this->hashX === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrSignerKey hashX field is null'
                              );
                          }
                          return StrKey::encodeSha256Hash($this->hashX);
                      case XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD:
                          if ($this->signedPayload === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrSignerKey signedPayload field is null'
                              );
                          }
                          return StrKey::encodeXdrSignedPayload($this->signedPayload);
                      default:
                          throw new \\InvalidArgumentException(
                              'Unknown XdrSignerKey discriminant: ' . $this->type->getValue()
                          );
                  }
        PHP
      end,
      from_body: lambda do |_ctx|
        <<~PHP.chomp
                  if (!is_string($value)) {
                      throw new \\InvalidArgumentException(
                          'Expected string for XdrSignerKey JSON value, got ' . get_debug_type($value)
                      );
                  }
                  if ($value === '') {
                      throw new \\InvalidArgumentException(
                          'Empty XdrSignerKey JSON value'
                      );
                  }
                  $prefix = $value[0];
                  if ($prefix === 'G') {
                      $result = new static(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519));
                      $result->ed25519 = StrKey::decodeAccountId($value);
                      return $result;
                  }
                  if ($prefix === 'T') {
                      $result = new static(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_PRE_AUTH_TX));
                      $result->preAuthTx = StrKey::decodePreAuthTx($value);
                      return $result;
                  }
                  if ($prefix === 'X') {
                      $result = new static(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_HASH_X));
                      $result->hashX = StrKey::decodeSha256Hash($value);
                      return $result;
                  }
                  if ($prefix === 'P') {
                      $result = new static(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD));
                      $result->signedPayload = StrKey::decodeXdrSignedPayload($value);
                      return $result;
                  }
                  throw new \\InvalidArgumentException(
                      'Invalid XdrSignerKey strkey prefix: ' . XdrJsonHelper::safePreview($value)
                  );
        PHP
      end,
    },

    # XdrSignedPayload — standalone P-strkey emitter. The wire form is a
    # single string; the inverse decodes back into a fresh XdrSignedPayload.
    'XdrSignedPayload' => {
      to_value_signature: 'public function toJsonValue(): string',
      to_body: lambda do |_ctx|
        <<~PHP.chomp
                  return StrKey::encodeXdrSignedPayload($this);
        PHP
      end,
      from_body: lambda do |_ctx|
        <<~PHP.chomp
                  if (!is_string($value)) {
                      throw new \\InvalidArgumentException(
                          'Expected string for XdrSignedPayload JSON value, got ' . get_debug_type($value)
                      );
                  }
                  return StrKey::decodeXdrSignedPayload($value);
        PHP
      end,
    },

    # =====================================================================
    # Category A — discriminated unions with mixed string/object forms
    # =====================================================================

    # XdrAsset — native arm renders as the bare string "native"; alphanum4
    # / alphanum12 arms render as single-key objects whose values delegate
    # to the wrapper sub-types' toJsonValue.
    'XdrAsset' => {
      to_value_signature: 'public function toJsonValue(): mixed',
      to_body: lambda do |_ctx|
        <<~PHP.chomp
                  switch ($this->type->getValue()) {
                      case XdrAssetType::ASSET_TYPE_NATIVE:
                          return 'native';
                      case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4:
                          if ($this->alphaNum4 === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrAsset alphaNum4 field is null'
                              );
                          }
                          return ['credit_alphanum4' => $this->alphaNum4->toJsonValue()];
                      case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12:
                          if ($this->alphaNum12 === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrAsset alphaNum12 field is null'
                              );
                          }
                          return ['credit_alphanum12' => $this->alphaNum12->toJsonValue()];
                      default:
                          throw new \\InvalidArgumentException(
                              'Unknown XdrAsset discriminant: ' . $this->type->getValue()
                          );
                  }
        PHP
      end,
      from_body: lambda do |_ctx|
        <<~PHP.chomp
                  if (is_array($value) && array_key_exists('$schema', $value)) {
                      unset($value['$schema']);
                  }
                  if (is_string($value)) {
                      if ($value !== 'native') {
                          throw new \\InvalidArgumentException(
                              'Unknown XdrAsset bare string: ' . XdrJsonHelper::safePreview($value)
                          );
                      }
                      return new static(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
                  }
                  if (!is_array($value) || count($value) !== 1) {
                      throw new \\InvalidArgumentException(
                          'Expected single-key object or "native" for XdrAsset JSON value'
                      );
                  }
                  $key = array_key_first($value);
                  if ($key === 'credit_alphanum4') {
                      $result = new static(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
                      $result->alphaNum4 = XdrAssetAlphaNum4::fromJsonValue($value['credit_alphanum4']);
                      return $result;
                  }
                  if ($key === 'credit_alphanum12') {
                      $result = new static(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
                      $result->alphaNum12 = XdrAssetAlphaNum12::fromJsonValue($value['credit_alphanum12']);
                      return $result;
                  }
                  throw new \\InvalidArgumentException(
                      'Unknown arm key for XdrAsset: ' . XdrJsonHelper::safePreview($key)
                  );
        PHP
      end,
    },

    # XdrMemo — none arm renders as the bare string "none"; text/id/hash/
    # return arms render as single-key objects. Note: MEMO_RETURN's
    # discriminant prefix-strips to "return" (not "ret_hash" — the field
    # name retHash maps via the discriminant, not via the field name).
    'XdrMemo' => {
      to_value_signature: 'public function toJsonValue(): mixed',
      to_body: lambda do |_ctx|
        <<~PHP.chomp
                  switch ($this->type->getValue()) {
                      case XdrMemoType::MEMO_NONE:
                          return 'none';
                      case XdrMemoType::MEMO_TEXT:
                          if ($this->text === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrMemo text field is null'
                              );
                          }
                          return ['text' => XdrJsonHelper::escapeString($this->text)];
                      case XdrMemoType::MEMO_ID:
                          if ($this->id === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrMemo id field is null'
                              );
                          }
                          return ['id' => XdrJsonHelper::uint64ToString($this->id)];
                      case XdrMemoType::MEMO_HASH:
                          if ($this->hash === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrMemo hash field is null'
                              );
                          }
                          return ['hash' => XdrJsonHelper::bytesToHex($this->hash)];
                      case XdrMemoType::MEMO_RETURN:
                          if ($this->returnHash === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrMemo returnHash field is null'
                              );
                          }
                          return ['return' => XdrJsonHelper::bytesToHex($this->returnHash)];
                      default:
                          throw new \\InvalidArgumentException(
                              'Unknown XdrMemo discriminant: ' . $this->type->getValue()
                          );
                  }
        PHP
      end,
      from_body: lambda do |_ctx|
        <<~PHP.chomp
                  if (is_array($value) && array_key_exists('$schema', $value)) {
                      unset($value['$schema']);
                  }
                  if (is_string($value)) {
                      if ($value !== 'none') {
                          throw new \\InvalidArgumentException(
                              'Unknown XdrMemo bare string: ' . XdrJsonHelper::safePreview($value)
                          );
                      }
                      return new static(new XdrMemoType(XdrMemoType::MEMO_NONE));
                  }
                  if (!is_array($value) || count($value) !== 1) {
                      throw new \\InvalidArgumentException(
                          'Expected single-key object or "none" for XdrMemo JSON value'
                      );
                  }
                  $key = array_key_first($value);
                  if ($key === 'text') {
                      if (!is_string($value['text'])) {
                          throw new \\InvalidArgumentException(
                              'Expected string for XdrMemo text arm, got ' . get_debug_type($value['text'])
                          );
                      }
                      $result = new static(new XdrMemoType(XdrMemoType::MEMO_TEXT));
                      $result->text = XdrJsonHelper::unescapeString($value['text']);
                      return $result;
                  }
                  if ($key === 'id') {
                      $result = new static(new XdrMemoType(XdrMemoType::MEMO_ID));
                      if (!is_string($value['id']) && !is_int($value['id'])) {
                          throw new \\InvalidArgumentException(
                              'Expected string or int for XdrMemo id arm, got ' . get_debug_type($value['id'])
                          );
                      }
                      $result->id = XdrJsonHelper::stringToUint64($value['id']);
                      return $result;
                  }
                  if ($key === 'hash') {
                      if (!is_string($value['hash'])) {
                          throw new \\InvalidArgumentException(
                              'Expected hex string for XdrMemo hash arm, got ' . get_debug_type($value['hash'])
                          );
                      }
                      $result = new static(new XdrMemoType(XdrMemoType::MEMO_HASH));
                      $result->hash = XdrJsonHelper::hexToBytes($value['hash']);
                      return $result;
                  }
                  if ($key === 'return') {
                      if (!is_string($value['return'])) {
                          throw new \\InvalidArgumentException(
                              'Expected hex string for XdrMemo return arm, got ' . get_debug_type($value['return'])
                          );
                      }
                      $result = new static(new XdrMemoType(XdrMemoType::MEMO_RETURN));
                      $result->returnHash = XdrJsonHelper::hexToBytes($value['return']);
                      return $result;
                  }
                  throw new \\InvalidArgumentException(
                      'Unknown arm key for XdrMemo: ' . XdrJsonHelper::safePreview($key)
                  );
        PHP
      end,
    },

    # =====================================================================
    # Category A — GMP integer reassembly
    # =====================================================================

    # XdrUInt128Parts — base-10 string assembled from two uint64 limbs.
    # The round-trip path reads back unsigned uint64 limb decimal strings;
    # PHP integers are signed 64-bit, so values that exceed PHP_INT_MAX must
    # be wrapped to their two's-complement signed-int representation before
    # storing. intval() would clip at PHP_INT_MAX and lose the upper bit;
    # we therefore use an inline closure that consults gmp_cmp against
    # 2^63 and subtracts 2^64 when needed.
    'XdrUInt128Parts' => {
      to_value_signature: 'public function toJsonValue(): string',
      to_body: lambda do |_ctx|
        <<~PHP.chomp
                  return XdrJsonHelper::uint128PartsToString((string) $this->hi, (string) $this->lo);
        PHP
      end,
      from_body: lambda do |_ctx|
        <<~PHP.chomp
                  if (!is_string($value)) {
                      throw new \\InvalidArgumentException(
                          'Expected string for XdrUInt128Parts JSON value, got ' . get_debug_type($value)
                      );
                  }
                  $parts = XdrJsonHelper::stringToUint128Parts($value);
                  return new static(
                      XdrJsonHelper::wrapUnsignedToSignedInt($parts['hi']),
                      XdrJsonHelper::wrapUnsignedToSignedInt($parts['lo'])
                  );
        PHP
      end,
    },

    # XdrInt128Parts — int128 = hi (signed int64) * 2^64 + lo (unsigned
    # uint64). The from-side keeps `hi` as a PHP signed int via intval (the
    # helper returns it in signed int64 base-10 form already), and wraps
    # `lo` from its unsigned uint64 form to PHP's signed-int representation
    # using the same closure as XdrUInt128Parts.
    'XdrInt128Parts' => {
      to_value_signature: 'public function toJsonValue(): string',
      to_body: lambda do |_ctx|
        <<~PHP.chomp
                  return XdrJsonHelper::int128PartsToString((string) $this->hi, (string) $this->lo);
        PHP
      end,
      from_body: lambda do |_ctx|
        <<~PHP.chomp
                  if (!is_string($value)) {
                      throw new \\InvalidArgumentException(
                          'Expected string for XdrInt128Parts JSON value, got ' . get_debug_type($value)
                      );
                  }
                  $parts = XdrJsonHelper::stringToInt128Parts($value);
                  return new static(
                      intval($parts['hi']),
                      XdrJsonHelper::wrapUnsignedToSignedInt($parts['lo'])
                  );
        PHP
      end,
    },

    # XdrUInt256Parts — four uint64 limbs. All four are unsigned, so each
    # value can be larger than PHP_INT_MAX and must be wrapped to PHP signed
    # int form. Note: the helper returns keys in camelCase ('hiHi', 'hiLo',
    # 'loHi', 'loLo') matching the underlying XdrJsonHelper return shape.
    'XdrUInt256Parts' => {
      to_value_signature: 'public function toJsonValue(): string',
      to_body: lambda do |_ctx|
        <<~PHP.chomp
                  return XdrJsonHelper::uint256PartsToString(
                      (string) $this->hiHi,
                      (string) $this->hiLo,
                      (string) $this->loHi,
                      (string) $this->loLo
                  );
        PHP
      end,
      from_body: lambda do |_ctx|
        <<~PHP.chomp
                  if (!is_string($value)) {
                      throw new \\InvalidArgumentException(
                          'Expected string for XdrUInt256Parts JSON value, got ' . get_debug_type($value)
                      );
                  }
                  $parts = XdrJsonHelper::stringToUint256Parts($value);
                  return new static(
                      XdrJsonHelper::wrapUnsignedToSignedInt($parts['hiHi']),
                      XdrJsonHelper::wrapUnsignedToSignedInt($parts['hiLo']),
                      XdrJsonHelper::wrapUnsignedToSignedInt($parts['loHi']),
                      XdrJsonHelper::wrapUnsignedToSignedInt($parts['loLo'])
                  );
        PHP
      end,
    },

    # XdrInt256Parts — hiHi (signed int64) and hiLo/loHi/loLo (unsigned
    # uint64). The hiHi limb is already in signed int64 form per the
    # helper; the other three limbs require the unsigned-to-signed wrap.
    'XdrInt256Parts' => {
      to_value_signature: 'public function toJsonValue(): string',
      to_body: lambda do |_ctx|
        <<~PHP.chomp
                  return XdrJsonHelper::int256PartsToString(
                      (string) $this->hiHi,
                      (string) $this->hiLo,
                      (string) $this->loHi,
                      (string) $this->loLo
                  );
        PHP
      end,
      from_body: lambda do |_ctx|
        <<~PHP.chomp
                  if (!is_string($value)) {
                      throw new \\InvalidArgumentException(
                          'Expected string for XdrInt256Parts JSON value, got ' . get_debug_type($value)
                      );
                  }
                  $parts = XdrJsonHelper::stringToInt256Parts($value);
                  return new static(
                      intval($parts['hiHi']),
                      XdrJsonHelper::wrapUnsignedToSignedInt($parts['hiLo']),
                      XdrJsonHelper::wrapUnsignedToSignedInt($parts['loHi']),
                      XdrJsonHelper::wrapUnsignedToSignedInt($parts['loLo'])
                  );
        PHP
      end,
    },

    # =====================================================================
    # Category B — bespoke wrappers with non-standard JSON shape
    # =====================================================================

    # XdrClaimableBalanceID — B-strkey over the 33-byte payload. SEP-0023
    # specifies the B-strkey body as the type byte (0x00 for V0) followed
    # by the 32-byte hash. The wrapper stores the hash as a 64-char hex
    # string; we hex2bin and prepend the type byte to assemble the
    # 33-byte buffer.
    'XdrClaimableBalanceID' => {
      to_value_signature: 'public function toJsonValue(): string',
      to_body: lambda do |_ctx|
        <<~PHP.chomp
                  switch ($this->type->getValue()) {
                      case XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0:
                          if ($this->hash === null || $this->hash === '') {
                              throw new \\InvalidArgumentException(
                                  'XdrClaimableBalanceID hash field is null or empty'
                              );
                          }
                          $rawHash = hex2bin($this->hash);
                          if ($rawHash === false || strlen($rawHash) !== 32) {
                              throw new \\InvalidArgumentException(
                                  'XdrClaimableBalanceID hash must be a 64-char hex string'
                              );
                          }
                          return StrKey::encodeClaimableBalanceId($rawHash);
                      default:
                          throw new \\InvalidArgumentException(
                              'Unknown XdrClaimableBalanceID discriminant: ' . $this->type->getValue()
                          );
                  }
        PHP
      end,
      from_body: lambda do |_ctx|
        <<~PHP.chomp
                  if (!is_string($value)) {
                      throw new \\InvalidArgumentException(
                          'Expected string for XdrClaimableBalanceID JSON value, got ' . get_debug_type($value)
                      );
                  }
                  $raw = StrKey::decodeClaimableBalanceId($value);
                  if (strlen($raw) !== 33) {
                      throw new \\InvalidArgumentException(
                          'Decoded XdrClaimableBalanceID must be 33 bytes; got ' . strlen($raw)
                          . ' for input ' . XdrJsonHelper::safePreview($value)
                      );
                  }
                  $typeByte = ord($raw[0]);
                  if ($typeByte !== XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0) {
                      throw new \\InvalidArgumentException(
                          'Unsupported XdrClaimableBalanceID type byte: ' . $typeByte
                          . ' for input ' . XdrJsonHelper::safePreview($value)
                      );
                  }
                  $hashHex = bin2hex(substr($raw, 1, 32));
                  return new XdrClaimableBalanceID(
                      new XdrClaimableBalanceIDType(XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0),
                      $hashHex
                  );
        PHP
      end,
    },

    # XdrSCAddress — five-arm strkey dispatch. account -> G-strkey via
    # delegation to XdrAccountID; contract -> C-strkey (hex storage);
    # muxed_account -> M-strkey over a 40-byte ed25519 || id pack;
    # claimable_balance -> delegation to XdrClaimableBalanceID (B-strkey);
    # liquidity_pool -> L-strkey (hex storage).
    'XdrSCAddress' => {
      to_value_signature: 'public function toJsonValue(): string',
      to_body: lambda do |_ctx|
        <<~PHP.chomp
                  switch ($this->type->getValue()) {
                      case XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT:
                          if ($this->accountId === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrSCAddress accountId field is null'
                              );
                          }
                          return $this->accountId->toJsonValue();
                      case XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT:
                          if ($this->contractId === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrSCAddress contractId field is null'
                              );
                          }
                          return StrKey::encodeContractIdHex($this->contractId);
                      case XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT:
                          if ($this->muxedAccount === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrSCAddress muxedAccount field is null'
                              );
                          }
                          $packed = XdrEncoder::opaqueFixed($this->muxedAccount->ed25519, 32);
                          $packed .= XdrEncoder::unsignedInteger64($this->muxedAccount->id);
                          return StrKey::encodeMuxedAccountId($packed);
                      case XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE:
                          if ($this->claimableBalanceId === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrSCAddress claimableBalanceId field is null'
                              );
                          }
                          return $this->claimableBalanceId->toJsonValue();
                      case XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL:
                          if ($this->liquidityPoolId === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrSCAddress liquidityPoolId field is null'
                              );
                          }
                          return StrKey::encodeLiquidityPoolIdHex($this->liquidityPoolId);
                      default:
                          throw new \\InvalidArgumentException(
                              'Unknown XdrSCAddress discriminant: ' . $this->type->getValue()
                          );
                  }
        PHP
      end,
      from_body: lambda do |_ctx|
        <<~PHP.chomp
                  if (!is_string($value)) {
                      throw new \\InvalidArgumentException(
                          'Expected string for XdrSCAddress JSON value, got ' . get_debug_type($value)
                      );
                  }
                  if ($value === '') {
                      throw new \\InvalidArgumentException(
                          'Empty XdrSCAddress JSON value'
                      );
                  }
                  $prefix = $value[0];
                  if ($prefix === 'G') {
                      $result = new static(new XdrSCAddressType(XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT));
                      $result->accountId = XdrAccountID::fromJsonValue($value);
                      return $result;
                  }
                  if ($prefix === 'C') {
                      $result = new static(new XdrSCAddressType(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT));
                      $result->contractId = StrKey::decodeContractIdHex($value);
                      return $result;
                  }
                  if ($prefix === 'M') {
                      $raw = StrKey::decodeMuxedAccountId($value);
                      if (strlen($raw) !== 40) {
                          throw new \\InvalidArgumentException(
                              'Decoded muxed account must be 40 bytes; got ' . strlen($raw)
                          );
                      }
                      $ed25519 = substr($raw, 0, 32);
                      $idBuf = new XdrBuffer(substr($raw, 32, 8));
                      $id = $idBuf->readUnsignedInteger64();
                      $result = new static(new XdrSCAddressType(XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT));
                      $result->muxedAccount = new XdrMuxedAccountMed25519($id, $ed25519);
                      return $result;
                  }
                  if ($prefix === 'B') {
                      $result = new static(new XdrSCAddressType(XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE));
                      $result->claimableBalanceId = XdrClaimableBalanceID::fromJsonValue($value);
                      return $result;
                  }
                  if ($prefix === 'L') {
                      $result = new static(new XdrSCAddressType(XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL));
                      $result->liquidityPoolId = StrKey::decodeLiquidityPoolIdHex($value);
                      return $result;
                  }
                  throw new \\InvalidArgumentException(
                      'Invalid XdrSCAddress strkey prefix: ' . XdrJsonHelper::safePreview($value)
                  );
        PHP
      end,
    },

    # XdrMuxedAccount — G-strkey over ed25519 (KEY_TYPE_ED25519) or
    # M-strkey over the 40-byte ed25519 || id pack (KEY_TYPE_MUXED_ED25519).
    #
    # Wrapper carve-out: XdrMuxedAccount's wrapper constructor signature
    # is `(?string $ed25519, ?XdrMuxedAccountMed25519 $med25519)`, which
    # diverges from the base's `(?XdrCryptoKeyType $type)`. The base's
    # `new static(...)` resolves to the wrapper at runtime and triggers
    # a TypeError. The from_body avoids `new static(...)` entirely and
    # constructs the wrapper directly through XdrMuxedAccount's hand-
    # written constructor; the base file's emission still imports the
    # wrapper-class symbol via the same namespace, so the direct
    # constructor call resolves cleanly.
    'XdrMuxedAccount' => {
      to_value_signature: 'public function toJsonValue(): string',
      to_body: lambda do |_ctx|
        <<~PHP.chomp
                  switch ($this->type->getValue()) {
                      case XdrCryptoKeyType::KEY_TYPE_ED25519:
                          if ($this->ed25519 === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrMuxedAccount ed25519 field is null'
                              );
                          }
                          return StrKey::encodeAccountId($this->ed25519);
                      case XdrCryptoKeyType::KEY_TYPE_MUXED_ED25519:
                          if ($this->med25519 === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrMuxedAccount med25519 field is null'
                              );
                          }
                          return $this->med25519->toJsonValue();
                      default:
                          throw new \\InvalidArgumentException(
                              'Unknown XdrMuxedAccount discriminant: ' . $this->type->getValue()
                          );
                  }
        PHP
      end,
      from_body: lambda do |_ctx|
        <<~PHP.chomp
                  if (!is_string($value)) {
                      throw new \\InvalidArgumentException(
                          'Expected string for XdrMuxedAccount JSON value, got ' . get_debug_type($value)
                      );
                  }
                  if ($value === '') {
                      throw new \\InvalidArgumentException(
                          'Empty XdrMuxedAccount JSON value'
                      );
                  }
                  $prefix = $value[0];
                  if ($prefix === 'G') {
                      $ed25519 = StrKey::decodeAccountId($value);
                      return new XdrMuxedAccount($ed25519);
                  }
                  if ($prefix === 'M') {
                      // Defence-in-depth: validate the M-strkey decodes to a
                      // 40-byte buffer here, before delegating to the
                      // XdrMuxedAccountMed25519 inner parser. The inner parser
                      // also checks, but echoing the rejection at the outer
                      // dispatch site keeps the error message anchored to the
                      // class the caller invoked. Mirrors the M-arm pattern in
                      // XdrSCAddress.
                      $raw = StrKey::decodeMuxedAccountId($value);
                      if (strlen($raw) !== 40) {
                          throw new \\InvalidArgumentException(
                              'Decoded muxed account must be 40 bytes; got ' . strlen($raw)
                              . ' for input ' . XdrJsonHelper::safePreview($value)
                          );
                      }
                      $med25519 = XdrMuxedAccountMed25519::fromJsonValue($value);
                      return new XdrMuxedAccount(null, $med25519);
                  }
                  throw new \\InvalidArgumentException(
                      'Invalid XdrMuxedAccount strkey prefix: ' . XdrJsonHelper::safePreview($value)
                  );
        PHP
      end,
    },

    # XdrAllowTrustOperationAsset — Cat-B union over (CREDIT_ALPHANUM4,
    # CREDIT_ALPHANUM12). The default generator emission would produce arm
    # keys `alphanum4` / `alphanum12` (the prefix-stripped form of the
    # ASSET_TYPE_CREDIT_ALPHANUM4 / ASSET_TYPE_CREDIT_ALPHANUM12 enum
    # discriminants). The XDR IDL field is `AssetCode` (a typedef whose
    # CREDIT_ALPHANUM4 / CREDIT_ALPHANUM12 arms hold opaque[4] and
    # opaque[12] respectively); SEP-0051 §String requires the AssetCode
    # bytes to be emitted as a bare escape-aware string under arm keys
    # `credit_alphanum4` / `credit_alphanum12`, applying trim-pad-escape
    # AssetCode semantics (rtrim trailing NULs on the 4-byte arm; rtrim
    # then right-pad to 5 bytes minimum on the 12-byte arm; then SEP-51
    # escape both).
    #
    # This override emits the canonical wire form directly; it bypasses the
    # generator's standard union dispatch entirely. The (parent_type,
    # field_name) entries for XdrAllowTrustOperationAssetBase.assetCode4 /
    # assetCode12 in SEP51_FIELD_OVERRIDES are documentary — they are not
    # consulted on this code path because the type-level override wins.
    'XdrAllowTrustOperationAsset' => {
      to_value_signature: 'public function toJsonValue(): mixed',
      to_body: lambda do |_ctx|
        <<~PHP.chomp
                  switch ($this->type->getValue()) {
                      case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4:
                          if ($this->assetCode4 === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrAllowTrustOperationAsset assetCode4 field is null'
                              );
                          }
                          // AssetCode4: rtrim trailing \\x00 then SEP-51 escape.
                          return ['credit_alphanum4' => XdrJsonHelper::escapeString(rtrim($this->assetCode4, "\\x00"))];
                      case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12:
                          if ($this->assetCode12 === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrAllowTrustOperationAsset assetCode12 field is null'
                              );
                          }
                          // AssetCode12: rtrim trailing \\x00 fully; if the
                          // trimmed length <= 4 right-pad to exactly 5 (preserves
                          // AssetCode4-vs-AssetCode12 distinguishability per
                          // SEP-0051 §"Asset Code Types"); throw on all-null.
                          $trimmed = rtrim($this->assetCode12, "\\x00");
                          $len = strlen($trimmed);
                          if ($len === 0) {
                              throw new \\InvalidArgumentException('AssetCode12 must not be all-null');
                          }
                          if ($len <= 4) {
                              $trimmed = str_pad($trimmed, 5, "\\x00", STR_PAD_RIGHT);
                          }
                          return ['credit_alphanum12' => XdrJsonHelper::escapeString($trimmed)];
                      default:
                          throw new \\InvalidArgumentException(
                              'Unknown XdrAllowTrustOperationAsset discriminant: ' . $this->type->getValue()
                          );
                  }
        PHP
      end,
      from_body: lambda do |_ctx|
        <<~PHP.chomp
                  if (is_array($value) && array_key_exists('$schema', $value)) {
                      unset($value['$schema']);
                  }
                  if (!is_array($value) || count($value) !== 1) {
                      throw new \\InvalidArgumentException(
                          'Expected single-key object for XdrAllowTrustOperationAsset, got ' . get_debug_type($value)
                      );
                  }
                  $key = array_key_first($value);
                  if (!is_string($key)) {
                      throw new \\InvalidArgumentException(
                          'Expected string arm key for XdrAllowTrustOperationAsset, got ' . get_debug_type($key)
                      );
                  }
                  $arm = $value[$key];
                  if ($key === 'credit_alphanum4') {
                      if (!is_string($arm)) {
                          throw new \\InvalidArgumentException(
                              'Expected string for credit_alphanum4 arm, got ' . get_debug_type($arm)
                          );
                      }
                      $decoded = XdrJsonHelper::unescapeString($arm);
                      if (strlen($decoded) > 4) {
                          throw new \\InvalidArgumentException(
                              'AssetCode4 must not exceed 4 bytes; got ' . strlen($decoded)
                          );
                      }
                      $result = new static(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
                      $result->assetCode4 = str_pad($decoded, 4, "\\x00", STR_PAD_RIGHT);
                      return $result;
                  }
                  if ($key === 'credit_alphanum12') {
                      if (!is_string($arm)) {
                          throw new \\InvalidArgumentException(
                              'Expected string for credit_alphanum12 arm, got ' . get_debug_type($arm)
                          );
                      }
                      $decoded = XdrJsonHelper::unescapeString($arm);
                      $len = strlen($decoded);
                      if ($len <= 4) {
                          throw new \\InvalidArgumentException(
                              'AssetCode12 must exceed 4 bytes; got ' . $len . ' (use AssetCode4 instead)'
                          );
                      }
                      if ($len > 12) {
                          throw new \\InvalidArgumentException(
                              'AssetCode12 must not exceed 12 bytes; got ' . $len
                          );
                      }
                      $result = new static(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
                      $result->assetCode12 = str_pad($decoded, 12, "\\x00", STR_PAD_RIGHT);
                      return $result;
                  }
                  throw new \\InvalidArgumentException(
                      'Unknown arm key for XdrAllowTrustOperationAsset: ' . XdrJsonHelper::safePreview($key)
                  );
        PHP
      end,
    },

    # =====================================================================
    # Category B — enum-based wrapper carve-outs
    # =====================================================================

    # XdrSignerKeyType — base enum has the four canonical XDR discriminants
    # (ED25519=0, PRE_AUTH_TX=1, HASH_X=2, ED25519_SIGNED_PAYLOAD=3); the
    # hand-written wrapper extends the base with a PHP-only constant
    # MUXED_ED25519=0x100 used internally by the SDK at decode boundaries
    # that may surface a muxed signer key. The base's default enum-emission
    # `match` would throw an UnhandledMatchError when reached on
    # MUXED_ED25519 because the base does not list 0x100 as a case.
    #
    # MUXED_ED25519=0x100 is a wrapper-only sentinel not present in the
    # canonical SEP-0051 SignerKeyType enum; it is emitted under an
    # SDK-internal extension arm `muxed_ed25519` so that toJsonValue()
    # never throws on a fully-constructed wrapper instance and
    # fromJsonValue accepts the same string when round-tripping.
    'XdrSignerKeyType' => {
      to_value_signature: 'public function toJsonValue(): string',
      to_body: lambda do |_ctx|
        <<~PHP.chomp
                  return match ($this->value) {
                      self::SIGNER_KEY_TYPE_ED25519 => 'ed25519',
                      self::SIGNER_KEY_TYPE_PRE_AUTH_TX => 'pre_auth_tx',
                      self::SIGNER_KEY_TYPE_HASH_X => 'hash_x',
                      self::SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD => 'ed25519_signed_payload',
                      XdrSignerKeyType::MUXED_ED25519 => 'muxed_ed25519',
                      // @codeCoverageIgnoreStart
                      default => throw new \\InvalidArgumentException(
                          'Unknown XdrSignerKeyType enum value: ' . $this->value
                      ),
                      // @codeCoverageIgnoreEnd
                  };
        PHP
      end,
      from_body: lambda do |_ctx|
        <<~PHP.chomp
                  if (!is_string($value)) {
                      throw new \\InvalidArgumentException(
                          'Expected string for XdrSignerKeyType JSON value, got ' . get_debug_type($value)
                      );
                  }
                  return match ($value) {
                      'ed25519' => new static(self::SIGNER_KEY_TYPE_ED25519),
                      'pre_auth_tx' => new static(self::SIGNER_KEY_TYPE_PRE_AUTH_TX),
                      'hash_x' => new static(self::SIGNER_KEY_TYPE_HASH_X),
                      'ed25519_signed_payload' => new static(self::SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD),
                      'muxed_ed25519' => new static(XdrSignerKeyType::MUXED_ED25519),
                      default => throw new \\InvalidArgumentException(
                          'Unknown XdrSignerKeyType JSON value: ' . XdrJsonHelper::safePreview($value)
                      ),
                  };
        PHP
      end,
    },

    # XdrTimeBounds — Cat-B wrapper carve-out. The wrapper's constructor
    # signature is `(\DateTime $minTime, \DateTime $maxTime)`, while the
    # generator's default base emission would build `new static(int, int)`
    # from the unpacked uint64 timestamps. At runtime `new static(...)`
    # resolves to the wrapper, so the int args trip a TypeError. This
    # override emits SEP-51 directly on the base file: the wire form is
    # the canonical SEP-0051 §Hyper Integer shape (two uint64 decimal
    # strings keyed `min_time` / `max_time`), and the from-side
    # constructs the wrapper with `\DateTime` instances built from the
    # parsed integer timestamps via the `@<unix>` parse form so timezone
    # defaults do not leak into the wrapper's stored DateTime.
    'XdrTimeBounds' => {
      to_value_signature: 'public function toJsonValue(): array',
      to_body: lambda do |_ctx|
        <<~PHP.chomp
                  return [
                      'min_time' => XdrJsonHelper::uint64ToString($this->minTimestamp),
                      'max_time' => XdrJsonHelper::uint64ToString($this->maxTimestamp),
                  ];
        PHP
      end,
      from_body: lambda do |_ctx|
        <<~PHP.chomp
                  if (is_array($value) && array_key_exists('$schema', $value)) {
                      unset($value['$schema']);
                  }
                  if (!is_array($value)) {
                      throw new \\InvalidArgumentException(
                          'Expected object for XdrTimeBounds JSON value, got ' . get_debug_type($value)
                      );
                  }
                  if (!array_key_exists('min_time', $value)) {
                      throw new \\InvalidArgumentException(
                          'Missing required field min_time for XdrTimeBounds'
                      );
                  }
                  if (!array_key_exists('max_time', $value)) {
                      throw new \\InvalidArgumentException(
                          'Missing required field max_time for XdrTimeBounds'
                      );
                  }
                  $minRaw = $value['min_time'];
                  $maxRaw = $value['max_time'];
                  if (!is_string($minRaw) && !is_int($minRaw)) {
                      throw new \\InvalidArgumentException(
                          'Expected uint64 JSON value (string or int) for min_time, got ' . get_debug_type($minRaw)
                      );
                  }
                  if (!is_string($maxRaw) && !is_int($maxRaw)) {
                      throw new \\InvalidArgumentException(
                          'Expected uint64 JSON value (string or int) for max_time, got ' . get_debug_type($maxRaw)
                      );
                  }
                  $minTimestamp = XdrJsonHelper::stringToUint64($minRaw);
                  $maxTimestamp = XdrJsonHelper::stringToUint64($maxRaw);
                  // The wrapper stores DateTime objects; build them from the
                  // unix-timestamp parse form so the wrapper's encode() path
                  // (which reads `format('U')`) reproduces the original ints.
                  $minDt = new \\DateTime('@' . $minTimestamp);
                  $maxDt = new \\DateTime('@' . $maxTimestamp);
                  return new static($minDt, $maxDt);
        PHP
      end,
    },

    # XdrTransaction — Cat-B wrapper carve-out. The wrapper's constructor
    # reorders parameters and changes types vs. the generated base (`(MuxedAccount,
    # SequenceNumber, array, ?int, ?Memo, ?Preconditions, ?TransactionExt)` vs
    # the base's `(MuxedAccount, int fee, SequenceNumber, ...)`). At runtime
    # `new static(sourceAccount, fee, sequenceNumber, ...)` ends up in the
    # wrapper constructor, where the int `fee` is bound to `XdrSequenceNumber
    # $sequenceNumber` and trips a TypeError. The override emits the
    # SEP-0051 §Structs wire form (camelCase IDL field names converted to
    # snake_case keys) and the from-side calls the wrapper constructor
    # through `new static(...)` using the wrapper's parameter order so the
    # constructed instance is well-typed.
    'XdrTransaction' => {
      to_value_signature: 'public function toJsonValue(): array',
      to_body: lambda do |_ctx|
        <<~PHP.chomp
                  return [
                      'source_account' => $this->sourceAccount->toJsonValue(),
                      'fee' => $this->fee,
                      'seq_num' => $this->sequenceNumber->toJsonValue(),
                      'cond' => $this->preconditions->toJsonValue(),
                      'memo' => $this->memo->toJsonValue(),
                      'operations' => array_map(static function ($item) { return $item->toJsonValue(); }, $this->operations),
                      'ext' => $this->ext->toJsonValue(),
                  ];
        PHP
      end,
      from_body: lambda do |_ctx|
        <<~PHP.chomp
                  if (is_array($value) && array_key_exists('$schema', $value)) {
                      unset($value['$schema']);
                  }
                  if (!is_array($value)) {
                      throw new \\InvalidArgumentException(
                          'Expected object for XdrTransaction JSON value, got ' . get_debug_type($value)
                      );
                  }
                  foreach (['source_account', 'fee', 'seq_num', 'cond', 'memo', 'operations', 'ext'] as $required) {
                      if (!array_key_exists($required, $value)) {
                          throw new \\InvalidArgumentException(
                              'Missing required field ' . $required . ' for XdrTransaction'
                          );
                      }
                  }
                  $sourceAccount = XdrMuxedAccount::fromJsonValue($value['source_account']);
                  if (!is_int($value['fee'])) {
                      throw new \\InvalidArgumentException(
                              'Expected int for fee, got ' . get_debug_type($value['fee'])
                      );
                  }
                  $fee = $value['fee'];
                  $sequenceNumber = XdrSequenceNumber::fromJsonValue($value['seq_num']);
                  $preconditions = XdrPreconditions::fromJsonValue($value['cond']);
                  $memo = XdrMemo::fromJsonValue($value['memo']);
                  if (!is_array($value['operations'])) {
                      throw new \\InvalidArgumentException(
                          'Expected JSON array for operations, got ' . get_debug_type($value['operations'])
                      );
                  }
                  $operations = [];
                  foreach ($value['operations'] as $item) {
                      $operations[] = XdrOperation::fromJsonValue($item);
                  }
                  $ext = XdrTransactionExt::fromJsonValue($value['ext']);
                  // Wrapper signature: (sourceAccount, sequenceNumber, operations, ?fee, ?memo, ?preconditions, ?ext).
                  return new static($sourceAccount, $sequenceNumber, $operations, $fee, $memo, $preconditions, $ext);
        PHP
      end,
    },

    # XdrManageDataOperation — Cat-B wrapper carve-out. The wrapper's
    # constructor signature is `(string $key, XdrDataValue $value)` — it
    # always wraps the second arg in `XdrDataValue` and forwards
    # `$value->getValue()` to the base. The default base from-side decodes
    # the data_value hex into a raw byte string and calls
    # `new static(string, ?string)`, which at runtime hits the wrapper and
    # trips a TypeError on the second argument. The override decodes the
    # hex bytes (or null), wraps them in `XdrDataValue`, then calls
    # `new static(...)` so the wrapper constructor receives the type it
    # declares. Wire form per SEP-0051 §Structs: keys `data_name`
    # (SEP-51-escaped string per §String) and `data_value` (lowercase
    # hex string per §Opaque, or null when the optional bytes are absent).
    'XdrManageDataOperation' => {
      to_value_signature: 'public function toJsonValue(): array',
      to_body: lambda do |_ctx|
        <<~PHP.chomp
                  return [
                      'data_name' => XdrJsonHelper::escapeString($this->dataName),
                      'data_value' => ($this->dataValue !== null ? XdrJsonHelper::bytesToHex($this->dataValue) : null),
                  ];
        PHP
      end,
      from_body: lambda do |_ctx|
        <<~PHP.chomp
                  if (is_array($value) && array_key_exists('$schema', $value)) {
                      unset($value['$schema']);
                  }
                  if (!is_array($value)) {
                      throw new \\InvalidArgumentException(
                          'Expected object for XdrManageDataOperation JSON value, got ' . get_debug_type($value)
                      );
                  }
                  if (!array_key_exists('data_name', $value)) {
                      throw new \\InvalidArgumentException(
                          'Missing required field data_name for XdrManageDataOperation'
                      );
                  }
                  if (!array_key_exists('data_value', $value)) {
                      throw new \\InvalidArgumentException(
                          'Missing required field data_value for XdrManageDataOperation'
                      );
                  }
                  if (!is_string($value['data_name'])) {
                      throw new \\InvalidArgumentException(
                          'Expected string for data_name, got ' . get_debug_type($value['data_name'])
                      );
                  }
                  $dataName = XdrJsonHelper::unescapeString($value['data_name']);
                  $rawBytes = null;
                  if ($value['data_value'] !== null) {
                      if (!is_string($value['data_value'])) {
                          throw new \\InvalidArgumentException(
                              'Expected hex string or null for data_value, got ' . get_debug_type($value['data_value'])
                          );
                      }
                      $rawBytes = XdrJsonHelper::hexToBytes($value['data_value']);
                  }
                  // Wrapper signature: (string $key, XdrDataValue $value).
                  return new static($dataName, new XdrDataValue($rawBytes));
        PHP
      end,
    },

    # XdrHostFunction — Cat-B wrapper carve-out for the `wasm` arm. The
    # wrapper's `decode()` path stores `$result->wasm = XdrDataValueMandatory::decode(...)`
    # — i.e. an `XdrDataValueMandatory` object — while the base's default
    # emission feeds `$this->wasm` directly into `XdrJsonHelper::bytesToHex`,
    # which expects raw bytes. The from-side analogously needs to wrap the
    # decoded bytes in `XdrDataValueMandatory` before storing on the
    # property (the property is typed `?XdrDataValueMandatory`). All other
    # arms (invoke_contract / create_contract / create_contract_v2)
    # delegate to inner struct toJsonValue/fromJsonValue exactly as the
    # default emission would; the override preserves the SEP-0051
    # §Discriminated unions single-key object wire shape with the
    # well-known `HOST_FUNCTION_TYPE_` prefix stripped from each arm key.
    'XdrHostFunction' => {
      to_value_signature: 'public function toJsonValue(): mixed',
      to_body: lambda do |_ctx|
        <<~PHP.chomp
                  switch ($this->type->getValue()) {
                      case XdrHostFunctionType::HOST_FUNCTION_TYPE_INVOKE_CONTRACT:
                          if ($this->invokeContract === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrHostFunction invokeContract field is null'
                              );
                          }
                          return ['invoke_contract' => $this->invokeContract->toJsonValue()];
                      case XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT:
                          if ($this->createContract === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrHostFunction createContract field is null'
                              );
                          }
                          return ['create_contract' => $this->createContract->toJsonValue()];
                      case XdrHostFunctionType::HOST_FUNCTION_TYPE_UPLOAD_CONTRACT_WASM:
                          if ($this->wasm === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrHostFunction wasm field is null'
                              );
                          }
                          // Wrapper stores `$wasm` as XdrDataValueMandatory; unwrap to bytes.
                          return ['upload_contract_wasm' => XdrJsonHelper::bytesToHex($this->wasm->getValue())];
                      case XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT_V2:
                          if ($this->createContractV2 === null) {
                              throw new \\InvalidArgumentException(
                                  'XdrHostFunction createContractV2 field is null'
                              );
                          }
                          return ['create_contract_v2' => $this->createContractV2->toJsonValue()];
                      // @codeCoverageIgnoreStart
                      default:
                          throw new \\InvalidArgumentException(
                              'Unknown discriminant for type on XdrHostFunctionType'
                          );
                      // @codeCoverageIgnoreEnd
                  }
        PHP
      end,
      from_body: lambda do |_ctx|
        <<~PHP.chomp
                  if (is_array($value) && array_key_exists('$schema', $value)) {
                      unset($value['$schema']);
                  }
                  if (!is_array($value) || count($value) !== 1) {
                      throw new \\InvalidArgumentException(
                          'Expected single-key object for XdrHostFunction, got ' . get_debug_type($value)
                      );
                  }
                  $key = array_key_first($value);
                  if (!is_string($key)) {
                      throw new \\InvalidArgumentException(
                          'Expected string arm key for XdrHostFunction, got ' . get_debug_type($key)
                      );
                  }
                  $arm = $value[$key];
                  switch ($key) {
                      case 'invoke_contract':
                          $r = new static(new XdrHostFunctionType(XdrHostFunctionType::HOST_FUNCTION_TYPE_INVOKE_CONTRACT));
                          $r->invokeContract = XdrInvokeContractArgs::fromJsonValue($arm);
                          return $r;
                      case 'create_contract':
                          $r = new static(new XdrHostFunctionType(XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT));
                          $r->createContract = XdrCreateContractArgs::fromJsonValue($arm);
                          return $r;
                      case 'upload_contract_wasm':
                          if (!is_string($arm)) {
                              throw new \\InvalidArgumentException(
                                  'Expected hex string for upload_contract_wasm arm, got ' . get_debug_type($arm)
                              );
                          }
                          $r = new static(new XdrHostFunctionType(XdrHostFunctionType::HOST_FUNCTION_TYPE_UPLOAD_CONTRACT_WASM));
                          // Wrapper property is typed `?XdrDataValueMandatory`; wrap the decoded bytes.
                          $r->wasm = new XdrDataValueMandatory(XdrJsonHelper::hexToBytes($arm));
                          return $r;
                      case 'create_contract_v2':
                          $r = new static(new XdrHostFunctionType(XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT_V2));
                          $r->createContractV2 = XdrCreateContractArgsV2::fromJsonValue($arm);
                          return $r;
                      default:
                          throw new \\InvalidArgumentException(
                              'Unknown arm key for XdrHostFunction: ' . XdrJsonHelper::safePreview($key)
                          );
                  }
        PHP
      end,
    },

    # XdrMuxedAccountMed25519 — standalone M-strkey over the 40-byte
    'XdrMuxedAccountMed25519' => {
      to_value_signature: 'public function toJsonValue(): string',
      to_body: lambda do |_ctx|
        <<~PHP.chomp
                  $packed = XdrEncoder::opaqueFixed($this->ed25519, 32);
                  $packed .= XdrEncoder::unsignedInteger64($this->id);
                  return StrKey::encodeMuxedAccountId($packed);
        PHP
      end,
      from_body: lambda do |_ctx|
        <<~PHP.chomp
                  if (!is_string($value)) {
                      throw new \\InvalidArgumentException(
                          'Expected string for XdrMuxedAccountMed25519 JSON value, got ' . get_debug_type($value)
                      );
                  }
                  $raw = StrKey::decodeMuxedAccountId($value);
                  if (strlen($raw) !== 40) {
                      throw new \\InvalidArgumentException(
                          'Decoded muxed account must be 40 bytes; got ' . strlen($raw)
                      );
                  }
                  $ed25519 = substr($raw, 0, 32);
                  $idBuf = new XdrBuffer(substr($raw, 32, 8));
                  $id = $idBuf->readUnsignedInteger64();
                  return new static($id, $ed25519);
        PHP
      end,
    },

  }.freeze

  # True when an override is registered for the given PHP type name.
  def has?(type_name)
    REGISTRY.key?(type_name)
  end

  # Return the override entry for the given type name, or nil.
  def lookup(type_name)
    REGISTRY[type_name]
  end
end
