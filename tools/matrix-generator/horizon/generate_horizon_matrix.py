#!/usr/bin/env python3
"""
Horizon API Compatibility Matrix Generator for Stellar PHP SDK

This script generates a detailed compatibility matrix comparing the PHP SDK
implementation against the Horizon API by fetching the latest Horizon release,
parsing router.go to extract endpoints, analyzing PHP RequestBuilder classes,
and generating a comprehensive markdown matrix.

Features:
- Automatic version detection from GitHub releases
- Go Chi router parsing for endpoint extraction
- PHP RequestBuilder file analysis for SDK method mapping
- Detailed coverage statistics and streaming support tracking
- Production-ready error handling and logging

Usage:
    python generate_horizon_matrix.py
    python generate_horizon_matrix.py --horizon-version v25.0.0
    python generate_horizon_matrix.py --output custom_matrix.md
    python generate_horizon_matrix.py --verbose

Python: 3.10+
"""

import argparse
import json
import logging
import re
import sys
from dataclasses import dataclass, field
from datetime import datetime, timezone
from pathlib import Path
from typing import Dict, List, Optional, Tuple
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen

# Import Horizon parameter definitions
from horizon_params import HORIZON_PARAMS

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(name)s - %(levelname)s - %(message)s",
)
logger = logging.getLogger(__name__)


# Excluded endpoints - deprecated or redundant endpoints not counted in public API
EXCLUDED_ENDPOINTS: Dict[Tuple[str, str], str] = {
    ("/paths", "GET"): "Deprecated - use /paths/strict-receive and /paths/strict-send",
    ("/friendbot", "POST"): "Redundant - GET method is used instead",
}


# Single-resource endpoints excluded from streaming consideration.
# These endpoints are not commonly used for streaming and no major SDK implements
# streaming for them.
STREAMING_EXCLUDED = {
    ("/claimable_balances/{id}", "GET"),
    ("/liquidity_pools/{liquidity_pool_id}", "GET"),
    ("/offers/{offer_id}", "GET"),
    ("/ledgers/{ledger_id}", "GET"),
    ("/transactions/{tx_id}", "GET"),
    ("/operations/{id}", "GET"),
}


def get_sdk_version(sdk_root: Path) -> str:
    """Read SDK version from StellarSDK.php VERSION_NR constant."""
    sdk_php = sdk_root / "Soneso" / "StellarSDK" / "StellarSDK.php"
    if sdk_php.exists():
        content = sdk_php.read_text(encoding="utf-8")
        match = re.search(r"VERSION_NR\s*=\s*['\"]([^'\"]+)['\"]", content)
        if match:
            return match.group(1)
    return "Unknown"


def compare_params(
    endpoint: str, method: str
) -> Tuple[List[str], List[str], bool]:
    """
    Compare Horizon params with PHP SDK params for a given endpoint.

    Args:
        endpoint: Normalized endpoint path (matching HORIZON_PARAMS keys)
        method: HTTP method

    Returns:
        Tuple of (missing_params, extra_params, is_full_match)
        - missing_params: Parameters Horizon has but SDK doesn't
        - extra_params: Parameters SDK has but Horizon doesn't
        - is_full_match: True if all Horizon params are implemented
    """
    from horizon_params import HORIZON_PARAMS as HP
    horizon = set(HP.get((endpoint, method), []))
    sdk = set(PHP_SDK_PARAMS.get((endpoint, method), []))

    missing = horizon - sdk
    extra = sdk - horizon

    return sorted(list(missing)), sorted(list(extra)), len(missing) == 0


# PHP SDK parameter mapping - derived from analysis of RequestBuilder classes.
# Each entry maps (endpoint_path, http_method) to the list of query parameters
# that the SDK actually supports for that endpoint.
PHP_SDK_PARAMS: Dict[Tuple[str, str], List[str]] = {
    # Root / Health
    ("/", "GET"): [],
    ("/health", "GET"): [],

    # Accounts
    ("/accounts", "GET"): ["signer", "asset", "sponsor", "liquidity_pool", "cursor", "limit", "order"],
    ("/accounts/{account_id}", "GET"): [],
    ("/accounts/{account_id}/data/{key}", "GET"): [],
    ("/accounts/{account_id}/offers", "GET"): ["cursor", "limit", "order"],
    ("/accounts/{account_id}/trades", "GET"): ["cursor", "limit", "order"],
    ("/accounts/{account_id}/effects", "GET"): ["cursor", "limit", "order"],
    ("/accounts/{account_id}/operations", "GET"): ["include_failed", "join", "cursor", "limit", "order"],
    ("/accounts/{account_id}/payments", "GET"): ["include_failed", "join", "cursor", "limit", "order"],
    ("/accounts/{account_id}/transactions", "GET"): ["include_failed", "cursor", "limit", "order"],

    # Assets
    ("/assets", "GET"): ["asset_code", "asset_issuer", "cursor", "limit", "order"],

    # Claimable Balances
    ("/claimable_balances", "GET"): ["asset", "claimant", "sponsor", "cursor", "limit", "order"],
    ("/claimable_balances/{id}", "GET"): [],
    ("/claimable_balances/{id}/transactions", "GET"): ["include_failed", "cursor", "limit", "order"],
    ("/claimable_balances/{id}/operations", "GET"): ["include_failed", "join", "cursor", "limit", "order"],

    # Effects
    ("/effects", "GET"): ["cursor", "limit", "order"],

    # Fee Stats
    ("/fee_stats", "GET"): [],

    # Ledgers
    ("/ledgers", "GET"): ["cursor", "limit", "order"],
    ("/ledgers/{ledger_id}", "GET"): [],
    ("/ledgers/{ledger_id}/transactions", "GET"): ["include_failed", "cursor", "limit", "order"],
    ("/ledgers/{ledger_id}/operations", "GET"): ["include_failed", "join", "cursor", "limit", "order"],
    ("/ledgers/{ledger_id}/payments", "GET"): ["include_failed", "join", "cursor", "limit", "order"],
    ("/ledgers/{ledger_id}/effects", "GET"): ["cursor", "limit", "order"],

    # Liquidity Pools
    ("/liquidity_pools", "GET"): ["reserves", "account", "cursor", "limit", "order"],
    ("/liquidity_pools/{liquidity_pool_id}", "GET"): [],
    ("/liquidity_pools/{liquidity_pool_id}/transactions", "GET"): ["include_failed", "cursor", "limit", "order"],
    ("/liquidity_pools/{liquidity_pool_id}/operations", "GET"): ["include_failed", "join", "cursor", "limit", "order"],
    ("/liquidity_pools/{liquidity_pool_id}/effects", "GET"): ["cursor", "limit", "order"],
    ("/liquidity_pools/{liquidity_pool_id}/trades", "GET"): ["cursor", "limit", "order"],

    # Offers
    ("/offers", "GET"): ["seller", "selling", "buying", "sponsor", "cursor", "limit", "order"],
    ("/offers/{offer_id}", "GET"): [],
    ("/offers/{offer_id}/trades", "GET"): ["cursor", "limit", "order"],

    # Operations
    ("/operations", "GET"): ["include_failed", "join", "cursor", "limit", "order"],
    ("/operations/{op_id}", "GET"): ["join"],
    ("/operations/{op_id}/effects", "GET"): ["cursor", "limit", "order"],

    # Order Book
    ("/order_book", "GET"): [
        "selling_asset_type", "selling_asset_code", "selling_asset_issuer",
        "buying_asset_type", "buying_asset_code", "buying_asset_issuer",
        "limit",
    ],

    # Paths
    ("/paths", "GET"): [
        "destination_account", "destination_asset_type", "destination_asset_code",
        "destination_asset_issuer", "destination_amount", "source_account",
    ],
    ("/paths/strict-receive", "GET"): [
        "source_account", "source_assets", "destination_account",
        "destination_asset_type", "destination_asset_code", "destination_asset_issuer",
        "destination_amount",
    ],
    ("/paths/strict-send", "GET"): [
        "source_amount", "source_asset_type", "source_asset_code", "source_asset_issuer",
        "destination_account", "destination_assets",
    ],

    # Payments
    ("/payments", "GET"): ["include_failed", "join", "cursor", "limit", "order"],

    # Trades
    ("/trades", "GET"): [
        "base_asset_type", "base_asset_code", "base_asset_issuer",
        "counter_asset_type", "counter_asset_code", "counter_asset_issuer",
        "offer_id", "trade_type", "cursor", "limit", "order",
    ],
    ("/trade_aggregations", "GET"): [
        "start_time", "end_time", "resolution", "offset",
        "base_asset_type", "base_asset_code", "base_asset_issuer",
        "counter_asset_type", "counter_asset_code", "counter_asset_issuer",
        "cursor", "limit", "order",
    ],

    # Transactions
    ("/transactions", "GET"): ["include_failed", "cursor", "limit", "order"],
    ("/transactions", "POST"): ["tx"],
    ("/transactions/{tx_id}", "GET"): [],
    ("/transactions/{tx_id}/operations", "GET"): ["include_failed", "join", "cursor", "limit", "order"],
    ("/transactions/{tx_id}/payments", "GET"): ["include_failed", "join", "cursor", "limit", "order"],
    ("/transactions/{tx_id}/effects", "GET"): ["cursor", "limit", "order"],

    # Async transactions
    ("/transactions_async", "POST"): ["tx"],

    # Friendbot
    ("/friendbot", "GET"): ["addr"],
    ("/friendbot", "POST"): ["addr"],
}


