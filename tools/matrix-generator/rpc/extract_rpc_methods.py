#!/usr/bin/env python3
"""
Extracts RPC method specifications from the stellar-rpc Go source code.

Fetches the latest stellar-rpc source from GitHub and generates a structured
JSON file documenting all RPC methods with their parameters, response fields,
and other metadata.

Usage:
    python extract_rpc_methods.py [--output PATH] [--rpc-version VERSION]
                                  [--go-sdk-path PATH] [--verbose]

Requirements:
    - Python 3.10+
    - requests library (pip install requests)

Authentication:
    To avoid GitHub API rate limits (60 req/hour unauthenticated vs 5,000 authenticated),
    set a GitHub token via one of these methods:

    1. Environment variable: export GITHUB_TOKEN=your_token
    2. gh CLI config: The token is read from ~/.config/gh/hosts.yml if available
    3. Command line: --token YOUR_TOKEN

    To create a token: https://github.com/settings/tokens
    Required scope: No scopes needed for public repo access (just need authentication)
"""

import json
import os
import re
import sys
from dataclasses import dataclass, field, asdict
from datetime import datetime
from pathlib import Path
from typing import Any, Optional

try:
    import requests
except ImportError:
    print("Error: requests library is required. Install with: pip install requests", file=sys.stderr)
    sys.exit(1)


def _go_type_to_json_type(go_type: str) -> str:
    """Convert a Go type string to a JSON type description."""
    go_type = go_type.lstrip('*')

    if go_type.startswith('[]'):
        inner_type = go_type[2:]
        return f"array[{_go_type_to_json_type(inner_type)}]"

    type_map = {
        'string': 'string',
        'bool': 'boolean',
        'int': 'integer',
        'int32': 'int32',
        'int64': 'int64',
        'uint': 'uint',
        'uint32': 'uint32',
        'uint64': 'uint64 (string)',
        'float32': 'float',
        'float64': 'float',
        'json.RawMessage': 'object',
        'time.Time': 'string (RFC3339)',
    }

    return type_map.get(go_type, go_type)


class ResponseStructParser:
    """Parses Go response structs from go-stellar-sdk protocol files."""

    def __init__(self, go_stellar_sdk_path: Path, verbose: bool = False):
        self.protocol_path = go_stellar_sdk_path / "protocols" / "rpc"
        self.verbose = verbose
        self.protocol_cache = {}
        self._load_protocol_files()

    def _load_protocol_files(self):
        """Load all protocol .go files into memory."""
        if not self.protocol_path.exists():
            if self.verbose:
                print(f"Warning: Protocol path not found: {self.protocol_path}")
            return

        for go_file in self.protocol_path.glob("*.go"):
            try:
                content = go_file.read_text(encoding="utf-8")
                self.protocol_cache[go_file.stem] = content
                if self.verbose:
                    print(f"  Loaded protocol file: {go_file.name}")
            except Exception as e:
                if self.verbose:
                    print(f"  Warning: Failed to load {go_file.name}: {e}")

    def parse_response_struct(self, method_name: str) -> dict[str, Any]:
        """Parse the response struct for a given RPC method."""
        struct_names = self._get_response_struct_names(method_name)

        for struct_name in struct_names:
            struct_def = self._find_struct_definition(struct_name)
            if struct_def:
                return self._parse_struct_fields(struct_name, struct_def)

        if self.verbose:
            print(f"  Warning: Response struct not found for {method_name}")
        return {"type": "object", "fields": [], "nested_types": {}}

    def _get_response_struct_names(self, method_name: str) -> list[str]:
        """Get possible response struct names for a method."""
        pascal_case = method_name[0].upper() + method_name[1:]
        return [
            f"{pascal_case}Response",
            f"{pascal_case}Result",
        ]

    def _find_struct_definition(self, struct_name: str) -> Optional[str]:
        """Find a struct definition in loaded protocol files."""
        pattern = rf'type\s+{re.escape(struct_name)}\s+struct\s*\{{([^}}]+(?:\{{[^}}]*\}}[^}}]*)*)\}}'

        for file_content in self.protocol_cache.values():
            match = re.search(pattern, file_content, re.DOTALL)
            if match:
                return match.group(1)

        return None

    def _parse_struct_fields(self, struct_name: str, struct_body: str) -> dict[str, Any]:
        """Parse fields from a struct body."""
        fields = []
        nested_types = {}

        field_pattern = r'(\w+)\s+([\w\[\]\.\*]+)\s*`json:"([^"]+)"([^`]*)`'

        for match in re.finditer(field_pattern, struct_body):
            field_name = match.group(1)
            field_type = match.group(2)
            json_tag = match.group(3)

            json_name = json_tag.split(',')[0]

            if json_name == "-":
                continue

            json_type = _go_type_to_json_type(field_type)

            if self._is_custom_type(field_type):
                nested_type_name = field_type.lstrip('*').lstrip('[').lstrip(']')
                if nested_type_name not in nested_types:
                    nested_struct = self._find_struct_definition(nested_type_name)
                    if nested_struct:
                        nested_types[nested_type_name] = self._parse_struct_fields(
                            nested_type_name, nested_struct
                        )

            fields.append({
                "name": json_name,
                "type": json_type,
                "description": f"Field: {json_name}"
            })

        return {
            "type": "object",
            "fields": fields,
            "nested_types": nested_types
        }

    def _is_custom_type(self, go_type: str) -> bool:
        """Check if a Go type is a custom (non-primitive) type."""
        base_type = go_type.lstrip('*').lstrip('[').lstrip(']')
        primitives = {
            'string', 'bool', 'int', 'int8', 'int16', 'int32', 'int64',
            'uint', 'uint8', 'uint16', 'uint32', 'uint64',
            'float32', 'float64', 'byte', 'rune',
            'json.RawMessage', 'time.Time'
        }
        return base_type not in primitives


