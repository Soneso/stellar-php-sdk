#!/usr/bin/env bash
# Diff the PHP SDK's current SEP-0051 output against the vendored corpus.
#
# Re-runs generate_corpus.py with --source php (the SDK's own toJson output;
# no oracle binary required), then compares the produced entries against the
# committed corpus, whose spec_reference_json is populated from the
# rs-stellar-xdr CLI oracle. Drift therefore means the SDK's emission no
# longer matches the reference implementation baseline.
#
# Only the `entries` array is compared: `generated_at` and `_meta` describe
# the provenance of each file (oracle vs php) and differ by design.
#
# Exit codes:
#   0 no drift.
#   1 drift detected (SDK output diverges from the vendored oracle baseline).
#   2 prerequisite failure (missing generator, generator failure, etc.).

set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$REPO_ROOT"

COMMITTED="tools/sep-51-test-fixtures/corpus.json"
GENERATOR="tools/sep-51-test-fixtures/generate_corpus.py"

if [ ! -f "$GENERATOR" ]; then
    echo "refresh_corpus: $GENERATOR missing" >&2
    exit 2
fi

if ! command -v python3 >/dev/null 2>&1; then
    echo "refresh_corpus: python3 not on PATH" >&2
    exit 2
fi

if ! command -v php >/dev/null 2>&1; then
    echo "refresh_corpus: php not on PATH" >&2
    exit 2
fi

# Re-run generator into a scratch path.
SCRATCH=$(mktemp -d)
trap 'rm -rf "$SCRATCH"' EXIT
SCRATCH_OUT="$SCRATCH/corpus.refreshed.json"

if ! python3 "$GENERATOR" --source php --output "$SCRATCH_OUT"; then
    echo "refresh_corpus: generator run failed" >&2
    exit 2
fi

if [ ! -f "$COMMITTED" ]; then
    echo "refresh_corpus: committed corpus $COMMITTED missing; cannot diff." >&2
    exit 2
fi

# Compare normalised entries (sorted keys, spec_reference_json parsed as JSON
# so formatting differences between emitters do not count as drift; the
# oracle_incomparable marker is metadata of the committed corpus only). On
# drift, the same normalisation feeds the printed diff, so the report always
# matches the comparison that failed.
if python3 - "$COMMITTED" "$SCRATCH_OUT" <<'PY'
import difflib, json, sys

def normalised_entries(path):
    doc = json.loads(open(path, encoding="utf-8").read())
    entries = []
    for e in doc["entries"]:
        e = dict(e)
        e.pop("oracle_incomparable", None)
        if isinstance(e.get("spec_reference_json"), str):
            e["spec_reference_json"] = json.loads(e["spec_reference_json"])
        entries.append(e)
    return json.dumps(entries, indent=2, sort_keys=True).splitlines()

a = normalised_entries(sys.argv[1])
b = normalised_entries(sys.argv[2])
if a == b:
    sys.exit(0)
sys.stderr.write("\n".join(
    difflib.unified_diff(a, b, fromfile="committed", tofile="refreshed", lineterm="")
) + "\n")
sys.exit(1)
PY
then
    echo "refresh_corpus: no drift."
    exit 0
else
    echo "refresh_corpus: drift detected vs committed corpus."
    exit 1
fi