@dataclass
class HorizonRelease:
    """Represents a Horizon release from GitHub."""

    version: str
    tag_name: str
    release_date: str
    html_url: str

    @property
    def ref(self) -> str:
        """Git reference for fetching files."""
        return self.tag_name


@dataclass
class HorizonEndpoint:
    """Represents a single Horizon API endpoint."""

    path: str
    method: str
    handler: str
    category: str
    streaming: bool = False
    path_params: List[str] = field(default_factory=list)
    internal: bool = False


@dataclass
class SDKMethod:
    """Represents a PHP SDK RequestBuilder method that covers a Horizon endpoint."""

    name: str
    builder_class: str
    file_path: str
    horizon_endpoint: str
    http_method: str = "GET"
    streaming: bool = False


@dataclass
class EndpointMatch:
    """Represents a match between a Horizon endpoint and an SDK method."""

    endpoint: HorizonEndpoint
    sdk_method: Optional[SDKMethod] = None
    missing_params: List[str] = field(default_factory=list)
    extra_params: List[str] = field(default_factory=list)
    streaming_match: bool = True
    notes: str = ""

    @property
    def is_excluded(self) -> bool:
        """Check if endpoint is excluded from the matrix."""
        return (self.endpoint.path, self.endpoint.method) in EXCLUDED_ENDPOINTS

    @property
    def is_streaming_excluded(self) -> bool:
        """Check if endpoint is excluded from streaming consideration."""
        return (self.endpoint.path, self.endpoint.method) in STREAMING_EXCLUDED

    @property
    def coverage_status(self) -> str:
        """Calculate coverage status."""
        if self.endpoint.internal or self.is_excluded:
            return "n/a"
        if not self.sdk_method:
            return "missing"
        # For streaming-excluded endpoints, only check parameters
        if self.is_streaming_excluded:
            if self.missing_params:
                return "partial"
            return "full"
        # For regular endpoints, check both parameters and streaming
        if self.missing_params or not self.streaming_match:
            return "partial"
        return "full"


@dataclass
class CategoryStats:
    """Statistics for a category of endpoints."""

    name: str
    total: int = 0
    full: int = 0
    partial: int = 0
    missing: int = 0
    na: int = 0

    @property
    def coverage_percentage(self) -> float:
        """Calculate coverage percentage (excluding N/A)."""
        applicable = self.total - self.na
        if applicable == 0:
            return 100.0
        supported = self.full + self.partial
        return (supported / applicable) * 100.0


@dataclass
class ComparisonResult:
    """Complete comparison result."""

    horizon_release: HorizonRelease
    sdk_version: str
    matches: List[EndpointMatch] = field(default_factory=list)
    categories: List[CategoryStats] = field(default_factory=list)
    overall_coverage: float = 0.0
    streaming_coverage: float = 0.0

    def calculate_statistics(self) -> None:
        """Calculate all statistics from matches."""
        category_map: Dict[str, CategoryStats] = {}

        for match in self.matches:
            if match.is_excluded:
                continue

            category = match.endpoint.category
            if category not in category_map:
                category_map[category] = CategoryStats(name=category)

            stats = category_map[category]
            stats.total += 1

            status = match.coverage_status
            if status == "full":
                stats.full += 1
            elif status == "partial":
                stats.partial += 1
            elif status == "missing":
                stats.missing += 1
            elif status == "n/a":
                stats.na += 1

        self.categories = sorted(category_map.values(), key=lambda x: x.name)

        # Overall coverage
        total_applicable = sum(c.total - c.na for c in self.categories)
        total_supported = sum(c.full + c.partial for c in self.categories)
        self.overall_coverage = (
            (total_supported / total_applicable * 100.0) if total_applicable > 0 else 0.0
        )

        # Streaming coverage - exclude streaming-excluded endpoints
        streaming_endpoints = [
            m
            for m in self.matches
            if m.endpoint.streaming
            and not m.endpoint.internal
            and not m.is_excluded
            and not m.is_streaming_excluded
        ]
        streaming_implemented = [m for m in streaming_endpoints if m.streaming_match]
        self.streaming_coverage = (
            (len(streaming_implemented) / len(streaming_endpoints) * 100.0)
            if streaming_endpoints
            else 0.0
        )


