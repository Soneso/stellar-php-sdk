#!/usr/bin/env python3
"""
Stellar RPC API vs PHP SDK Compatibility Matrix Generator

Compares the Stellar RPC API methods with the PHP SDK Soroban implementation
and generates a detailed compatibility matrix with coverage statistics.

Analyzes actual PHP source code to extract method signatures and response
properties, then compares against rpc_methods.json produced by extract_rpc_methods.py.

Usage:
    python generate_rpc_matrix.py [--rpc-data PATH] [--sdk-root PATH]
                                  [--output PATH] [--verbose]
"""

import json
import re
import sys
import urllib.request
import urllib.error
from dataclasses import dataclass
from datetime import datetime, timezone
from enum import Enum
from pathlib import Path
from typing import Any, Optional


# ---------------------------------------------------------------------------
# Data structures
# ---------------------------------------------------------------------------

class SupportStatus(Enum):
    """Support status for an RPC method."""
    FULLY_SUPPORTED = "Full"
    PARTIALLY_SUPPORTED = "Partial"
    NOT_SUPPORTED = "Missing"


@dataclass
class RPCVersionInfo:
    """RPC version information from GitHub."""
    version: str
    release_date: str
    html_url: str


@dataclass
class SDKMethod:
    """A parsed PHP SDK method."""
    name: str
    rpc_method: str
    parameters: list[str]
    response_class: str


@dataclass
class SDKResponseClass:
    """A parsed PHP response class."""
    name: str
    properties: list[str]  # PHP property names (camelCase, maps to JSON keys)


@dataclass
class MethodComparison:
    """Comparison data for a single RPC method."""
    rpc_method: str
    sdk_method: str
    sdk_params: list[str]
    response_class: str
    status: SupportStatus
    rpc_required_params: list[str]
    rpc_optional_params: list[str]
    sdk_supported_params: list[str]
    rpc_response_fields: list[str]
    sdk_response_fields: list[str]
    missing_params: list[str]
    missing_fields: list[str]
    notes: str
    category: str


# ---------------------------------------------------------------------------
# Constants
# ---------------------------------------------------------------------------

# RPC method categories for grouping in the output table
METHOD_CATEGORIES: dict[str, str] = {
    "sendTransaction": "Transaction Methods",
    "simulateTransaction": "Transaction Methods",
    "getTransaction": "Transaction Methods",
    "getTransactions": "Transaction Methods",
    "getLatestLedger": "Ledger Methods",
    "getLedgers": "Ledger Methods",
    "getLedgerEntries": "Ledger Methods",
    "getEvents": "Event Methods",
    "getNetwork": "Network Info Methods",
    "getVersionInfo": "Network Info Methods",
    "getFeeStats": "Network Info Methods",
    "getHealth": "Network Info Methods",
}

# Maps RPC method names to their PHP response class names
RESPONSE_TYPE_MAP: dict[str, str] = {
    "getHealth": "GetHealthResponse",
    "getNetwork": "GetNetworkResponse",
    "getEvents": "GetEventsResponse",
    "getTransaction": "GetTransactionResponse",
    "getTransactions": "GetTransactionsResponse",
    "sendTransaction": "SendTransactionResponse",
    "simulateTransaction": "SimulateTransactionResponse",
    "getLedgerEntries": "GetLedgerEntriesResponse",
    "getLedgers": "GetLedgersResponse",
    "getLatestLedger": "GetLatestLedgerResponse",
    "getFeeStats": "GetFeeStatsResponse",
    "getVersionInfo": "GetVersionInfoResponse",
}

# RPC optional params we explicitly ignore (SDK intentionally omits them)
IGNORED_RPC_PARAMS: set[str] = {"xdrFormat"}

# PHP request-object parameter expansion, keyed by (rpc_method, php_param_name).
# Each entry lists the RPC params that the PHP request object wraps for that method.
REQUEST_OBJECT_PARAMS: dict[tuple[str, str], list[str]] = {
    ("simulateTransaction", "request"): ["transaction", "resourceConfig", "authMode"],
    ("getEvents", "request"): ["startLedger", "endLedger", "filters", "pagination"],
    ("getTransactions", "request"): ["startLedger", "endLedger", "pagination"],
    ("getLedgers", "request"): ["startLedger", "endLedger", "pagination"],
}

