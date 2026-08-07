# SEP-51 Test Fixtures

Correctness baseline for the SDK's SEP-51 (XDR-JSON) emission. The committed
`corpus.json` holds 257 XDR base64 fixtures; for each fixture, the decode
output of the rs-stellar-xdr CLI oracle (the SEP-0051 reference
implementation, >= 28.0.0) is stored as `spec_reference_json`.
`Soneso/StellarSDKTests/Unit/Xdr/Sep51/CorpusSnapshotTest.php` asserts the
SDK's `toJson` output against every entry on each test run, so a divergence
from the reference implementation fails fast - the corpus detects error, not
merely drift.

Entries whose XDR bytes have no standalone CLI-oracle equivalent carry an
`oracle_incomparable` field with a justification; their reference JSON is the
SDK's own output and pins them against unintended drift only.

## Files

| File | Role |
|------|------|
| `corpus.json` | Committed oracle baseline consumed by `CorpusSnapshotTest`. |
| `_corpus_seed.php` | Builds each fixture (id, type, base64) via the SDK's existing factories and validates the base64 round-trips through the XDR codec. |
| `_corpus_to_json.php` | Reads the seed list on stdin, decodes each base64 via `Xdr<Type>::fromBase64Xdr`, and emits the SDK's SEP-0051 JSON via `toJson()` into each entry's `spec_reference_json` field (used by `--source php`). |
| `generate_corpus.py` | Populates `spec_reference_json` from the oracle (`--source oracle`, default; requires the `stellar-xdr` CLI >= 28.0.0) or from the SDK (`--source php`) and writes the result to `corpus.json`. |
| `refresh_corpus.sh` | Regenerates with `--source php` and diffs the entries against the committed oracle baseline. Exit 0 = no drift, exit 1 = drift detected, exit 2 = prerequisite missing. |

## Regenerating the corpus

Requires the rs-stellar-xdr CLI at 28.0.0 or newer (older releases emit
`type_` instead of `type` for six ScSpec types and would bake the wrong key
into the corpus):

```bash
cargo install --locked stellar-xdr --features cli
python3 tools/sep-51-test-fixtures/generate_corpus.py
```

Or use the drift-check wrapper, which regenerates into a scratch path and
diffs against the committed copy without overwriting it:

```bash
bash tools/sep-51-test-fixtures/refresh_corpus.sh
```

The corpus should be regenerated (from the oracle) whenever fixtures are
added or the pinned XDR revision changes. A `CorpusSnapshotTest` failure
means the SDK's emission diverges from the reference implementation: fix the
emission, do not regenerate the corpus from PHP output to make it pass.

## Adding a fixture

1. Add a new entry to the `FIXTURES` list at the bottom of `_corpus_seed.php`.
   Each entry is a tuple of `(id, type, base64, spec_anchor, notes)` where
   `type` is the unprefixed XDR class name (e.g. `Asset`, not `XdrAsset`).
2. Regenerate `corpus.json` via `generate_corpus.py`.
3. Run the unit test suite to confirm `CorpusSnapshotTest` accepts the new
   entry's `spec_reference_json`.

## CI

`.github/workflows/sep-51-corpus-drift.yml` runs `refresh_corpus.sh` on
manual trigger and opens an issue tagged `sep-51-corpus-drift` if the
committed corpus has drifted from what `generate_corpus.py` would produce
today.
