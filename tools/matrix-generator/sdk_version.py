"""SDK version lookup shared by the Horizon, RPC and SEP generators."""

import re
from pathlib import Path


def get_sdk_version(sdk_root: Path) -> str:
    """Read VERSION_NR from Soneso/StellarSDK/StellarSDK.php; raise when it cannot be read."""
    sdk_php = sdk_root / "Soneso" / "StellarSDK" / "StellarSDK.php"
    try:
        content = sdk_php.read_text(encoding="utf-8")
    except OSError as e:
        raise RuntimeError(f"Cannot read the SDK version file {sdk_php}: {e}") from e
    match = re.search(r"VERSION_NR\s*=\s*['\"]([^'\"]+)['\"]", content)
    if not match:
        raise RuntimeError(f"No VERSION_NR constant in {sdk_php}")
    return match.group(1)
