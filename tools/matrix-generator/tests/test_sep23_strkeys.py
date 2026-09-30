"""SEP-23 (Strkeys) in sep/generate_sep_matrix.py: document parsing, SDK source checks and matrix runs."""

import contextlib
import hashlib
import io
import re
import sys
import tempfile
import unittest
from pathlib import Path
from unittest import mock

MATRIX_GENERATOR_DIR = Path(__file__).resolve().parents[1]
REPO_ROOT = MATRIX_GENERATOR_DIR.parents[1]
FIXTURES_DIR = Path(__file__).resolve().parent / "fixtures"
sys.path.insert(0, str(MATRIX_GENERATOR_DIR / "sep"))

import generate_sep_matrix as sep  # noqa: E402
from support import FakeUrlopen  # noqa: E402
from test_sep_matrix import SEP_0001, raw_url  # noqa: E402

# ecosystem/sep-0023.md of stellar-protocol master at 9cd7030, version 1.3.0.
SEP_0023 = (FIXTURES_DIR / "sep-0023.md").read_text(encoding="utf-8")
SEP_0023_BLOB = "8bbca61ecfe50853c2a25887d7bcb4e7fe4a3838"

SEP_0023_KEY_TYPES = [
    ("STRKEY_PUBKEY", "6 << 3", 48, "G"),
    ("STRKEY_MUXED", "12 << 3", 96, "M"),
    ("STRKEY_PRIVKEY", "18 << 3", 144, "S"),
    ("STRKEY_PRE_AUTH_TX", "19 << 3", 152, "T"),
    ("STRKEY_HASH_X", "23 << 3", 184, "X"),
    ("STRKEY_SIGNED_PAYLOAD", "15 << 3", 120, "P"),
    ("STRKEY_CONTRACT", "2 << 3", 16, "C"),
    ("STRKEY_LIQUIDITY_POOL", "11 << 3", 88, "L"),
    ("STRKEY_CLAIMABLE_BALANCE", "1 << 3", 8, "B"),
]

SEP_0023_VECTORS = [
    ("valid_01", "Valid non-multiplexed account",
     "GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ"),
    ("valid_02", "Valid multiplexed account",
     "MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUQ"),
    ("valid_03", "Valid multiplexed account in which unsigned id exceeds maximum signed 64-bit integer",
     "MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAJLK"),
    ("valid_04", "Valid signed payload with an ed25519 public key and a 32-byte payload.",
     "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAQACAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUPB6IBZGM"),
    ("valid_05", "Valid signed payload with an ed25519 public key and a 29-byte payload which becomes zero padded.",
     "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAOQCAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUAAAAFGBU"),
    ("valid_06", "Valid contract",
     "CA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUWDA"),
    ("valid_07", "Valid liquidity pool address",
     "LA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUPJN"),
    ("valid_08", "Valid claimable balance address",
     "BAAD6DBUX6J22DMZOHIEZTEQ64CVCHEDRKWZONFEUL5Q26QD7R76RGR4TU"),
    ("invalid_01", "Invalid length (Ed25519 should be 32 bytes, not 5)",
     "GAAAAAAAACGC6"),
    ("invalid_02", "The unused trailing bit must be zero in the encoding of the last three bytes (24 bits) "
                   "as five base-32 symbols (25 bits)",
     "MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUR"),
    ("invalid_03", "Invalid length (congruent to 1 mod 8)",
     "GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZA"),
    ("invalid_04", "Invalid length (base-32 decoding should yield 35 bytes, not 36)",
     "GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUACUSI"),
    ("invalid_05", "Invalid algorithm (low 3 bits of version byte are 7)",
     "G47QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVP2I"),
    ("invalid_06", "Invalid length (congruent to 6 mod 8)",
     "MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAJLKA"),
    ("invalid_07", "Invalid length (base-32 decoding should yield 43 bytes, not 44)",
     "MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAAV75I"),
    ("invalid_08", "Invalid algorithm (low 3 bits of version byte are 7)",
     "M47QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUQ"),
    ("invalid_09", "Padding bytes are not allowed",
     "MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUK==="),
    ("invalid_10", "Invalid checksum",
     "MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUO"),
    ("invalid_11", "Length prefix specifies length that is shorter than payload in signed payload",
     "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAQACAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUPB6IAAAAAAAAPM"),
    ("invalid_12", "Length prefix specifies length that is longer than payload in signed payload",
     "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAOQCAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4Z2PQ"),
    ("invalid_13", "No zero padding in signed payload",
     "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAOQCAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DXFH6"),
    ("invalid_14", "The unused trailing 2-bits must be zero in the encoding of the last symbol.",
     "BAAD6DBUX6J22DMZOHIEZTEQ64CVCHEDRKWZONFEUL5Q26QD7R76RGR4TV"),
    ("invalid_15", "Invalid claimable balance type (first byte of binary key is not 0)",
     "BAAT6DBUX6J22DMZOHIEZTEQ64CVCHEDRKWZONFEUL5Q26QD7R76RGXACA"),
]

