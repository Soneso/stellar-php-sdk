# XDR Code Generator

Generates PHP XDR types from Stellar's `.x` definition files using the [xdrgen](https://github.com/stellar/xdrgen) Ruby gem.

## Prerequisites

- Docker (for Makefile targets)
- Ruby, PHP, and the Composer dependencies in `vendor/` on the host, for the SEP-51 round-trip test emission that `make xdr-update` runs outside Docker
- Ruby 3.x and Bundler (only if running the generator without Docker)

## Usage

### Generate XDR files

From the repo root using the Makefile:

```bash
make xdr-generate              # fetch .x files and generate PHP types
make xdr-emit-roundtrip-tests  # emit the SEP-51 round-trip tests on the host
make xdr-update                # re-download .x files, regenerate types and generated tests
make xdr-clean-generated       # remove only generated PHP files
make xdr-clean-all             # remove generated PHP files and .x definitions
```

`make xdr-update` runs `xdr-clean-all`, `xdr-generate`, `xdr-emit-roundtrip-tests` and `xdr-generate-tests` in this order. The round-trip step runs `emit_roundtrip_tests.rb` with the host's Ruby (standard library only), outside Docker, because it inspects the generated classes through PHP and `vendor/autoload.php`; run `composer install` first. It writes the five generated test files in `Soneso/StellarSDKTests/Unit/Xdr/Sep51/` from the generated classes and `tools/sep-51-test-fixtures/corpus.json`.

Or run the generator directly:

```bash
cd tools/xdr-generator
bundle config set --local path vendor/bundle
bundle install
bundle exec ruby generate.rb
```

Output goes to `Soneso/StellarSDK/Xdr/`. Each run also rewrites `phpstan-generated.neon`, the list of generated files that `phpstan.neon.dist` excludes from static analysis, so the hand-written classes in the same directory are still analyzed. The XDR Generator workflow regenerates and diffs it with the generated classes.

### Update to a new XDR spec version