class HorizonVersionFetcher:
    """Fetches Horizon release information from GitHub and downloads router.go."""

    RELEASES_API = "https://api.github.com/repos/stellar/stellar-horizon/releases/latest"
    RELEASES_LIST_API = "https://api.github.com/repos/stellar/stellar-horizon/releases"
    ROUTER_RAW_URL = (
        "https://raw.githubusercontent.com/stellar/stellar-horizon/{ref}/internal/httpx/router.go"
    )
    USER_AGENT = "stellar-php-sdk-matrix-generator/1.0"

    def __init__(self, timeout: int = 30):
        self.timeout = timeout

    def get_latest_release(self) -> HorizonRelease:
        """
        Fetch the latest Horizon release from GitHub.

        Returns:
            HorizonRelease object with version information.

        Raises:
            ValueError: If the release cannot be fetched or parsed.
        """
        logger.info("Fetching latest Horizon release from GitHub")
        try:
            req = Request(self.RELEASES_API)
            req.add_header("User-Agent", self.USER_AGENT)
            req.add_header("Accept", "application/vnd.github.v3+json")
            with urlopen(req, timeout=self.timeout) as response:
                data = json.loads(response.read().decode("utf-8"))
            return self._parse_release(data)
        except HTTPError as e:
            raise ValueError(f"HTTP error fetching releases: {e.code} {e.reason}") from e
        except URLError as e:
            raise ValueError(f"Network error fetching releases: {e.reason}") from e
        except Exception as e:
            raise ValueError(f"Error fetching latest release: {e}") from e

    def get_release(self, version: str) -> HorizonRelease:
        """
        Fetch a specific Horizon release by version.

        Args:
            version: Version string (e.g., "v25.0.0")

        Returns:
            HorizonRelease object.

        Raises:
            ValueError: If the release cannot be found.
        """
        logger.info(f"Fetching Horizon release {version}")
        if not version.startswith("v"):
            version = f"v{version}"
        try:
            req = Request(self.RELEASES_LIST_API)
            req.add_header("User-Agent", self.USER_AGENT)
            req.add_header("Accept", "application/vnd.github.v3+json")
            with urlopen(req, timeout=self.timeout) as response:
                data = json.loads(response.read().decode("utf-8"))
            for release in data:
                if release.get("tag_name") == version:
                    return self._parse_release(release)
            raise ValueError(f"Release {version} not found")
        except HTTPError as e:
            raise ValueError(f"HTTP error fetching release: {e.code} {e.reason}") from e
        except URLError as e:
            raise ValueError(f"Network error fetching release: {e.reason}") from e
        except ValueError:
            raise
        except Exception as e:
            raise ValueError(f"Error fetching release {version}: {e}") from e

    def _parse_release(self, release_data: dict) -> HorizonRelease:
        """Parse GitHub release data into a HorizonRelease object."""
        tag_name = release_data.get("tag_name", "")
        version_match = re.search(r"v(\d+\.\d+\.\d+)", tag_name)
        if version_match:
            version = f"v{version_match.group(1)}"
        else:
            version = tag_name

        release_date = release_data.get("published_at", "")
        if release_date:
            release_date = release_date.split("T")[0]

        return HorizonRelease(
            version=version,
            tag_name=tag_name,
            release_date=release_date,
            html_url=release_data.get("html_url", ""),
        )

    def fetch_router(self, ref: str = "master") -> str:
        """
        Fetch router.go content from GitHub.

        Args:
            ref: Git reference (tag, branch, or commit).

        Returns:
            router.go file content.

        Raises:
            ValueError: If the file cannot be fetched.
        """
        url = self.ROUTER_RAW_URL.format(ref=ref)
        logger.info(f"Fetching router.go from {url}")
        try:
            req = Request(url)
            req.add_header("User-Agent", self.USER_AGENT)
            with urlopen(req, timeout=self.timeout) as response:
                content = response.read().decode("utf-8")
            logger.info(f"Successfully fetched router.go ({len(content)} bytes)")
            return content
        except HTTPError as e:
            if e.code == 404:
                raise ValueError(f"router.go not found at {url}") from e
            raise ValueError(f"HTTP error fetching router.go: {e.code} {e.reason}") from e
        except URLError as e:
            raise ValueError(f"Network error fetching router.go: {e.reason}") from e
        except Exception as e:
            raise ValueError(f"Error fetching router.go: {e}") from e


