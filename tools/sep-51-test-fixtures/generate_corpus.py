#!/usr/bin/env python3
"""Generate the SEP-51 snapshot corpus.

The corpus is a correctness baseline: a list of (id, type, base64, ...)
entries whose committed `spec_reference_json` is the decode output of the
rs-stellar-xdr CLI oracle (the SEP-0051 reference implementation) for that
XDR base64 input. CorpusSnapshotTest asserts the PHP SDK's toJson() output
against every entry on every CI run, so a divergence from the reference
implementation fails fast - the corpus detects error, not merely drift.

Pipeline:

  1. Run the PHP seed (_corpus_seed.php) to obtain the fixture catalogue. The
     seed validates each fixture's base64 round-trips through the PHP SDK's
     XDR codec.
  2. Populate each entry's `spec_reference_json`:
       --source oracle (default): decode the base64 with the stellar-xdr CLI
         (requires >= 28.0.0; earlier versions emit `type_` instead of `type`
         for six ScSpec types). Used to (re)generate the vendored corpus.
       --source php: decode and re-emit via the PHP SDK's own toJson()
         (_corpus_to_json.php). Used by refresh_corpus.sh to diff the SDK's
         current output against the vendored oracle baseline without needing
         the oracle binary.
  3. Wrap the entries in a top-level object documenting the corpus's role and
     provenance and write it to <output> (default corpus.json).

Usage:
  python3 tools/sep-51-test-fixtures/generate_corpus.py \
      [--output <path>] [--source oracle|php] [--oracle-bin <path>]
"""

from __future__ import annotations

import argparse
import json
import re
import subprocess
import sys
from datetime import datetime, timezone
from pathlib import Path
from typing import Any


REPO_ROOT = Path(__file__).resolve().parents[2]
COMMITTED_OUTPUT = REPO_ROOT / "tools" / "sep-51-test-fixtures" / "corpus.json"
SEED_SCRIPT = REPO_ROOT / "tools" / "sep-51-test-fixtures" / "_corpus_seed.php"
TO_JSON_SCRIPT = REPO_ROOT / "tools" / "sep-51-test-fixtures" / "_corpus_to_json.php"

MIN_ORACLE_VERSION = (28, 0, 0)

# Raw Int64/Uint64 at CLI top level are JSON numbers in both directions (a CLI
# dispatch artefact); SEP-0051 mandates strings when nested. Standalone
# entries for these types cannot be populated from the oracle.
ORACLE_INCOMPARABLE_TYPES = {"Int64", "Uint64"}

# Corpus `type` values are PHP-side names (Xdr<type> classes); most map to the
# CLI identifier via heck UpperCamelCase, these differ structurally.
TYPE_NAME_OVERRIDES = {
    "AllowTrustOperation": "AllowTrustOp",
    "AssetAlphaNum4": "AlphaNum4",
    "AssetAlphaNum12": "AlphaNum12",
    "ClaimClaimableBalanceOperation": "ClaimClaimableBalanceOp",
    "ClawbackClaimableBalanceOperation": "ClawbackClaimableBalanceOp",
    "SignedPayload": "SignerKeyEd25519SignedPayload",
}

# Entries whose XDR bytes do not correspond to any standalone CLI type, so no
# oracle output exists for them. Their spec_reference_json is populated from
# the PHP SDK's own output and the entry carries an `oracle_incomparable`
# marker: CorpusSnapshotTest still pins them against unintended drift, but
# they are not reference-anchored. Keep this list enumerated and justified;
# never skip an entry silently.
_INLINE_OPAQUE_REASON = (
    "SEP-0051 §Opaque Data (Fixed Length) mandates a hexadecimal string for "
    "opaque[N]; rs-stellar-xdr serialises fixed opaque declared INLINE in "
    "the .x (rather than through a named typedef) as a JSON array of "
    "numbers, diverging from the spec text. The SDK follows the spec (hex "
    "string)."
)

