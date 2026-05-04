#!/usr/bin/env bash
# SEP-51 coverage gate (G-Coverage). Three subgates:
#   (a) XdrJsonHelper.php: 100% line + 90% branch
#   (b) every changed PHP file: >=95% line on new methods only
#   (c) aggregate over all changed files: >=95% line
#
# Coverage driver detection algorithm (per implementation plan §3.2):
#   - if xdebug is loaded: require XDEBUG_MODE to contain 'coverage'.
#   - if both xdebug and pcov: fail; instruct to choose one.
#   - if only pcov: subgate (a) is rejected (branches require xdebug);
#     subgates (b)/(c) still run.
#   - if neither: hard fail.
#
# Inputs (env):
#   COVERAGE_CLOVER  path to clover.xml (default: coverage/clover.xml)
#   DIFF_BASE        git ref for "diff base" (default: main)
#   PHASE_NUM        if set and < 8, gate is warning-only (continue-on-error
#                    upstream); when >= 8 the gate exits non-zero on failure.
#   STATE_FILE       optional; orchestration-state.json; if present, PHASE_NUM
#                    is read from it.
#
# Exit codes:
#   0 all subgates green (or warning-only mode)
#   2 abort: driver requirements not met (subgate (a) attempted without xdebug etc.)
#   1 hard failure (a subgate threshold missed)

set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$REPO_ROOT"

COVERAGE_CLOVER="${COVERAGE_CLOVER:-coverage/clover.xml}"
DIFF_BASE="${DIFF_BASE:-main}"
STATE_FILE="${STATE_FILE:-tools/sep-51-fixtures/orchestration-state.json}"

# Phase-aware: gate is warning-only until Phase 8 acceptance.
PHASE_NUM="${PHASE_NUM:-0}"
if [ -f "$STATE_FILE" ] && command -v jq >/dev/null 2>&1; then
    cur=$(jq -r '.current_phase // empty' "$STATE_FILE" 2>/dev/null || true)
    if [ -n "$cur" ]; then
        PHASE_NUM="$cur"
    fi
fi

phase_numeric() {
    case "$1" in
        5a|5b) echo "5" ;;
        *) echo "$1" ;;
    esac
}

PHASE_INT=$(phase_numeric "$PHASE_NUM")
case "$PHASE_INT" in
    ''|*[!0-9]*)
        WARN_ONLY=1
        ;;
    *)
        if [ "$PHASE_INT" -ge 8 ]; then
            WARN_ONLY=0
        else
            WARN_ONLY=1
        fi
        ;;
esac

note() { echo "coverage_gate: $*"; }

abort() {
    local code="$1"; shift
    note "ABORT: $*"
    exit "$code"
}

failure() {
    if [ "$WARN_ONLY" = "1" ]; then
        note "WARNING (phase $PHASE_NUM, gate non-blocking until phase 8): $*"
        return 0
    fi
    note "FAIL: $*"
    exit 1
}

# Driver detection.
HAS_XDEBUG=$(php -r 'exit(extension_loaded("xdebug") ? 0 : 1);' 2>/dev/null && echo 1 || echo 0)
HAS_PCOV=$(php -r 'exit(extension_loaded("pcov") ? 0 : 1);' 2>/dev/null && echo 1 || echo 0)

if [ "$HAS_XDEBUG" = "1" ] && [ "$HAS_PCOV" = "1" ]; then
    abort 2 "both xdebug and pcov are loaded; disable one (subgate (a) requires xdebug; subgate (b)/(c) accept either)."
fi
if [ "$HAS_XDEBUG" = "0" ] && [ "$HAS_PCOV" = "0" ]; then
    abort 2 "no coverage driver loaded; install xdebug for branch coverage or pcov for line-only."
fi
if [ "$HAS_XDEBUG" = "1" ]; then
    XDEBUG_MODE_VAL="${XDEBUG_MODE:-}"
    if [[ "$XDEBUG_MODE_VAL" != *coverage* ]]; then
        abort 2 "xdebug loaded but XDEBUG_MODE does not contain 'coverage'; export XDEBUG_MODE=coverage."
    fi