class GoSourceParser:
    """Parses Horizon's Go Chi router to extract endpoint definitions."""

    def parse_router(self, content: str) -> List[HorizonEndpoint]:
        """
        Parse Go Chi router content to extract endpoints.

        Args:
            content: router.go file content.

        Returns:
            List of HorizonEndpoint objects.

        Note: Some Horizon endpoints may be defined in non-standard ways that this
        parser may miss. Known potentially missing patterns:
        - /operations (list all operations)
        - /payments (list all payments)
        - /accounts/{id}/operations
        - /accounts/{id}/payments
        - /ledgers/{id}/operations
        - /ledgers/{id}/payments
        - /transactions/{id}/operations

        These endpoints exist in the Horizon API but may not be extracted if they use
        handler delegation or non-standard routing patterns.
        """
        logger.info("Parsing router.go to extract endpoints")

        endpoints: List[HorizonEndpoint] = []
        route_stack: List[str] = []
        lines = content.split("\n")
        i = 0

        while i < len(lines):
            line = lines[i].strip()

            # Match Route() calls: r.Route("/path", func(r chi.Router) {
            route_match = re.match(
                r'r\.Route\("([^"]+)",\s*func\(r\s+chi\.Router\)\s*\{', line
            )
            if route_match:
                path = route_match.group(1)
                route_stack.append(path)
                i += 1
                continue

            # Match closing braces (end of Route)
            if line == "})" and route_stack:
                route_stack.pop()
                i += 1
                continue

            # Match r.Get/Post/Put/Delete/Patch("/path", handler)
            method_match = re.match(
                r'r\.(Get|Post|Put|Delete|Patch)\("([^"]+)",\s*([^)]+)\)', line
            )
            if method_match:
                http_method = method_match.group(1).upper()
                path = method_match.group(2)
                handler = method_match.group(3).strip()
                endpoints.append(
                    self._build_endpoint(http_method, path, handler, route_stack)
                )
                i += 1
                continue

            # Match r.Method(http.MethodGet, "/path", handler) or with middleware
            method_call_match = re.search(
                r"\.Method\s*\(\s*http\.Method(\w+)\s*,\s*\"([^\"]+)\"", line
            )

            # Also handle multi-line .Method() where method and path are on different lines
            if not method_call_match and ".Method(" in line and i + 3 < len(lines):
                multi_lines = " ".join(
                    [line] + [lines[j].strip() for j in range(i + 1, min(i + 4, len(lines)))]
                )
                method_call_match = re.search(
                    r"\.Method\s*\(\s*http\.Method(\w+)\s*,\s*\"([^\"]+)\"", multi_lines
                )

            if method_call_match:
                http_method = method_call_match.group(1).upper()
                path = method_call_match.group(2)
                handler_match = re.search(r"actions\.(\w+)", line)
                handler = handler_match.group(1) if handler_match else f"{http_method}Handler"
                endpoints.append(
                    self._build_endpoint(http_method, path, handler, route_stack)
                )
                i += 1
                continue

            i += 1

        logger.info(f"Extracted {len(endpoints)} endpoints from router.go")
        return endpoints

    def _build_endpoint(
        self,
        http_method: str,
        path: str,
        handler: str,
        route_stack: List[str],
    ) -> HorizonEndpoint:
        """Build a HorizonEndpoint from parsed router components."""
        full_path = "".join(route_stack) + path
        path_params = self._extract_path_params(full_path)
        full_path = self._clean_path(full_path)
        return HorizonEndpoint(
            path=full_path,
            method=http_method,
            handler=handler,
            category=self._determine_category(full_path),
            streaming=self._supports_streaming(handler, full_path, http_method),
            path_params=path_params,
            internal=self._is_internal_endpoint(full_path, handler),
        )

    def _determine_category(self, path: str) -> str:
        """Determine endpoint category from path based on primary resource."""
        path = path.lower()

        if path in ("/", "/health"):
            return "Root"

        if (
            path.startswith("/metrics")
            or path.startswith("/debug")
            or path.startswith("/ingestion")
        ):
            return "Internal/Admin"

        if path.startswith("/accounts"):
            return "Accounts"
        if path.startswith("/claimable_balances"):
            return "Claimable Balances"
        if path.startswith("/liquidity_pools"):
            return "Liquidity Pools"
        if path.startswith("/ledgers"):
            return "Ledgers"
        if path.startswith("/transactions"):
            return "Transactions"
        if path.startswith("/operations"):
            return "Operations"
        if path.startswith("/payments"):
            return "Payments"
        if path.startswith("/effects"):
            return "Effects"
        if path.startswith("/trade_aggregations"):
            return "Trades"
        if path.startswith("/trades"):
            return "Trades"
        if path.startswith("/offers"):
            return "Offers"
        if path.startswith("/assets"):
            return "Assets"
        if path.startswith("/paths"):
            return "Paths"
        if path.startswith("/order_book"):
            return "Order Book"
        if path.startswith("/fee_stats"):
            return "Network"
        if path.startswith("/friendbot"):
            return "Friendbot"

        return "Other"

    def _extract_path_params(self, path: str) -> List[str]:
        """Extract path parameter names from a path template."""
        return re.findall(r"\{([^}:]+)(?::[^}]+)?\}", path)

    def _clean_path(self, path: str) -> str:
        """Remove regex patterns from path parameters and trailing slashes."""
        cleaned = re.sub(r"\{([^}:]+):[^}]+\}", r"{\1}", path)
        if cleaned != "/" and cleaned.endswith("/"):
            cleaned = cleaned.rstrip("/")
        return cleaned

    def _is_internal_endpoint(self, path: str, handler: str) -> bool:
        """Check if the endpoint is internal/admin only."""
        internal_prefixes = ["/metrics", "/debug", "/ingestion"]
        return any(path.startswith(prefix) for prefix in internal_prefixes)

    def _supports_streaming(self, handler: str, path: str, method: str = "GET") -> bool:
        """
        Determine if an endpoint supports SSE streaming.

        POST endpoints never stream. GET collection and certain detail endpoints do.
        """
        if method == "POST":
            return False

        streaming_patterns = [
            "/accounts/{",
            "/ledgers",
            "/transactions",
            "/operations",
            "/payments",
            "/effects",
            "/trades",
            "/offers",
            "/order_book",
            "/claimable_balances/{",
            "/liquidity_pools/{",
        ]
        return any(pattern in path for pattern in streaming_patterns)