SDK_VERSION_PATH = "Soneso/StellarSDK/StellarSDK.php"
VERSION_BYTE_PATH = "Soneso/StellarSDK/Crypto/VersionByte.php"
STRKEY_PATH = "Soneso/StellarSDK/Crypto/StrKey.php"
STRKEY_TEST_PATH = "Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php"
MATRIX_PATH = "compatibility/sep/SEP-0023_COMPATIBILITY_MATRIX.md"
KEY_TYPE_SECTION = "Key types"
VECTOR_SECTION = "Test vectors quoted in the StrKey unit test files"
SUPPORTED = sep.SupportStatus.SUPPORTED
NOT_SUPPORTED = sep.SupportStatus.NOT_SUPPORTED

# VersionByte constants and StrKey methods of an SDK that implements every SEP-23 key type.
VERSION_BYTES = [
    ("ACCOUNT_ID", "6 << 3"), ("MUXED_ACCOUNT_ID", "12 << 3"), ("SEED", "18 << 3"), ("PRE_AUTH_TX", "19 << 3"),
    ("SHA256_HASH", "23 << 3"), ("SIGNED_PAYLOAD", "15 << 3"), ("CONTRACT_ID", "2 << 3"),
    ("LIQUIDITY_POOL_ID", "11 << 3"), ("CLAIMABLE_BALANCE_ID", "1 << 3"),
]
STRKEY_METHODS = [
    "encodeAccountId", "decodeAccountId", "encodeMuxedAccountId", "decodeMuxedAccountId",
    "encodeSeed", "decodeSeed", "encodePreAuthTx", "decodePreAuthTx", "encodeSha256Hash", "decodeSha256Hash",
    "encodeSignedPayload", "decodeSignedPayload", "encodeContractId", "decodeContractId",
    "encodeLiquidityPoolId", "decodeLiquidityPoolId", "encodeClaimableBalanceId", "decodeClaimableBalanceId",
]


def edit(document, old, new):
    """Replace *old*, which must occur exactly once in *document*, with *new*."""
    if document.count(old) != 1:
        raise AssertionError(f"{old!r} occurs {document.count(old)} times in the document")
    return document.replace(old, new)


def php_class(name, *body_lines):
    return "\n".join([f"class {name}", "{", *(f"    {line}" for line in body_lines), "}", ""])


def php_file(*classes):
    return "\n".join(["<?php", "", *classes])


def version_byte_file(constants):
    """VersionByte with a method before the constants, so its braces are counted around a nested block."""
    return php_file(php_class("VersionByte", "public static function all() : array { return []; }",
                              *(f"const {name} = {expression};" for name, expression in constants)))


def strkey_file(methods):
    return php_file(php_class("StrKey", *(f"public static function {m}(string $data) : string {{}}" for m in methods)))


def php_test_file(*literals):
    """A PHP test class that assigns each quoted literal to a variable."""
    return php_file(php_class("StrKeyTest", "public function testVectors(): void", "{",
                              *(f"    $vector{index} = {literal};" for index, literal in enumerate(literals)), "}"))


