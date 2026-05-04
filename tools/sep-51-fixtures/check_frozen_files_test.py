#!/usr/bin/env python3
"""Fixture-driven test for tools/sep-51-fixtures/check_frozen_files.py.

Each test case constructs a temporary repo + state file in a TemporaryDirectory,
invokes the script as a subprocess, and asserts the resulting exit code +
stderr fragment.

Run: python3 tools/sep-51-fixtures/check_frozen_files_test.py
"""

from __future__ import annotations

import hashlib
import json
import os
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path
from typing import Any


SCRIPT = Path(__file__).resolve().parent / "check_frozen_files.py"


def sha256_bytes(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()


def init_git_repo(root: Path) -> None:
    """Initialise a minimal git repo so check_frozen_files.py can resolve repo root."""
    env = os.environ.copy()
    env["GIT_TERMINAL_PROMPT"] = "0"
    subprocess.run(["git", "init", "-q", str(root)], check=True, env=env)
    subprocess.run(
        ["git", "-C", str(root), "config", "user.email", "ci@example.com"],
        check=True,
        env=env,
    )
    subprocess.run(
        ["git", "-C", str(root), "config", "user.name", "ci"],
        check=True,
        env=env,
    )


def write_state(state_path: Path, state: dict[str, Any]) -> None:
    state_path.write_text(json.dumps(state, indent=2), encoding="utf-8")


def run_script(state_path: Path) -> tuple[int, str]:
    result = subprocess.run(
        [sys.executable, str(SCRIPT), str(state_path)],
        capture_output=True,
        text=True,
        check=False,
    )
    return result.returncode, result.stderr


class TestRunner:
    def __init__(self) -> None:
        self.passed = 0
        self.failed = 0

    def case(self, name: str, fn) -> None:
        try:
            fn()
            print(f"  PASS  {name}")
            self.passed += 1
        except AssertionError as exc:
            print(f"  FAIL  {name}: {exc}")
            self.failed += 1
        except Exception as exc:  # noqa: BLE001
            print(f"  ERROR {name}: {type(exc).__name__}: {exc}")
            self.failed += 1


def case_empty_state(tmp: Path) -> None:
    init_git_repo(tmp)
    state = tmp / "state.json"
    write_state(state, {"schema_version": 1, "phase_states": {}})
    code, _ = run_script(state)
    assert code == 0, f"expected 0, got {code}"


def case_single_accept_match(tmp: Path) -> None:
    init_git_repo(tmp)
    payload = b"hello world\n"
    (tmp / "f.txt").write_bytes(payload)
    state = tmp / "state.json"
    write_state(
        state,
        {
            "schema_version": 1,
            "phase_states": {
                "0": {
                    "status": "accepted",
                    "frozen_file_shas": {"f.txt": sha256_bytes(payload)},
                }
            },
        },
    )
    code, _ = run_script(state)
    assert code == 0, f"expected 0, got {code}"


def case_single_accept_mismatch(tmp: Path) -> None:
    init_git_repo(tmp)
    (tmp / "f.txt").write_bytes(b"new content\n")
    state = tmp / "state.json"
    write_state(
        state,
        {
            "schema_version": 1,
            "phase_states": {
                "0": {
                    "status": "accepted",
                    "frozen_file_shas": {
                        "f.txt": sha256_bytes(b"old content\n")
                    },
                }
            },
        },
    )
    code, err = run_script(state)
    assert code == 2, f"expected 2, got {code}"
    assert "FROZEN_DIFF" in err and "f.txt" in err


def case_deleted_file(tmp: Path) -> None:
    init_git_repo(tmp)
    state = tmp / "state.json"
    write_state(
        state,
        {
            "schema_version": 1,
            "phase_states": {
                "0": {
                    "status": "accepted",
                    "frozen_file_shas": {"missing.txt": sha256_bytes(b"x")},
                }
            },
        },
    )
    code, err = run_script(state)
    assert code == 2, f"expected 2, got {code}"
    assert "deleted" in err and "missing.txt" in err


def case_max_phase_wins(tmp: Path) -> None:
    init_git_repo(tmp)
    payload = b"hello\n"
    (tmp / "f.txt").write_bytes(payload)
    state = tmp / "state.json"
    # Phase 0 records an obsolete sha; Phase 5a records the right one. Max wins.
    write_state(
        state,
        {
            "schema_version": 1,
            "phase_states": {
                "0": {
                    "status": "accepted",
                    "frozen_file_shas": {"f.txt": "0" * 64},
                },
                "5a": {
                    "status": "accepted",
                    "frozen_file_shas": {"f.txt": sha256_bytes(payload)},
                },
            },
        },
    )
    code, _ = run_script(state)
    assert code == 0, f"expected 0, got {code}"


def case_status_in_progress_ignored(tmp: Path) -> None:
    init_git_repo(tmp)
    state = tmp / "state.json"
    write_state(
        state,
        {
            "schema_version": 1,
            "phase_states": {
                "0": {
                    "status": "in_progress",
                    "frozen_file_shas": {"missing.txt": "0" * 64},
                }
            },
        },
    )
    code, _ = run_script(state)
    assert code == 0, f"expected 0 (non-enforcing), got {code}"


def case_phase_reopened_enforced(tmp: Path) -> None:
    init_git_repo(tmp)
    state = tmp / "state.json"
    # phase_reopened with frozen_file_shas should still be enforced.
    write_state(
        state,
        {
            "schema_version": 1,
            "phase_states": {
                "0": {
                    "status": "phase_reopened",
                    "frozen_file_shas": {"missing.txt": "0" * 64},
                }
            },
        },
    )
    code, err = run_script(state)
    assert code == 2, f"expected 2, got {code}"
    assert "deleted" in err


def case_pre_reopen_shape_only(tmp: Path) -> None:
    init_git_repo(tmp)
    state = tmp / "state.json"
    # Pre-reopen shas are validated for shape but NOT enforced.
    write_state(
        state,
        {
            "schema_version": 1,
            "phase_states": {
                "0": {
                    "status": "phase_reopened",
                    "frozen_file_shas": {},
                    "frozen_file_shas_pre_reopen": {"phantom.txt": "0" * 64},
                }
            },
        },
    )
    code, _ = run_script(state)
    assert code == 0, f"expected 0 (pre-reopen forensic only), got {code}"


def case_missing_state_file(tmp: Path) -> None:
    state = tmp / "absent.json"
    code, err = run_script(state)
    assert code == 3, f"expected 3, got {code}"
    assert "STATE_FILE_MISSING" in err


def case_parse_failure(tmp: Path) -> None:
    init_git_repo(tmp)
    state = tmp / "state.json"
    state.write_text("{not json", encoding="utf-8")
    code, err = run_script(state)
    assert code == 4, f"expected 4, got {code}"
    assert "STATE_FILE_MALFORMED" in err


def case_schema_version_wrong(tmp: Path) -> None:
    init_git_repo(tmp)
    state = tmp / "state.json"
    write_state(state, {"schema_version": 2, "phase_states": {}})
    code, err = run_script(state)
    assert code == 4, f"expected 4, got {code}"
    assert "schema_version" in err


def case_phase_states_not_dict(tmp: Path) -> None:
    init_git_repo(tmp)
    state = tmp / "state.json"
    write_state(state, {"schema_version": 1, "phase_states": []})
    code, err = run_script(state)
    assert code == 4, f"expected 4, got {code}"
    assert "phase_states" in err


def case_phase_missing_status(tmp: Path) -> None:
    init_git_repo(tmp)
    state = tmp / "state.json"
    write_state(state, {"schema_version": 1, "phase_states": {"0": {}}})
    code, err = run_script(state)
    assert code == 4, f"expected 4, got {code}"
    assert "status" in err


def case_frozen_shas_not_dict(tmp: Path) -> None:
    init_git_repo(tmp)
    state = tmp / "state.json"
    write_state(
        state,
        {
            "schema_version": 1,
            "phase_states": {
                "0": {"status": "accepted", "frozen_file_shas": [1, 2, 3]}
            },
        },
    )
    code, err = run_script(state)
    assert code == 4, f"expected 4, got {code}"
    assert "frozen_file_shas" in err


def case_malformed_sha_value(tmp: Path) -> None:
    init_git_repo(tmp)
    state = tmp / "state.json"
    write_state(
        state,
        {
            "schema_version": 1,
            "phase_states": {
                "0": {
                    "status": "accepted",
                    "frozen_file_shas": {"f.txt": "NOT_A_SHA"},
                }
            },
        },
    )
    code, err = run_script(state)
    assert code == 4, f"expected 4, got {code}"
    assert "lowercase hex" in err or "sha" in err


def case_uppercase_sha_rejected(tmp: Path) -> None:
    init_git_repo(tmp)
    state = tmp / "state.json"
    upper_sha = ("a" * 63) + "F"
    write_state(
        state,
        {
            "schema_version": 1,
            "phase_states": {
                "0": {"status": "accepted", "frozen_file_shas": {"f.txt": upper_sha}}
            },
        },
    )
    code, err = run_script(state)
    assert code == 4, f"expected 4, got {code}"
    assert "lowercase hex" in err or "sha" in err


def case_reopen_history_too_long(tmp: Path) -> None:
    init_git_repo(tmp)
    state = tmp / "state.json"
    write_state(
        state,
        {
            "schema_version": 1,
            "phase_states": {
                "0": {
                    "status": "accepted",
                    "frozen_file_shas": {},
                    "reopen_history": [
                        {"fingerprint": "a", "at": "2026-05-04T00:00:00Z"},
                        {"fingerprint": "b", "at": "2026-05-04T00:00:00Z"},
                        {"fingerprint": "c", "at": "2026-05-04T00:00:00Z"},
                        {"fingerprint": "d", "at": "2026-05-04T00:00:00Z"},
                    ],
                }
            },
        },
    )
    code, err = run_script(state)
    assert code == 4, f"expected 4, got {code}"
    assert "cap of 3" in err


def case_reopen_history_missing_keys(tmp: Path) -> None:
    init_git_repo(tmp)
    state = tmp / "state.json"
    write_state(
        state,
        {
            "schema_version": 1,
            "phase_states": {
                "0": {
                    "status": "accepted",
                    "frozen_file_shas": {},
                    "reopen_history": [{"fingerprint": "a"}],
                }
            },
        },
    )
    code, err = run_script(state)
    assert code == 4, f"expected 4, got {code}"
    assert "fingerprint" in err or "at" in err


def main() -> int:
    runner = TestRunner()

    cases = [
        ("empty state", case_empty_state),
        ("single accept matches", case_single_accept_match),
        ("single accept mismatches", case_single_accept_mismatch),
        ("deleted file", case_deleted_file),
        ("max phase wins on overlap", case_max_phase_wins),
        ("in_progress status ignored", case_status_in_progress_ignored),
        ("phase_reopened enforces", case_phase_reopened_enforced),
        ("pre_reopen shape-only (no enforcement)", case_pre_reopen_shape_only),
        ("missing state file", case_missing_state_file),
        ("malformed json parse failure", case_parse_failure),
        ("schema_version wrong", case_schema_version_wrong),
        ("phase_states not dict", case_phase_states_not_dict),
        ("phase missing status", case_phase_missing_status),
        ("frozen_file_shas not dict", case_frozen_shas_not_dict),
        ("malformed sha value", case_malformed_sha_value),
        ("uppercase sha rejected", case_uppercase_sha_rejected),
        ("reopen_history > 3", case_reopen_history_too_long),
        ("reopen_history missing keys", case_reopen_history_missing_keys),
    ]

    for name, fn in cases:
        with tempfile.TemporaryDirectory() as raw:
            tmp = Path(raw)
            runner.case(name, lambda fn=fn, tmp=tmp: fn(tmp))

    print(f"\n{runner.passed} passed, {runner.failed} failed")
    return 0 if runner.failed == 0 else 1


if __name__ == "__main__":
    sys.exit(main())
