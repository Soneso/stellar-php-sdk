#!/usr/bin/env bash
# Refresh the SEP-51 snapshot corpus and diff against the committed copy.
#
# Re-runs generate_corpus.py (which seeds via PHP and snapshots the SDK's own
# canonical SEP-0051 toJson output for every fixture), then compares the
# produced corpus.json against the committed reference.
#
# Exit codes:
#   0 no drift.
#   1 drift detected (corpus diverges from committed reference).
#   2 prerequisite failure (missing generator, generator failure, etc.).

set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$REPO_ROOT"

COMMITTED="tools/sep-51-fixtures/corpus.json"
GENERATOR="tools/sep-51-fixtures/generate_corpus.py"

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

if ! python3 "$GENERATOR" --output "$SCRATCH_OUT"; then
    echo "refresh_corpus: generator run failed" >&2
    exit 2
fi

if [ ! -f "$COMMITTED" ]; then
    echo "refresh_corpus: committed corpus $COMMITTED missing; cannot diff." >&2
    exit 2
fi

# Compare normalised JSON (sorted keys) for deterministic diff.
if python3 - "$COMMITTED" "$SCRATCH_OUT" <<'PY'
import json, sys
a = json.loads(open(sys.argv[1], encoding="utf-8").read())
b = json.loads(open(sys.argv[2], encoding="utf-8").read())
a_norm = json.dumps(a, sort_keys=True)
b_norm = json.dumps(b, sort_keys=True)
sys.exit(0 if a_norm == b_norm else 1)
PY
then
    echo "refresh_corpus: no drift."
    exit 0
else
    echo "refresh_corpus: drift detected vs committed corpus."
    python3 - "$COMMITTED" "$SCRATCH_OUT" <<'PY' || true
import difflib, json, sys
a = json.dumps(json.load(open(sys.argv[1], encoding="utf-8")), indent=2, sort_keys=True).splitlines()
b = json.dumps(json.load(open(sys.argv[2], encoding="utf-8")), indent=2, sort_keys=True).splitlines()
sys.stderr.write("\n".join(difflib.unified_diff(a, b, fromfile="committed", tofile="refreshed", lineterm="")) + "\n")
PY
    exit 1
fi
