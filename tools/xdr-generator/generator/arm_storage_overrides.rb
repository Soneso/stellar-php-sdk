# frozen_string_literal: true

# Union arms whose PHP field holds its value in a representation other than the
# raw bytes the XDR declaration gives, and the code every generated reader and
# writer of such a field uses. ARM_ALIAS_OVERRIDES below lists the alias fields
# some unions keep beside an arm field.
#
# XdrSCAddress keeps the contract id and the liquidity pool id as the 32-byte
# hash in hexadecimal and accepts the "C..." / "L..." strkey spelling as well,
# because that is how the SDK's callers name a contract and a pool everywhere
# else. The four sites below cover every generated reader and writer of those
# two fields; the SEP-51 pair is carried by the type's entry in
# stellar_json_overrides.rb, which resolves the field through the same helper.
#
# XdrContractExecutable keeps the wasm hash as the 32-byte hash in hexadecimal
# in wasmIdHex, the one spelling forWasmId() and the SEP-51 pair accept. The
# field is public and settable, so the sites below that take it from the caller
# resolve it through the canonical accessor rather than use it as it stands:
# pack('H*') answers a non-hexadecimal string with 32 zero bytes, which would
# name a contract the caller never asked for. decode() needs no resolver, since
# bin2hex of a 32-byte read is canonical already. SEP-0011 gives an opaque[32]
# field 64 hexadecimal characters, which is what the accessor returns, so the
# TxRep line carries the hash unchanged in both directions.
#
# ---------------------------------------------------------------------------
# ARM_STORAGE_OVERRIDES
# Per-arm emission, keyed by [<PhpUnionName>, <armFieldName>]:
#
#   encode:     statements appended to the arm's case in encode()
#   decode:     statements assigning the arm's field in decode()
#   to_txrep:   statements writing the arm's TxRep line (toTxRep() has already
#               written the discriminant line by the time the arm runs)
#   from_txrep: statements reading the arm's TxRep line back into the field
#
# Bodies are written unindented; the emitter indents them to the arm's level.
# ---------------------------------------------------------------------------
ARM_STORAGE_OVERRIDES = {
  ['XdrSCAddress', 'contractId'] => {
    encode: "$bytes .= XdrEncoder::opaqueFixed(pack('H*', $this->getCanonicalContractIdHex()), 32);",
    decode: '$result->contractId = bin2hex($xdr->readOpaqueFixed(32));',
    to_txrep: "$lines[$prefix . '.contractId'] = $this->getCanonicalContractIdHex();",
    from_txrep: "$result->contractId = TxRepHelper::getValue($map, $prefix . '.contractId') ?? '';",
  },
  ['XdrSCAddress', 'liquidityPoolId'] => {
    encode: "$bytes .= XdrEncoder::opaqueFixed(pack('H*', $this->getCanonicalLiquidityPoolIdHex()), 32);",
    decode: '$result->liquidityPoolId = bin2hex($xdr->readOpaqueFixed(32));',
    to_txrep: "$lines[$prefix . '.liquidityPoolId'] = $this->getCanonicalLiquidityPoolIdHex();",
    from_txrep: "$result->liquidityPoolId = TxRepHelper::getValue($map, $prefix . '.liquidityPoolId') ?? '';",
  },
  ['XdrContractExecutable', 'wasmIdHex'] => {
    encode: "$bytes .= XdrEncoder::opaqueFixed(pack('H*', $this->getCanonicalWasmIdHex()), 32);",
    decode: '$result->wasmIdHex = bin2hex($xdr->readOpaqueFixed(32));',
    to_txrep: "$lines[$prefix . '.wasm_hash'] = $this->getCanonicalWasmIdHex();",
    from_txrep: "$result->wasmIdHex = self::canonicalWasmIdHex(TxRepHelper::getValue($map, $prefix . '.wasm_hash'));",
  },
}.freeze

