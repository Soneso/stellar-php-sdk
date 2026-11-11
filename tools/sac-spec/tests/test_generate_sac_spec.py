"""Offline tests of generate_sac_spec.py, run through --source, --pin and --root."""

import base64
import contextlib
import hashlib
import http.client
import io
import json
import os
import sys
import tempfile
import unittest

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

import generate_sac_spec  # noqa: E402
from unittest import mock  # noqa: E402

SOURCE_BYTES = bytes(range(256)) * 3 + b"\x00\x00\x00\x01spec"
TEMPLATE = (
    "repository={{REPOSITORY}}\n"
    "file={{FILE}}\n"
    "commit={{COMMIT}}\n"
    "sha256={{SHA256}}\n"
    "size={{SIZE}}\n"
    "base64={{BASE64}}\n"
)
COMMIT = "0123456789abcdef0123456789abcdef01234567"


class GenerateSacSpecTest(unittest.TestCase):

    def setUp(self):
        self._dir = tempfile.TemporaryDirectory()
        self.root = self._dir.name
        os.makedirs(os.path.join(self.root, "tools", "spec"))
        self.source = os.path.join(self.root, "spec.xdr")
        with open(self.source, "wb") as handle:
            handle.write(SOURCE_BYTES)
        self.template = os.path.join(self.root, "tools", "spec", "out.template")
        self.write_template(TEMPLATE)
        self.output = os.path.join(self.root, "out", "Spec.out")
        os.makedirs(os.path.dirname(self.output))
        self.pin = os.path.join(self.root, "pin.json")
        self.write_pin(hashlib.sha256(SOURCE_BYTES).hexdigest())

    def tearDown(self):
        self._dir.cleanup()

    def write_template(self, text):
        with open(self.template, "w", encoding="utf-8", newline="") as handle:
            handle.write(text)

    def write_pin(self, sha256):
        pin = {
            "repository": "example/spec-repo",
            "commit": COMMIT,
            "file": "spec.xdr",
            "sha256": sha256,
            "template": "tools/spec/out.template",
            "output": "out/Spec.out",
        }
        with open(self.pin, "w", encoding="utf-8") as handle:
            json.dump(pin, handle)

    def run_main(self):
        stdout = io.StringIO()
        stderr = io.StringIO()
        with contextlib.redirect_stdout(stdout), contextlib.redirect_stderr(stderr):
            code = generate_sac_spec.main(
                ["--pin", self.pin, "--root", self.root, "--source", self.source])
        return code, stdout.getvalue(), stderr.getvalue()

    def read_output(self):
        with open(self.output, "rb") as handle:
            return handle.read()

    def test_matching_digest_fills_every_placeholder(self):
        code, _, stderr = self.run_main()
        self.assertEqual(0, code, stderr)
        text = self.read_output().decode("utf-8")
        digest = hashlib.sha256(SOURCE_BYTES).hexdigest()
        expected = (
            "repository=example/spec-repo\n"
            "file=spec.xdr\n"
            "commit=" + COMMIT + "\n"
            "sha256=" + digest + "\n"
            "size={:,}\n".format(len(SOURCE_BYTES))
            + "base64=" + base64.b64encode(SOURCE_BYTES).decode("ascii") + "\n"
        )
        self.assertEqual(expected, text)
        self.assertNotIn("{{", text)
        self.assertEqual(SOURCE_BYTES, base64.b64decode(text.split("base64=")[1].strip(), validate=True))

    def test_mismatched_digest_exits_1_names_both_digests_and_writes_nothing(self):
        wrong = "f" * 64
        self.write_pin(wrong)
        code, _, stderr = self.run_main()
        self.assertEqual(1, code)
        self.assertIn(wrong, stderr)
        self.assertIn(hashlib.sha256(SOURCE_BYTES).hexdigest(), stderr)
        self.assertFalse(os.path.exists(self.output))
        self.assertEqual([], os.listdir(os.path.dirname(self.output)))

    def test_leftover_placeholder_exits_1_and_writes_nothing(self):
        self.write_template(TEMPLATE + "unknown={{UNKNOWN}}\n")
        code, _, stderr = self.run_main()
        self.assertEqual(1, code)
        self.assertIn("placeholder", stderr)
        self.assertFalse(os.path.exists(self.output))
        self.assertEqual([], os.listdir(os.path.dirname(self.output)))

    def test_two_runs_give_identical_bytes(self):
        self.assertEqual(0, self.run_main()[0])
        first = self.read_output()
        self.assertEqual(0, self.run_main()[0])
        self.assertEqual(first, self.read_output())

    def test_output_has_lf_endings(self):
        self.write_template(TEMPLATE.replace("\n", "\r\n"))
        self.assertEqual(0, self.run_main()[0])
        self.assertNotIn(b"\r", self.read_output())

    def test_malformed_commit_exits_1(self):
        with open(self.pin, "r", encoding="utf-8") as handle:
            pin = json.load(handle)
        pin["commit"] = COMMIT.upper()
        with open(self.pin, "w", encoding="utf-8") as handle:
            json.dump(pin, handle)
        code, _, stderr = self.run_main()
        self.assertEqual(1, code)
        self.assertIn("commit", stderr)
        self.assertFalse(os.path.exists(self.output))

    def test_malformed_sha256_exits_1(self):
        self.write_pin("abc")
        code, _, stderr = self.run_main()
        self.assertEqual(1, code)
        self.assertIn("sha256", stderr)
        self.assertFalse(os.path.exists(self.output))

    def test_non_utf8_template_exits_1_names_the_path_and_writes_nothing(self):
        with open(self.template, "wb") as handle:
            handle.write(b"base64={{BASE64}}\n\xff\xfe\n")
        code, _, stderr = self.run_main()
        self.assertEqual(1, code)
        self.assertIn(self.template, stderr)
        self.assertFalse(os.path.exists(self.output))
        self.assertEqual([], os.listdir(os.path.dirname(self.output)))

    def test_non_utf8_pin_exits_1_and_names_the_path(self):
        with open(self.pin, "wb") as handle:
            handle.write(b"\xff\xfe{}")
        code, _, stderr = self.run_main()
        self.assertEqual(1, code)
        self.assertIn(self.pin, stderr)
        self.assertFalse(os.path.exists(self.output))

    def test_incomplete_download_exits_1_names_the_url_and_writes_nothing(self):
        def truncated(url, timeout):
            raise http.client.IncompleteRead(b"partial", 100)

        stdout = io.StringIO()
        stderr = io.StringIO()
        with mock.patch.object(generate_sac_spec.urllib.request, "urlopen", truncated), \
                contextlib.redirect_stdout(stdout), contextlib.redirect_stderr(stderr):
            code = generate_sac_spec.main(["--pin", self.pin, "--root", self.root])
        self.assertEqual(1, code)
        url = "https://raw.githubusercontent.com/example/spec-repo/" + COMMIT + "/spec.xdr"
        self.assertIn(url, stderr.getvalue())
        self.assertIn("IncompleteRead", stderr.getvalue())
        self.assertFalse(os.path.exists(self.output))
        self.assertEqual([], os.listdir(os.path.dirname(self.output)))

    def test_missing_source_exits_1_and_names_the_path(self):
        os.remove(self.source)
        code, _, stderr = self.run_main()
        self.assertEqual(1, code)
        self.assertIn(self.source, stderr)
        self.assertFalse(os.path.exists(self.output))


if __name__ == "__main__":
    unittest.main()
