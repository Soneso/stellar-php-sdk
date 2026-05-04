#!/usr/bin/env python3
"""Validate frozen-file SHAs recorded in the SEP-51 orchestration state file.

Contract: read-only validator. Reads a state JSON document, computes the
authoritative <path, sha256> map from accepted/reopened phase entries, and
asserts every authoritative path's current on-disk SHA matches.

Exit codes:
  0  every authoritative <path, sha> matches its current content SHA on disk;
     no state file (well-formed) flagged any phase as freeze-enforcing yet.
  2  one or more frozen paths drifted (mismatch or deleted-on-disk).
  3  STATE_FILE_MISSING.
  4  STATE_FILE_MALFORMED with reason.
"""

from __future__ import annotations

import hashlib
import json
import re
import subprocess
import sys
from pathlib import Path
from typing import Any


SHA256_RE = re.compile(r"^[0-9a-f]{64}$")
ENFORCING_STATUSES = {"accepted", "phase_reopened"}
KNOWN_STATUSES = {
    "accepted",
    "phase_reopened",
    "in_progress",
    "paused_for_user",
    "blocked_on_dependency",
    "pending",
    "mechanical_check",
}
FIX_OR_REVIEW_RE = re.compile(r"^(?:fix|review)_round_\d+$")


def fail_malformed(reason: str) -> None:
    print(f"STATE_FILE_MALFORMED: {reason}", file=sys.stderr)
    sys.exit(4)


def fail_missing() -> None:
    print("STATE_FILE_MISSING", file=sys.stderr)
    sys.exit(3)


def repo_root_from(state_path: Path) -> Path:
    """Resolve the repository root from the directory containing the state file."""
    try:
        result = subprocess.run(
            ["git", "rev-parse", "--show-toplevel"],
            cwd=state_path.parent,
            capture_output=True,
            check=True,
            text=True,
        )
    except (FileNotFoundError, subprocess.CalledProcessError) as exc:
        fail_malformed(f"git rev-parse --show-toplevel failed: {exc}")
    return Path(result.stdout.strip())


def validate_status(phase_num: str, status: Any) -> str:
    if not isinstance(status, str):
        fail_malformed(f"phase_states[{phase_num}].status not a string")
    if status in KNOWN_STATUSES or FIX_OR_REVIEW_RE.match(status):
        return status
    fail_malformed(f"phase_states[{phase_num}].status unknown value '{status}'")
    return ""  # unreachable; fail_malformed exits


def validate_sha_map(phase_num: str, field: str, value: Any) -> dict[str, str]:
    if not isinstance(value, dict):
        fail_malformed(f"phase_states[{phase_num}].{field} not a dict")
    out: dict[str, str] = {}
    for path, sha in value.items():
        if not isinstance(path, str) or not isinstance(sha, str):
            fail_malformed(
                f"phase_states[{phase_num}].{field} contains non-string path/sha"
            )
        if not SHA256_RE.match(sha):
            fail_malformed(
                f"phase_states[{phase_num}].{field}[{path}] sha is not 64-char lowercase hex"
            )
        out[path] = sha
    return out


def validate_reopen_history(phase_num: str, value: Any) -> None:
    if not isinstance(value, list):
        fail_malformed(f"phase_states[{phase_num}].reopen_history not a list")
    if len(value) > 3:
        fail_malformed(
            f"phase_states[{phase_num}].reopen_history exceeds cap of 3"
        )
    for idx, entry in enumerate(value):
        if not isinstance(entry, dict):
            fail_malformed(
                f"phase_states[{phase_num}].reopen_history[{idx}] not an object"
            )
        if "fingerprint" not in entry or "at" not in entry:
            fail_malformed(
                f"phase_states[{phase_num}].reopen_history[{idx}] missing fingerprint or at"
            )
        if not isinstance(entry["fingerprint"], str) or not isinstance(entry["at"], str):
            fail_malformed(
                f"phase_states[{phase_num}].reopen_history[{idx}] fingerprint/at must be strings"
            )


def parse_phase_num(raw: str) -> tuple[int, int]:
    """Return a sort key for a phase identifier such as "0", "5a", "5b", "10"."""
    match = re.match(r"^(\d+)([a-z]?)$", raw)
    if not match:
        fail_malformed(f"phase_states key '{raw}' is not a valid phase number")
    primary = int(match.group(1))
    suffix = match.group(2)
    secondary = ord(suffix) if suffix else 0
    return primary, secondary


def sha256_of(path: Path) -> str:
    h = hashlib.sha256()
    with path.open("rb") as fh:
        for chunk in iter(lambda: fh.read(65536), b""):
            h.update(chunk)
    return h.hexdigest()


def main() -> int:
    if len(sys.argv) != 2:
        print("usage: check_frozen_files.py <state.json>", file=sys.stderr)
        return 4
    state_path = Path(sys.argv[1])
    if not state_path.exists():
        fail_missing()

    try:
        state = json.loads(state_path.read_text(encoding="utf-8"))
    except json.JSONDecodeError as exc:
        fail_malformed(f"json parse failure: {exc}")

    if not isinstance(state, dict):
        fail_malformed("top-level value is not an object")
    if state.get("schema_version") != 1:
        fail_malformed("schema_version != 1")
    phase_states = state.get("phase_states")
    if not isinstance(phase_states, dict):
        fail_malformed("phase_states not a dict")

    # Build authoritative path -> (sha, source phase) map; max(phase_num) wins on overlap.
    authoritative: dict[str, tuple[str, tuple[int, int], str]] = {}
    for phase_num, entry in phase_states.items():
        if not isinstance(entry, dict):
            fail_malformed(f"phase_states[{phase_num}] not an object")
        status = validate_status(phase_num, entry.get("status"))
        sort_key = parse_phase_num(phase_num)

        # Validate optional shapes regardless of status.
        if "frozen_file_shas" in entry:
            shas = validate_sha_map(phase_num, "frozen_file_shas", entry["frozen_file_shas"])
        else:
            shas = {}
        if "frozen_file_shas_pre_reopen" in entry:
            validate_sha_map(
                phase_num,
                "frozen_file_shas_pre_reopen",
                entry["frozen_file_shas_pre_reopen"],
            )
        if "reopen_history" in entry:
            validate_reopen_history(phase_num, entry["reopen_history"])

        if status not in ENFORCING_STATUSES:
            continue

        for path, sha in shas.items():
            current = authoritative.get(path)
            if current is None or sort_key > current[1]:
                authoritative[path] = (sha, sort_key, phase_num)

    if not authoritative:
        return 0

    repo_root = repo_root_from(state_path)
    failures: list[str] = []
    for path, (expected_sha, _, _phase) in sorted(authoritative.items()):
        full = (repo_root / path).resolve()
        if not full.exists() or not full.is_file():
            failures.append(f"FROZEN_DIFF: {path}: deleted")
            continue
        actual = sha256_of(full)
        if actual != expected_sha:
            failures.append(f"FROZEN_DIFF: {path}: expected {expected_sha} got {actual}")

    if failures:
        for line in failures:
            print(line, file=sys.stderr)
        return 2
    return 0


if __name__ == "__main__":
    sys.exit(main())