# GitHub API configuration
GITHUB_API_BASE = "https://api.github.com"
GITHUB_RAW_BASE = "https://raw.githubusercontent.com"
REPO_OWNER = "stellar"
REPO_NAME = "stellar-rpc"
DEFAULT_BRANCH = "main"

# Method handler directories (different in different versions)
# v21-v22: cmd/soroban-rpc/internal/methods
# v23+: cmd/stellar-rpc/internal/methods
METHODS_DIRS = [
    "cmd/stellar-rpc/internal/methods",  # v23+
    "cmd/soroban-rpc/internal/methods",  # v21-v22
]

# Known RPC method list with their file mappings
KNOWN_METHODS = {
    "getHealth": ["get_health.go", "health.go"],  # v25+ uses get_health.go, v21-v24 uses health.go
    "getNetwork": "get_network.go",
    "getVersionInfo": "get_version_info.go",
    "getFeeStats": "get_fee_stats.go",
    "getLatestLedger": "get_latest_ledger.go",
    "getLedgerEntries": "get_ledger_entries.go",
    "getLedgers": "get_ledgers.go",
    "getEvents": "get_events.go",
    "getTransaction": "get_transaction.go",
    "getTransactions": "get_transactions.go",
    "sendTransaction": "send_transaction.go",
    "simulateTransaction": "simulate_transaction.go",
}

# Cache for GitHub token
_github_token_cache: Optional[str] = None
_github_token_checked: bool = False


def get_github_token() -> Optional[str]:
    """Get GitHub token for authenticated API requests.

    Checks in order:
    1. GITHUB_TOKEN environment variable
    2. gh CLI config file (~/.config/gh/hosts.yml)
    """
    global _github_token_cache, _github_token_checked

    if _github_token_checked:
        return _github_token_cache

    _github_token_checked = True

    token = os.environ.get('GITHUB_TOKEN')
    if token:
        _github_token_cache = token
        return token

    gh_config_path = Path.home() / '.config' / 'gh' / 'hosts.yml'
    if gh_config_path.exists():
        try:
            content = gh_config_path.read_text()
            for line in content.split('\n'):
                if 'oauth_token:' in line:
                    token = line.split('oauth_token:')[1].strip()
                    if token:
                        _github_token_cache = token
                        return token
        except (IOError, IndexError):
            pass

    return None


@dataclass
class Parameter:
    """Represents a method parameter."""
    name: str
    type: str
    description: str
    required: bool = False
    default: Optional[str] = None