class Sep23ParseTest(unittest.TestCase):
    def key_types(self, document):
        return [(k.name, k.base_expression, k.base_value, k.first_char)
                for k in sep.parse_sep23_key_types(document, "sep-0023.md")]

    def vectors(self, document):
        return [(v.name, v.title, v.strkey) for v in sep.parse_sep23_test_vectors(document, "sep-0023.md")]

    def test_fixture_is_the_upstream_document(self):
        data = (FIXTURES_DIR / "sep-0023.md").read_bytes()
        self.assertEqual(hashlib.sha1(b"blob %d\0" % len(data) + data).hexdigest(), SEP_0023_BLOB)

    def test_key_types_come_from_the_version_byte_table(self):
        self.assertEqual(self.key_types(SEP_0023), SEP_0023_KEY_TYPES)

    def test_vectors_come_from_the_valid_and_invalid_cases_in_document_order(self):
        self.assertEqual(self.vectors(SEP_0023), SEP_0023_VECTORS)

    def test_items_follow_an_edited_document(self):
        document = edit(SEP_0023, "| STRKEY_PUBKEY            | 6 << 3     |", "| STRKEY_PUBKEY            | 7 << 3     |")
        document = edit(document, "`CA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUWDA`",
                        "`CA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUWDB`")

        self.assertEqual(self.key_types(document)[0], ("STRKEY_PUBKEY", "7 << 3", 56, "G"))
        self.assertEqual(self.vectors(document)[5][2], "CA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUWDB")

    def test_invalid_list_ends_at_the_paste_paragraph(self):
        document = edit(SEP_0023, "## Implementation", "1. Numbered note below the C array\n\n## Implementation")

        self.assertEqual(self.vectors(document), SEP_0023_VECTORS)

    def test_malformed_document_raises(self):
        table = re.search(r"^\| Key type .*?\n\n", SEP_0023, re.MULTILINE | re.DOTALL).group()
        cases = [
            ("Specification renamed", self.key_types, edit(SEP_0023, "## Specification\n", "## Details\n"),
             "no '## Specification' section"),
            ("table after Specification", self.key_types, edit(SEP_0023, table, "") + "\n## Appendix\n\n" + table,
             "expected one version-byte table .* found 0"),
            ("Key type column renamed", self.key_types,
             edit(SEP_0023, "| Key type                 |", "| Kind                     |"),
             "expected one version-byte table .* found 0"),
            ("First char column renamed", self.key_types, edit(SEP_0023, "| First char |", "| Prefix     |"),
             "expected one version-byte table .* found 0"),
            ("second table", self.key_types, edit(SEP_0023, "| STRKEY_ALG_SHA256 | 0     |\n",
                                                  "| STRKEY_ALG_SHA256 | 0     |\n\n" + table),
             "expected one version-byte table .* found 2"),
            ("table without rows", self.key_types, re.sub(r"^\| STRKEY_\w+ +\|.*\n", "", SEP_0023, flags=re.MULTILINE),
             "the version-byte table has no rows"),
            ("short row", self.key_types, edit(SEP_0023, "| 12 << 3    | M          | yes   | PK   |", "| 12 << 3    | M |"),
             r"version-byte table row \['STRKEY_MUXED', '12 << 3', 'M'\] has 3 cells, the header has 5"),
            ("base value in another form", self.key_types, edit(SEP_0023, "| 6 << 3     |", "| 6 <<< 3    |"),
             "cannot evaluate the base value '6 <<< 3' of STRKEY_PUBKEY"),
            ("Tests renamed", self.vectors, edit(SEP_0023, "## Tests\n", "## Test Cases\n"), "no '## Tests' section"),
            ("valid list renamed", self.vectors, edit(SEP_0023, "### Valid test cases\n", "### Accepted\n"),
             "no '### Valid test cases' section"),
            ("invalid list renamed", self.vectors, edit(SEP_0023, "### Invalid test cases\n", "### Rejected\n"),
             "no '### Invalid test cases' section"),
            ("empty valid list", self.vectors,
             re.sub(r"(### Valid test cases\n).*?(?=### Invalid test cases)", r"\1\n", SEP_0023, flags=re.DOTALL),
             "no valid test cases"),
            ("empty invalid list", self.vectors,
             re.sub(r"(### Invalid test cases\n).*?(?=You can paste)", r"\1\n", SEP_0023, flags=re.DOTALL),
             "no invalid test cases"),
            ("case without a Strkey entry", self.vectors, edit(SEP_0023, "- Strkey `GA7Q", "- Key `GA7Q"),
             r"valid test case 1 \('Valid non-multiplexed account'\) has 0 Strkey entries, expected 1"),
        ]
        for label, parse, document, message in cases:
            with self.subTest(label):
                with self.assertRaisesRegex(ValueError, rf"^SEP-23 sep-0023\.md: {message}$"):
                    parse(document)