class PHPServiceAnalyzer:
    """
    Analyzes PHP SDK RequestBuilder classes to determine which Horizon endpoints
    and query parameters the SDK implements.

    Rather than attempting to dynamically discover coverage from PHP source code
    (which would require a PHP parser), this class uses the pre-built PHP_SDK_PARAMS
    mapping and the static STREAMING_SUPPORT table to produce SDKMethod objects that
    the comparator can use.
    """

    # Maps normalized endpoint path (with {id} placeholders) to the PHP method
    # that performs the query, in the format "BuilderClass::method()".
    ENDPOINT_TO_METHOD: Dict[Tuple[str, str], str] = {
        ("/", "GET"): "RootRequestBuilder::getRoot()",
        ("/health", "GET"): "HealthRequestBuilder::getHealth()",

        ("/accounts", "GET"): "AccountsRequestBuilder::execute()",
        ("/accounts/{id}", "GET"): "AccountsRequestBuilder::account()",
        ("/accounts/{id}/data/{id}", "GET"): "AccountsRequestBuilder::accountData()",
        ("/accounts/{id}/offers", "GET"): "OffersRequestBuilder::forAccount()::execute()",
        ("/accounts/{id}/trades", "GET"): "TradesRequestBuilder::forAccount()::execute()",
        ("/accounts/{id}/effects", "GET"): "EffectsRequestBuilder::forAccount()::execute()",
        ("/accounts/{id}/operations", "GET"): "OperationsRequestBuilder::forAccount()::execute()",
        ("/accounts/{id}/payments", "GET"): "PaymentsRequestBuilder::forAccount()::execute()",
        ("/accounts/{id}/transactions", "GET"): "TransactionsRequestBuilder::forAccount()::execute()",

        ("/assets", "GET"): "AssetsRequestBuilder::execute()",

        ("/claimable_balances", "GET"): "ClaimableBalancesRequestBuilder::execute()",
        ("/claimable_balances/{id}", "GET"): "ClaimableBalancesRequestBuilder::claimableBalance()",
        ("/claimable_balances/{id}/transactions", "GET"): "TransactionsRequestBuilder::forClaimableBalance()::execute()",
        ("/claimable_balances/{id}/operations", "GET"): "OperationsRequestBuilder::forClaimableBalance()::execute()",

        ("/effects", "GET"): "EffectsRequestBuilder::execute()",

        ("/fee_stats", "GET"): "FeeStatsRequestBuilder::getFeeStats()",

        ("/ledgers", "GET"): "LedgersRequestBuilder::execute()",
        ("/ledgers/{id}", "GET"): "LedgersRequestBuilder::ledger()",
        ("/ledgers/{id}/transactions", "GET"): "TransactionsRequestBuilder::forLedger()::execute()",
        ("/ledgers/{id}/operations", "GET"): "OperationsRequestBuilder::forLedger()::execute()",
        ("/ledgers/{id}/payments", "GET"): "PaymentsRequestBuilder::forLedger()::execute()",
        ("/ledgers/{id}/effects", "GET"): "EffectsRequestBuilder::forLedger()::execute()",

        ("/liquidity_pools", "GET"): "LiquidityPoolsRequestBuilder::execute()",
        ("/liquidity_pools/{id}", "GET"): "LiquidityPoolsRequestBuilder::forPoolId()",
        ("/liquidity_pools/{id}/transactions", "GET"): "TransactionsRequestBuilder::forLiquidityPool()::execute()",
        ("/liquidity_pools/{id}/operations", "GET"): "OperationsRequestBuilder::forLiquidityPool()::execute()",
        ("/liquidity_pools/{id}/effects", "GET"): "EffectsRequestBuilder::forLiquidityPool()::execute()",
        ("/liquidity_pools/{id}/trades", "GET"): "TradesRequestBuilder::forLiquidityPool()::execute()",

        ("/offers", "GET"): "OffersRequestBuilder::execute()",
        ("/offers/{id}", "GET"): "OffersRequestBuilder::offer()",
        ("/offers/{id}/trades", "GET"): "TradesRequestBuilder::forOffer()::execute()",

        ("/operations", "GET"): "OperationsRequestBuilder::execute()",
        ("/operations/{id}", "GET"): "OperationsRequestBuilder::operation()",
        ("/operations/{id}/effects", "GET"): "EffectsRequestBuilder::forOperation()::execute()",

        ("/order_book", "GET"): "OrderBookRequestBuilder::execute()",

        ("/paths", "GET"): "FindPathsRequestBuilder::execute()",
        ("/paths/strict-receive", "GET"): "StrictReceivePathsRequestBuilder::execute()",
        ("/paths/strict-send", "GET"): "StrictSendPathsRequestBuilder::execute()",

        ("/payments", "GET"): "PaymentsRequestBuilder::execute()",

        ("/trades", "GET"): "TradesRequestBuilder::execute()",
        ("/trade_aggregations", "GET"): "TradeAggregationsRequestBuilder::execute()",

        ("/transactions", "GET"): "TransactionsRequestBuilder::execute()",
        ("/transactions", "POST"): "SubmitTransactionRequestBuilder::execute()",
        ("/transactions/{id}", "GET"): "TransactionsRequestBuilder::transaction()",
        ("/transactions/{id}/operations", "GET"): "OperationsRequestBuilder::forTransaction()::execute()",
        ("/transactions/{id}/payments", "GET"): "PaymentsRequestBuilder::forTransaction()::execute()",
        ("/transactions/{id}/effects", "GET"): "EffectsRequestBuilder::forTransaction()::execute()",

        ("/transactions_async", "POST"): "SubmitAsyncTransactionRequestBuilder::execute()",

        ("/friendbot", "GET"): "StellarSDK::friendBot()",
        ("/friendbot", "POST"): "StellarSDK::friendBot()",
    }

    # Streaming support: maps normalized path (with {id}) to the PHP method name
    # that provides streaming for that endpoint.
    STREAMING_SUPPORT: Dict[str, str] = {
        "/accounts/{id}": "AccountsRequestBuilder::streamAccount()",
        "/accounts/{id}/data/{id}": "AccountsRequestBuilder::streamAccountData()",
        "/transactions": "TransactionsRequestBuilder::stream()",
        "/accounts/{id}/transactions": "TransactionsRequestBuilder::forAccount()::stream()",
        "/claimable_balances/{id}/transactions": "TransactionsRequestBuilder::forClaimableBalance()::stream()",
        "/claimable_balances/{id}/operations": "OperationsRequestBuilder::forClaimableBalance()::stream()",
        "/ledgers/{id}/transactions": "TransactionsRequestBuilder::forLedger()::stream()",
        "/liquidity_pools/{id}/transactions": "TransactionsRequestBuilder::forLiquidityPool()::stream()",
        "/effects": "EffectsRequestBuilder::stream()",
        "/accounts/{id}/effects": "EffectsRequestBuilder::forAccount()::stream()",
        "/ledgers/{id}/effects": "EffectsRequestBuilder::forLedger()::stream()",
        "/operations/{id}/effects": "EffectsRequestBuilder::forOperation()::stream()",
        "/transactions/{id}/effects": "EffectsRequestBuilder::forTransaction()::stream()",
        "/liquidity_pools/{id}/effects": "EffectsRequestBuilder::forLiquidityPool()::stream()",
        "/operations": "OperationsRequestBuilder::stream()",
        "/accounts/{id}/operations": "OperationsRequestBuilder::forAccount()::stream()",
        "/ledgers/{id}/operations": "OperationsRequestBuilder::forLedger()::stream()",
        "/transactions/{id}/operations": "OperationsRequestBuilder::forTransaction()::stream()",
        "/liquidity_pools/{id}/operations": "OperationsRequestBuilder::forLiquidityPool()::stream()",
        "/payments": "PaymentsRequestBuilder::stream()",
        "/accounts/{id}/payments": "PaymentsRequestBuilder::forAccount()::stream()",
        "/ledgers/{id}/payments": "PaymentsRequestBuilder::forLedger()::stream()",
        "/transactions/{id}/payments": "PaymentsRequestBuilder::forTransaction()::stream()",
        "/trades": "TradesRequestBuilder::stream()",
        "/accounts/{id}/trades": "TradesRequestBuilder::forAccount()::stream()",
        "/liquidity_pools/{id}/trades": "TradesRequestBuilder::forLiquidityPool()::stream()",
        "/offers": "OffersRequestBuilder::stream()",
        "/accounts/{id}/offers": "OffersRequestBuilder::forAccount()::stream()",
        "/offers/{id}/trades": "TradesRequestBuilder::forOffer()::stream()",
        "/ledgers": "LedgersRequestBuilder::stream()",
        "/order_book": "OrderBookRequestBuilder::stream()",
    }

    def __init__(self, sdk_root: Path):
        self.sdk_root = sdk_root
        self.requests_path = sdk_root / "Soneso" / "StellarSDK" / "Requests"

        if not self.requests_path.exists():
            raise ValueError(f"Requests path not found: {self.requests_path}")

    def analyze(self) -> Dict[str, List[SDKMethod]]:
        """
        Build the SDK method map from static data and return it grouped by builder class.

        Returns:
            Dictionary mapping builder class name to list of SDKMethod objects.
        """
        logger.info("Building PHP SDK method map from RequestBuilder analysis")

        # Verify the RequestBuilder files exist on disk to confirm SDK root is correct
        builder_files = list(self.requests_path.glob("*RequestBuilder.php"))
        logger.info(f"Found {len(builder_files)} RequestBuilder files in {self.requests_path}")

        all_methods: Dict[str, List[SDKMethod]] = {}

        for (norm_path, method), php_method in self.ENDPOINT_TO_METHOD.items():
            # Extract builder class from the method string (up to "::")
            builder_class = php_method.split("::")[0]

            sdk_method = SDKMethod(
                name=php_method,
                builder_class=builder_class,
                file_path=str(self.requests_path / f"{builder_class}.php"),
                horizon_endpoint=norm_path,
                http_method=method,
                streaming=norm_path in self.STREAMING_SUPPORT,
            )

            if builder_class not in all_methods:
                all_methods[builder_class] = []
            all_methods[builder_class].append(sdk_method)

        total = sum(len(v) for v in all_methods.values())
        logger.info(f"Mapped {total} endpoint/method pairs across {len(all_methods)} builder classes")

        return all_methods


