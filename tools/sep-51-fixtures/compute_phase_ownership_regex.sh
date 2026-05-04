#!/usr/bin/env bash
# Emit the union of phase-N-owned regexes for the current phase plus all
# uncommitted prior phases recorded in the orchestration state file.
#
# Inputs (positional, all required):
#   $1 PHASE_NUM        current phase (e.g. "0", "1", "2", "3", "4", "5a", "5b", "6"..."10")
#   $2 LAST_PHASE_HEAD  git ref pointing to the last phase boundary (informational; this
#                       script does not consult git, but the §3.4 mechanical-check passes
#                       it for symmetry with the diff window in the upstream gate)
#   $3 STATE_FILE       path to orchestration-state.json
#
# Output: a single line on stdout: "(<regex_a>|<regex_b>|...)" with no trailing
# newline beyond what `echo` adds.
#
# Exit codes:
#   0 success
#   2 PHASE_OWNED_FILE_MISSING
#   3 STATE_FILE_MALFORMED
#   4 INVALID_PHASE_NUM

set -euo pipefail

readonly KNOWN_PHASES="0 1 2 3 4 5a 5b 6 7 8 9 10"

if [ "$#" -lt 3 ]; then
    echo "usage: compute_phase_ownership_regex.sh <PHASE_NUM> <LAST_PHASE_HEAD> <STATE_FILE>" >&2
    exit 4
fi

PHASE_NUM="$1"
# LAST_PHASE_HEAD ($2) is currently unused by this script but kept for the §3.4 contract.
STATE_FILE="$3"

phase_known=0
for known in $KNOWN_PHASES; do
    if [ "$PHASE_NUM" = "$known" ]; then
        phase_known=1
        break
    fi
done
if [ "$phase_known" -eq 0 ]; then
    echo "INVALID_PHASE_NUM: $PHASE_NUM" >&2
    exit 4
fi

# Default to empty uncommitted_phases when the state file is missing (Phase 0 entry case).
uncommitted=""
if [ -f "$STATE_FILE" ]; then
    if ! command -v jq >/dev/null 2>&1; then
        echo "STATE_FILE_MALFORMED: jq not available; required for state parsing" >&2
        exit 3
    fi
    if ! jq -e . "$STATE_FILE" >/dev/null 2>&1; then
        echo "STATE_FILE_MALFORMED" >&2
        exit 3
    fi
    # uncommitted_phases is an array of phase identifiers (string or number).
    if ! uncommitted=$(jq -r '(.uncommitted_phases // []) | .[] | tostring' "$STATE_FILE" 2>/dev/null); then
        echo "STATE_FILE_MALFORMED" >&2
        exit 3
    fi
fi

# Build the deduplicated phase set: current phase plus uncommitted phases.
declare -a phases
phases+=("$PHASE_NUM")
if [ -n "${uncommitted:-}" ]; then
    while IFS= read -r p; do
        [ -z "$p" ] && continue
        # Skip duplicates.
        skip=0
        for existing in "${phases[@]}"; do
            if [ "$existing" = "$p" ]; then
                skip=1
                break
            fi
        done
        if [ "$skip" -eq 0 ]; then
            phases+=("$p")
        fi
    done <<< "$uncommitted"
fi

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

# Concatenate every phase's owned-regex file, one PCRE alternation per line.
declare -a alternations
for p in "${phases[@]}"; do
    owned_file="${REPO_ROOT}/tools/sep-51-fixtures/phase-${p}-owned.txt"
    if [ ! -f "$owned_file" ]; then
        echo "PHASE_OWNED_FILE_MISSING: tools/sep-51-fixtures/phase-${p}-owned.txt" >&2
        exit 2
    fi
    while IFS= read -r line; do
        [ -z "$line" ] && continue
        alternations+=("$line")
    done < "$owned_file"
done

# Deduplicate alternations (preserving first-seen order).
declare -a seen
seen_count=0
declare -a unique
for alt in "${alternations[@]}"; do
    skip=0
    i=0
    while [ "$i" -lt "$seen_count" ]; do
        if [ "${seen[$i]}" = "$alt" ]; then
            skip=1
            break
        fi
        i=$((i + 1))
    done
    if [ "$skip" -eq 0 ]; then
        seen[$seen_count]="$alt"
        seen_count=$((seen_count + 1))
        unique+=("$alt")
    fi
done

if [ "${#unique[@]}" -eq 0 ]; then
    # No regexes for this phase; emit a no-match alternation so callers can still
    # safely invoke `grep -E "$result"`.
    echo "(?!)"
    exit 0
fi

# Join with '|' inside a single capturing group.
joined=""
for alt in "${unique[@]}"; do
    if [ -z "$joined" ]; then
        joined="$alt"
    else
        joined="${joined}|${alt}"
    fi
done

echo "(${joined})"
exit 0
