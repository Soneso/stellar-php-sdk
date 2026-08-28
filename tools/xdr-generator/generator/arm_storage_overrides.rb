# frozen_string_literal: true

# Union arms whose PHP field holds its value in a representation other than the
# raw bytes the XDR declaration gives, and the code every generated reader and
# writer of such a field uses.
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
# hand-written wrapper that inherits it.
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
     * what another refuses.
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
     * field resolves it through here, so none takes what another refuses.
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
     * refuses.
     *
     * @return string the wasm hash as 64 lower case hexadecimal characters
     * @throws InvalidArgumentException when the field is unset, or holds a string that
     * is not 64 hexadecimal characters
     */
    public function getCanonicalWasmIdHex(): string {
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
