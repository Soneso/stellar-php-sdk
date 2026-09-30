"""Upstream preamble parsing, header lines and failure behavior of sep/generate_sep_matrix.py."""

import contextlib
import io
import sys
import tempfile
import unittest
import urllib.error
from pathlib import Path
from unittest import mock

MATRIX_GENERATOR_DIR = Path(__file__).resolve().parents[1]
REPO_ROOT = MATRIX_GENERATOR_DIR.parents[1]
sys.path.insert(0, str(MATRIX_GENERATOR_DIR / "sep"))

import generate_sep_matrix as sep  # noqa: E402
from support import FakeUrlopen  # noqa: E402


def sep_document(*preamble_lines, body=("## Simple Summary", "", "Summary text.")):
    """A SEP document in the upstream layout: title, fenced preamble block, body."""
    return "\n".join(["## Preamble", "", "```", *preamble_lines, "```", "", *body, ""])


def raw_url(number):
    return f"https://raw.githubusercontent.com/stellar/stellar-protocol/master/ecosystem/sep-{number:04d}.md"


SEP_0001 = sep_document("SEP: 0001", "Title: Stellar Info File", "Status: Active", "Version: 2.7.0")
SEP_0005 = sep_document("SEP: 0005", "Title: Key Derivation Methods for Stellar Keys", "Status: Final")
SEP_0010 = sep_document("SEP: 0010", "Title: Stellar Web Authentication", "Status: Active", "Version: 3.4.1")


class ParsePreambleTest(unittest.TestCase):
    def parse(self, document):
        return sep.parse_sep_preamble(document, "sep-test.md")

    def test_version_with_colon(self):
        self.assertEqual(self.parse(SEP_0001), sep.SEPPreamble(status="Active", version="2.7.0"))

    def test_version_without_colon(self):
        document = sep_document("SEP: 0024", "Status: Active", "Version 3.8.0")
        self.assertEqual(self.parse(document), sep.SEPPreamble(status="Active", version="3.8.0"))

    def test_no_version_line(self):
        self.assertEqual(self.parse(SEP_0005), sep.SEPPreamble(status="Final", version=None))

    def test_status_keeps_parenthetical(self):
        status = "Active (Interactive components are deprecated in favor of SEP-24)"
        document = sep_document("SEP: 0006", f"Status: {status}", "Version 4.3.0")
        self.assertEqual(self.parse(document).status, status)

    def test_lines_after_the_preamble_block_are_not_read(self):
        document = sep_document("SEP: 0005", "Status: Final", body=("## Changelog", "", "Version 9.9.9"))
        self.assertEqual(self.parse(document), sep.SEPPreamble(status="Final", version=None))

    def test_missing_preamble_heading_reads_the_first_40_lines(self):
        document = "\n".join(["# SEP-0099", "", "Status: Draft", "Version: 0.1.0", "", "Text."])
        self.assertEqual(self.parse(document), sep.SEPPreamble(status="Draft", version="0.1.0"))

    def test_fallback_stops_after_40_lines(self):
        document = "\n".join(["# SEP-0099"] + ["Text."] * 39 + ["Status: Draft"])
        with self.assertRaisesRegex(ValueError, "no Status line"):
            self.parse(document)

    def test_missing_status_raises(self):
        with self.assertRaisesRegex(ValueError, "sep-test.md: no Status line"):
            self.parse(sep_document("SEP: 0001", "Version: 2.7.0"))

    def test_unrecognized_version_line_raises(self):
        with self.assertRaisesRegex(ValueError, "unrecognized Version line 'Version: 1.0.0 draft'"):
            self.parse(sep_document("Status: Draft", "Version: 1.0.0 draft"))


class FetchTest(unittest.TestCase):
    def test_request_error_names_the_url(self):
        routes = {raw_url(2): urllib.error.URLError("connection reset")}
        with mock.patch("urllib.request.urlopen", FakeUrlopen(routes)):
            with self.assertRaisesRegex(RuntimeError, r"GET .*/sep-0002\.md failed: .*connection reset"):
                sep.fetch_sep_preamble(2)