ORACLE_INCOMPARABLE_ENTRIES = {
    "peer_address_ip_ipv4": _INLINE_OPAQUE_REASON,
    "peer_address_ip_ipv6": _INLINE_OPAQUE_REASON,
    "peer_address_ipv4_loopback": _INLINE_OPAQUE_REASON,
    "curve25519_secret_standalone": _INLINE_OPAQUE_REASON,
    "curve25519_public_standalone": _INLINE_OPAQUE_REASON,
    "hmac_sha256_key_standalone": _INLINE_OPAQUE_REASON,
    "hmac_sha256_mac_standalone": _INLINE_OPAQUE_REASON,
    "short_hash_seed_standalone": _INLINE_OPAQUE_REASON,
    "serialized_binary_fuse_filter": _INLINE_OPAQUE_REASON,
    "data_value_present": (
        "XdrDataValue models the optional-wrapped DataValue* used by "
        "ManageData (leading presence flag); the CLI's standalone DataValue "
        "is the bare opaque<64> and parses these bytes differently."
    ),
    "data_value_absent": (
        "XdrDataValue models the optional-wrapped DataValue*; the absent "
        "form (presence flag 0) has no standalone CLI equivalent and the "
        "SDK emits JSON null where a bare empty DataValue would emit \"\"."
    ),
}


def heck_upper_camel(name: str) -> str:
    """Map a bare XDR type name to the rs-stellar-xdr CLI type identifier
    (heck UpperCamelCase, e.g. SCVal -> ScVal, IPAddrType -> IpAddrType)."""
    words: list[str] = []
    for segment in name.split("_"):
        if not segment:
            continue
        current = ""
        chars = list(segment)
        for i, ch in enumerate(chars):
            if current:
                prev = current[-1]
                nxt = chars[i + 1] if i + 1 < len(chars) else ""
                if ch.isupper() and (prev.islower() or prev.isdigit()
                                     or (prev.isupper() and nxt.islower())):
                    words.append(current)
                    current = ""
            current += ch
        if current:
            words.append(current)
    return "".join(w[0].upper() + w[1:].lower() for w in words)


def run_seed() -> bytes:
    if not SEED_SCRIPT.exists():
        raise SystemExit(f"seed script not found: {SEED_SCRIPT}")
    seed = subprocess.run(
        ["php", str(SEED_SCRIPT)],
        cwd=str(REPO_ROOT),
        check=True,
        capture_output=True,
    )
    return seed.stdout


def populate_via_php(seed_stdout: bytes) -> list[dict[str, Any]]:
    if not TO_JSON_SCRIPT.exists():
        raise SystemExit(f"to-json companion script not found: {TO_JSON_SCRIPT}")
    populated = subprocess.run(
        ["php", str(TO_JSON_SCRIPT)],
        cwd=str(REPO_ROOT),
        check=True,
        input=seed_stdout,
        capture_output=True,
    )
    return json.loads(populated.stdout.decode("utf-8"))


def oracle_version_info(oracle_bin: str) -> dict[str, str]:
    """Return {'version': ..., 'xdr_commit': ...}; exit if below the minimum."""
    try:
        out = subprocess.run(
            [oracle_bin, "version"], check=True, capture_output=True, text=True
        ).stdout
    except (OSError, subprocess.CalledProcessError) as exc:
        raise SystemExit(
            f"oracle binary {oracle_bin!r} not runnable: {exc}. Install with: "
            "cargo install --locked stellar-xdr --features cli"
        )
    version_match = re.search(r"stellar-xdr\s+(\d+)\.(\d+)\.(\d+)", out)
    commit_match = re.search(r"xdr:\s*([0-9a-f]{40})", out)
    if not version_match:
        raise SystemExit(f"cannot parse oracle version from: {out!r}")
    version = tuple(int(g) for g in version_match.groups())
    if version < MIN_ORACLE_VERSION:
        raise SystemExit(
            f"oracle {'.'.join(map(str, version))} is older than the required "
            f"{'.'.join(map(str, MIN_ORACLE_VERSION))}: pre-28 releases emit "
            "the Rust keyword escape `type_` for six ScSpec types and would "
            "bake the wrong key into the corpus."
        )
    return {
        "version": ".".join(map(str, version)),
        "xdr_commit": commit_match.group(1) if commit_match else "unknown",
    }


