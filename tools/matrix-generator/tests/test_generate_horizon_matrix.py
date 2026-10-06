"""SDK version failure behavior of horizon/generate_horizon_matrix.py."""

import sys
import tempfile
import unittest
from pathlib import Path
from unittest import mock

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "horizon"))

import generate_horizon_matrix as horizon  # noqa: E402


class UnreadableSdkVersionTest(unittest.TestCase):
    def setUp(self):
        tmp = tempfile.TemporaryDirectory()
        self.addCleanup(tmp.cleanup)
        self.sdk_root = Path(tmp.name)
        (self.sdk_root / "Soneso" / "StellarSDK" / "Requests").mkdir(parents=True)

    def test_unreadable_version_writes_no_matrix(self):
        output = self.sdk_root / "COMPATIBILITY_MATRIX.md"
        with mock.patch.object(
                horizon.HorizonVersionFetcher, "get_latest_release", side_effect=AssertionError("no request expected")
        ) as lookup, self.assertLogs(horizon.logger, "ERROR") as logs:
            exit_code = horizon.HorizonMatrixGenerator(self.sdk_root).generate(output_path=str(output))

        self.assertEqual(exit_code, 1)
        self.assertFalse(output.exists())
        self.assertIn("StellarSDK.php", logs.output[0])
        lookup.assert_not_called()


if __name__ == "__main__":
    unittest.main()
