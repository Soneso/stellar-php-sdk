"""SDK version lookup of sdk_version.py, shared by the Horizon, RPC and SEP generators."""

import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from sdk_version import get_sdk_version  # noqa: E402


class SdkVersionTest(unittest.TestCase):
    def setUp(self):
        tmp = tempfile.TemporaryDirectory()
        self.addCleanup(tmp.cleanup)
        self.sdk_root = Path(tmp.name)
        self.sdk_file = self.sdk_root / "Soneso" / "StellarSDK" / "StellarSDK.php"

    def test_missing_version_file_raises(self):
        with self.assertRaisesRegex(RuntimeError, "Cannot read the SDK version file"):
            get_sdk_version(self.sdk_root)

    def test_version_file_without_constant_raises(self):
        self.sdk_file.parent.mkdir(parents=True)
        self.sdk_file.write_text("<?php\nclass StellarSDK {}\n", encoding="utf-8")
        with self.assertRaisesRegex(RuntimeError, "No VERSION_NR constant"):
            get_sdk_version(self.sdk_root)

    def test_reads_version_constant(self):
        self.sdk_file.parent.mkdir(parents=True)
        self.sdk_file.write_text("<?php\nclass StellarSDK\n{\n    const VERSION_NR = \"1.15.0\";\n}\n", encoding="utf-8")
        self.assertEqual(get_sdk_version(self.sdk_root), "1.15.0")


if __name__ == "__main__":
    unittest.main()