# ---------------------------------------------------------------------------
# ARM_STORAGE_HELPERS
# Class members the overrides above call, keyed by <PhpUnionName>. Emitted into
# the generated class so it resolves its own fields: the canonicalisation has
# one implementation, reached alike by the generated readers and by the
# hand-written wrapper that inherits it. The resolvers name a value that starts
# with "S" or "s", which may be a secret seed, by its kind through
# SecretSeedRedaction; a type whose members call it imports it.
#
# Bodies are written unindented; the emitter indents them to the class body.
# ---------------------------------------------------------------------------
ARM_STORAGE_HELPERS = {
  'XdrSCAddress' => <<~PHP.chomp,
    /**
     * Length of a "C..." contract strkey.
     */
    private const CONTRACT_STRKEY_LENGTH = 56;

    /**
     * Length of a 32-byte contract id in hexadecimal characters.
     */
    private const CONTRACT_HEX_LENGTH = 64;

    /**
     * Length of an "L..." liquidity pool strkey.
     */
    private const LIQUIDITY_POOL_STRKEY_LENGTH = 56;

    /**
     * Length of a 32-byte liquidity pool id in hexadecimal characters.
     */
    private const LIQUIDITY_POOL_HEX_LENGTH = 64;

    /**
     * Returns the 32-byte contract id as 64 lower case hexadecimal characters,
     * whichever of the accepted spellings the field holds.
     *
     * A contract id is accepted as its strkey form "C..." or as the bare hash in
     * hexadecimal. Every reader of the field resolves it through here, so none takes
     * what another refuses. A value that starts with "S" or "s", the first letter of a
     * secret seed, is neither spelling, and the error names it by its kind without
     * showing it.
     *
     * @return string the contract id as 64 lower case hexadecimal characters
     * @throws InvalidArgumentException when the field is unset, holds a string that is
     * neither a "C..." strkey nor hexadecimal, or has a length matching neither shape
     */
    public function getCanonicalContractIdHex(): string {
        $value = $this->contractId;
        if ($value === null || $value === '') {
            throw new InvalidArgumentException('Contract id is not set');
        }
        $seedKind = SecretSeedRedaction::seedKind($value);
        if ($seedKind !== null) {
            throw new InvalidArgumentException(
                'Contract id must be a "C..." strkey or a hexadecimal string, ' . $seedKind . ' given'
            );
        }
        if (strlen($value) === self::CONTRACT_STRKEY_LENGTH && $value[0] === 'C') {
            // The base32 and hexadecimal alphabets share A-F and 2-7, so a string of
            // this length and shape can read as either. The strkey reading is taken
            // first because it is the only one that can succeed: 56 characters is not
            // the length of the hexadecimal spelling. A value that fails it and is
            // hexadecimal falls to the hex rules below, which name the rule it breaks.
            try {
                return StrKey::decodeContractIdHex($value);
            } catch (InvalidArgumentException $e) {
                if (!ctype_xdigit($value)) {
                    throw $e;
                }
            }
        }
        if (!ctype_xdigit($value)) {
            throw new InvalidArgumentException(
                'Contract id must be a "C..." strkey or a hexadecimal string,'
                . ' "' . XdrJsonHelper::safePreview($value) . '" given'
            );
        }
        if (strlen($value) !== self::CONTRACT_HEX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Contract id must be %d characters as a "C..." strkey, or the contract'
                    . ' hash as %d hexadecimal characters; %d characters given',
                self::CONTRACT_STRKEY_LENGTH,
                self::CONTRACT_HEX_LENGTH,
                strlen($value)
            ));
        }
        // Hexadecimal is case insensitive, so the canonical spelling is lower case and
        // every reader of the contract id reports the same string for either spelling.
        return strtolower($value);
    }

    /**
     * Returns the 32-byte liquidity pool id as 64 lower case hexadecimal characters,
     * whichever of the accepted spellings the field holds.
     *
     * A pool id is accepted as its strkey form "L..." or as the bare hash in
     * hexadecimal. PoolID is a plain 32-byte hash on the wire with no discriminant
     * ahead of it, so there is no prefixed spelling to accept. Every reader of the
     * field resolves it through here, so none takes what another refuses. A value that
     * starts with "S" or "s", the first letter of a secret seed, is neither spelling,
     * and the error names it by its kind without showing it.
     *
     * @return string the pool id as 64 lower case hexadecimal characters
     * @throws InvalidArgumentException when the field is unset, holds a string that is
     * neither an "L..." strkey nor hexadecimal, or has a length matching neither shape
     */
    public function getCanonicalLiquidityPoolIdHex(): string {
        $value = $this->liquidityPoolId;
        if ($value === null || $value === '') {
            throw new InvalidArgumentException('Liquidity pool id is not set');
        }
        $seedKind = SecretSeedRedaction::seedKind($value);
        if ($seedKind !== null) {
            throw new InvalidArgumentException(
                'Liquidity pool id must be an "L..." strkey or a hexadecimal string, ' . $seedKind . ' given'
            );
        }
        if (strlen($value) === self::LIQUIDITY_POOL_STRKEY_LENGTH && $value[0] === 'L') {
            // Requiring the version letter here leaves a hexadecimal id of the same
            // length to the hex branches below, which name the rule it breaks.
            return StrKey::decodeLiquidityPoolIdHex($value);
        }
        if (!ctype_xdigit($value)) {
            throw new InvalidArgumentException(
                'Liquidity pool id must be an "L..." strkey or a hexadecimal string,'
                . ' "' . XdrJsonHelper::safePreview($value) . '" given'
            );
        }
        if (strlen($value) !== self::LIQUIDITY_POOL_HEX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Liquidity pool id must be %d characters as an "L..." strkey, or the pool'
                    . ' hash as %d hexadecimal characters; %d characters given',
                self::LIQUIDITY_POOL_STRKEY_LENGTH,
                self::LIQUIDITY_POOL_HEX_LENGTH,
                strlen($value)
            ));
        }
        // Hexadecimal is case insensitive, so the canonical spelling is lower case and
        // every reader of the pool id reports the same string for either spelling.
        return strtolower($value);
    }
  PHP
  'XdrContractExecutable' => <<~PHP.chomp,
    /**
     * Length of a 32-byte wasm hash in hexadecimal characters.
     */
    private const WASM_HASH_HEX_LENGTH = 64;

    /**
     * Returns the 32-byte wasm hash the field holds as 64 lower case hexadecimal
     * characters.
     *
     * Every reader of the field resolves it through here, so none takes what another
     * refuses. A field that starts with "S" or "s", the first letter of a secret seed,
     * is not hexadecimal, and the error names it by its kind without showing it.
     *
     * @return string the wasm hash as 64 lower case hexadecimal characters
     * @throws InvalidArgumentException when the field is unset, or holds a string that
     * is not 64 hexadecimal characters
     */
    public function getCanonicalWasmIdHex(): string {
        $seedKind = SecretSeedRedaction::seedKind($this->wasmIdHex ?? '');
        if ($seedKind !== null) {
            throw new InvalidArgumentException('Wasm hash must be a hexadecimal string, ' . $seedKind . ' given');
        }
        return self::canonicalWasmIdHex($this->wasmIdHex);
    }

    /**
     * Returns $value as 64 lower case hexadecimal characters, refusing anything else.
     *
     * A wasm hash has the one spelling: the bare 32-byte hash in hexadecimal. Unlike a
     * contract id it has no strkey form, so there is no second spelling to resolve;
     * the rule is the shape of the hexadecimal, and the canonical form its lower case.
     *
     * Reading a TxRep line goes through here rather than through the accessor above, so
     * a malformed line is named where it is read.
     *
     * @param string|null $value the wasm hash as it was given
     * @return string the wasm hash as 64 lower case hexadecimal characters
     * @throws InvalidArgumentException when $value is unset, or is not 64 hexadecimal
     * characters
     */
    private static function canonicalWasmIdHex(?string $value): string {
        if ($value === null || $value === '') {
            throw new InvalidArgumentException('Wasm hash is not set');
        }
        if (!ctype_xdigit($value)) {
            throw new InvalidArgumentException(
                'Wasm hash must be a hexadecimal string,'
                . ' "' . XdrJsonHelper::safePreview($value) . '" given'
            );
        }
        if (strlen($value) !== self::WASM_HASH_HEX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Wasm hash must be the 32-byte hash as %d hexadecimal characters;'
                    . ' %d characters given',
                self::WASM_HASH_HEX_LENGTH,
                strlen($value)
            ));
        }
        // Hexadecimal is case insensitive, so the canonical spelling is lower case and
        // every reader of the wasm hash reports the same string for either spelling.
        return strtolower($value);
    }
  PHP
}.freeze

