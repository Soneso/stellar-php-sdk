# SEP-51 Test Fixtures

Snapshot regression baseline for the SDK's SEP-51 (XDR-JSON) emission. The
committed `corpus.json` holds 226 XDR base64 fixtures; for each fixture, the
canonical SEP-0051 `toJson` output produced by the SDK is stored as
`spec_reference_json`. `Soneso/StellarSDKTests/Unit/Xdr/Sep51/CorpusSnapshotTest.php`
re-asserts every entry on each test run, so any unintended change to the JSON
emission surface fails fast as a snapshot diff.

## Files

| File | Role |
|------|------|
| `corpus.json` | Committed snapshot consumed by `CorpusSnapshotTest`. |
| `_corpus_seed.php` | Builds each fixture (id, type, base64) via the SDK's existing factories and validates the base64 round-trips through the XDR codec. |
| `_corpus_to_json.php` | Reads the seed list on stdin, decodes each base64 via `Xdr<Type>::fromBase64Xdr`, and emits the canonical SEP-0051 JSON via `toJson()` into each entry's `spec_reference_json` field. |
| `generate_corpus.py` | Wires the seed and to-json scripts together and writes the result to `corpus.json`. |
| `refresh_corpus.sh` | Regenerates the corpus and diffs against the committed copy. Exit 0 = no drift, exit 1 = drift detected, exit 2 = prerequisite missing. |

## Regenerating the corpus

```bash
python3 tools/sep-51-test-fixtures/generate_corpus.py
```

Or use the drift-check wrapper, which regenerates into a scratch path and
diffs against the committed copy without overwriting it:

```bash
bash tools/sep-51-test-fixtures/refresh_corpus.sh
```

The corpus should be regenerated whenever the SDK's SEP-51 emission for any
covered XDR type changes. `CorpusSnapshotTest` will fail the test suite first
if a code change drifts the output without the corpus being refreshed.

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