# Maps PHP parameter names to their RPC counterparts
PARAM_MAPPINGS: dict[str, str] = {
    "base64EncodedKeys": "keys",
    "transactionId": "hash",
    "transaction": "transaction",
}

# Response field suffixes to skip (the PHP SDK decodes XDR natively)
IGNORED_FIELD_SUFFIXES = ("Json",)

# JSON keys provided by the base class SorobanRpcResponse (inherited by all responses)
# These properties are declared in SorobanRpcResponse.php, not in each child class,
# so the per-file scanner doesn't see them.
BASE_RESPONSE_FIELDS: set[str] = {"error"}

# camelCase PHP property -> JSON key remapping where they differ
# (most PHP properties map directly to their JSON key names via camelCase convention)
PHP_PROPERTY_TO_JSON_KEY: dict[str, str] = {
    # getVersionInfo: SDK reads both snake_case (protocol < 22) and camelCase
    "commitHash": "commitHash",
    "buildTimeStamp": "buildTimestamp",
    "captiveCoreVersion": "captiveCoreVersion",
    "protocolVersion": "protocolVersion",
    # sendTransaction: diagnosticEvents is populated from 'diagnosticEventsXdr' JSON key
    "diagnosticEvents": "diagnosticEventsXdr",
}

# Human-readable method notes for fully-supported methods
METHOD_NOTES: dict[str, str] = {
    "sendTransaction": "Full support including diagnosticEventsXdr and errorResultXdr.",
    "simulateTransaction": "Supports transaction, resourceConfig (instructionLeeway), and authMode (protocol 23+).",
    "getTransaction": "Full support including protocol 22+ txHash, protocol 23+ events, diagnosticEventsXdr.",
    "getTransactions": "Full pagination support with cursor and limit.",
    "getLatestLedger": "Returns id, protocolVersion, sequence, closeTime, headerXdr, metadataXdr.",
    "getLedgers": "Full pagination support with cursor and limit.",
    "getLedgerEntries": "Supports up to 200 keys, returns entries with TTL info.",
    "getEvents": "Full support including endLedger, filters, pagination, cursor.",
    "getNetwork": "Returns friendbotUrl (optional), passphrase, and protocolVersion.",
    "getVersionInfo": "Protocol 22+ compliant (camelCase fields; also reads snake_case for backward compat).",
    "getFeeStats": "Full support for sorobanInclusionFee and inclusionFee statistics.",
    "getHealth": "Full support for status, ledgerRetentionWindow, oldestLedger, latestLedger.",
}


# ---------------------------------------------------------------------------
# Helper: fetch RPC version info from GitHub
# ---------------------------------------------------------------------------

def fetch_rpc_version() -> RPCVersionInfo:
    """Fetch the latest stellar-rpc release version from GitHub."""
    url = "https://api.github.com/repos/stellar/stellar-rpc/releases"
    try:
        req = urllib.request.Request(url)
        req.add_header("User-Agent", "stellar-php-sdk-matrix-generator/1.0")
        with urllib.request.urlopen(req, timeout=10) as response:
            releases = json.loads(response.read().decode("utf-8"))
            for release in releases:
                tag = release.get("tag_name", "")
                if tag.startswith("v") and not tag.startswith("rpcclient"):
                    published = release.get("published_at", "")[:10]
                    return RPCVersionInfo(
                        version=tag,
                        release_date=published,
                        html_url=release.get("html_url", ""),
                    )
    except (urllib.error.URLError, json.JSONDecodeError) as e:
        print(f"Warning: Could not fetch RPC version from GitHub: {e}")

    # Fallback
    return RPCVersionInfo(
        version="v25.0.0",
        release_date="2025-12-12",
        html_url="https://github.com/stellar/stellar-rpc/releases/tag/v25.0.0",
    )


def get_sdk_version(sdk_root: Path) -> str:
    """Read VERSION_NR from Soneso/StellarSDK/StellarSDK.php."""
    sdk_php = sdk_root / "Soneso" / "StellarSDK" / "StellarSDK.php"
    if sdk_php.exists():
        content = sdk_php.read_text(encoding="utf-8")
        match = re.search(r"VERSION_NR\s*=\s*['\"]([^'\"]+)['\"]", content)
        if match:
            return match.group(1)
    return "Unknown"


