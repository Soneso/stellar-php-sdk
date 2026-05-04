#!/usr/bin/env bash
# SEP-51 G-Negative gate.
#
# Discovers union markers emitted by the Phase 3 generator extension and
# enforces that NegativeInputTest.php declares the per-shape battery for each.
#
# Marker grammar (emitted as the first line of every union fromJsonValue method
# body in *Base.php files):
#     // @sep51-union <UnionClassName> shape=<shape>
#
# Shape values: void_only | non_void | mixed | int_cased
#
# Per-shape required test methods (in
# Soneso/StellarSDKTests/Unit/Xdr/Sep51/NegativeInputTest.php):
#   void_only: testNegative_<UnionClass>_unknownString,
#              _intInput, _nullInput, _arrayInput
#   non_void:  _unknownDiscriminant, _multiKeyDict, _bareStringWrongPlace, _wrongType
#   mixed:     _unknownDiscriminant, _multiKeyDict, _bareStringWrongPlace,
#              _wrongType, _unknownArmKey
#   int_cased: _unknownVersionString, _integerInput, _multiKeyDict, _nullInput
#
# Exit codes:
#   0 every discovered union has all required negative-test methods.
#   1 at least one missing method.

set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$REPO_ROOT"

XDR_DIR="Soneso/StellarSDK/Xdr"
TEST_FILE="Soneso/StellarSDKTests/Unit/Xdr/Sep51/NegativeInputTest.php"

# Find marker lines across every *Base.php in the XDR dir.
MARKER_PATTERN='//[[:space:]]*@sep51-union[[:space:]]+[A-Za-z0-9_]+[[:space:]]+shape=(void_only|non_void|mixed|int_cased)'
markers=$(grep -hEo "$MARKER_PATTERN" "$XDR_DIR"/Xdr*Base.php 2>/dev/null | sort -u || true)

if [ -z "$markers" ]; then
    echo "negative_gate: no union markers found; gate is a no-op."
    exit 0
fi

# If the test file does not yet exist, the gate fails informatively rather than
# silently passing. Phase 5b owns this file; the §3.4 gate dispatch is supposed
# to skip the gate on Phases 0/1/2/3/4/5a, so reaching this branch on those
# phases is itself a misconfiguration.
if [ ! -f "$TEST_FILE" ]; then
    echo "negative_gate: union markers exist but $TEST_FILE is absent." >&2
    echo "negative_gate: Phase 5b owns this file; the gate must be skipped on prior phases." >&2
    exit 1
fi

declare -A SHAPE_METHODS_VOID_ONLY=( [unknownString]=1 [intInput]=1 [nullInput]=1 [arrayInput]=1 )
declare -A SHAPE_METHODS_NON_VOID=( [unknownDiscriminant]=1 [multiKeyDict]=1 [bareStringWrongPlace]=1 [wrongType]=1 )
declare -A SHAPE_METHODS_MIXED=( [unknownDiscriminant]=1 [multiKeyDict]=1 [bareStringWrongPlace]=1 [wrongType]=1 [unknownArmKey]=1 )
declare -A SHAPE_METHODS_INT_CASED=( [unknownVersionString]=1 [integerInput]=1 [multiKeyDict]=1 [nullInput]=1 )

methods_for_shape() {
    case "$1" in
        void_only) printf '%s\n' "${!SHAPE_METHODS_VOID_ONLY[@]}" ;;
        non_void)  printf '%s\n' "${!SHAPE_METHODS_NON_VOID[@]}" ;;
        mixed)     printf '%s\n' "${!SHAPE_METHODS_MIXED[@]}" ;;
        int_cased) printf '%s\n' "${!SHAPE_METHODS_INT_CASED[@]}" ;;
    esac
}

failures=0
test_body=$(cat "$TEST_FILE")

# Iterate each unique marker line.
while IFS= read -r marker; do
    [ -z "$marker" ] && continue
    # Marker form: // @sep51-union <Class> shape=<shape>
    cls=$(printf '%s' "$marker" | awk '{print $3}')
    shape=$(printf '%s' "$marker" | awk -F'shape=' '{print $2}' | awk '{print $1}')

    while IFS= read -r suffix; do
        [ -z "$suffix" ] && continue
        method="testNegative_${cls}_${suffix}"
        if ! printf '%s' "$test_body" | grep -qE "function[[:space:]]+${method}\\b"; then
            echo "negative_gate: MISSING method $method (shape=$shape)" >&2
            failures=$((failures + 1))
        fi
    done < <(methods_for_shape "$shape")
done <<< "$markers"

if [ "$failures" -gt 0 ]; then
    echo "negative_gate: $failures missing method(s)." >&2
    exit 1
fi

echo "negative_gate: all union markers covered."
exit 0