# ---------------------------------------------------------------------------
# ARM_ALIAS_OVERRIDES
# Unions whose SDK API keeps a second field beside an arm field: an alias that
# holds the arm's value in another shape, or the field of another arm that also
# stands for it. Keyed by <PhpUnionName>:
#
#   properties: the alias properties the generated class declares, each with a
#               getter, a setter and the doc comment given; an alias that is an
#               arm field already is not listed
#   to_arms:    statements that copy the aliases into the arm fields; every
#               generated writer (encode, toTxRep, toJsonValue) runs them first,
#               so the arm fields hold the resolved value after a write
#   from_arms:  statements that fill the aliases from the arm fields; every
#               generated reader (decode, fromTxRep, fromJsonValue) runs them on
#               the value it read
#
# The emitter wraps to_arms and from_arms in two private methods of the class,
# ARM_ALIAS_TO_ARMS_METHOD and ARM_ALIAS_FROM_ARMS_METHOD. Bodies are written
# unindented and use $this.
#
# When an alias and its arm field are both set, the writers apply one
# precedence per type: the liquidityPool arm field of XdrLedgerKey wins over its
# alias; address and salt (when both are set) and asset of XdrContractIDPreimage
# win over their arm fields, whatever the discriminant; manageOfferResult of
# XdrOperationResultTr wins over manageBuyOfferResult for a MANAGE_BUY_OFFER
# result. The alias of XdrLedgerKey holds the pool id as 64 hexadecimal
# characters or as an "L..." strkey and is checked before it is copied: the
# TxRep writer of XdrLedgerKeyLiquidityPool writes the bytes it holds as
# hexadecimal without checking their length.
# ---------------------------------------------------------------------------
ARM_ALIAS_TO_ARMS_METHOD = 'resolveAliasFields'
ARM_ALIAS_FROM_ARMS_METHOD = 'fillAliasFields'

