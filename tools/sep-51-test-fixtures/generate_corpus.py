#!/usr/bin/env python3
"""Generate the SEP-51 snapshot corpus.

The corpus is a regression baseline: a list of (id, type, base64, ...) entries
for which the committed `spec_reference_json` is the SDK's own canonical
SEP-0051 toJson output for that XDR base64 input. CorpusSnapshotTest re-asserts
each entry on every CI run, so any unintended change to the emission surface
fails fast as a snapshot diff.

Pipeline:

  1. Run the PHP seed (tools/sep-51-test-fixtures/_corpus_seed.php) to obtain the
     fixture catalogue. The seed validates each fixture's base64 round-trips
     through the PHP SDK's XDR codec.
  2. Pipe the seed list through tools/sep-51-test-fixtures/_corpus_to_json.php,
     which decodes each base64 via Xdr<Type>::fromBase64Xdr and re-emits the
     canonical SEP-0051 JSON via toJson(). The result is written into each
     entry's `spec_reference_json` field.
  3. Wrap the entries in a top-level object that documents the corpus's role
     and write it to <output> (default tools/sep-51-test-fixtures/corpus.json).

Usage:
  python3 tools/sep-51-test-fixtures/generate_corpus.py [--output <path>]
"""

from __future__ import annotations

import argparse
import json
import subprocess
import sys
from datetime import datetime, timezone
from pathlib import Path
from typing import Any


REPO_ROOT = Path(__file__).resolve().parents[2]
COMMITTED_OUTPUT = REPO_ROOT / "tools" / "sep-51-test-fixtures" / "corpus.json"
SEED_SCRIPT = REPO_ROOT / "tools" / "sep-51-test-fixtures" / "_corpus_seed.php"
TO_JSON_SCRIPT = REPO_ROOT / "tools" / "sep-51-test-fixtures" / "_corpus_to_json.php"


def load_populated_fixtures() -> list[dict[str, Any]]:
    """Run the seed pipeline and return entries with spec_reference_json populated.

    The seed script constructs each fixture programmatically via the PHP SDK's
    existing factories and emits a JSON list to stdout. The to-json companion
    decodes each base64 and re-emits the canonical SEP-0051 JSON via toJson(),
    writing the result into each entry's spec_reference_json field.
    """
    if not SEED_SCRIPT.exists():
        raise SystemExit(f"seed script not found: {SEED_SCRIPT}")
    if not TO_JSON_SCRIPT.exists():
        raise SystemExit(f"to-json companion script not found: {TO_JSON_SCRIPT}")

    seed = subprocess.run(
        ["php", str(SEED_SCRIPT)],
        cwd=str(REPO_ROOT),
        check=True,
        capture_output=True,
    )
    populated = subprocess.run(
        ["php", str(TO_JSON_SCRIPT)],
        cwd=str(REPO_ROOT),
        check=True,
        input=seed.stdout,
        capture_output=True,
    )
    return json.loads(populated.stdout.decode("utf-8"))


def render_entry(fixture: dict[str, Any]) -> dict[str, Any]:
    return {
        "id": fixture["id"],
        "type": fixture["type"],
        "base64": fixture["base64"],
        "spec_reference_json": fixture.get("spec_reference_json"),
        "spec_anchor": fixture.get("spec_anchor"),
        "notes": fixture.get("notes"),
    }


def build_corpus(fixtures: list[dict[str, Any]]) -> dict[str, Any]:
    return {
        "schema_version": 2,
        "_meta": {
            "role": (
                "Snapshot regression baseline for the PHP SDK's SEP-0051 emission. "
                "Each entry's spec_reference_json is the SDK's own canonical "
                "toJson() output for the entry's base64 XDR input; CorpusSnapshotTest "
                "re-asserts every entry on every CI run to catch unintended drift."
            ),
            "spec_anchor": "https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0051.md",
        },
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "entries": [render_entry(f) for f in fixtures],
    }


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--output", default=str(COMMITTED_OUTPUT))
    args = parser.parse_args()

    fixtures = load_populated_fixtures()
    corpus = build_corpus(fixtures)

    out_path = Path(args.output)
    out_path.parent.mkdir(parents=True, exist_ok=True)
    out_path.write_text(json.dumps(corpus, indent=2) + "\n", encoding="utf-8")
    print(f"wrote {len(corpus['entries'])} entries to {out_path}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
