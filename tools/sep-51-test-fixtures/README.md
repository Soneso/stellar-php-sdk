# SEP-51 Test Fixtures

Correctness baseline for the SDK's SEP-51 (XDR-JSON) emission. The committed
`corpus.json` holds 262 XDR base64 fixtures; for each fixture, the decode
output of the rs-stellar-xdr CLI oracle (the SEP-0051 reference
implementation, at the build pinned in `oracle-pin.json`) is stored as
`spec_reference_json`.
`Soneso/StellarSDKTests/Unit/Xdr/Sep51/CorpusSnapshotTest.php` asserts the
SDK's `toJson` output against every entry on each test run, so a divergence
from the reference implementation fails fast - the corpus detects error, not
merely drift.

Some entries have no usable oracle output: XDR bytes with no standalone
CLI-oracle equivalent, CLI renderings that diverge from the spec text, or
members newer than the XDR revision the pinned build vendors. These entries
carry an `oracle_incomparable` field with a justification; their reference
JSON is the SDK's own output and pins them against unintended drift only.

## Files

| File | Role |
|------|------|
| `corpus.json` | Committed oracle baseline consumed by `CorpusSnapshotTest`; `_meta.oracle` records the build that generated it. |
| `oracle-pin.json` | The pinned reference build (`version`, `tool_commit`, `xdr_commit`, exact `install` command) and the SEP-0051 revision (`sep_spec`) this implementation follows. `CorpusOraclePinTest` fails when another build generated `corpus.json`. |
| `_corpus_seed.php` | Builds each fixture (id, type, base64) via the SDK's existing factories and validates the base64 round-trips through the XDR codec. |
| `_corpus_to_json.php` | Reads the seed list on stdin, decodes each base64 via `Xdr<Type>::fromBase64Xdr`, and emits the SDK's SEP-0051 JSON via `toJson()` into each entry's `spec_reference_json` field (used by `--source php`). |
| `generate_corpus.py` | Populates `spec_reference_json` from the oracle (`--source oracle`, default) or from the SDK (`--source php`) and writes the result to `corpus.json`. Writing `corpus.json` requires the pinned build; any other `--output` accepts any build >= 28.0.0. |
| `refresh_corpus.sh` | Regenerates into a scratch path and diffs the entries against the committed baseline: with `--source php` by default, or through a given CLI build with `--oracle-bin PATH` (advisory mode). Exit 0 = no drift, exit 1 = drift detected, exit 2 = prerequisite missing. Never writes `corpus.json`. |

## Regenerating the corpus

Requires the rs-stellar-xdr CLI at the build pinned in `oracle-pin.json`,
because key spellings changed between releases (before 28.0.0, `type_`
instead of `type` for six ScSpec types). The generator refuses any other
build for the committed file:

```bash
$(jq -r .install tools/sep-51-test-fixtures/oracle-pin.json)
python3 tools/sep-51-test-fixtures/generate_corpus.py
```

Or use the drift-check wrapper, which regenerates into a scratch path and
diffs against the committed copy without overwriting it:

```bash
bash tools/sep-51-test-fixtures/refresh_corpus.sh
```

The corpus should be regenerated (from the oracle) whenever fixtures are
added or the pin changes. A `CorpusSnapshotTest` failure
means the SDK's emission diverges from the reference implementation: fix the
emission, do not regenerate the corpus from PHP output to make it pass.

## Adding a fixture

1. Add an `add($fixtures, $id, $type, $object, $specAnchor, $notes)` call in
   `_corpus_seed.php`, where `$type` is the unprefixed XDR class name (for
   example `Asset`, not `XdrAsset`) and `$object` is the SDK XDR instance the
   helper encodes to base64.
2. Regenerate `corpus.json` via `generate_corpus.py`.
3. Run the unit test suite to confirm `CorpusSnapshotTest` accepts the new
   entry's `spec_reference_json`.

## CI

`.github/workflows/sep-51-reference-watch.yml` runs every Monday, and on
manual dispatch with a `dry_run` option. It files one issue per finding and
comments only when the report content changes:

- `sep-51-corpus-drift`: `refresh_corpus.sh` found the SDK's output out of
  step with the committed corpus. Reproduce locally; fix the emission for a
  changed entry, regenerate with the pinned build for an added fixture.
- `sep-51-reference-release`: crates.io has a `stellar-xdr` release newer
  than the pin, or the SEP-0051 preamble differs from `sep_spec`. For a
  release, the issue carries the advisory verdict: "behaviour unchanged"
  means update `oracle-pin.json`, regenerate `corpus.json` with the new
  build, and run the Sep51 tests; "behaviour changed" lists the renderings
  that differ, each a decision between the reference and the spec text.