@dataclass
class MethodSpec:
    """Represents an RPC method specification."""
    name: str
    description: str
    handler_file: str
    parameters: dict[str, list[Parameter]] = field(default_factory=lambda: {"required": [], "optional": []})
    response: dict[str, Any] = field(default_factory=dict)
    introduced_in: str = ""
    last_modified: str = ""
    notes: str = ""

    def to_dict(self) -> dict[str, Any]:
        """Convert to dictionary."""
        result = {
            "name": self.name,
            "description": self.description,
            "handler_file": self.handler_file,
            "parameters": {
                "required": [asdict(p) for p in self.parameters["required"]],
                "optional": [asdict(p) for p in self.parameters["optional"]]
            },
            "response": self.response,
            "introduced_in": self.introduced_in,
            "last_modified": self.last_modified,
        }
        if self.notes:
            result["notes"] = self.notes
        return result


class GitHubFetcher:
    """Handles fetching files from GitHub."""

    def __init__(self, token: Optional[str] = None, verbose: bool = False):
        self.token = token if token else get_github_token()
        self.verbose = verbose
        self.session = requests.Session()
        self.session.headers.update({"User-Agent": "stellar-php-sdk-matrix-generator/1.0"})
        if self.token:
            self.session.headers.update({"Authorization": f"Bearer {self.token}"})
            if verbose:
                print("Authentication: Enabled (5,000 requests/hour)")
        elif verbose:
            print("Authentication: Not configured (60 requests/hour)")
            print("  Tip: Set GITHUB_TOKEN env var for higher rate limits")

    def fetch_go_stellar_sdk_protocol_file(self, method_name: str, ref: Optional[str] = None) -> Optional[str]:
        """Fetch protocol file from go-stellar-sdk repository."""
        protocol_files = {
            "getLedgerEntries": "get_ledger_entries.go",
            "getLedgers": "get_ledgers.go",
            "getEvents": "get_events.go",
            "getTransaction": "get_transaction.go",
            "getTransactions": "get_transactions.go",
            "sendTransaction": "send_transaction.go",
            "simulateTransaction": "simulate_transaction.go",
            "getHealth": "get_health.go",
            "getNetwork": "get_network.go",
            "getVersionInfo": "get_version_info.go",
            "getFeeStats": "get_fee_stats.go",
            "getLatestLedger": "get_latest_ledger.go",
        }

        protocol_file = protocol_files.get(method_name)
        if not protocol_file:
            return None

        version_ref = ref or "main"
        url = f"{GITHUB_RAW_BASE}/stellar/go-stellar-sdk/{version_ref}/protocols/rpc/{protocol_file}"

        if self.verbose:
            print(f"  Fetching protocol file: protocols/rpc/{protocol_file}")

        try:
            response = self.session.get(url, timeout=30)
            response.raise_for_status()
            return response.text
        except requests.RequestException as e:
            if self.verbose:
                print(f"    Warning: Could not fetch protocol file: {e}")
            return None

    def get_latest_release_version(self) -> str:
        """Fetch the latest release version from GitHub releases."""
        url = f"{GITHUB_API_BASE}/repos/{REPO_OWNER}/{REPO_NAME}/releases"
        try:
            response = self.session.get(url, timeout=30)
            response.raise_for_status()
            releases = response.json()

            for release in releases:
                tag_name = release.get("tag_name", "")
                if tag_name.startswith("v") and not tag_name.startswith("v0."):
                    version = tag_name
                    if self.verbose:
                        print(f"Latest stellar-rpc version: {version}")
                    return version

            if self.verbose:
                print("Warning: No suitable release found, using latest")
            return releases[0].get("tag_name", "unknown") if releases else "unknown"
        except requests.RequestException as e:
            if self.verbose:
                print(f"Warning: Failed to fetch release version: {e}")
            return "unknown"

    def fetch_file(self, file_path: str, ref: Optional[str] = None) -> str:
        """Fetch a file from the repository."""
        version_ref = ref or DEFAULT_BRANCH
        url = f"{GITHUB_RAW_BASE}/{REPO_OWNER}/{REPO_NAME}/{version_ref}/{file_path}"

        if self.verbose:
            print(f"Fetching: {file_path}")

        try:
            response = self.session.get(url, timeout=30)
            response.raise_for_status()
            return response.text
        except requests.RequestException as e:
            raise RuntimeError(f"Failed to fetch {file_path}: {e}") from e

    def list_directory(self, dir_path: str, ref: Optional[str] = None) -> list[dict[str, Any]]:
        """List directory contents via GitHub API."""
        version_ref = ref or DEFAULT_BRANCH
        url = f"{GITHUB_API_BASE}/repos/{REPO_OWNER}/{REPO_NAME}/contents/{dir_path}?ref={version_ref}"

        if self.verbose:
            print(f"Listing directory: {dir_path}")

        try:
            response = self.session.get(url, timeout=30)
            response.raise_for_status()
            return response.json()
        except requests.RequestException as e:
            raise RuntimeError(f"Failed to list directory {dir_path}: {e}") from e


