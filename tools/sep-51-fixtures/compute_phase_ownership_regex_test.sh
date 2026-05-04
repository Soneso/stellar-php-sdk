#!/usr/bin/env bash
# Fixture-driven test for tools/sep-51-fixtures/compute_phase_ownership_regex.sh.
#
# Each test case constructs a sandbox tree with phase-N-owned.txt fixtures and
# a state file, invokes the script, and asserts exit code + stdout / stderr.
#
# Run: bash tools/sep-51-fixtures/compute_phase_ownership_regex_test.sh

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SCRIPT="${SCRIPT_DIR}/compute_phase_ownership_regex.sh"

PASS=0
FAIL=0

# Helper: run script in a sandbox where we synthesize phase-N-owned.txt files
# under tools/sep-51-fixtures/, then invoke compute_phase_ownership_regex.sh
# from a copy positioned at the same relative path inside the sandbox.
make_sandbox() {
    local sandbox="$1"
    mkdir -p "$sandbox/tools/sep-51-fixtures"
    cp "$SCRIPT" "$sandbox/tools/sep-51-fixtures/compute_phase_ownership_regex.sh"
    chmod +x "$sandbox/tools/sep-51-fixtures/compute_phase_ownership_regex.sh"
}

run_in_sandbox() {
    # Args: sandbox phase last_head state
    local sandbox="$1" phase="$2" last_head="$3" state="$4"
    bash "$sandbox/tools/sep-51-fixtures/compute_phase_ownership_regex.sh" \
        "$phase" "$last_head" "$state" 2>"$sandbox/.stderr"
    return $?
}

assert_eq() {
    local name="$1" expected="$2" actual="$3"
    if [ "$expected" = "$actual" ]; then
        echo "  PASS  $name"
        PASS=$((PASS + 1))
    else
        echo "  FAIL  $name"
        echo "         expected: $expected"
        echo "         actual:   $actual"
        FAIL=$((FAIL + 1))
    fi
}

assert_exit() {
    local name="$1" expected="$2" actual="$3"
    if [ "$expected" -eq "$actual" ]; then
        echo "  PASS  $name (exit $actual)"
        PASS=$((PASS + 1))
    else
        echo "  FAIL  $name: expected exit $expected, got $actual"
        FAIL=$((FAIL + 1))
    fi
}

# Test 1: empty uncommitted_phases — single phase output.
test_single_phase() {
    local sandbox; sandbox=$(mktemp -d)
    trap "rm -rf '$sandbox'" RETURN
    make_sandbox "$sandbox"
    printf '%s\n' '^owned_a\.php$' > "$sandbox/tools/sep-51-fixtures/phase-1-owned.txt"
    printf '{"schema_version":1,"uncommitted_phases":[]}' > "$sandbox/state.json"
    out=$(run_in_sandbox "$sandbox" "1" "main" "$sandbox/state.json")
    rc=$?
    assert_exit "single phase exit code" 0 "$rc"
    assert_eq "single phase output" '(^owned_a\.php$)' "$out"
}

# Test 2: single uncommitted prior phase — union of two.
test_union_two() {
    local sandbox; sandbox=$(mktemp -d)
    trap "rm -rf '$sandbox'" RETURN
    make_sandbox "$sandbox"
    printf '%s\n' '^a\.php$' > "$sandbox/tools/sep-51-fixtures/phase-2-owned.txt"
    printf '%s\n' '^b\.php$' > "$sandbox/tools/sep-51-fixtures/phase-3-owned.txt"
    printf '{"schema_version":1,"uncommitted_phases":[2]}' > "$sandbox/state.json"
    out=$(run_in_sandbox "$sandbox" "3" "main" "$sandbox/state.json")
    rc=$?
    assert_exit "union of two exit code" 0 "$rc"
    # Order: current phase (3) first, then uncommitted (2).
    assert_eq "union of two output" '(^b\.php$|^a\.php$)' "$out"
}

# Test 3: multiple uncommitted phases.
test_multiple_uncommitted() {
    local sandbox; sandbox=$(mktemp -d)
    trap "rm -rf '$sandbox'" RETURN
    make_sandbox "$sandbox"
    printf '%s\n' '^a\.php$' > "$sandbox/tools/sep-51-fixtures/phase-2-owned.txt"
    printf '%s\n' '^b\.php$' > "$sandbox/tools/sep-51-fixtures/phase-3-owned.txt"
    printf '%s\n' '^c\.php$' > "$sandbox/tools/sep-51-fixtures/phase-4-owned.txt"
    printf '{"schema_version":1,"uncommitted_phases":[2,3]}' > "$sandbox/state.json"
    out=$(run_in_sandbox "$sandbox" "4" "main" "$sandbox/state.json")
    rc=$?
    assert_exit "multiple uncommitted exit code" 0 "$rc"
    assert_eq "multiple uncommitted output" '(^c\.php$|^a\.php$|^b\.php$)' "$out"
}