# ---------------------------------------------------------------------------
# PHP source analyzer
# ---------------------------------------------------------------------------

class PHPSorobanAnalyzer:
    """Analyzes PHP Soroban source files to extract SDK implementation details."""

    def __init__(self, sdk_root: Path, verbose: bool = False):
        self.sdk_root = sdk_root
        self.soroban_path = sdk_root / "Soneso" / "StellarSDK" / "Soroban"
        self.verbose = verbose
        self.methods: dict[str, SDKMethod] = {}
        self.response_classes: dict[str, SDKResponseClass] = {}

    def analyze(self) -> None:
        """Analyze SDK source files."""
        self._parse_soroban_server()
        self._parse_response_classes()

    def _parse_soroban_server(self) -> None:
        """Parse SorobanServer.php to extract public RPC-mapped method signatures."""
        server_path = self.soroban_path / "SorobanServer.php"
        if not server_path.exists():
            print(f"Warning: SorobanServer.php not found at {server_path}")
            return

        content = server_path.read_text(encoding="utf-8")

        # Match public function declarations.
        # Pattern: public function methodName(params) : ReturnType
        method_pattern = r'public\s+function\s+(\w+)\s*\(([^)]*)\)\s*:'

        # Only consider methods that are direct RPC dispatchers
        rpc_methods = set(RESPONSE_TYPE_MAP.keys())

        for match in re.finditer(method_pattern, content):
            method_name = match.group(1)
            params_str = match.group(2)

            if method_name not in rpc_methods:
                continue

            params = self._parse_php_params(params_str)
            response_class = RESPONSE_TYPE_MAP.get(method_name, "")

            self.methods[method_name] = SDKMethod(
                name=method_name,
                rpc_method=method_name,
                parameters=params,
                response_class=response_class,
            )

            if self.verbose:
                print(f"  Found SDK method: {method_name}({', '.join(params)})")

    def _parse_php_params(self, params_str: str) -> list[str]:
        """Extract parameter names from a PHP function signature string."""
        params = []
        if not params_str.strip():
            return params

        for segment in params_str.split(","):
            segment = segment.strip()
            # PHP param: optional TypeHint $paramName = default
            var_match = re.search(r'\$(\w+)', segment)
            if var_match:
                params.append(var_match.group(1))

        return params

    def _parse_response_classes(self) -> None:
        """Parse Responses/*.php files to extract public properties."""
        responses_path = self.soroban_path / "Responses"
        if not responses_path.exists():
            print(f"Warning: Responses directory not found at {responses_path}")
            return

        for php_file in sorted(responses_path.glob("*.php")):
            self._parse_response_file(php_file)

    def _parse_response_file(self, file_path: Path) -> None:
        """Parse a single response PHP file to extract public properties."""
        content = file_path.read_text(encoding="utf-8")

        # Find the class name — we want *Response classes
        class_match = re.search(r'class\s+(\w+Response)\s', content)
        if not class_match:
            return

        class_name = class_match.group(1)

        # Collect public properties declared with: public ?Type $name or public Type $name
        # Also handles constructor-promoted properties in PHP 8+
        prop_pattern = r'public\s+(?:\??[\w\\]+\s+)?\$(\w+)'
        properties = []

        for prop_match in re.finditer(prop_pattern, content):
            prop_name = prop_match.group(1)
            properties.append(prop_name)

        self.response_classes[class_name] = SDKResponseClass(
            name=class_name,
            properties=properties,
        )

        if self.verbose:
            print(f"  Parsed {class_name}: {len(properties)} properties")


# ---------------------------------------------------------------------------
# RPC matrix generator
# ---------------------------------------------------------------------------

