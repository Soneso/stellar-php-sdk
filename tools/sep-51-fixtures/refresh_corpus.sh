#!/usr/bin/env bash
# Phase 10 maintenance script.
#
# Operates inside a managed venv at tools/.venv-corpus-refresh/. Re-installs
# py-stellar-base at the version pinned in tools/baselines/sep-51-tooling-versions.json
# (or the latest if the pinned version is not specified), re-runs generate_corpus.py,
# and diffs the produced corpus.json against the committed copy.
#
# Exit codes:
#   0 no drift.
#   1 drift detected (corpus diverges from committed reference).
#   2 py-stellar-base unavailable or other prerequisite failure.

set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$REPO_ROOT"

VENV="tools/.venv-corpus-refresh"
COMMITTED="tools/sep-51-fixtures/corpus.json"
GENERATOR="tools/sep-51-fixtures/generate_corpus.py"
TOOLING="tools/baselines/sep-51-tooling-versions.json"

if [ ! -f "$GENERATOR" ]; then
    echo "refresh_corpus: $GENERATOR missing" >&2
    exit 2
fi

# Resolve target py-stellar-base version.
TARGET_VERSION=""
if [ -f "$TOOLING" ] && command -v jq >/dev/null 2>&1; then
    TARGET_VERSION=$(jq -r '.py_stellar_base_version // ""' "$TOOLING" 2>/dev/null || true)
fi

# Build / refresh the venv.
if [ ! -d "$VENV" ]; then
    if ! python3 -m venv "$VENV"; then
        echo "refresh_corpus: failed to create venv at $VENV" >&2
        exit 2
    fi
fi
PIP="$VENV/bin/pip"
PY="$VENV/bin/python3"
if [ ! -x "$PIP" ] || [ ! -x "$PY" ]; then
    echo "refresh_corpus: venv binaries missing under $VENV/bin" >&2
    exit 2
fi

"$PIP" install --quiet --upgrade pip >/dev/null 2>&1 || true
if [ -n "$TARGET_VERSION" ]; then
    if ! "$PIP" install --quiet --upgrade "stellar-sdk==${TARGET_VERSION}"; then
        echo "refresh_corpus: pip install stellar-sdk==${TARGET_VERSION} failed" >&2
        exit 2
    fi
else
    if ! "$PIP" install --quiet --upgrade stellar-sdk; then
        echo "refresh_corpus: pip install stellar-sdk failed" >&2
        exit 2
    fi
fi

if ! "$PY" -c 'import stellar_sdk' >/dev/null 2>&1; then
    echo "refresh_corpus: stellar_sdk module not importable after install" >&2
    exit 2
fi

# Re-run generator into a scratch path.
SCRATCH=$(mktemp -d)
trap 'rm -rf "$SCRATCH"' EXIT
SCRATCH_OUT="$SCRATCH/corpus.refreshed.json"

if ! "$PY" "$GENERATOR" --output "$SCRATCH_OUT"; then
    echo "refresh_corpus: generator run failed" >&2
    exit 2
fi

if [ ! -f "$COMMITTED" ]; then
    echo "refresh_corpus: committed corpus $COMMITTED missing; cannot diff." >&2
    exit 2
fi

# Compare normalised JSON (sorted keys) for deterministic diff.
if "$PY" - "$COMMITTED" "$SCRATCH_OUT" <<'PY'
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
    "$PY" - "$COMMITTED" "$SCRATCH_OUT" <<'PY' || true
import difflib, json, sys
a = json.dumps(json.load(open(sys.argv[1], encoding="utf-8")), indent=2, sort_keys=True).splitlines()
b = json.dumps(json.load(open(sys.argv[2], encoding="utf-8")), indent=2, sort_keys=True).splitlines()
sys.stderr.write("\n".join(difflib.unified_diff(a, b, fromfile="committed", tofile="refreshed", lineterm="")) + "\n")
PY
    exit 1
fi