class HeaderLinesTest(unittest.TestCase):
    def render_header(self, version):
        matrix = sep.CompatibilityMatrix(
            sep_info=sep.SEPInfo(
                number=1,
                title="Stellar Info File",
                url="https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0001.md",
                status="Active",
                version=version,
            ),
            overall_status=sep.SupportStatus.SUPPORTED,
            sdk_version="1.15.0",
            generated_at="2026-09-29 10:00 UTC",
        )
        return sep.MatrixRenderer().render_matrix(matrix).split("\n")[:8]

    def test_sep_version_and_status_follow_the_support_status(self):
        self.assertEqual(self.render_header("2.7.0"), [
            "# SEP-01: Stellar Info File",
            "",
            "**Status:** ✅ Supported  ",
            "**SEP Version:** 2.7.0  ",
            "**SEP Status:** Active  ",
            "**SDK Version:** 1.15.0  ",
            "**Generated:** 2026-09-29 10:00 UTC  ",
            "**Spec:** [https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0001.md]"
            "(https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0001.md)",
        ])

    def test_missing_version_prints_na(self):
        self.assertEqual(self.render_header(None)[3], "**SEP Version:** N/A  ")


class GeneratorRunTest(unittest.TestCase):
    def setUp(self):
        tmp = tempfile.TemporaryDirectory()
        self.addCleanup(tmp.cleanup)
        self.output = Path(tmp.name)

    def run_main(self, routes, *sep_numbers, sdk_root=REPO_ROOT):
        fake = FakeUrlopen(routes)
        argv = ["generate_sep_matrix.py", "--sdk-root", str(sdk_root), "--output", str(self.output),
                "--sep", *(str(number) for number in sep_numbers)]
        with mock.patch.object(sys, "argv", argv), \
                mock.patch("urllib.request.urlopen", fake), \
                contextlib.redirect_stderr(io.StringIO()):
            try:
                sep.main()
            except SystemExit as exit_:
                return exit_.code, fake
        return 0, fake

    def test_writes_preamble_values_fetched_by_sep_number(self):
        exit_code, fake = self.run_main({raw_url(5): SEP_0005}, 5)

        self.assertEqual(exit_code, 0)
        self.assertEqual(fake.urls, [raw_url(5)])
        lines = (self.output / "SEP-0005_COMPATIBILITY_MATRIX.md").read_text(encoding="utf-8").split("\n")
        self.assertEqual(lines[3:5], ["**SEP Version:** N/A  ", "**SEP Status:** Final  "])

    def test_one_failing_fetch_writes_no_file_and_exits_nonzero(self):
        routes = {
            raw_url(1): SEP_0001,
            raw_url(2): urllib.error.URLError("connection reset"),
            raw_url(10): SEP_0010,
        }
        with mock.patch.object(sep.SEPMatrixGenerator, "write_outputs") as write_outputs, \
                mock.patch.object(sep.SEP01Analyzer, "analyze", autospec=True) as analyze_sep_01:
            exit_code, _ = self.run_main(routes, 1, 2, 10)

        self.assertNotEqual(exit_code, 0)
        write_outputs.assert_not_called()
        analyze_sep_01.assert_not_called()
        self.assertEqual(list(self.output.iterdir()), [])

    def test_malformed_preamble_writes_no_file(self):
        routes = {raw_url(1): SEP_0001, raw_url(10): sep_document("SEP: 0010", "Version: 3.4.1")}

        exit_code, _ = self.run_main(routes, 1, 10)

        self.assertNotEqual(exit_code, 0)
        self.assertEqual(list(self.output.iterdir()), [])

    def test_failing_analyzer_writes_no_file(self):
        routes = {raw_url(1): SEP_0001, raw_url(5): SEP_0005}
        with mock.patch.object(sep.SEP05Analyzer, "analyze", side_effect=RuntimeError("analyzer defect")):
            exit_code, _ = self.run_main(routes, 1, 5)

        self.assertNotEqual(exit_code, 0)
        self.assertEqual(list(self.output.iterdir()), [])

    def test_unreadable_sdk_version_writes_no_file(self):
        with tempfile.TemporaryDirectory() as sdk_root:
            exit_code, _ = self.run_main({raw_url(1): SEP_0001}, 1, sdk_root=sdk_root)

        self.assertNotEqual(exit_code, 0)
        self.assertEqual(list(self.output.iterdir()), [])


class SdkVersionTest(unittest.TestCase):
    def test_missing_version_file_raises(self):
        with tempfile.TemporaryDirectory() as tmp:
            with self.assertRaisesRegex(RuntimeError, "Cannot read the SDK version file"):
                sep.get_sdk_version(Path(tmp))

    def test_version_file_without_constant_raises(self):
        with tempfile.TemporaryDirectory() as tmp:
            sdk_file = Path(tmp) / "Soneso" / "StellarSDK" / "StellarSDK.php"
            sdk_file.parent.mkdir(parents=True)
            sdk_file.write_text("<?php\nclass StellarSDK {}\n", encoding="utf-8")
            with self.assertRaisesRegex(RuntimeError, "No VERSION_NR constant"):
                sep.get_sdk_version(Path(tmp))


if __name__ == "__main__":
    unittest.main()
