"""Release citation and failure behavior of rpc/generate_rpc_matrix.py."""

import contextlib
import io
import json
import re
import sys
import tempfile
import unittest
import urllib.error
from pathlib import Path
from unittest import mock

MATRIX_GENERATOR_DIR = Path(__file__).resolve().parents[1]
REPO_ROOT = MATRIX_GENERATOR_DIR.parents[1]
sys.path.insert(0, str(MATRIX_GENERATOR_DIR / "rpc"))

import generate_rpc_matrix  # noqa: E402
from rpc_releases import STELLAR_RPC_REPO  # noqa: E402
from support import FakeUrlopen, release, release_routes, releases_url  # noqa: E402

SDK_VERSION = re.search(
    r"VERSION_NR\s*=\s*['\"]([^'\"]+)['\"]",
    (REPO_ROOT / "Soneso" / "StellarSDK" / "StellarSDK.php").read_text(encoding="utf-8"),
).group(1)

# v29.0.0 is newer than the recorded v28.0.1: the header must cite v28.0.1's version, date and URL.
RELEASE_PAGES = [[
    release("v29.0.0", published_at="2026-10-01T12:00:00Z"),
    release("v28.0.1", published_at="2026-08-27T18:40:46Z"),
    release("v28.0.0", published_at="2026-08-17T10:00:00Z"),
]]


class GeneratorRunTest(unittest.TestCase):
    def setUp(self):
        tmp = tempfile.TemporaryDirectory()
        self.addCleanup(tmp.cleanup)
        self.tmp = Path(tmp.name)
        self.rpc_data = self.tmp / "rpc_methods.json"
        self.output = self.tmp / "RPC_COMPATIBILITY_MATRIX.md"
        environment = mock.patch.dict("os.environ", {"GITHUB_TOKEN": "test-token"})
        environment.start()
        self.addCleanup(environment.stop)

    def write_rpc_data(self, metadata):
        self.rpc_data.write_text(json.dumps({"metadata": metadata, "methods": {}}), encoding="utf-8")

    def run_generator(self, routes, sdk_root=REPO_ROOT):
        argv = [
            "generate_rpc_matrix.py",
            "--rpc-data", str(self.rpc_data),
            "--sdk-root", str(sdk_root),
            "--output", str(self.output),
        ]
        with mock.patch.object(sys, "argv", argv), \
                mock.patch("urllib.request.urlopen", FakeUrlopen(routes)), \
                contextlib.redirect_stdout(io.StringIO()), \
                contextlib.redirect_stderr(io.StringIO()):
            return generate_rpc_matrix.main()

    def test_header_cites_the_recorded_release(self):
        self.write_rpc_data({"version": "v28.0.1"})

        self.assertEqual(self.run_generator(release_routes(STELLAR_RPC_REPO, RELEASE_PAGES)), 0)

        lines = self.output.read_text(encoding="utf-8").split("\n")
        self.assertEqual(lines[2], "**RPC Version:** v28.0.1 (released 2026-08-27)  ")
        self.assertEqual(
            lines[3], "**RPC Source:** [v28.0.1](https://github.com/stellar/stellar-rpc/releases/tag/v28.0.1)  "
        )
        self.assertEqual(lines[4], f"**SDK Version:** {SDK_VERSION}  ")
        self.assertRegex(lines[5], r"^\*\*Generated:\*\* \d{4}-\d{2}-\d{2} \d{2}:\d{2} UTC$")

    def test_recorded_version_absent_from_release_list_writes_nothing(self):
        self.write_rpc_data({"version": "v27.9.9"})

        self.assertEqual(self.run_generator(release_routes(STELLAR_RPC_REPO, RELEASE_PAGES)), 1)
        self.assertFalse(self.output.exists())

    def test_missing_recorded_version_writes_nothing(self):
        self.write_rpc_data({"source": "stellar-rpc"})

        self.assertEqual(self.run_generator(release_routes(STELLAR_RPC_REPO, RELEASE_PAGES)), 1)
        self.assertFalse(self.output.exists())

    def test_release_request_error_writes_nothing(self):
        self.write_rpc_data({"version": "v28.0.1"})
        routes = {releases_url(STELLAR_RPC_REPO): urllib.error.URLError("network unreachable")}

        self.assertEqual(self.run_generator(routes), 1)
        self.assertFalse(self.output.exists())

    def test_unreadable_sdk_version_writes_nothing(self):
        self.write_rpc_data({"version": "v28.0.1"})

        exit_code = self.run_generator(release_routes(STELLAR_RPC_REPO, RELEASE_PAGES), sdk_root=self.tmp)

        self.assertEqual(exit_code, 1)
        self.assertFalse(self.output.exists())


class SdkVersionTest(unittest.TestCase):
    def test_missing_version_file_raises(self):
        with tempfile.TemporaryDirectory() as tmp:
            with self.assertRaisesRegex(RuntimeError, "Cannot read the SDK version file"):
                generate_rpc_matrix.get_sdk_version(Path(tmp))

    def test_version_file_without_constant_raises(self):
        with tempfile.TemporaryDirectory() as tmp:
            sdk_file = Path(tmp) / "Soneso" / "StellarSDK" / "StellarSDK.php"
            sdk_file.parent.mkdir(parents=True)
            sdk_file.write_text("<?php\nclass StellarSDK {}\n", encoding="utf-8")
            with self.assertRaisesRegex(RuntimeError, "No VERSION_NR constant"):
                generate_rpc_matrix.get_sdk_version(Path(tmp))

    def test_reads_version_constant(self):
        self.assertEqual(generate_rpc_matrix.get_sdk_version(REPO_ROOT), SDK_VERSION)


if __name__ == "__main__":
    unittest.main()