class Sep23AnalyzerTest(unittest.TestCase):
    """SEP23Analyzer against a temporary SDK tree whose VersionByte and StrKey implement every key type."""

    def setUp(self):
        tmp = tempfile.TemporaryDirectory()
        self.addCleanup(tmp.cleanup)
        self.sdk_root = Path(tmp.name)
        self.write(SDK_VERSION_PATH, php_file(php_class("StellarSDK", 'const VERSION_NR = "9.9.9";')))
        self.write(VERSION_BYTE_PATH, version_byte_file(VERSION_BYTES))
        self.write(STRKEY_PATH, strkey_file(STRKEY_METHODS))
        self.write(STRKEY_TEST_PATH, php_test_file())

    def write(self, relative_path, content):
        path = self.sdk_root / relative_path
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(content, encoding="utf-8")

    def fields(self, section_name):
        document = sep.SEPDocument(url=raw_url(23), text=SEP_0023, preamble=sep.parse_sep_preamble(SEP_0023, "fixture"))
        matrix = sep.SEP23Analyzer(sep.SDKAnalyzer(self.sdk_root), document).analyze()
        return {f.name: f for s in matrix.sections if s.name == section_name for f in s.fields}

    def statuses(self, section_name):
        return {name: f.status for name, f in self.fields(section_name).items()}

    def test_every_key_type_is_implemented(self):
        fields = self.fields(KEY_TYPE_SECTION)

        self.assertEqual({f.status for f in fields.values()}, {SUPPORTED})
        self.assertEqual(list(fields), [name for name, _, _, _ in SEP_0023_KEY_TYPES])
        self.assertEqual(fields["STRKEY_PUBKEY"].sdk_class,
                         "VersionByte::ACCOUNT_ID, StrKey::encodeAccountId() / decodeAccountId()")
        self.assertEqual(fields["STRKEY_PUBKEY"].description, "Base value 6 << 3 (48), first character G")

    def test_value_that_differs_renders_not_implemented(self):
        self.write(VERSION_BYTE_PATH, version_byte_file(
            [(name, "19 << 3" if name == "SEED" else expression) for name, expression in VERSION_BYTES]))

        fields = self.fields(KEY_TYPE_SECTION)

        self.assertEqual((fields["STRKEY_PRIVKEY"].status, fields["STRKEY_PRIVKEY"].notes),
                         (NOT_SUPPORTED, "VersionByte::SEED is 152, the specification requires 144"))
        self.assertEqual({f.status for name, f in fields.items() if name != "STRKEY_PRIVKEY"}, {SUPPORTED})

    def test_typed_constant_is_read(self):
        self.write(VERSION_BYTE_PATH, version_byte_file([("int ACCOUNT_ID", "6 << 3")] + VERSION_BYTES[1:]))

        self.assertEqual(set(self.statuses(KEY_TYPE_SECTION).values()), {SUPPORTED})

    def test_value_forms_evaluate(self):
        self.write(VERSION_BYTE_PATH, version_byte_file([
            ("ACCOUNT_ID", "0b110_000"), ("MUXED_ACCOUNT_ID", "0x6_0"), ("SEED", "0o220"), ("PRE_AUTH_TX", "0230"),
            ("SHA256_HASH", "1_84"), ("SIGNED_PAYLOAD", "0b1111 << 0b11"), ("CONTRACT_ID", "2 << 0o3"),
            ("LIQUIDITY_POOL_ID", "0X58"), ("CLAIMABLE_BALANCE_ID", "0B1 << 3"),
        ]))

        self.assertEqual(set(self.statuses(KEY_TYPE_SECTION).values()), {SUPPORTED})

    def test_key_type_the_sdk_does_not_implement_renders_not_implemented(self):
        with mock.patch.dict(sep.SEP23Analyzer.PHP_NAMES, {"STRKEY_HASH_X": None}):
            fields = self.fields(KEY_TYPE_SECTION)

        self.assertEqual((fields["STRKEY_HASH_X"].status, fields["STRKEY_HASH_X"].notes),
                         (NOT_SUPPORTED, "Not implemented in the SDK"))

    def test_source_drift_raises(self):
        version_byte = r"Soneso/StellarSDK/Crypto/VersionByte\.php"
        cases = [
            ("VersionByte class absent", VERSION_BYTE_PATH, None, ValueError,
             "SEP-23 class VersionByte not found in Soneso/StellarSDK"),
            ("StrKey class absent", STRKEY_PATH, None, ValueError, "SEP-23 class StrKey not found in Soneso/StellarSDK"),
            ("constant absent", VERSION_BYTE_PATH, version_byte_file(VERSION_BYTES[:2] + VERSION_BYTES[3:]), ValueError,
             f"SEP-23 VersionByte::SEED not found in {version_byte}"),
            ("constant in another class of the file", VERSION_BYTE_PATH,
             version_byte_file(VERSION_BYTES[1:]) + php_class("Other", "const ACCOUNT_ID = 6 << 3;"), ValueError,
             f"SEP-23 VersionByte::ACCOUNT_ID not found in {version_byte}"),
            ("decode method absent", STRKEY_PATH, strkey_file([m for m in STRKEY_METHODS if m != "decodeSeed"]),
             ValueError, r"SEP-23 StrKey::decodeSeed\(\) not found in Soneso/StellarSDK/Crypto/StrKey\.php"),
            ("encode method absent", STRKEY_PATH, strkey_file([m for m in STRKEY_METHODS if m != "encodeContractId"]),
             ValueError, r"SEP-23 StrKey::encodeContractId\(\) not found in Soneso/StellarSDK/Crypto/StrKey\.php"),
            ("value in another form", VERSION_BYTE_PATH,
             version_byte_file([("ACCOUNT_ID", "self::BASE << 3")] + VERSION_BYTES[1:]), ValueError,
             f"SEP-23 cannot evaluate VersionByte::ACCOUNT_ID = 'self::BASE << 3' in {version_byte}"),
            ("repeated digit separator", VERSION_BYTE_PATH, version_byte_file([("ACCOUNT_ID", "4__8")] + VERSION_BYTES[1:]),
             ValueError, f"SEP-23 cannot evaluate VersionByte::ACCOUNT_ID = '4__8' in {version_byte}"),
            ("test file absent", STRKEY_TEST_PATH, None, RuntimeError,
             r"SEP-23 cannot read Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest\.php: .*"),
        ]
        for label, relative_path, content, error, message in cases:
            with self.subTest(label):
                original = (self.sdk_root / relative_path).read_text(encoding="utf-8")
                if content is None:
                    (self.sdk_root / relative_path).unlink()
                else:
                    self.write(relative_path, content)
                with self.assertRaisesRegex(error, rf"^{message}$"):
                    self.fields(KEY_TYPE_SECTION)
                self.write(relative_path, original)

    def test_key_type_without_php_names_raises(self):
        with mock.patch.dict(sep.SEP23Analyzer.PHP_NAMES):
            del sep.SEP23Analyzer.PHP_NAMES["STRKEY_PUBKEY"]
            with self.assertRaisesRegex(
                    ValueError, r"^SEP-23 key type STRKEY_PUBKEY has no entry in SEP23Analyzer\.PHP_NAMES$"):
                self.fields(KEY_TYPE_SECTION)

    def test_prefixes_and_padded_variants_of_a_vector_do_not_count(self):
        self.write(STRKEY_TEST_PATH, php_test_file(
            f"'X{SEP_0023_VECTORS[0][2]}'",
            "'GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZA'",
            '"MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAJLKA"',
            "'MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUO==='",
            '"BAAD6DBUX6J22DMZOHIEZTEQ64CVCHEDRKWZONFEUL5Q26QD7R76RGR4TV==="',
        ))

        statuses = self.statuses(VECTOR_SECTION)

        self.assertEqual([statuses[name] for name in ("valid_01", "valid_03", "invalid_10", "invalid_14")],
                         [NOT_SUPPORTED] * 4)
        self.assertEqual({name for name, status in statuses.items() if status == SUPPORTED}, {"invalid_03", "invalid_06"})

    def test_every_vector_counts_in_either_quote_style(self):
        self.write(STRKEY_TEST_PATH, php_test_file(*(f"'{strkey}'" if index % 2 else f'"{strkey}"'
                                                     for index, (_, _, strkey) in enumerate(SEP_0023_VECTORS))))

        self.assertEqual(self.statuses(VECTOR_SECTION), {name: SUPPORTED for name, _, _ in SEP_0023_VECTORS})


