#!/usr/bin/env python3
"""
Extracts RPC method specifications from the stellar-rpc Go source code.

Reads the method handlers of a stellar-rpc release and the request and response
definitions of the go-stellar-sdk version that the release's go.mod pins, and
writes a JSON file documenting every RPC method with its parameters, response
fields and other metadata.

Usage:
    python extract_rpc_methods.py [--output PATH] [--rpc-version VERSION] [--verbose]

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
import re
import sys
from dataclasses import dataclass, field, asdict
from datetime import datetime
from pathlib import Path
from typing import Any, Iterable, Optional

try:
    import requests
except ImportError:
    print("Error: requests library is required. Install with: pip install requests", file=sys.stderr)
    sys.exit(1)

from rpc_releases import REQUEST_TIMEOUT_SECONDS, STELLAR_RPC_REPO, USER_AGENT, get_github_token, resolve_release


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


def _struct_body(struct_name: str, source: str) -> Optional[str]:
    """Return the body of the first declaration of struct *struct_name* in *source*, or None."""
    match = re.search(rf'type\s+{struct_name}\s+struct\s*\{{([^}}]+)\}}', source, re.DOTALL)
    return match.group(1) if match else None


GITHUB_RAW_BASE = "https://raw.githubusercontent.com"
GO_STELLAR_SDK_REPO = "stellar/go-stellar-sdk"

# Handler directory and method registrations of the releases whose go.mod pins
# go-stellar-sdk (v25.0.0 and later).
METHODS_DIR = "cmd/stellar-rpc/internal/methods"
JSONRPC_FILE = "cmd/stellar-rpc/internal/jsonrpc.go"
_REGISTRATION = re.compile(r"\bmethodName:\s*protocol\.([A-Za-z][A-Za-z0-9]*)MethodName\b")

# The go.mod requirement of go-stellar-sdk. A Go pseudo-version (vX.Y.Z-yyyymmddhhmmss-<hash>
# or vX.Y.Z-0.yyyymmddhhmmss-<hash>) is not a tag; its git ref is the 12-hex commit hash.
_GO_STELLAR_SDK_REQUIREMENT = re.compile(r"github\.com/stellar/go-stellar-sdk\s+(\S+)")
_PSEUDO_VERSION = re.compile(r"v\S*\d{14}-([0-9a-f]{12})")

# A response struct line: a JSON-tagged field, or the name of an embedded struct whose
# fields Go promotes into the enclosing struct.
_TAGGED_FIELD = re.compile(r'(\w+)\s+([\w\[\]\.\*]+)\s*`json:"([^"]+)"([^`]*)`')
_EMBEDDED_STRUCT = re.compile(r"[A-Z]\w*")

# Known RPC methods and their source file, named alike in METHODS_DIR and in
# go-stellar-sdk protocols/rpc.
KNOWN_METHODS = {
    "getHealth": "get_health.go",
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


def check_method_set(method_names: Iterable[str]) -> None:
    """Raise unless the method names registered upstream are exactly KNOWN_METHODS, naming every difference."""
    found = set(method_names)
    expected = set(KNOWN_METHODS)
    missing = sorted(expected - found)
    extra = sorted(found - expected)
    if missing or extra:
        differences = []
        if missing:
            differences.append(f"missing: {', '.join(missing)}")
        if extra:
            differences.append(f"extra: {', '.join(extra)}")
        raise RuntimeError(f"Registered stellar-rpc methods differ from KNOWN_METHODS ({'; '.join(differences)})")


@dataclass
class Parameter:
    """Represents a method parameter."""
    name: str
    type: str
    required: bool = False


@dataclass
class MethodSpec:
    """Represents an RPC method specification."""
    name: str
    handler_file: str
    parameters: dict[str, list[Parameter]] = field(default_factory=lambda: {"required": [], "optional": []})
    response: dict[str, Any] = field(default_factory=dict)

    def to_dict(self) -> dict[str, Any]:
        """Convert to dictionary."""
        return {
            "name": self.name,
            "handler_file": self.handler_file,
            "parameters": {
                "required": [asdict(p) for p in self.parameters["required"]],
                "optional": [asdict(p) for p in self.parameters["optional"]]
            },
            "response": self.response,
        }


class GitHubFetcher:
    """Handles fetching files from GitHub."""

    def __init__(self, token: Optional[str] = None, verbose: bool = False):
        self.token = token if token else get_github_token()
        self.verbose = verbose
        self.session = requests.Session()
        self.session.headers.update({"User-Agent": USER_AGENT})
        if self.token:
            self.session.headers.update({"Authorization": f"Bearer {self.token}"})
            if verbose:
                print("Authentication: Enabled (5,000 requests/hour)")
        elif verbose:
            print("Authentication: Not configured (60 requests/hour)")
            print("  Tip: Set GITHUB_TOKEN env var for higher rate limits")

    def _get(self, url: str) -> requests.Response:
        """GET *url*; raise RuntimeError on a request error or an unsuccessful status."""
        try:
            response = self.session.get(url, timeout=REQUEST_TIMEOUT_SECONDS)
            response.raise_for_status()
        except requests.RequestException as e:
            raise RuntimeError(f"GET {url} failed: {e}") from e
        return response

    def fetch_go_stellar_sdk_protocol_file(self, method_name: str, ref: str) -> str:
        """Fetch the protocols/rpc file of *method_name* from go-stellar-sdk at *ref*."""
        protocol_file = KNOWN_METHODS[method_name]
        url = f"{GITHUB_RAW_BASE}/{GO_STELLAR_SDK_REPO}/{ref}/protocols/rpc/{protocol_file}"

        if self.verbose:
            print(f"  Fetching protocol file: protocols/rpc/{protocol_file}")

        return self._get(url).text

    def resolve_rpc_version(self, requested: Optional[str]) -> str:
        """Return the stellar-rpc release tag to extract from.

        *requested* must be a vX.Y.Z or vX.Y.Z-suffix tag of a non-draft release (a
        prerelease qualifies); without it, the newest stable vX.Y.Z release by semver
        is used.
        """
        version = resolve_release(STELLAR_RPC_REPO, requested, self.token).tag
        if self.verbose:
            print(f"stellar-rpc version: {version}")
        return version

    def go_stellar_sdk_ref(self, rpc_version: str) -> str:
        """Return the go-stellar-sdk git ref that the go.mod of stellar-rpc *rpc_version* pins.

        A release version is its own tag; a pseudo-version resolves to its commit hash.
        Releases before v25.0.0 require no go-stellar-sdk module and raise.
        """
        match = _GO_STELLAR_SDK_REQUIREMENT.search(self.fetch_file("go.mod", rpc_version))
        if not match:
            raise RuntimeError(f"stellar-rpc {rpc_version} go.mod has no github.com/stellar/go-stellar-sdk requirement")
        pseudo = _PSEUDO_VERSION.fullmatch(match.group(1))
        ref = pseudo.group(1) if pseudo else match.group(1)
        if self.verbose:
            print(f"go-stellar-sdk ref pinned by stellar-rpc {rpc_version}: {ref}")
        return ref

    def fetch_file(self, file_path: str, ref: str) -> str:
        """Fetch a stellar-rpc file at *ref*."""
        if self.verbose:
            print(f"Fetching: {file_path}")
        return self._get(f"{GITHUB_RAW_BASE}/{STELLAR_RPC_REPO}/{ref}/{file_path}").text


class GoSourceParser:
    """Parses Go source files to extract RPC method specifications."""

    def __init__(self, verbose: bool = False):
        self.verbose = verbose
        self.protocol_source = ""

    def set_protocol_source(self, protocol_source: str):
        """Set the concatenated go-stellar-sdk protocol files that hold the request and response structs."""
        self.protocol_source = protocol_source

    def parse_method_handler(self, method_name: str, go_source: str, handler_file: str) -> MethodSpec:
        """Parse a method handler Go file and return a MethodSpec."""
        if self.verbose:
            print(f"  Parsing {method_name}...")

        parameters = self._extract_parameters(go_source, method_name)
        response = self._extract_response(go_source, method_name)

        return MethodSpec(
            name=method_name,
            handler_file=handler_file,
            parameters=parameters,
            response=response
        )

    def _extract_parameters(self, go_source: str, method_name: str) -> dict[str, list[Parameter]]:
        """Extract method parameters from the request struct.

        Parameter-less methods declare an empty struct (`struct{}`); a request struct
        found in no source raises.
        """
        parameters: dict[str, list[Parameter]] = {"required": [], "optional": []}

        struct_name = self._method_to_struct_name(method_name) + "Request"
        struct_pattern = rf'type\s+{struct_name}\s+struct\s*\{{([^}}]*)\}}'
        match = re.search(struct_pattern, go_source, re.DOTALL)

        if not match:
            match = re.search(struct_pattern, self.protocol_source, re.DOTALL)

        if not match:
            raise RuntimeError(
                f"{method_name}: request struct {struct_name} not found in the handler source or the protocol sources"
            )

        struct_body = match.group(1)
        field_pattern = r'(\w+)\s+([\*\[\]]*[\w\.]+(?:\[[\w\.]+\])?)\s*`json:"([^"]+)"([^`]*)`'

        for field_match in re.finditer(field_pattern, struct_body):
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

            param = Parameter(
                name=json_name,
                type=_go_type_to_json_type(field_type),
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

    def _extract_response(self, go_source: str, method_name: str) -> dict[str, Any]:
        """Extract the response fields from the <Method>Response struct in the handler source or the protocol sources."""
        struct_name = self._method_to_struct_name(method_name) + "Response"
        struct_body = _struct_body(struct_name, go_source) or _struct_body(struct_name, self.protocol_source)
        if not struct_body:
            raise RuntimeError(
                f"{method_name}: no response struct {struct_name} in the handler source or the protocol sources"
            )

        fields = self._response_fields(method_name, struct_body)
        if not fields:
            raise RuntimeError(f"{method_name}: response struct {struct_name} has no JSON-tagged fields")

        return {"type": "object", "fields": fields}

    def _response_fields(self, method_name: str, struct_body: str) -> list[dict[str, str]]:
        """Return the JSON fields of a response struct body in declaration order.

        An embedded struct contributes its own fields at its position; it must be
        declared in the protocol sources.
        """
        fields = []
        for line in struct_body.splitlines():
            embedded = _EMBEDDED_STRUCT.fullmatch(line.strip())
            if embedded:
                embedded_body = _struct_body(embedded.group(0), self.protocol_source)
                if embedded_body is None:
                    raise RuntimeError(
                        f"{method_name}: embedded struct {embedded.group(0)} not found in the protocol sources"
                    )
                fields.extend(self._response_fields(method_name, embedded_body))
                continue
            for field_match in _TAGGED_FIELD.finditer(line):
                json_name = field_match.group(3).split(',')[0]
                if json_name != "-":
                    fields.append({"name": json_name, "type": _go_type_to_json_type(field_match.group(2))})
        return fields

    def _method_to_struct_name(self, method_name: str) -> str:
        """Convert method name to struct name (e.g. getHealth -> GetHealth)."""
        return method_name[0].upper() + method_name[1:]


class RPCMethodExtractor:
    """Main extraction orchestrator."""

    def __init__(self, github_token: Optional[str] = None, verbose: bool = False):
        self.fetcher = GitHubFetcher(token=github_token, verbose=verbose)
        self.parser = GoSourceParser(verbose=verbose)
        self.verbose = verbose

    def extract(self, rpc_version: Optional[str] = None) -> dict[str, Any]:
        """Extract all RPC methods and generate JSON structure.

        The methods that the release registers must be exactly KNOWN_METHODS. Any
        fetch or parse failure raises; no method is written without its handler and
        response definition.
        """
        if self.verbose:
            print("Starting RPC method extraction...")

        rpc_version = self.fetcher.resolve_rpc_version(rpc_version)
        go_sdk_ref = self.fetcher.go_stellar_sdk_ref(rpc_version)
        check_method_set(self._registered_methods(rpc_version))
        # Every protocol file is loaded first: a struct may embed one declared in another file.
        self.parser.set_protocol_source("\n\n".join(
            self.fetcher.fetch_go_stellar_sdk_protocol_file(method_name, go_sdk_ref) for method_name in KNOWN_METHODS
        ))

        methods = {}
        for method_name in KNOWN_METHODS:
            methods[method_name] = self._extract_method(method_name, rpc_version).to_dict()

        output = {
            "metadata": {
                "source": "stellar-rpc",
                "repository": f"https://github.com/{STELLAR_RPC_REPO}",
                "version": rpc_version,
                "extracted_date": datetime.now().strftime("%Y-%m-%d"),
                "total_methods": len(methods),
                "protocol": "JSON-RPC 2.0",
                "protocol_definitions": f"https://github.com/{GO_STELLAR_SDK_REPO}/tree/{go_sdk_ref}/protocols/rpc"
            },
            "methods": methods
        }

        if self.verbose:
            print(f"\nExtraction complete: {len(methods)} methods")
            print(f"stellar-rpc version: {rpc_version}")

        return output

    def _registered_methods(self, rpc_version: str) -> list[str]:
        """Return the JSON-RPC method names that JSONRPC_FILE registers at *rpc_version*.

        Raises when the file holds no protocol.<Name>MethodName registrations.
        """
        registered = _REGISTRATION.findall(self.fetcher.fetch_file(JSONRPC_FILE, rpc_version))
        if not registered:
            raise RuntimeError(f"No protocol.<Name>MethodName registrations found in jsonrpc.go at {rpc_version}")
        return [name[0].lower() + name[1:] for name in registered]

    def _extract_method(self, method_name: str, rpc_version: str) -> MethodSpec:
        """Extract a single method specification from its handler and the protocol sources."""
        handler_file = f"{METHODS_DIR}/{KNOWN_METHODS[method_name]}"
        go_source = self.fetcher.fetch_file(handler_file, rpc_version)
        return self.parser.parse_method_handler(method_name, go_source, handler_file)


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
        help="stellar-rpc release tag (vX.Y.Z or vX.Y.Z-suffix) to extract from; must be a non-draft "
             "release, prereleases allowed (default: newest stable vX.Y.Z release by semver)"
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