fi

if [ ! -f "$COVERAGE_CLOVER" ]; then
    abort 2 "coverage clover not found at $COVERAGE_CLOVER; run phpunit --coverage-clover=$COVERAGE_CLOVER first."
fi

# Subgate (a) — XdrJsonHelper.php: 100% line + 90% branch.
HELPER_REL="Soneso/StellarSDK/Xdr/XdrJsonHelper.php"
if [ ! -f "$HELPER_REL" ]; then
    note "subgate (a) skipped: $HELPER_REL does not exist yet (Phase 1 deliverable)."
else
    if [ "$HAS_PCOV" = "1" ] && [ "$HAS_XDEBUG" = "0" ]; then
        abort 2 "subgate (a) requires xdebug; run via .github/workflows/sep-51-coverage.yml lane."
    else
        # Run a focused python parse over clover.xml.
        python3 - "$COVERAGE_CLOVER" "$HELPER_REL" <<'PY' || HELPER_FAIL=1
import sys, xml.etree.ElementTree as ET, os

clover, target = sys.argv[1], sys.argv[2]
tree = ET.parse(clover)
root = tree.getroot()

# Find the file element by absolute or repo-relative path tail match.
target_abs = os.path.abspath(target)
file_el = None
for f in root.iter('file'):
    name = f.get('name', '')
    if name == target_abs or name.endswith('/' + target):
        file_el = f
        break

if file_el is None:
    print(f"subgate (a) FAIL: no clover entry for {target}", file=sys.stderr)
    sys.exit(1)

stmt_lines = [ln for ln in file_el.iter('line') if ln.get('type') == 'stmt']
cond_lines = [ln for ln in file_el.iter('line') if ln.get('type') == 'cond']

# Branch coverage requires conditional nodes; xdebug emits these.
if not cond_lines:
    print("subgate (a) FAIL: no branch nodes in clover.xml; coverage was not collected with branches (xdebug required).", file=sys.stderr)
    sys.exit(1)

stmt_total = len(stmt_lines)
stmt_hit = sum(1 for ln in stmt_lines if int(ln.get('count', '0')) > 0)
line_pct = (stmt_hit / stmt_total * 100.0) if stmt_total else 100.0

# Branch (cond) lines carry truecount + falsecount; need both > 0 to count "covered".
cond_covered = 0
for ln in cond_lines:
    tc = int(ln.get('truecount', '0'))
    fc = int(ln.get('falsecount', '0'))
    if tc > 0 and fc > 0:
        cond_covered += 1
cond_total = len(cond_lines)
branch_pct = (cond_covered / cond_total * 100.0) if cond_total else 100.0

failed = False
if line_pct < 100.0:
    print(f"subgate (a) FAIL: line coverage {line_pct:.2f}% < 100.00% on {target}", file=sys.stderr)
    failed = True
if branch_pct < 90.0:
    print(f"subgate (a) FAIL: branch coverage {branch_pct:.2f}% < 90.00% on {target}", file=sys.stderr)
    failed = True
if failed:
    sys.exit(1)
print(f"subgate (a) OK: line={line_pct:.2f}% branch={branch_pct:.2f}%")
PY
        if [ "${HELPER_FAIL:-0}" = "1" ]; then
            failure "subgate (a) thresholds missed; see stderr."
        fi
    fi
fi

# Subgate (b) and (c) operate on the changed-files set.
DIFF_FILES_PHP=$(git diff --name-only "$DIFF_BASE"...HEAD -- '*.php' 2>/dev/null || true)

if [ -z "$DIFF_FILES_PHP" ]; then
    note "subgates (b)/(c) skipped: no PHP files changed vs $DIFF_BASE."
    note "All applicable subgates passed."
    exit 0
fi

# Subgate (b): every changed PHP file's NEW methods >= 95% line coverage.
NEW_METHODS_HELPER="${REPO_ROOT}/tools/sep-51-fixtures/identify_new_methods.php"
if [ ! -x "$NEW_METHODS_HELPER" ] && [ ! -f "$NEW_METHODS_HELPER" ]; then
    note "subgates (b)/(c) skipped: identify_new_methods.php not found."
    exit 0
