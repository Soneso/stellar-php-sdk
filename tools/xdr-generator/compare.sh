#!/bin/bash
# Compare generated PHP XDR files against originals after normalization.
# Usage: ./compare.sh [file_pattern|all]
# Examples:
#   ./compare.sh                    # Compare all modified files
#   ./compare.sh XdrPrice           # Compare one specific file
#   ./compare.sh all                # Compare all XDR files (not just modified)
#
# Normalization rules (applied to both sides before diffing):
#   - Strip trailing whitespace
#   - Collapse multiple blank lines to one
#   - Normalize brace spacing
#   - Remove the generated file header (so hand-written files can match)

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
XDR_DIR="$REPO_ROOT/Soneso/StellarSDK/Xdr"
PATTERN="${1:-}"

cd "$REPO_ROOT"

# Get list of files to compare
if [ "$PATTERN" = "all" ]; then
  FILES=$(find "$XDR_DIR" -name '*.php' -type f | sort)
elif [ -n "$PATTERN" ]; then
  FILES=$(git diff --name-only -- "Soneso/StellarSDK/Xdr/*${PATTERN}*" 2>/dev/null || true)
else
  FILES=$(git diff --name-only -- Soneso/StellarSDK/Xdr/)
fi

if [ -z "$FILES" ]; then
  echo "No XDR files found to compare."
  exit 0
fi

EXACT=0
DIFF=0
TOTAL=0
TMPDIR=$(mktemp -d)
trap 'rm -rf "$TMPDIR"' EXIT

# Normalize PHP file for comparison
normalize() {
  local file="$1"
  sed \
    -e 's/[[:space:]]*$//' \
    -e '/^\/\/ This file was automatically generated/d' \
    -e '/^\/\/ DO NOT EDIT/d' \
    "$file" | \
  cat -s
}

for FILE in $FILES; do
  # Handle absolute vs relative paths
  if [[ "$FILE" == /* ]]; then
    RELFILE="${FILE#$REPO_ROOT/}"
  else
    RELFILE="$FILE"
  fi
  BASENAME=$(basename "$RELFILE")
  TOTAL=$((TOTAL + 1))

  # Check if file exists in working tree
  if [ ! -f "$REPO_ROOT/$RELFILE" ]; then
    echo "NEW:    $BASENAME"
    continue
  fi

  # Check if file exists in git HEAD
  if ! git show "HEAD:$RELFILE" > /dev/null 2>&1; then
    echo "NEW:    $BASENAME (not in HEAD)"
    continue
  fi

  # Normalize the generated (current working tree) version
  normalize "$REPO_ROOT/$RELFILE" > "$TMPDIR/generated_$BASENAME"

  # Normalize the original (git HEAD) version
  git show "HEAD:$RELFILE" > "$TMPDIR/original_raw_$BASENAME" 2>/dev/null
  normalize "$TMPDIR/original_raw_$BASENAME" > "$TMPDIR/original_$BASENAME"

  # Compare
  if diff -q "$TMPDIR/original_$BASENAME" "$TMPDIR/generated_$BASENAME" > /dev/null 2>&1; then
    EXACT=$((EXACT + 1))
  else
    DIFF=$((DIFF + 1))
    echo "DIFF:   $BASENAME"
    if [ -n "$PATTERN" ] && [ "$PATTERN" != "all" ]; then
      # Show diff for targeted comparisons
      diff "$TMPDIR/original_$BASENAME" "$TMPDIR/generated_$BASENAME" || true
      echo "---"
    fi
  fi
done

echo ""
echo "Summary: $EXACT exact, $DIFF with diffs, $TOTAL total"