# Test 4: malformed state file.
test_malformed_state() {
    local sandbox; sandbox=$(mktemp -d)
    trap "rm -rf '$sandbox'" RETURN
    make_sandbox "$sandbox"
    printf '%s\n' '^a\.php$' > "$sandbox/tools/sep-51-fixtures/phase-1-owned.txt"
    printf '{not json' > "$sandbox/state.json"
    bash "$sandbox/tools/sep-51-fixtures/compute_phase_ownership_regex.sh" \
        "1" "main" "$sandbox/state.json" >"$sandbox/.stdout" 2>"$sandbox/.stderr"
    rc=$?
    assert_exit "malformed state exit code" 3 "$rc"
    err=$(cat "$sandbox/.stderr")
    if printf '%s' "$err" | grep -q 'STATE_FILE_MALFORMED'; then
        echo "  PASS  malformed state stderr message"
        PASS=$((PASS + 1))
    else
        echo "  FAIL  malformed state stderr should contain STATE_FILE_MALFORMED, got: $err"
        FAIL=$((FAIL + 1))
    fi
}

# Test 5: missing phase-owned file.
test_missing_owned_file() {
    local sandbox; sandbox=$(mktemp -d)
    trap "rm -rf '$sandbox'" RETURN
    make_sandbox "$sandbox"
    # Do NOT create phase-3-owned.txt; only phase-1-owned.txt.
    printf '%s\n' '^a\.php$' > "$sandbox/tools/sep-51-fixtures/phase-1-owned.txt"
    printf '{"schema_version":1,"uncommitted_phases":[]}' > "$sandbox/state.json"
    bash "$sandbox/tools/sep-51-fixtures/compute_phase_ownership_regex.sh" \
        "3" "main" "$sandbox/state.json" >"$sandbox/.stdout" 2>"$sandbox/.stderr"
    rc=$?
    assert_exit "missing phase-owned exit code" 2 "$rc"
    err=$(cat "$sandbox/.stderr")
    if printf '%s' "$err" | grep -q 'PHASE_OWNED_FILE_MISSING'; then
        echo "  PASS  missing phase-owned stderr message"
        PASS=$((PASS + 1))
    else
        echo "  FAIL  missing phase-owned stderr: $err"
        FAIL=$((FAIL + 1))
    fi
}

# Test 6: invalid phase number.
test_invalid_phase() {
    local sandbox; sandbox=$(mktemp -d)
    trap "rm -rf '$sandbox'" RETURN
    make_sandbox "$sandbox"
    printf '{"schema_version":1,"uncommitted_phases":[]}' > "$sandbox/state.json"
    bash "$sandbox/tools/sep-51-fixtures/compute_phase_ownership_regex.sh" \
        "11" "main" "$sandbox/state.json" >"$sandbox/.stdout" 2>"$sandbox/.stderr"
    rc=$?
    assert_exit "invalid phase exit code" 4 "$rc"
    err=$(cat "$sandbox/.stderr")
    if printf '%s' "$err" | grep -q 'INVALID_PHASE_NUM'; then
        echo "  PASS  invalid phase stderr message"
        PASS=$((PASS + 1))
    else
        echo "  FAIL  invalid phase stderr: $err"
        FAIL=$((FAIL + 1))
    fi
}

# Test 7: state file missing entirely — treat uncommitted as empty.
test_state_missing() {
    local sandbox; sandbox=$(mktemp -d)
    trap "rm -rf '$sandbox'" RETURN
    make_sandbox "$sandbox"
    printf '%s\n' '^z\.php$' > "$sandbox/tools/sep-51-fixtures/phase-0-owned.txt"
    out=$(run_in_sandbox "$sandbox" "0" "main" "$sandbox/no-such-state.json")
    rc=$?
    assert_exit "state-missing exit code" 0 "$rc"
    assert_eq "state-missing output" '(^z\.php$)' "$out"
}

# Test 8: phase 5a / 5b accepted as known.
test_phase_5a_known() {
    local sandbox; sandbox=$(mktemp -d)
    trap "rm -rf '$sandbox'" RETURN
    make_sandbox "$sandbox"
    printf '%s\n' '^q\.php$' > "$sandbox/tools/sep-51-fixtures/phase-5a-owned.txt"
    printf '{"schema_version":1,"uncommitted_phases":[]}' > "$sandbox/state.json"
    out=$(run_in_sandbox "$sandbox" "5a" "main" "$sandbox/state.json")
    rc=$?
    assert_exit "phase 5a exit code" 0 "$rc"
    assert_eq "phase 5a output" '(^q\.php$)' "$out"
}

echo "compute_phase_ownership_regex.sh fixture tests:"
test_single_phase
test_union_two
test_multiple_uncommitted
test_malformed_state
test_missing_owned_file
test_invalid_phase
test_state_missing
test_phase_5a_known

echo ""
echo "$PASS passed, $FAIL failed"
if [ "$FAIL" -ne 0 ]; then
    exit 1
fi
exit 0