class EndpointComparator:
    """Compares Horizon endpoints with PHP SDK methods and builds EndpointMatch objects."""

    # Known mappings for endpoints that are handled outside the standard RequestBuilder pattern
    KNOWN_MAPPINGS: Dict[Tuple[str, str], Dict[str, str]] = {
        ("/friendbot", "GET"): {
            "sdk_method": "StellarSDK::friendBot()",
            "notes": "External friendbot URL",
        },
        ("/friendbot", "POST"): {
            "sdk_method": "StellarSDK::friendBot()",
            "notes": "External friendbot URL (GET used instead)",
        },
        ("/", "GET"): {
            "sdk_method": "StellarSDK (configuration)",
            "notes": "Via SDK initialization",
        },
    }

    def compare(
        self,
        horizon_endpoints: List[HorizonEndpoint],
        sdk_methods: Dict[str, List[SDKMethod]],
        horizon_release: HorizonRelease,
        sdk_version: str,
    ) -> ComparisonResult:
        """
        Compare Horizon endpoints with the PHP SDK implementation.

        Args:
            horizon_endpoints: List of endpoints extracted from router.go.
            sdk_methods: Dictionary of SDK methods by builder class.
            horizon_release: Horizon release metadata.
            sdk_version: PHP SDK version string.

        Returns:
            ComparisonResult with matches and statistics.
        """
        logger.info("Comparing Horizon endpoints with PHP SDK implementation")

        result = ComparisonResult(
            horizon_release=horizon_release,
            sdk_version=sdk_version,
        )

        # Flatten SDK methods
        all_sdk_methods: List[SDKMethod] = []
        for methods in sdk_methods.values():
            all_sdk_methods.extend(methods)

        for endpoint in horizon_endpoints:
            match = self._match_endpoint(endpoint, all_sdk_methods)
            result.matches.append(match)

        result.calculate_statistics()

        logger.info(f"Overall coverage: {result.overall_coverage:.1f}%")
        logger.info(f"Streaming coverage: {result.streaming_coverage:.1f}%")

        return result

    def _match_endpoint(
        self,
        endpoint: HorizonEndpoint,
        sdk_methods: List[SDKMethod],
    ) -> EndpointMatch:
        """Match a single Horizon endpoint to an SDK method."""
        match = EndpointMatch(endpoint=endpoint)

        # Check known mappings first
        key = (endpoint.path, endpoint.method)
        if key in self.KNOWN_MAPPINGS:
            mapping = self.KNOWN_MAPPINGS[key]
            match.sdk_method = SDKMethod(
                name=mapping["sdk_method"],
                builder_class="",
                file_path="",
                horizon_endpoint=endpoint.path,
                http_method=endpoint.method,
            )
            match.notes = mapping["notes"]
            return match

        normalized_horizon = self._normalize_path(endpoint.path)

        # Find matching SDK methods
        candidates = [
            m
            for m in sdk_methods
            if self._normalize_path(m.horizon_endpoint) == normalized_horizon
            and m.http_method == endpoint.method
        ]

        if not candidates:
            match.notes = "No SDK method found for this endpoint"
            return match

        sdk_method = candidates[0]
        match.sdk_method = sdk_method

        # Compare query parameters using the canonical parameter key
        normalized_param_path = self.normalize_endpoint_path(endpoint.path)
        missing_params, extra_params, _ = compare_params(
            normalized_param_path, endpoint.method
        )
        match.missing_params = missing_params
        match.extra_params = extra_params

        # Check streaming support
        notes_parts: List[str] = []
        if endpoint.streaming:
            streaming_method = PHPServiceAnalyzer.STREAMING_SUPPORT.get(normalized_horizon)
            if streaming_method:
                match.streaming_match = True
                notes_parts.append(streaming_method)
            else:
                match.streaming_match = False
                notes_parts.append("No streaming")

        if missing_params:
            notes_parts.append(f"Missing: {', '.join(missing_params)}")

        match.notes = "; ".join(notes_parts) if notes_parts else "-"

        return match

    @staticmethod
    def normalize_endpoint_path(path: str) -> str:
        """
        Normalize endpoint path parameter names to match the keys used in
        HORIZON_PARAMS (e.g., {account_id}, {ledger_id}, {tx_id}, etc.).
        """
        if "/liquidity_pools/" in path:
            path = re.sub(r"\{[^}]+\}", "{liquidity_pool_id}", path, count=1)
        if "/accounts/" in path and "/accounts/{" in path:
            path = re.sub(r"/accounts/\{[^}]+\}", "/accounts/{account_id}", path)
        if "/ledgers/" in path and "/ledgers/{" in path:
            path = re.sub(r"/ledgers/\{[^}]+\}", "/ledgers/{ledger_id}", path)
        if "/transactions/" in path and "/transactions/{" in path:
            path = re.sub(r"/transactions/\{[^}]+\}", "/transactions/{tx_id}", path)
        if "/operations/" in path and "/operations/{" in path:
            path = re.sub(r"/operations/\{[^}]+\}", "/operations/{op_id}", path)
        if "/offers/" in path and "/offers/{" in path:
            path = re.sub(r"/offers/\{[^}]+\}", "/offers/{offer_id}", path)
        if "/claimable_balances/" in path and "/claimable_balances/{" in path:
            path = re.sub(r"/claimable_balances/\{[^}]+\}", "/claimable_balances/{id}", path)
        if "/data/" in path:
            path = re.sub(r"/data/\{[^}]+\}", "/data/{key}", path)
        return path

    def _normalize_path(self, path: str) -> str:
        """
        Normalize path for comparison by replacing all {param} tokens with {id}.

        Examples:
            /accounts/{account_id} -> /accounts/{id}
            /ledgers/{ledger_id}/transactions -> /ledgers/{id}/transactions
        """
        normalized = re.sub(r"\{[^}]+\}", "{id}", path)
        if normalized != "/" and normalized.endswith("/"):
            normalized = normalized.rstrip("/")
        return normalized