1. Update `XDR_COMMIT` in the repo-root `Makefile` to the new [stellar/stellar-xdr](https://github.com/stellar/stellar-xdr) commit
2. Run `make xdr-update`
3. Run the unit suite, which includes the generated XDR tests: `php -d memory_limit=-1 vendor/bin/phpunit --testsuite unit --no-coverage`. The suite exceeds PHP's default memory limit of 128 MB, and `--no-coverage` keeps a loaded Xdebug from failing a passing run.
4. If new types introduce naming conflicts, update the override files (see below)

### Run tests

```bash
make xdr-generator-test                # run snapshot and helper tests via Docker
make xdr-generator-update-snapshots    # update snapshots after intentional changes
make xdr-validate                      # validate generated types against XDR definitions
make xdr-generate-tests                # regenerate PHP XDR unit tests
make xdr-compare                       # compare every file in Soneso/StellarSDK/Xdr/ with HEAD
```

`make xdr-compare` is for migration comparisons. After a regeneration, it lists the files in `Soneso/StellarSDK/Xdr/` that differ from their committed version at `HEAD`, ignoring trailing whitespace, the generator header, and repeated blank lines. It prints a summary and exits 0 whether or not files differ.

`xdr-generator-test` covers `test/generator_snapshot_test.rb` plus the codegen-time helper units (`generator_helpers_test.rb`, `json_helpers_test.rb`, `sep51_field_overrides_test.rb`). A new test file must be listed in the target to be gated.

Or directly (requires `bundle install` first):

```bash
cd tools/xdr-generator
bundle exec ruby -Itest test/generator_snapshot_test.rb
bundle exec ruby -Itest test/generator_helpers_test.rb
bundle exec ruby -Itest test/json_helpers_test.rb
bundle exec ruby -Itest test/sep51_field_overrides_test.rb
bundle exec ruby test/update_snapshots.rb
bundle exec ruby test/validate_generated_types.rb
bundle exec ruby test/generate_tests.rb
```

Generated test output goes to `Soneso/StellarSDKTests/Unit/Xdr/Generated/`.

## Generator architecture

| File | Purpose |
|---|---|
| `generate.rb` | Entry point |
| `generator/generator.rb` | Core PHP renderer (structs, enums, unions, typedefs) |
| `generator/type_overrides.rb` | Typedef resolution (`TYPE_OVERRIDES`) and base/wrapper type list (`BASE_WRAPPER_TYPES`) |
| `generator/field_overrides.rb` | Maps struct/union field names and per-field type overrides |
| `generator/name_overrides.rb` | Maps XDR type names to PHP class names |
| `generator/member_overrides.rb` | Maps enum constant names (prefix stripping, individual renames) |
| `generator/arm_storage_overrides.rb` | Encode, decode, and TxRep emission for union arms whose PHP field holds a converted value, plus the class members that emission calls |
| `generator/sep51_field_overrides.rb` | SEP-51 emission of one field of one parent type |
| `generator/stellar_json_overrides.rb` | Per-type SEP-51 method bodies for Stellar-specific types |
| `generator/cat_a_inline_targets.rb` | Catalog of types with a Stellar-specific SEP-51 form; no generator code reads it |
| `generator/txrep_types.rb` | Types that get `toTxRep` and `fromTxRep` methods |
| `test/generator_snapshot_test.rb` | Snapshot tests comparing generated output to expected files |
| `test/update_snapshots.rb` | Regenerates snapshot files after intentional generator changes |
| `test/validate_generated_types.rb` | Validates generated files against XDR definitions |
| `test/generate_tests.rb` | Generates roundtrip encode/decode unit tests for all XDR types |

## Base/wrapper pattern

34 types generate a `*Base.php` file instead of a plain `*.php` file. These are types where the SDK has hand-maintained helper methods, such as factory methods, custom constructors, and StrKey encoding, that cannot be derived from the XDR spec alone.

The hand-maintained wrapper file extends the generated base class:

```
Soneso/StellarSDK/Xdr/XdrSCValBase.php   <- generated (encode/decode/fields)
Soneso/StellarSDK/Xdr/XdrSCVal.php       <- hand-maintained (factory methods such as forBool and forU32)
```

The full list of wrapper types is in `generator/type_overrides.rb` (`BASE_WRAPPER_TYPES`).

## Override files

The nine override files in `generator/` hold what the XDR definitions do not say. They set the PHP names and types of the SDK API and the storage of some union arms. They also select the types that get SEP-51 (XDR-JSON) and TxRep methods, and the form of those methods.

- **`type_overrides.rb`**: `TYPE_OVERRIDES` maps XDR typedefs to the PHP types the SDK uses (for example, `XdrTimePoint` -> `int`, `XdrSCVec` -> `array`). `BASE_WRAPPER_TYPES` lists types that generate base classes. `SKIP_TYPES` lists types excluded from generation. `EXTENSION_POINT_FIELDS` simplifies void-only extension unions to `int`.
- **`field_overrides.rb`**: `FIELD_OVERRIDES` remaps field names (for example, `code` -> `resultCode`). `FIELD_TYPE_OVERRIDES` overrides the type of specific fields (for example, forcing `BigInteger` instead of `int`).
- **`name_overrides.rb`**: Maps XDR type names to PHP class names where the SDK convention differs from the spec.
- **`member_overrides.rb`**: `MEMBER_OVERRIDES` remaps individual enum constant names. `MEMBER_PREFIX_STRIP` strips common prefixes from enum constants (for example, `PAYMENT_SUCCESS` -> `SUCCESS`). These affect PHP constant names only; SEP-51 JSON wire names are always derived from the original XDR identifiers (`json_helpers.rb`).
- **`arm_storage_overrides.rb`**: `ARM_STORAGE_OVERRIDES` supplies the `encode`/`decode`/`toTxRep`/`fromTxRep` emission for union arms whose PHP field holds something other than the raw bytes its XDR declaration gives (for example, `XdrSCAddress` keeps the contract id as hexadecimal). `ARM_STORAGE_HELPERS` emits the class members that emission calls, so the canonicalization has one implementation the generated readers and the hand-written wrapper share.
- **`sep51_field_overrides.rb`**: `SEP51_FIELD_OVERRIDES` changes the SEP-51 emission of one field of one parent type. It covers typedef-collapsed values, such as ids stored as hexadecimal or raw bytes that JSON writes as strkeys, and asset codes. It is separate from `FIELD_OVERRIDES` in `field_overrides.rb`.
- **`stellar_json_overrides.rb`**: per-type SEP-51 method bodies for Stellar-specific types whose JSON form the default emission cannot produce. Examples are strkeys, unions that flatten to a string, and 128-bit and 256-bit integers. Per-field overrides take precedence over it.
- **`cat_a_inline_targets.rb`**: `CAT_A_INLINE_TARGETS`, a catalog of types with a Stellar-specific SEP-51 form that the header comment of `stellar_json_overrides.rb` refers to; `generator.rb` requires the file, but no code reads the constant. The emission decision is `emits_sep51?` in `generator.rb`, together with the per-type method bodies registered in `stellar_json_overrides.rb`.
- **`txrep_types.rb`**: the types that get `toTxRep` and `fromTxRep` methods, which are the types reachable from a transaction envelope.