class GoSourceParser:
    """Parses Go source files to extract RPC method specifications."""

    def __init__(self, verbose: bool = False, go_stellar_sdk_path: Optional[Path] = None):
        self.verbose = verbose
        self.protocol_source = None

        self.response_parser = None
        if go_stellar_sdk_path and go_stellar_sdk_path.exists():
            self.response_parser = ResponseStructParser(go_stellar_sdk_path, verbose=verbose)
            if verbose:
                print("ResponseStructParser initialized successfully")

    def set_protocol_source(self, protocol_source: str):
        """Set the protocol file source for extracting external type definitions."""
        self.protocol_source = protocol_source

    def parse_method_handler(self, method_name: str, go_source: str, handler_file: str) -> MethodSpec:
        """Parse a method handler Go file and return a MethodSpec."""
        if self.verbose:
            print(f"  Parsing {method_name}...")

        description = self._extract_description(go_source, method_name)
        parameters = self._extract_parameters(go_source, method_name)
        response = self._extract_response(go_source, method_name)

        return MethodSpec(
            name=method_name,
            description=description,
            handler_file=handler_file,
            parameters=parameters,
            response=response
        )

    def _extract_description(self, go_source: str, method_name: str) -> str:
        """Extract method description from comments."""
        patterns = [
            rf'//\s*(.+?)[\r\n]+func\s+\(.*?\)\s*{re.escape(method_name)}Handler',
            rf'//\s*(.+?)[\r\n]+func\s+New[A-Z]\w*Handler',
            r'//\s*Package\s+methods\s+(.+)',
        ]

        for pattern in patterns:
            match = re.search(pattern, go_source, re.MULTILINE | re.IGNORECASE)
            if match:
                return match.group(1).strip()

        return f"RPC method: {method_name}"

    def _extract_parameters(self, go_source: str, method_name: str) -> dict[str, list[Parameter]]:
        """Extract method parameters from request struct."""
        parameters: dict[str, list[Parameter]] = {"required": [], "optional": []}

        struct_name = self._method_to_struct_name(method_name) + "Request"
        struct_pattern = rf'type\s+{struct_name}\s+struct\s*\{{([^}}]+)\}}'
        match = re.search(struct_pattern, go_source, re.DOTALL)

        if not match and self.protocol_source:
            match = re.search(struct_pattern, self.protocol_source, re.DOTALL)

        if not match:
            if self.verbose:
                print(f"    No request struct found for {method_name} (this is OK for parameter-less methods)")
            return parameters

        struct_body = match.group(1)
        field_pattern = r'(\w+)\s+([\*\[\]]*[\w\.]+(?:\[[\w\.]+\])?)\s*`json:"([^"]+)"([^`]*)`'

        for field_match in re.finditer(field_pattern, struct_body):
            field_name = field_match.group(1)
            field_type = field_match.group(2)
            json_tag = field_match.group(3)
            tags = field_match.group(4)

            json_name = json_tag.split(',')[0]

            if json_name == "-":
                continue

            is_optional = (
                "omitempty" in json_tag or
                field_type.startswith("*") or
                "optional:" in tags
            )

            param_type = _go_type_to_json_type(field_type)
            param_description = self._generate_parameter_description(method_name, json_name, field_type)

            param = Parameter(
                name=json_name,
                type=param_type,
                description=param_description,
                required=not is_optional
            )

            if is_optional:
                parameters["optional"].append(param)
            else:
                parameters["required"].append(param)

        if self.verbose:
            req_count = len(parameters["required"])
            opt_count = len(parameters["optional"])
            print(f"    Found {req_count} required, {opt_count} optional parameters")

        return parameters

    def _generate_parameter_description(self, method_name: str, param_name: str, go_type: str) -> str:
        """Generate a parameter description based on context."""
        descriptions = {
            "keys": "Array of ledger entry keys to fetch (base64-encoded XDR)",
            "hash": "Transaction hash to retrieve",
            "transaction": "Base64-encoded transaction envelope XDR",
            "startLedger": "Starting ledger sequence number (inclusive)",
            "endLedger": "Ending ledger sequence number (exclusive)",
            "filters": "Event filters to apply",
            "pagination": "Pagination options (cursor and limit)",
            "cursor": "Pagination cursor",
            "limit": "Maximum number of results to return",
            "resourceConfig": "Resource configuration for simulation",
            "authMode": "Authorization mode (enforce, record, or record_allow_nonroot)",
            "xdrFormat": "Output format (xdr or json)",
        }

        if param_name in descriptions:
            return descriptions[param_name]

        return f"Parameter: {param_name}"

    def _extract_response(self, go_source: str, method_name: str) -> dict[str, Any]:
        """Extract response structure from response struct."""
        if self.response_parser:
            response_struct = self.response_parser.parse_response_struct(method_name)
            if response_struct and response_struct.get("fields"):
                return response_struct

        struct_names = [
            self._method_to_struct_name(method_name) + "Response",
            self._method_to_struct_name(method_name) + "Result",
        ]

        if method_name == "getHealth":
            struct_names.insert(0, "HealthCheckResult")

        struct_body = None

        for name in struct_names:
            struct_pattern = rf'type\s+{name}\s+struct\s*\{{([^}}]+)\}}'
            match = re.search(struct_pattern, go_source, re.DOTALL)
            if match:
                struct_body = match.group(1)
                break

        if not struct_body and self.protocol_source:
            for name in struct_names:
                struct_pattern = rf'type\s+{name}\s+struct\s*\{{([^}}]+)\}}'
                match = re.search(struct_pattern, self.protocol_source, re.DOTALL)
                if match:
                    struct_body = match.group(1)
                    break

        if not struct_body:
            return {"type": "object", "fields": []}

        fields = []
        field_pattern = r'(\w+)\s+([\w\[\]\.\*]+)\s*`json:"([^"]+)"([^`]*)`'

        for field_match in re.finditer(field_pattern, struct_body):
            field_type = field_match.group(2)
            json_tag = field_match.group(3)

            json_name = json_tag.split(',')[0]

            if json_name == "-":
                continue

            fields.append({
                "name": json_name,
                "type": _go_type_to_json_type(field_type),
                "description": f"Field: {json_name}"
            })

        return {
            "type": "object",
            "fields": fields,
            "nested_types": {}
        }

    def _method_to_struct_name(self, method_name: str) -> str:
        """Convert method name to struct name (e.g. getHealth -> GetHealth)."""
        if not method_name:
            return ""
        return method_name[0].upper() + method_name[1:]


