# XDR Code Generator

Generates PHP XDR types from Stellar's `.x` definition files using the [xdrgen](https://github.com/stellar/xdrgen) Ruby gem.

## Prerequisites

- Docker (for Makefile targets)
- Ruby 3.x and Bundler (only if running without Docker)

## Usage

### Generate XDR files

From the repo root using the Makefile:

```bash
make xdr-generate         # fetch .x files and generate PHP types
make xdr-update           # re-download .x files and regenerate
make xdr-clean-generated  # remove only generated PHP files
make xdr-clean-all        # remove generated PHP files and .x definitions
```

Or run the generator directly:

```bash
cd tools/xdr-generator
bundle config set --local path vendor/bundle
bundle install
bundle exec ruby generate.rb
```

Output goes to `Soneso/StellarSDK/Xdr/`.

### Update to a new XDR spec version

1. Update `XDR_COMMIT` in the repo-root `Makefile` to the new [stellar/stellar-xdr](https://github.com/stellar/stellar-xdr) commit
2. Run `make xdr-update`
3. Run tests: `./vendor/bin/phpunit --testsuite unit`
4. If new types introduce naming conflicts, update the override files (see below)

### Run tests

```bash
make xdr-generator-test                # run snapshot tests via Docker
make xdr-generator-update-snapshots    # update snapshots after intentional changes
make xdr-validate                      # validate generated types against XDR definitions
make xdr-generate-tests                # regenerate PHP XDR unit tests
```

Or directly (requires `bundle install` first):

```bash
cd tools/xdr-generator
bundle exec ruby -Itest test/generator_snapshot_test.rb
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
| `generator/name_overrides.rb` | Maps XDR type names to PHP class names |
| `generator/member_overrides.rb` | Maps enum constant names (prefix stripping, individual renames) |
| `generator/field_overrides.rb` | Maps struct/union field names and per-field type overrides |
| `generator/type_overrides.rb` | Typedef resolution (`TYPE_OVERRIDES`) and base/wrapper type list (`BASE_WRAPPER_TYPES`) |
| `test/generator_snapshot_test.rb` | Snapshot tests comparing generated output to expected files |
| `test/update_snapshots.rb` | Regenerates snapshot files after intentional generator changes |
| `test/validate_generated_types.rb` | Validates generated files against XDR definitions |
| `test/generate_tests.rb` | Generates roundtrip encode/decode unit tests for all XDR types |

## Base/wrapper pattern

34 types generate a `*Base.php` file instead of a plain `*.php` file. These are types where the SDK has hand-maintained helper methods (factory methods, custom constructors, StrKey encoding, etc.) that cannot be derived from the XDR spec alone.

The hand-maintained wrapper file extends the generated base class:

```
Soneso/StellarSDK/Xdr/XdrSCValBase.php   <- generated (encode/decode/fields)
Soneso/StellarSDK/Xdr/XdrSCVal.php       <- hand-maintained (forBool, forU32, etc.)
```

The full list of wrapper types is in `generator/type_overrides.rb` (`BASE_WRAPPER_TYPES`).

## Override files

The override files preserve the existing SDK API where hand-written PHP code diverged from the canonical XDR names:

- **`type_overrides.rb`** — `TYPE_OVERRIDES` maps XDR typedefs to the PHP types the SDK uses (e.g. `XdrTimePoint` -> `int`, `XdrSCVec` -> `array`). `BASE_WRAPPER_TYPES` lists types that generate base classes. `SKIP_TYPES` lists types excluded from generation. `EXTENSION_POINT_FIELDS` simplifies void-only extension unions to `int`.
- **`field_overrides.rb`** — `FIELD_OVERRIDES` remaps field names (e.g. `code` -> `resultCode`). `FIELD_TYPE_OVERRIDES` overrides the type of specific fields (e.g. forcing `BigInteger` instead of `int`).
- **`name_overrides.rb`** — Maps XDR type names to PHP class names where the SDK convention differs from the spec.
- **`member_overrides.rb`** — `MEMBER_OVERRIDES` remaps individual enum constant names. `MEMBER_PREFIX_STRIP` strips common prefixes from enum constants (e.g. `PAYMENT_SUCCESS` -> `SUCCESS`).