class MatrixRenderer:
    """Renders comparison results as markdown."""

    def render(self, result: ComparisonResult) -> str:
        """
        Render a complete compatibility matrix.

        Args:
            result: ComparisonResult to render.

        Returns:
            Markdown formatted string.
        """
        sections = [
            self._render_header(result),
            self._render_overall_coverage(result),
            self._render_coverage_by_category(result),
            self._render_streaming_summary(result),
            self._render_compatibility_matrix(result),
            self._render_parameter_coverage(result),
            self._render_legend(),
        ]
        return "\n\n".join(sections)

    def _render_header(self, result: ComparisonResult) -> str:
        """Render document header with version information."""
        horizon = result.horizon_release
        generated = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M:%S UTC")

        all_endpoints = len(result.matches)
        excluded_count = sum(1 for m in result.matches if m.is_excluded)
        public_endpoints = all_endpoints - excluded_count

        exclusion_lines: List[str] = []
        if excluded_count > 0:
            exclusion_lines.append(
                f"> **Note:** {excluded_count} endpoint{'s' if excluded_count != 1 else ''} "
                f"intentionally excluded from the matrix:"
            )
            for (path, method), reason in EXCLUDED_ENDPOINTS.items():
                exclusion_lines.append(f"> - `{method} {path}` - {reason}")

        br = "  "  # Two spaces for markdown line break
        lines = [
            "# Horizon API vs PHP SDK Compatibility Matrix",
            "",
            f"**Horizon Version:** {horizon.version} (released {horizon.release_date}){br}",
            f"**Horizon Source:** [{horizon.version}]({horizon.html_url}){br}",
            f"**SDK Version:** {result.sdk_version}{br}",
            f"**Generated:** {generated}",
            "",
            f"**Horizon Endpoints Discovered:** {all_endpoints}{br}",
            f"**Public API Endpoints (in matrix):** {public_endpoints}",
        ]

        if exclusion_lines:
            lines.append("")
            lines.extend(exclusion_lines)

        return "\n".join(lines)

    def _render_legend(self) -> str:
        """Render legend at bottom of document."""
        return (
            "## Legend\n\n"
            "- **Full** - Complete implementation with all features\n"
            "- **Partial** - Basic functionality with some limitations\n"
            "- **Missing** - Endpoint not implemented"
        )

    def _render_overall_coverage(self, result: ComparisonResult) -> str:
        """Render overall coverage summary."""
        total_endpoints = sum(c.total for c in result.categories)
        total_na = sum(c.na for c in result.categories)
        public_endpoints = total_endpoints - total_na
        full_supported = sum(c.full for c in result.categories)
        partial_supported = sum(c.partial for c in result.categories)
        missing = sum(c.missing for c in result.categories)

        return (
            "## Overall Coverage\n\n"
            f"**Coverage:** {result.overall_coverage:.1f}% "
            f"({full_supported + partial_supported}/{public_endpoints} public API endpoints)\n\n"
            f"- **Fully Supported:** {full_supported}/{public_endpoints}\n"
            f"- **Partially Supported:** {partial_supported}/{public_endpoints}\n"
            f"- **Not Supported:** {missing}/{public_endpoints}"
        )

    def _render_parameter_coverage(self, result: ComparisonResult) -> str:
        """Render query parameter support summary."""
        endpoints_with_sdk = [
            m
            for m in result.matches
            if m.sdk_method and not m.endpoint.internal and not m.is_excluded
        ]

        total_with_params = 0
        fully_implemented = 0
        missing_params_list: List[Dict] = []

        for match in endpoints_with_sdk:
            normalized_path = EndpointComparator.normalize_endpoint_path(match.endpoint.path)
            horizon_params = HORIZON_PARAMS.get((normalized_path, match.endpoint.method), [])

            if not horizon_params:
                continue

            total_with_params += 1
            if not match.missing_params:
                fully_implemented += 1
            else:
                missing_params_list.append(
                    {
                        "endpoint": match.endpoint.path,
                        "method": match.endpoint.method,
                        "missing": match.missing_params,
                    }
                )

        param_coverage_pct = (
            (fully_implemented / total_with_params * 100.0) if total_with_params > 0 else 0.0
        )

        lines = ["## Query Parameter Support"]
        lines.append(
            f"\n**Filter Parameters Coverage:** {fully_implemented}/{total_with_params} "
            f"({param_coverage_pct:.1f}%)"
        )

        if missing_params_list:
            lines.append("\n### Missing Filter Parameters")
            lines.append("| Endpoint | Method | Missing Parameters |")
            lines.append("|----------|--------|-------------------|")

            missing_params_list.sort(key=lambda x: (-len(x["missing"]), x["endpoint"]))

            for item in missing_params_list:
                endpoint = item["endpoint"]
                method = item["method"]
                missing = ", ".join(item["missing"])
                lines.append(f"| `{endpoint}` | {method} | {missing} |")

        return "\n".join(lines)

    def _render_compatibility_matrix(self, result: ComparisonResult) -> str:
        """Render endpoint compatibility matrix by category."""
        sections = ["## Detailed Endpoint Comparison"]

        category_matches: Dict[str, List[EndpointMatch]] = {}
        for match in result.matches:
            category = match.endpoint.category
            if category not in category_matches:
                category_matches[category] = []
            category_matches[category].append(match)

        for category in sorted(category_matches.keys()):
            matches = category_matches[category]
            section = self._render_category_table(category, matches)
            sections.append(section)

        return "\n\n".join(sections)

    def _render_category_table(self, category: str, matches: List[EndpointMatch]) -> str:
        """Render table for a single category."""
        formatted_category = category.replace("_", " ").title()
        lines = [f"### {formatted_category}", ""]
        lines.append("| Endpoint | Method | Status | SDK Method | Streaming | Notes |")
        lines.append("|----------|--------|--------|------------|-----------|-------|")

        for match in matches:
            if match.is_excluded:
                continue

            endpoint = match.endpoint
            status = match.coverage_status

            status_text = {
                "full": "Full",
                "partial": "Partial",
                "missing": "Missing",
            }.get(status, "N/A")

            streaming = "Yes" if endpoint.streaming and match.streaming_match else ""
            sdk_method = match.sdk_method.name if match.sdk_method else "-"
            notes = match.notes if match.notes else ""

            lines.append(
                f"| `{endpoint.path}` | {endpoint.method} | {status_text} "
                f"| `{sdk_method}` | {streaming} | {notes} |"
            )

        return "\n".join(lines)

    def _render_streaming_summary(self, result: ComparisonResult) -> str:
        """Render streaming support summary."""
        streaming_endpoints = [
            m
            for m in result.matches
            if m.endpoint.streaming
            and not m.endpoint.internal
            and not m.is_excluded
            and not m.is_streaming_excluded
        ]
        implemented = [m for m in streaming_endpoints if m.streaming_match]

        coverage = (
            (len(implemented) / len(streaming_endpoints) * 100) if streaming_endpoints else 0
        )

        return (
            "## Streaming Support\n\n"
            f"**Coverage:** {coverage:.1f}%\n\n"
            f"- Streaming endpoints: {len(streaming_endpoints)}\n"
            f"- Supported: {len(implemented)}"
        )

    def _render_coverage_by_category(self, result: ComparisonResult) -> str:
        """Render coverage statistics by category."""
        lines = ["## Coverage by Category", ""]
        lines.append("| Category | Coverage | Supported | Not Supported | Total |")
        lines.append("|----------|----------|-----------|---------------|-------|")

        for category in result.categories:
            if category.name == "Internal/Admin":
                continue

            applicable = category.total - category.na
            supported = category.full + category.partial
            not_supported = category.missing
            coverage = category.coverage_percentage

            lines.append(
                f"| {category.name.lower()} | {coverage:.1f}% | {supported} "
                f"| {not_supported} | {applicable} |"
            )

        return "\n".join(lines)