class RPCMethodExtractor:
    """Main extraction orchestrator."""

    def __init__(self, github_token: Optional[str] = None, verbose: bool = False,
                 go_sdk_path: Optional[Path] = None):
        self.fetcher = GitHubFetcher(token=github_token, verbose=verbose)
        self.parser = GoSourceParser(verbose=verbose, go_stellar_sdk_path=go_sdk_path)
        self.verbose = verbose

    def extract(self, rpc_version: Optional[str] = None) -> dict[str, Any]:
        """Extract all RPC methods and generate JSON structure."""
        if self.verbose:
            print("Starting RPC method extraction...")

        if not rpc_version:
            rpc_version = self.fetcher.get_latest_release_version()

        self._fetch_protocol_files(rpc_version)

        methods = {}
        for method_name, file_names in KNOWN_METHODS.items():
            if isinstance(file_names, str):
                file_names = [file_names]

            try:
                method_spec = self._extract_method(method_name, file_names, rpc_version)
                methods[method_name] = method_spec.to_dict()
            except Exception as e:
                if self.verbose:
                    print(f"  Warning: Failed to extract {method_name}: {e}")
                methods[method_name] = self._create_placeholder_method(method_name, file_names[0])

        output = {
            "metadata": {
                "source": "stellar-rpc",
                "repository": f"https://github.com/{REPO_OWNER}/{REPO_NAME}",
                "version": rpc_version,
                "extracted_date": datetime.now().strftime("%Y-%m-%d"),
                "total_methods": len(methods),
                "protocol": "JSON-RPC 2.0",
                "protocol_definitions": "https://github.com/stellar/go-stellar-sdk/tree/main/protocols/rpc"
            },
            "methods": methods
        }

        if self.verbose:
            print(f"\nExtraction complete: {len(methods)} methods")
            print(f"stellar-rpc version: {rpc_version}")

        return output

    def _fetch_protocol_files(self, rpc_version: str):
        """Fetch protocol files and combine them for the parser."""
        protocol_dirs = [
            "protocol",
            "cmd/stellar-rpc/lib/protocol",
        ]

        combined_protocol_source = ""

        for protocol_dir in protocol_dirs:
            try:
                files = self.fetcher.list_directory(protocol_dir, rpc_version)
                if self.verbose:
                    print(f"Found protocol directory: {protocol_dir}")

                for file_info in files:
                    if file_info.get("type") == "file" and file_info.get("name", "").endswith(".go"):
                        file_path = f"{protocol_dir}/{file_info['name']}"
                        try:
                            source = self.fetcher.fetch_file(file_path, rpc_version)
                            combined_protocol_source += "\n\n" + source
                            if self.verbose:
                                print(f"  Loaded protocol file: {file_info['name']}")
                        except Exception:
                            pass

                if combined_protocol_source:
                    break

            except Exception:
                continue

        if combined_protocol_source:
            self.parser.set_protocol_source(combined_protocol_source)
            if self.verbose:
                print("Protocol files loaded successfully")

    def _extract_method(self, method_name: str, file_names: list[str], rpc_version: str) -> MethodSpec:
        """Extract a single method specification."""
        go_source = None
        handler_file = None
        last_error = None

        for file_name in file_names:
            for methods_dir in METHODS_DIRS:
                try:
                    handler_file = f"{methods_dir}/{file_name}"
                    go_source = self.fetcher.fetch_file(handler_file, rpc_version)
                    break
                except Exception as e:
                    last_error = e
                    continue
            if go_source:
                break

        if not go_source:
            raise last_error if last_error else RuntimeError(f"Failed to fetch any of {file_names}")

        protocol_source = self.fetcher.fetch_go_stellar_sdk_protocol_file(method_name, ref="main")
        if protocol_source:
            if self.parser.protocol_source:
                self.parser.protocol_source += "\n\n" + protocol_source
            else:
                self.parser.set_protocol_source(protocol_source)

        return self.parser.parse_method_handler(method_name, go_source, handler_file)

    def _create_placeholder_method(self, method_name: str, file_name: str) -> dict[str, Any]:
        """Create placeholder method spec when extraction fails."""
        return {
            "name": method_name,
            "description": f"RPC method: {method_name}",
            "handler_file": f"{METHODS_DIRS[0]}/{file_name}",
            "parameters": {
                "required": [],
                "optional": []
            },
            "response": {
                "type": "object",
                "fields": []
            },
            "introduced_in": "",
            "last_modified": "",
            "notes": "Extraction failed - manual update needed"
        }