ARM_ALIAS_OVERRIDES = {
  'XdrLedgerKey' => {
    properties: [
      {
        name: 'liquidityPoolID',
        type: 'string',
        doc: <<~DOC.chomp,
          The pool id of the LIQUIDITY_POOL arm as 64 hexadecimal characters or an
          "L..." strkey. liquidityPool takes precedence when both are set.
        DOC
      },
    ],
    to_arms: <<~PHP.chomp,
      if ($this->liquidityPoolID !== null && $this->liquidityPool === null) {
          $idHex = str_starts_with($this->liquidityPoolID, 'L')
              ? StrKey::decodeLiquidityPoolIdHex($this->liquidityPoolID)
              : $this->liquidityPoolID;
          if (strlen($idHex) !== 64 || !ctype_xdigit($idHex)) {
              $given = SecretSeedRedaction::seedKind($this->liquidityPoolID)
                  ?? '"' . XdrJsonHelper::safePreview(SecretSeedRedaction::redactSecretSeeds($this->liquidityPoolID)) . '"';
              throw new InvalidArgumentException(
                  'Liquidity pool id must be an "L..." strkey or 64 hexadecimal characters, ' . $given . ' given'
              );
          }
          $this->liquidityPool = new XdrLedgerKeyLiquidityPool(hex2bin($idHex));
      }
    PHP
    from_arms: <<~PHP.chomp,
      if ($this->liquidityPool !== null) {
          $this->liquidityPoolID = bin2hex($this->liquidityPool->liquidityPoolID);
      }
    PHP
  },
  'XdrContractIDPreimage' => {
    properties: [
      {
        name: 'address',
        type: 'XdrSCAddress',
        doc: <<~DOC.chomp,
          The address of the CONTRACT_ID_PREIMAGE_FROM_ADDRESS arm. Together with salt
          it takes precedence over fromAddress.
        DOC
      },
      {
        name: 'salt',
        type: 'string',
        doc: <<~DOC.chomp,
          The 32-byte salt of the CONTRACT_ID_PREIMAGE_FROM_ADDRESS arm. Together with
          address it takes precedence over fromAddress.
        DOC
      },
      {
        name: 'asset',
        type: 'XdrAsset',
        doc: <<~DOC.chomp,
          The asset of the CONTRACT_ID_PREIMAGE_FROM_ASSET arm. It takes precedence
          over fromAsset.
        DOC
      },
    ],
    to_arms: <<~PHP.chomp,
      if ($this->address !== null && $this->salt !== null) {
          $this->fromAddress = new XdrContractIDPreimageFromAddress($this->address, $this->salt);
      }
      if ($this->asset !== null) {
          $this->fromAsset = $this->asset;
      }
    PHP
    from_arms: <<~PHP.chomp,
      if ($this->fromAddress !== null) {
          $this->address = $this->fromAddress->address;
          $this->salt = $this->fromAddress->salt;
      }
      if ($this->fromAsset !== null) {
          $this->asset = $this->fromAsset;
      }
    PHP
  },
  'XdrOperationResultTr' => {
    properties: [],
    to_arms: <<~PHP.chomp,
      if ($this->type->getValue() === XdrOperationType::MANAGE_BUY_OFFER && $this->manageOfferResult !== null) {
          $this->manageBuyOfferResult = $this->manageOfferResult;
      }
    PHP
    from_arms: <<~PHP.chomp,
      if ($this->manageBuyOfferResult !== null) {
          $this->manageOfferResult = $this->manageBuyOfferResult;
      }
    PHP
  },
}.freeze

# ---------------------------------------------------------------------------
# Lookup helpers
# ---------------------------------------------------------------------------

# Returns the emission for one site of an arm whose field stores a non-raw
# representation, or nil when the arm has none.
def arm_storage_override(union_name, field_name, site)
  entry = ARM_STORAGE_OVERRIDES[[union_name, field_name]]
  entry && entry[site]
end

# Returns the class members an overridden arm calls, or nil when the type has
# none.
def arm_storage_helpers(union_name)
  ARM_STORAGE_HELPERS[union_name]
end

# Returns the ARM_ALIAS_OVERRIDES entry of a union, or nil when it has none.
def arm_aliases(union_name)
  ARM_ALIAS_OVERRIDES[union_name]
end

# Returns the PHP the type's ARM_STORAGE_HELPERS and ARM_ALIAS_OVERRIDES bodies
# emit, so the union can import the classes they call.
def arm_storage_code(union_name)
  aliases = arm_aliases(union_name)
  [arm_storage_helpers(union_name), aliases && aliases[:to_arms], aliases && aliases[:from_arms]].compact.join("\n")
end