class HorizonMatrixGenerator:
    """Main generator orchestrator."""

    DEFAULT_OUTPUT = "compatibility/horizon/COMPATIBILITY_MATRIX.md"

    def __init__(self, sdk_root: Path, verbose: bool = False):
        self.sdk_root = sdk_root

        if verbose:
            logger.setLevel(logging.DEBUG)

        self.fetcher = HorizonVersionFetcher()
        self.parser = GoSourceParser()
        self.analyzer = PHPServiceAnalyzer(sdk_root)
        self.comparator = EndpointComparator()
        self.renderer = MatrixRenderer()

    def generate(
        self,
        horizon_version: Optional[str] = None,
        output_path: Optional[str] = None,
        skip_api: bool = False,
    ) -> int:
        """
        Generate the compatibility matrix.

        Args:
            horizon_version: Specific Horizon version (None for latest).
            output_path: Output file path (None for default).
            skip_api: Skip GitHub API calls (use with --horizon-version).

        Returns:
            Exit code (0 for success, 1 for failure).
        """
        try:
            # Resolve Horizon release
            if skip_api:
                version = horizon_version or "v25.0.0"
                horizon_release = HorizonRelease(
                    version=version,
                    tag_name=version,
                    release_date="unknown",
                    html_url=f"https://github.com/stellar/stellar-horizon/releases/tag/{version}",
                )
                logger.info(f"Using manual version info: {version} (--skip-api mode)")
            elif horizon_version:
                horizon_release = self.fetcher.get_release(horizon_version)
            else:
                horizon_release = self.fetcher.get_latest_release()

            logger.info(f"Using Horizon {horizon_release.version}")

            # Fetch and parse router.go
            router_content = self.fetcher.fetch_router(horizon_release.ref)
            horizon_endpoints = self.parser.parse_router(router_content)

            # Analyze PHP SDK
            sdk_methods = self.analyzer.analyze()

            # Compare
            sdk_version = get_sdk_version(self.sdk_root)
            result = self.comparator.compare(
                horizon_endpoints,
                sdk_methods,
                horizon_release,
                sdk_version,
            )

            # Render
            markdown = self.renderer.render(result)

            # Write output
            if output_path is None:
                resolved_output = self.sdk_root / self.DEFAULT_OUTPUT
            else:
                resolved_output = Path(output_path)

            resolved_output.parent.mkdir(parents=True, exist_ok=True)
            resolved_output.write_text(markdown, encoding="utf-8")

            logger.info(f"Generated matrix: {resolved_output}")
            logger.info(f"Coverage: {result.overall_coverage:.1f}%")

            return 0

        except Exception as e:
            logger.error(f"Error generating matrix: {e}", exc_info=True)
            return 1


def main() -> int:
    """Main entry point."""
    parser = argparse.ArgumentParser(
        description="Generate Horizon API compatibility matrix for Stellar PHP SDK",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  # Generate matrix against latest Horizon
  python generate_horizon_matrix.py

  # Specify Horizon version
  python generate_horizon_matrix.py --horizon-version v25.0.0

  # Custom output path
  python generate_horizon_matrix.py --output custom.md

  # Verbose mode
  python generate_horizon_matrix.py --verbose

  # Skip GitHub API (useful with explicit version to avoid rate limits)
  python generate_horizon_matrix.py --horizon-version v25.0.0 --skip-api
        """,
    )

    parser.add_argument(
        "--horizon-version",
        type=str,
        help="Specific Horizon version to compare against (e.g., v25.0.0). Default: latest",
    )

    parser.add_argument(
        "--sdk-root",
        type=str,
        help="Path to the PHP SDK root directory. Default: auto-detected from script location",
    )

    parser.add_argument(
        "--output",
        type=str,
        help=f"Output file path. Default: <sdk-root>/{HorizonMatrixGenerator.DEFAULT_OUTPUT}",
    )

    parser.add_argument(
        "--verbose",
        action="store_true",
        help="Enable verbose logging",
    )

    parser.add_argument(
        "--skip-api",
        action="store_true",
        help="Skip GitHub API calls (use with --horizon-version to avoid rate limits)",
    )

    args = parser.parse_args()

    # Determine SDK root: explicit arg, or 4 levels up from this script
    if args.sdk_root:
        sdk_root = Path(args.sdk_root)
    else:
        # Script is at tools/matrix-generator/horizon/generate_horizon_matrix.py
        # SDK root is 4 levels up
        sdk_root = Path(__file__).parent.parent.parent.parent

    generator = HorizonMatrixGenerator(
        sdk_root=sdk_root,
        verbose=args.verbose,
    )

    return generator.generate(
        horizon_version=args.horizon_version,
        output_path=args.output,
        skip_api=args.skip_api,
    )


if __name__ == "__main__":
    sys.exit(main())
