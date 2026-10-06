#!/usr/bin/env bash
# Diff a fresh SEP-0051 corpus generation against the vendored corpus.
#
# Usage: refresh_corpus.sh [--oracle-bin PATH]
#
# Without arguments, re-runs generate_corpus.py with --source php (the SDK's
# own toJson output; no oracle binary required), then compares the produced
# entries against the committed corpus, whose spec_reference_json is populated
# from the rs-stellar-xdr CLI oracle. Drift therefore means the SDK's emission
# no longer matches the reference implementation baseline.
#
# With --oracle-bin PATH, re-runs the generator with --source oracle through
# that CLI build instead (advisory mode). The entries then show whether a
# reference build other than the pinned one renders the fixtures differently.
# A build newer than the pin is accepted because the output is a scratch file;
# the committed corpus is never written by this script. A relative PATH with
# a slash is resolved against the caller's directory; a bare name is looked
# up on the search path.
#
# Only the `entries` array is compared: `generated_at` and `_meta` describe
# the provenance of each file and differ by design.
#
# Exit codes:
#   0 no drift.
#   1 drift detected (the fresh entries diverge from the vendored baseline).
#   2 prerequisite failure (missing generator, generator or comparison failure, etc.).

set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

SOURCE_ARGS=(--source php)
while [ $# -gt 0 ]; do
    case "$1" in
        --oracle-bin)
            if [ $# -lt 2 ] || [ -z "$2" ]; then
                echo "refresh_corpus: --oracle-bin requires a path" >&2
                exit 2
            fi
            ORACLE_BIN="$2"
            # The caller's relative path is anchored before the cd into the repository.
            case "$ORACLE_BIN" in
                /*) ;;
                */*) ORACLE_BIN="$PWD/${ORACLE_BIN#./}" ;;
            esac
            SOURCE_ARGS=(--source oracle --oracle-bin "$ORACLE_BIN")
            shift 2
            ;;
        *)
            echo "refresh_corpus: unknown argument '$1' (usage: refresh_corpus.sh [--oracle-bin PATH])" >&2
            exit 2
            ;;
    esac
done

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
SCRATCH=$(mktemp -d) || { echo "refresh_corpus: cannot create scratch directory" >&2; exit 2; }
trap 'rm -rf "$SCRATCH"' EXIT
SCRATCH_OUT="$SCRATCH/corpus.refreshed.json"

# Only the generator's stderr carries findings; its stdout names the random scratch path.
if ! python3 "$GENERATOR" "${SOURCE_ARGS[@]}" --output "$SCRATCH_OUT" >/dev/null; then
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
# matches the comparison that failed. An unreadable corpus exits 2, not 1.
python3 - "$COMMITTED" "$SCRATCH_OUT" <<'PY'
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

try:
    a = normalised_entries(sys.argv[1])
    b = normalised_entries(sys.argv[2])
except Exception as exc:
    sys.stderr.write(f"refresh_corpus: cannot compare the corpora: {exc!r}\n")
    sys.exit(2)
if a == b:
    sys.exit(0)
sys.stderr.write("\n".join(
    difflib.unified_diff(a, b, fromfile="committed", tofile="refreshed", lineterm="")
) + "\n")
sys.exit(1)
PY
status=$?
case "$status" in
    0) echo "refresh_corpus: no drift."; exit 0 ;;
    1) echo "refresh_corpus: drift detected vs committed corpus."; exit 1 ;;
    2) exit 2 ;;
    *) echo "refresh_corpus: comparison failed (python exit $status)." >&2; exit 2 ;;
esac