def populate_via_oracle(seed_stdout: bytes, oracle_bin: str) -> list[dict[str, Any]]:
    fixtures = json.loads(seed_stdout.decode("utf-8"))
    php_populated = None
    failures: list[str] = []
    for fixture in fixtures:
        entry_id = fixture["id"]
        xdr_type = fixture["type"]
        if entry_id in ORACLE_INCOMPARABLE_ENTRIES:
            if php_populated is None:
                php_populated = {
                    f["id"]: f for f in populate_via_php(seed_stdout)
                }
            fixture["spec_reference_json"] = (
                php_populated[entry_id]["spec_reference_json"]
            )
            fixture["oracle_incomparable"] = ORACLE_INCOMPARABLE_ENTRIES[entry_id]
            continue
        if xdr_type in ORACLE_INCOMPARABLE_TYPES:
            failures.append(
                f"entry {entry_id!r} has standalone type {xdr_type}, which the "
                "CLI oracle renders as a JSON number while SEP-0051 mandates a "
                "string when nested; add it to ORACLE_INCOMPARABLE_ENTRIES "
                "with a justification or exclude it."
            )
            continue
        cli_type = TYPE_NAME_OVERRIDES.get(xdr_type) or heck_upper_camel(xdr_type)
        decoded = subprocess.run(
            [oracle_bin, "decode", "--type", cli_type,
             "--input", "single-base64", "--output", "json"],
            input=fixture["base64"],
            capture_output=True,
            text=True,
        )
        if decoded.returncode != 0:
            failures.append(
                f"oracle decode failed for entry {entry_id!r} "
                f"(type {cli_type}): {decoded.stderr.strip()}"
            )
            continue
        fixture["spec_reference_json"] = decoded.stdout.strip()
    if failures:
        raise SystemExit("\n".join(failures))
    return fixtures


def render_entry(fixture: dict[str, Any]) -> dict[str, Any]:
    entry = {
        "id": fixture["id"],
        "type": fixture["type"],
        "base64": fixture["base64"],
        "spec_reference_json": fixture.get("spec_reference_json"),
        "spec_anchor": fixture.get("spec_anchor"),
        "notes": fixture.get("notes"),
    }
    if "oracle_incomparable" in fixture:
        entry["oracle_incomparable"] = fixture["oracle_incomparable"]
    return entry


def build_corpus(fixtures: list[dict[str, Any]],
                 source: str,
                 oracle_info: dict[str, str] | None) -> dict[str, Any]:
    if source == "oracle":
        role = (
            "Correctness baseline for the PHP SDK's SEP-0051 emission. Each "
            "entry's spec_reference_json is the decode output of the "
            "rs-stellar-xdr CLI oracle for the entry's base64 XDR input; "
            "CorpusSnapshotTest asserts the SDK's toJson() output against "
            "every entry on every CI run, so divergence from the reference "
            "implementation fails fast."
        )
    else:
        role = (
            "Scratch re-emission of the PHP SDK's own SEP-0051 toJson() "
            "output, produced for drift comparison against the committed "
            "oracle baseline. NOT reference-anchored; never commit this "
            "file as corpus.json."
        )
    meta: dict[str, Any] = {
        "role": role,
        "spec_anchor": "https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0051.md",
        "source": source,
    }
    if oracle_info is not None:
        meta["oracle"] = {
            "tool": "stellar-xdr CLI (rs-stellar-xdr)",
            **oracle_info,
        }
    return {
        "schema_version": 2,
        "_meta": meta,
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "entries": [render_entry(f) for f in fixtures],
    }


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--output", default=str(COMMITTED_OUTPUT))
    parser.add_argument("--source", choices=("oracle", "php"), default="oracle")
    parser.add_argument("--oracle-bin", default="stellar-xdr")
    args = parser.parse_args()

    if (args.source == "php"
            and Path(args.output).resolve() == COMMITTED_OUTPUT.resolve()):
        raise SystemExit(
            "--source php would overwrite the committed oracle baseline with "
            "the SDK's own output, silently removing the reference anchor. "
            "Pass an explicit --output scratch path (refresh_corpus.sh does "
            "this) or regenerate with --source oracle."
        )

    seed_stdout = run_seed()
    oracle_info: dict[str, str] | None = None
    if args.source == "oracle":
        oracle_info = oracle_version_info(args.oracle_bin)
        fixtures = populate_via_oracle(seed_stdout, args.oracle_bin)
    else:
        fixtures = populate_via_php(seed_stdout)

    corpus = build_corpus(fixtures, args.source, oracle_info)

    out_path = Path(args.output)
    out_path.parent.mkdir(parents=True, exist_ok=True)
    out_path.write_text(json.dumps(corpus, indent=2) + "\n", encoding="utf-8")
    print(f"wrote {len(corpus['entries'])} entries to {out_path} "
          f"(source: {args.source})")
    return 0


if __name__ == "__main__":
    sys.exit(main())