class RPCMatrixGenerator:
    """Generates the RPC compatibility matrix by comparing rpc_methods.json to PHP source."""

    def __init__(self, sdk_root: Path, rpc_methods_file: Path, verbose: bool = False):
        self.sdk_root = sdk_root
        self.rpc_methods_file = rpc_methods_file
        self.verbose = verbose
        self.rpc_data: dict[str, Any] = {}
        self.rpc_version = fetch_rpc_version()
        self.sdk_version = get_sdk_version(sdk_root)
        self.analyzer = PHPSorobanAnalyzer(sdk_root, verbose=verbose)
        self.comparisons: list[MethodComparison] = []

    def analyze(self) -> None:
        """Load RPC data and analyze PHP SDK against it."""
        if self.verbose:
            print(f"  Loading RPC methods from: {self.rpc_methods_file.name}")

        if not self.rpc_methods_file.exists():
            raise FileNotFoundError(f"RPC methods file not found: {self.rpc_methods_file}")

        with open(self.rpc_methods_file, encoding="utf-8") as f:
            self.rpc_data = json.load(f)

        # Override version tag from JSON metadata if present (keep release_date
        # and html_url from the GitHub API — extracted_date is the extraction run
        # date, not the actual release date)
        metadata = self.rpc_data.get("metadata", {})
        if metadata.get("version"):
            self.rpc_version = RPCVersionInfo(
                version=metadata.get("version", self.rpc_version.version),
                release_date=self.rpc_version.release_date,
                html_url=self.rpc_version.html_url,
            )

        if self.verbose:
            print("  Parsing PHP source files...")

        self.analyzer.analyze()

        if self.verbose:
            print(f"  Found {len(self.analyzer.methods)} SDK methods")
            print(f"  Found {len(self.analyzer.response_classes)} response classes")

        rpc_methods = self.rpc_data.get("methods", {})

        for rpc_method, rpc_def in rpc_methods.items():
            sdk_method = self.analyzer.methods.get(rpc_method)
            comparison = self._compare_method(rpc_method, rpc_def, sdk_method)
            self.comparisons.append(comparison)

    def _extract_rpc_fields(self, rpc_def: dict[str, Any]) -> list[str]:
        """Extract response field names from the RPC definition dict."""
        field_names = []
        response_data = rpc_def.get("response", {})

        if isinstance(response_data, dict):
            for f in response_data.get("fields", []):
                if isinstance(f, dict):
                    name = f.get("name") or f.get("json_name", "")
                else:
                    name = str(f)
                if name and not name.endswith(IGNORED_FIELD_SUFFIXES):
                    field_names.append(name)
        else:
            # Fallback for older format
            for f in rpc_def.get("response_fields", []):
                name = f.get("json_name", "") if isinstance(f, dict) else str(f)
                if name and not name.endswith(IGNORED_FIELD_SUFFIXES):
                    field_names.append(name)

        return field_names

    def _extract_rpc_params(self, rpc_def: dict[str, Any]) -> tuple[list[str], list[str]]:
        """Extract required and optional parameter names from the RPC definition dict."""
        params_data = rpc_def.get("parameters", {})

        if isinstance(params_data, dict) and ("required" in params_data or "optional" in params_data):
            required = [
                p.get("name", p) if isinstance(p, dict) else p
                for p in params_data.get("required", [])
                if (p.get("name", p) if isinstance(p, dict) else p) not in IGNORED_RPC_PARAMS
            ]
            optional = [
                p.get("name", p) if isinstance(p, dict) else p
                for p in params_data.get("optional", [])
                if (p.get("name", p) if isinstance(p, dict) else p) not in IGNORED_RPC_PARAMS
            ]
        else:
            required = [p for p in rpc_def.get("required_params", []) if p not in IGNORED_RPC_PARAMS]
            optional = [p for p in rpc_def.get("optional_params", []) if p not in IGNORED_RPC_PARAMS]

        return required, optional

    def _map_sdk_params_to_rpc(self, sdk_params: list[str], rpc_method: str = "") -> set[str]:
        """Map PHP SDK parameter names to their RPC equivalents."""
        mapped: set[str] = set()

        for param in sdk_params:
            rpc_name = PARAM_MAPPINGS.get(param, param)
            mapped.add(rpc_name)

            key = (rpc_method, param)
            if key in REQUEST_OBJECT_PARAMS:
                mapped.update(REQUEST_OBJECT_PARAMS[key])

        return mapped

    def _map_sdk_properties_to_json_keys(self, properties: list[str]) -> set[str]:
        """Map PHP property names to JSON key names for comparison.

        Includes BASE_RESPONSE_FIELDS which are inherited from SorobanRpcResponse
        but not declared in individual response class files.
        """
        keys: set[str] = set(BASE_RESPONSE_FIELDS)

        for prop in properties:
            keys.add(PHP_PROPERTY_TO_JSON_KEY.get(prop, prop))

        return keys

    def _compare_method(
        self,
        rpc_method: str,
        rpc_def: dict[str, Any],
        sdk_method: Optional[SDKMethod],
    ) -> MethodComparison:
        """Build a MethodComparison for one RPC method."""
        rpc_required_list, rpc_optional_list = self._extract_rpc_params(rpc_def)
        rpc_response_fields = self._extract_rpc_fields(rpc_def)

        if sdk_method is None:
            return MethodComparison(
                rpc_method=rpc_method,
                sdk_method="-",
                sdk_params=[],
                response_class="-",
                status=SupportStatus.NOT_SUPPORTED,
                rpc_required_params=rpc_required_list,
                rpc_optional_params=rpc_optional_list,
                sdk_supported_params=[],
                rpc_response_fields=rpc_response_fields,
                sdk_response_fields=[],
                missing_params=rpc_required_list + rpc_optional_list,
                missing_fields=rpc_response_fields,
                notes="Method not implemented",
                category=METHOD_CATEGORIES.get(rpc_method, "Other"),
            )

        # --- parameter coverage ---
        sdk_mapped_params = self._map_sdk_params_to_rpc(sdk_method.parameters, rpc_method)
        rpc_required = set(rpc_required_list)
        rpc_optional = set(rpc_optional_list)

        missing_required = rpc_required - sdk_mapped_params
        missing_optional = rpc_optional - sdk_mapped_params
        missing_params = sorted(missing_required | missing_optional)

        # --- response field coverage ---
        response_cls = self.analyzer.response_classes.get(sdk_method.response_class)
        sdk_props = response_cls.properties if response_cls else []
        sdk_json_keys = self._map_sdk_properties_to_json_keys(sdk_props)
        rpc_fields_set = set(rpc_response_fields)
        missing_fields = sorted(rpc_fields_set - sdk_json_keys)

        # --- status ---
        if missing_required:
            status = SupportStatus.NOT_SUPPORTED
        elif missing_params or missing_fields:
            status = SupportStatus.PARTIALLY_SUPPORTED
        else:
            status = SupportStatus.FULLY_SUPPORTED

        notes = self._build_notes(rpc_method, missing_params, missing_fields, status)

        # Format SDK method signature for display
        if sdk_method.parameters:
            params_str = ", ".join(f"${p}" for p in sdk_method.parameters)
            sdk_method_str = f"{sdk_method.name}({params_str})"
        else:
            sdk_method_str = f"{sdk_method.name}()"

        return MethodComparison(
            rpc_method=rpc_method,
            sdk_method=sdk_method_str,
            sdk_params=sdk_method.parameters,
            response_class=sdk_method.response_class,
            status=status,
            rpc_required_params=rpc_required_list,
            rpc_optional_params=rpc_optional_list,
            sdk_supported_params=sorted(sdk_mapped_params),
            rpc_response_fields=rpc_response_fields,
            sdk_response_fields=sorted(sdk_json_keys),
            missing_params=missing_params,
            missing_fields=missing_fields,
            notes=notes,
            category=METHOD_CATEGORIES.get(rpc_method, "Other"),
        )

    def _build_notes(
        self,
        method: str,
        missing_params: list[str],
        missing_fields: list[str],
        status: SupportStatus,
    ) -> str:
        """Build a human-readable notes string for a method comparison."""
        if status == SupportStatus.FULLY_SUPPORTED:
            return METHOD_NOTES.get(method, "All parameters and response fields implemented.")

        parts = []
        if missing_params:
            parts.append(f"Missing params: {', '.join(missing_params)}")
        if missing_fields:
            parts.append(f"Missing fields: {', '.join(missing_fields)}")

        return "; ".join(parts) if parts else "Partial implementation."

    # ---------------------------------------------------------------------------
    # Markdown generation
    # ---------------------------------------------------------------------------

    def generate_markdown(self, output_path: Path) -> None:
        """Write the compatibility matrix markdown file."""
        br = "  "  # Two spaces = markdown line break

        total = len(self.comparisons)
        fully = sum(1 for c in self.comparisons if c.status == SupportStatus.FULLY_SUPPORTED)
        partial = sum(1 for c in self.comparisons if c.status == SupportStatus.PARTIALLY_SUPPORTED)
        missing = sum(1 for c in self.comparisons if c.status == SupportStatus.NOT_SUPPORTED)
        coverage = (fully / total * 100) if total > 0 else 0.0

        generated_at = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M UTC")

        lines = [
            "# Soroban RPC vs PHP SDK Compatibility Matrix",
            "",
            f"**RPC Version:** {self.rpc_version.version} (released {self.rpc_version.release_date}){br}",
            f"**RPC Source:** [{self.rpc_version.version}]({self.rpc_version.html_url}){br}",
            f"**SDK Version:** {self.sdk_version}{br}",
            f"**Generated:** {generated_at}",
            "",
            "## Overall Coverage",
            "",
            f"**Coverage:** {coverage:.1f}%",
            "",
            f"- **Fully Supported:** {fully}/{total}",
            f"- **Partially Supported:** {partial}/{total}",
            f"- **Not Supported:** {missing}/{total}",
            "",
            "## Method Comparison",
            "",
        ]

        # Group by category
        categories: dict[str, list[MethodComparison]] = {}
        for comp in self.comparisons:
            categories.setdefault(comp.category, []).append(comp)

        category_order = [
            "Transaction Methods",
            "Ledger Methods",
            "Event Methods",
            "Network Info Methods",
        ]

        for category in category_order:
            if category not in categories:
                continue

            lines.append(f"### {category}")
            lines.append("")
            lines.append("| RPC Method | Status | SDK Method | Response Class | Notes |")
            lines.append("|------------|--------|------------|----------------|-------|")

            for comp in sorted(categories[category], key=lambda c: c.rpc_method):
                status_label = comp.status.value
                lines.append(
                    f"| {comp.rpc_method} | {status_label} | `{comp.sdk_method}` "
                    f"| {comp.response_class} | {comp.notes} |"
                )

            lines.append("")

        # Parameter coverage table
        lines.extend([
            "## Parameter Coverage",
            "",
            "Detailed breakdown of parameter support per method.",
            "",
            "| RPC Method | RPC Params | SDK Params | Missing |",
            "|------------|------------|------------|---------|",
        ])

        for comp in sorted(self.comparisons, key=lambda c: c.rpc_method):
            rpc_total = len(comp.rpc_required_params) + len(comp.rpc_optional_params)
            sdk_count = len(comp.sdk_supported_params)
            missing_str = ", ".join(comp.missing_params) if comp.missing_params else "-"
            lines.append(
                f"| {comp.rpc_method} | {rpc_total} | {sdk_count} | {missing_str} |"
            )

        lines.append("")

        # Response field coverage table
        lines.extend([
            "## Response Field Coverage",
            "",
            "Detailed breakdown of response field support per method.",
            "",
            "| RPC Method | RPC Fields | SDK Fields | Missing |",
            "|------------|------------|------------|---------|",
        ])

        for comp in sorted(self.comparisons, key=lambda c: c.rpc_method):
            rpc_count = len(comp.rpc_response_fields)
            sdk_count = len(comp.sdk_response_fields)
            missing_str = ", ".join(comp.missing_fields) if comp.missing_fields else "-"
            lines.append(
                f"| {comp.rpc_method} | {rpc_count} | {sdk_count} | {missing_str} |"
            )

        lines.append("")

        # Legend
        lines.extend([
            "## Legend",
            "",
            "| Status | Description |",
            "|--------|-------------|",
            "| Full | Method implemented with all required parameters and response fields |",
            "| Partial | Basic functionality present, missing some optional parameters or response fields |",
            "| Missing | Method not implemented in SDK |",
            "",
        ])

        output_path.parent.mkdir(parents=True, exist_ok=True)
        output_path.write_text("\n".join(lines), encoding="utf-8")
        print(f"  Generated: {output_path}")