def main() -> int:
    """Main entry point."""
    import argparse

    parser = argparse.ArgumentParser(
        description="Extract RPC method specifications from stellar-rpc repository"
    )
    parser.add_argument(
        "--output",
        "-o",
        type=Path,
        default=Path(__file__).parent / "data" / "rpc_methods.json",
        help="Output JSON file path (default: data/rpc_methods.json)"
    )
    parser.add_argument(
        "--rpc-version",
        type=str,
        help="Specific stellar-rpc version to extract from (default: latest release)"
    )
    parser.add_argument(
        "--go-sdk-path",
        type=Path,
        default=None,
        help="Path to local go-stellar-sdk repository (optional, for protocol file parsing)"
    )
    parser.add_argument(
        "--token",
        "-t",
        type=str,
        help="GitHub personal access token (for higher rate limits)"
    )
    parser.add_argument(
        "--verbose",
        "-v",
        action="store_true",
        help="Enable verbose output"
    )

    args = parser.parse_args()

    try:
        extractor = RPCMethodExtractor(
            github_token=args.token,
            verbose=args.verbose,
            go_sdk_path=args.go_sdk_path,
        )
        data = extractor.extract(rpc_version=args.rpc_version)

        args.output.parent.mkdir(parents=True, exist_ok=True)

        with open(args.output, "w", encoding="utf-8") as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
            f.write("\n")

        print(f"\nSuccessfully extracted {data['metadata']['total_methods']} methods")
        print(f"stellar-rpc version: {data['metadata']['version']}")
        print(f"Output written to: {args.output}")

        return 0

    except Exception as e:
        print(f"Error: {e}", file=sys.stderr)
        if args.verbose:
            import traceback
            traceback.print_exc()
        return 1


if __name__ == "__main__":
    sys.exit(main())
