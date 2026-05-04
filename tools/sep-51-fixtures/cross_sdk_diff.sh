#!/usr/bin/env bash
# Developer-only cross-SDK validator.
#
# For each entry in tools/sep-51-fixtures/corpus.json with divergence_reason == null:
#   - PHP: <Type>::fromBase64Xdr($base64)->toJson()
#   - py-stellar-base: <Type>.from_xdr($base64).to_json()
#   - structurally compare the two JSON strings via python json.loads + ==
#
# This script is NOT part of the CI mechanical-check suite; it requires
# py-stellar-base to be importable in the active python environment, which is
# satisfied either by a developer venv or by tools/.venv-corpus-refresh/.
#
# Exit codes:
#   0 every non-divergent entry matches structurally.
#   1 at least one entry mismatches.
#   2 prerequisite missing (py-stellar-base not importable, corpus missing, etc.).

set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$REPO_ROOT"

CORPUS="${CORPUS:-tools/sep-51-fixtures/corpus.json}"

if [ ! -f "$CORPUS" ]; then
    echo "cross_sdk_diff: corpus not found at $CORPUS" >&2
    exit 2
fi

# Sanity-check the python side first.
if ! python3 -c 'import stellar_sdk' >/dev/null 2>&1; then
    echo "cross_sdk_diff: py-stellar-base not importable in current python; activate the venv at tools/.venv-corpus-refresh/ or pip install stellar-sdk." >&2
    exit 2
fi

PY_TMP=$(mktemp -d)
trap 'rm -rf "$PY_TMP"' EXIT

# Iterate corpus entries. Use python to drive the loop — bash + jq + php is
# unwieldy and python already sees the corpus.
python3 - "$CORPUS" "$PY_TMP" <<'PY'
import json, os, subprocess, sys
from pathlib import Path

corpus_path, scratch = sys.argv[1], Path(sys.argv[2])
data = json.loads(Path(corpus_path).read_text(encoding="utf-8"))
entries = data.get("entries", [])
fails = 0
checked = 0

for entry in entries:
    if entry.get("divergence_reason"):
        continue
    type_name = entry.get("type")
    base64 = entry.get("base64")
    eid = entry.get("id", "<unnamed>")
    if not type_name or not base64:
        print(f"SKIP {eid}: missing type or base64", file=sys.stderr)
        continue

    # PHP side: invoke a one-shot script that emits JSON to stdout.
    php_script = scratch / "run.php"
    php_script.write_text(
        f"""<?php
require __DIR__ . '/../vendor/autoload.php';
$cls = 'Soneso\\\\StellarSDK\\\\Xdr\\\\{type_name}';
if (!class_exists($cls)) {{ fwrite(STDERR, "missing class $cls"); exit(2); }}
$obj = $cls::fromBase64Xdr({json.dumps(base64)});
echo $obj->toJson();
""",
        encoding="utf-8",
    )
    try:
        php_out = subprocess.check_output(
            ["php", str(php_script)], cwd=Path.cwd(), timeout=60
        ).decode("utf-8")
    except subprocess.CalledProcessError as exc:
        print(f"FAIL {eid}: PHP call raised: {exc}", file=sys.stderr)
        fails += 1
        continue

    # py side.
    py_snippet = (
        f"import sys, json\n"
        f"from stellar_sdk.xdr import {type_name}\n"
        f"obj = {type_name}.from_xdr({base64!r})\n"
        f"sys.stdout.write(obj.to_json())\n"
    )
    try:
        py_out = subprocess.check_output(
            ["python3", "-c", py_snippet], timeout=60
        ).decode("utf-8")
    except subprocess.CalledProcessError as exc:
        print(f"FAIL {eid}: py call raised: {exc}", file=sys.stderr)
        fails += 1
        continue

    try:
        a = json.loads(php_out)
        b = json.loads(py_out)
    except json.JSONDecodeError as exc:
        print(f"FAIL {eid}: JSON parse: {exc}", file=sys.stderr)
        fails += 1
        continue

    if a == b:
        checked += 1
        print(f"OK   {eid}")
    else:
        fails += 1
        print(f"FAIL {eid}: structural mismatch", file=sys.stderr)
        print(f"  php: {json.dumps(a)[:200]}", file=sys.stderr)
        print(f"  py:  {json.dumps(b)[:200]}", file=sys.stderr)

print(f"checked={checked} failed={fails}", file=sys.stderr)
sys.exit(1 if fails else 0)
PY
exit $?