class Sep23GeneratorRunTest(unittest.TestCase):
    def setUp(self):
        tmp = tempfile.TemporaryDirectory()
        self.addCleanup(tmp.cleanup)
        self.output = Path(tmp.name)

    def run_main(self, routes, *sep_numbers):
        argv = ["generate_sep_matrix.py", "--sdk-root", str(REPO_ROOT), "--output", str(self.output),
                "--sep", *(str(number) for number in sep_numbers)]
        stderr = io.StringIO()
        with mock.patch.object(sys, "argv", argv), mock.patch("urllib.request.urlopen", FakeUrlopen(routes)), \
                contextlib.redirect_stderr(stderr):
            try:
                sep.main()
            except SystemExit as exit_:
                return exit_.code, stderr.getvalue()
        return 0, stderr.getvalue()

    @staticmethod
    def normalized(matrix):
        """*matrix* with the Generated and SDK Version values replaced, so a run or a release does not change it."""
        return re.sub(r"^(\*\*(?:Generated|SDK Version):\*\*) .*?( *)$", r"\1 <value>\2", matrix, flags=re.MULTILINE)

    def test_matrix_of_this_repository_matches_the_tracked_matrix(self):
        exit_code, _ = self.run_main({raw_url(23): SEP_0023}, 23)

        self.assertEqual(exit_code, 0)
        written = self.normalized((self.output / Path(MATRIX_PATH).name).read_text(encoding="utf-8"))
        tracked = self.normalized((REPO_ROOT / MATRIX_PATH).read_text(encoding="utf-8"))
        self.assertEqual(tracked.split("\n")[5:7], ["**SDK Version:** <value>  ", "**Generated:** <value>  "])
        self.assertEqual(written, tracked)

    def test_failed_run_exits_nonzero_and_writes_nothing(self):
        claimable_balance_row = "| STRKEY_CLAIMABLE_BALANCE | 1 << 3     | B          | no    | Hash |\n"
        cases = [
            ("key type without PHP names", edit(SEP_0023, claimable_balance_row, claimable_balance_row
                                                + "| STRKEY_EXAMPLE           | 3 << 3     | Y          | no    | Hash |\n"),
             "SEP-23 key type STRKEY_EXAMPLE has no entry in SEP23Analyzer.PHP_NAMES"),
            ("document without Tests", edit(SEP_0023, "## Tests\n", "## Test Cases\n"), "no '## Tests' section"),
        ]
        for label, document, message in cases:
            with self.subTest(label):
                exit_code, stderr = self.run_main({raw_url(1): SEP_0001, raw_url(23): document}, 1, 23)

                self.assertEqual(exit_code, 1)
                self.assertEqual(list(self.output.iterdir()), [])
                self.assertIn(message, stderr)


if __name__ == "__main__":
    unittest.main()