# ---------------------------------------------------------------------------
# CLI entry point
# ---------------------------------------------------------------------------

def _auto_detect_sdk_root(script_path: Path) -> Path:
    """Walk up from the script location to find the SDK root (contains Soneso/)."""
    candidate = script_path.parent
    for _ in range(10):
        if (candidate / "Soneso" / "StellarSDK").exists():
            return candidate
        candidate = candidate.parent
    # Fallback: three levels up from tools/matrix-generator/rpc/
    return script_path.parent.parent.parent


def main() -> int:
    """Main entry point."""
    import argparse

    script_dir = Path(__file__).resolve().parent

    parser = argparse.ArgumentParser(
        description="Generate Stellar RPC vs PHP SDK compatibility matrix"
    )
    parser.add_argument(
        "--rpc-data",
        type=Path,
        default=script_dir / "rpc_methods.json",
        help="Path to rpc_methods.json (default: same directory as this script)",
    )
    parser.add_argument(
        "--sdk-root",
        type=Path,
        default=None,
        help="Path to the PHP SDK root directory (default: auto-detected)",
    )
    parser.add_argument(
        "--output",
        type=Path,
        default=None,
        help="Output path for the markdown file "
             "(default: <sdk-root>/compatibility/rpc/RPC_COMPATIBILITY_MATRIX.md)",
    )
    parser.add_argument(
        "--verbose",
        "-v",
        action="store_true",
        help="Enable verbose output",
    )

    args = parser.parse_args()

    sdk_root = args.sdk_root or _auto_detect_sdk_root(script_dir)
    output_path = args.output or (sdk_root / "compatibility" / "rpc" / "RPC_COMPATIBILITY_MATRIX.md")

    print("=" * 70)
    print("Stellar RPC API vs PHP SDK Compatibility Matrix Generator")
    print("=" * 70)
    print()

    try:
        generator = RPCMatrixGenerator(
            sdk_root=sdk_root,
            rpc_methods_file=args.rpc_data,
            verbose=args.verbose,
        )

        print(f"SDK root:    {sdk_root}")
        print(f"RPC data:    {args.rpc_data}")
        print(f"Output:      {output_path}")
        print()

        print("Loading RPC version information...")
        print(f"  RPC Version:  {generator.rpc_version.version}")
        print(f"  Release Date: {generator.rpc_version.release_date}")
        print(f"  SDK Version:  {generator.sdk_version}")
        print()

        print("Analyzing PHP SDK implementation...")
        generator.analyze()
        print()

        print("Generating compatibility matrix...")
        generator.generate_markdown(output_path)
        print()

        # Summary
        total = len(generator.comparisons)
        fully = sum(1 for c in generator.comparisons if c.status == SupportStatus.FULLY_SUPPORTED)
        partial = sum(1 for c in generator.comparisons if c.status == SupportStatus.PARTIALLY_SUPPORTED)
        not_sup = sum(1 for c in generator.comparisons if c.status == SupportStatus.NOT_SUPPORTED)

        print("=" * 70)
        print("SUMMARY")
        print("=" * 70)
        print(f"RPC Version:       {generator.rpc_version.version}")
        print(f"SDK Version:       {generator.sdk_version}")
        print(f"Total Methods:     {total}")
        if total > 0:
            print(f"Fully Supported:   {fully}/{total} ({fully / total * 100:.1f}%)")
        if partial:
            print(f"Partial Support:   {partial}/{total}")
        if not_sup:
            print(f"Not Supported:     {not_sup}/{total}")

        issues = [c for c in generator.comparisons if c.status != SupportStatus.FULLY_SUPPORTED]
        if issues:
            print()
            print("Issues found:")
            for c in issues:
                print(f"  - {c.rpc_method}: {c.notes}")

        print()
        print("=" * 70)
        print("Matrix generation complete.")
        print("=" * 70)

        return 0

    except FileNotFoundError as e:
        print(f"ERROR: {e}", file=sys.stderr)
        print("Run extract_rpc_methods.py first to generate rpc_methods.json", file=sys.stderr)
        return 1
    except Exception as e:
        print(f"ERROR: {e}", file=sys.stderr)
        import traceback
        traceback.print_exc()
        return 1


if __name__ == "__main__":
    sys.exit(main())