fi

# Aggregate counters for subgate (c).
AGG_HIT=0
AGG_TOTAL=0
SUBGATE_B_FAIL=0

while IFS= read -r f; do
    [ -z "$f" ] && continue
    [ ! -f "$f" ] && continue
    case "$f" in *Test.php) continue ;; esac

    # Identify new methods (line ranges).
    new_ranges=$(php "$NEW_METHODS_HELPER" "$f" "$DIFF_BASE" 2>/dev/null || true)

    python3 - "$COVERAGE_CLOVER" "$f" <<PY || true
import sys, os, xml.etree.ElementTree as ET
clover, target = sys.argv[1], sys.argv[2]
ranges_raw = """${new_ranges}"""
ranges = []
for rl in ranges_raw.splitlines():
    rl = rl.strip()
    if not rl: continue
    # Format: <file>:<startLine>-<endLine>:<methodName>
    parts = rl.split(':')
    if len(parts) < 3: continue
    rng = parts[1].split('-')
    if len(rng) != 2: continue
    try:
        ranges.append((int(rng[0]), int(rng[1])))
    except ValueError:
        pass

tree = ET.parse(clover)
root = tree.getroot()
target_abs = os.path.abspath(target)
file_el = None
for fe in root.iter('file'):
    n = fe.get('name', '')
    if n == target_abs or n.endswith('/' + target):
        file_el = fe; break

stmt_total = 0
stmt_hit = 0
if file_el is not None:
    for ln in file_el.iter('line'):
        if ln.get('type') != 'stmt':
            continue
        try:
            num = int(ln.get('num', '0'))
        except ValueError:
            continue
        in_range = (not ranges) or any(lo <= num <= hi for lo, hi in ranges)
        if not in_range:
            continue
        stmt_total += 1
        if int(ln.get('count', '0')) > 0:
            stmt_hit += 1

print(f"COUNTS:{target}:{stmt_hit}:{stmt_total}")
PY
done <<< "$DIFF_FILES_PHP" > /tmp/sep51-coverage-counts.txt 2>/dev/null

while IFS= read -r line; do
    [ -z "$line" ] && continue
    case "$line" in COUNTS:*) ;; *) continue ;; esac
    rest="${line#COUNTS:}"
    fname="${rest%%:*}"
    rest2="${rest#*:}"
    hit="${rest2%%:*}"
    total="${rest2##*:}"
    AGG_HIT=$((AGG_HIT + hit))
    AGG_TOTAL=$((AGG_TOTAL + total))
    if [ "$total" -gt 0 ]; then
        # Use awk for float math.
        pct=$(awk -v h="$hit" -v t="$total" 'BEGIN{printf "%.2f", (h/t)*100}')
        below=$(awk -v p="$pct" 'BEGIN{print (p < 95.0) ? 1 : 0}')
        if [ "$below" = "1" ]; then
            note "subgate (b) FAIL: $fname new-methods line coverage $pct% < 95%"
            SUBGATE_B_FAIL=1
        fi
    fi
done < /tmp/sep51-coverage-counts.txt

if [ "$SUBGATE_B_FAIL" = "1" ]; then
    failure "one or more changed files missed the 95% new-method line threshold."
fi

# Subgate (c).
if [ "$AGG_TOTAL" -gt 0 ]; then
    agg_pct=$(awk -v h="$AGG_HIT" -v t="$AGG_TOTAL" 'BEGIN{printf "%.2f", (h/t)*100}')
    below=$(awk -v p="$agg_pct" 'BEGIN{print (p < 95.0) ? 1 : 0}')
    if [ "$below" = "1" ]; then
        failure "subgate (c) FAIL: aggregate line coverage $agg_pct% < 95% over changed files."
    fi
    note "subgate (c) OK: aggregate line coverage $agg_pct%"
fi

note "All applicable subgates passed."
exit 0
