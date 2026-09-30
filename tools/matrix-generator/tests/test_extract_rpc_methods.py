"""Release lookups and failure behavior of rpc/extract_rpc_methods.py."""

import contextlib
import io
import json
import sys
import tempfile
import unittest
from pathlib import Path
from typing import Any
from unittest import mock

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "rpc"))

import requests  # noqa: E402

import extract_rpc_methods as extractor  # noqa: E402
from rpc_releases import GO_STELLAR_SDK_REPO, STELLAR_RPC_REPO, ReleaseLookupError  # noqa: E402
from support import (  # noqa: E402
    SELECTION_CASES,
    SELECTION_FAILURE_CASES,
    FakeUrlopen,
    release,
    release_routes,
)

RPC_TAG = "v28.0.1"
GO_SDK_TAG = "v0.7.3"
RAW = "https://raw.githubusercontent.com"
HANDLER_DIR = "cmd/stellar-rpc/internal/methods"

PROTOCOL_FILES = {
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


def handler_url(file_name, methods_dir=HANDLER_DIR):
    return f"{RAW}/stellar/stellar-rpc/{RPC_TAG}/{methods_dir}/{file_name}"


def protocol_url(method_name):
    return f"{RAW}/{GO_STELLAR_SDK_REPO}/{GO_SDK_TAG}/protocols/rpc/{PROTOCOL_FILES[method_name]}"


def listing_url(directory):
    return f"https://api.github.com/repos/stellar/stellar-rpc/contents/{directory}?ref={RPC_TAG}"


# go-stellar-sdk declares the request struct of these methods as `struct{}`.
PARAMETERLESS_METHODS = {"getHealth", "getNetwork", "getVersionInfo", "getFeeStats", "getLatestLedger"}


def protocol_source(method_name):
    struct = method_name[0].upper() + method_name[1:]
    if method_name in PARAMETERLESS_METHODS:
        request = f"type {struct}Request struct{{}}\n\n"
    else:
        request = (
            f"type {struct}Request struct {{\n"
            f"\tStartLedger uint32 `json:\"startLedger\"`\n"
            f"\tLimit uint `json:\"limit,omitempty\"`\n"
            f"}}\n\n"
        )
    return request + (
        f"type {struct}Response struct {{\n"
        f"\tLatestLedger uint32 `json:\"latestLedger\"`\n"
        f"}}\n"
    )


def upstream_routes():
    """stellar-rpc v28.0.1 layout: handlers under cmd/stellar-rpc, no protocol directory."""
    routes: dict[str, Any] = {}
    for method_name, file_names in extractor.KNOWN_METHODS.items():
        file_name = file_names if isinstance(file_names, str) else file_names[0]
        routes[handler_url(file_name)] = f"// {method_name} handler\npackage methods\n"
        routes[protocol_url(method_name)] = protocol_source(method_name)
    return routes


def release_list_routes():
    routes = release_routes(STELLAR_RPC_REPO, [[release("rpcclient-v24.0.0"), release(RPC_TAG)]])
    routes.update(release_routes(
        GO_STELLAR_SDK_REPO,
        [[release("v0.6.1", repo=GO_STELLAR_SDK_REPO), release(GO_SDK_TAG, repo=GO_STELLAR_SDK_REPO)]],
    ))
    return routes


class FakeRequestsGet:
    """Replacement for requests.Session.get.

    A route value is an exception instance (raised), an int (that HTTP status with an
    empty body) or a str (HTTP 200 with that body). Every other URL answers HTTP 404.
    """

    def __init__(self, routes):
        self.routes = routes

    def __call__(self, url, **kwargs):
        route = self.routes.get(url, 404)
        if isinstance(route, BaseException):
            raise route
        status, text = (route, "") if isinstance(route, int) else (200, route)
        response = requests.Response()
        response.status_code = status
        response._content = text.encode("utf-8")
        response.encoding = "utf-8"
        response.url = url
        response.reason = "OK" if status < 400 else "Error"
        return response


class ExtractorTestCase(unittest.TestCase):
    def setUp(self):
        self.routes = upstream_routes()
        self.release_lists = release_list_routes()
        for patcher in (
            mock.patch.dict("os.environ", {"GITHUB_TOKEN": "test-token"}),
            mock.patch.object(requests.Session, "get", FakeRequestsGet(self.routes)),
            mock.patch("urllib.request.urlopen", FakeUrlopen(self.release_lists)),
        ):
            patcher.start()
            self.addCleanup(patcher.stop)

    def extract(self):
        return extractor.RPCMethodExtractor().extract()


class ExtractorRunTest(ExtractorTestCase):
    def run_main(self, *arguments):
        tmp = tempfile.TemporaryDirectory()
        self.addCleanup(tmp.cleanup)
        output = Path(tmp.name) / "data" / "rpc_methods.json"
        argv = ["extract_rpc_methods.py", "--output", str(output), *arguments]
        with mock.patch.object(sys, "argv", argv), \
                contextlib.redirect_stdout(io.StringIO()), \
                contextlib.redirect_stderr(io.StringIO()):
            return extractor.main(), output

    def test_full_method_set_is_written(self):
        exit_code, output = self.run_main()

        self.assertEqual(exit_code, 0)
        data = json.loads(output.read_text(encoding="utf-8"))
        self.assertEqual(set(data["methods"]), set(extractor.KNOWN_METHODS))
        self.assertEqual(data["metadata"]["version"], RPC_TAG)
        self.assertEqual(
            data["metadata"]["protocol_definitions"],
            f"https://github.com/stellar/go-stellar-sdk/tree/{GO_SDK_TAG}/protocols/rpc",
        )
        for name, method in data["methods"].items():
            with self.subTest(method=name):
                self.assertEqual([f["name"] for f in method["response"]["fields"]], ["latestLedger"])
                self.assertNotIn("notes", method)

    def test_one_handler_fetch_failure_writes_no_json(self):
        self.routes[handler_url("get_events.go")] = requests.ConnectionError("connection reset")

        exit_code, output = self.run_main()

        self.assertEqual(exit_code, 1)
        self.assertFalse(output.exists())

    def test_method_set_mismatch_writes_no_json(self):
        with mock.patch.object(extractor, "check_method_set", side_effect=RuntimeError("missing getLedgers")):
            exit_code, output = self.run_main()

        self.assertEqual(exit_code, 1)
        self.assertFalse(output.exists())

    def test_rpc_version_override_absent_from_release_list_writes_no_json(self):
        exit_code, output = self.run_main("--rpc-version", "v27.9.9")

        self.assertEqual(exit_code, 1)
        self.assertFalse(output.exists())


class HandlerFetchTest(ExtractorTestCase):
    def test_missing_handler_file_moves_to_next_candidate(self):
        del self.routes[handler_url("get_health.go")]
        self.routes[handler_url("health.go")] = "package methods\n"

        methods = self.extract()["methods"]

        self.assertEqual(methods["getHealth"]["handler_file"], f"{HANDLER_DIR}/health.go")

    def test_server_error_on_a_candidate_raises(self):
        self.routes[handler_url("get_health.go")] = 500
        self.routes[handler_url("health.go")] = "package methods\n"

        with self.assertRaisesRegex(RuntimeError, "get_health.go failed: 500"):
            self.extract()

    def test_no_candidate_present_raises_naming_the_method(self):
        del self.routes[handler_url("get_fee_stats.go")]

        with self.assertRaisesRegex(RuntimeError, "getFeeStats: no handler file at v28.0.1"):
            self.extract()


class ProtocolSourceTest(ExtractorTestCase):
    def test_go_stellar_sdk_request_error_raises(self):
        self.routes[protocol_url("getEvents")] = requests.ConnectionError("connection reset")
        fetcher = extractor.GitHubFetcher()

        with self.assertRaisesRegex(RuntimeError, "get_events.go failed: connection reset"):
            fetcher.fetch_go_stellar_sdk_protocol_file("getEvents", GO_SDK_TAG)

    def test_go_stellar_sdk_missing_file_raises(self):
        del self.routes[protocol_url("getEvents")]
        fetcher = extractor.GitHubFetcher()

        with self.assertRaisesRegex(RuntimeError, "get_events.go failed: 404"):
            fetcher.fetch_go_stellar_sdk_protocol_file("getEvents", GO_SDK_TAG)

    def test_unmapped_method_raises(self):
        with self.assertRaisesRegex(ValueError, "No go-stellar-sdk protocol file is mapped for getFoo"):
            extractor.GitHubFetcher().fetch_go_stellar_sdk_protocol_file("getFoo", GO_SDK_TAG)

    def test_response_struct_missing_in_every_source_raises_naming_the_method(self):
        self.routes[protocol_url("getFeeStats")] = (
            "type GetFeeStatsRequest struct {\n\tLimit uint `json:\"limit\"`\n}\n"
        )

        with self.assertRaisesRegex(RuntimeError, r"getFeeStats: no response struct \(GetFeeStatsResponse"):
            self.extract()

    def test_response_struct_without_tagged_fields_raises(self):
        self.routes[protocol_url("getNetwork")] = (
            "type GetNetworkRequest struct{}\n\ntype GetNetworkResponse struct {\n\tInner\n}\n"
        )

        with self.assertRaisesRegex(RuntimeError, "getNetwork: response struct GetNetworkResponse has no"):
            self.extract()

    def test_protocol_directory_listing_error_raises(self):
        self.routes[listing_url("protocol")] = 500

        with self.assertRaisesRegex(RuntimeError, "contents/protocol.* failed: 500"):
            self.extract()

    def test_protocol_directory_file_fetch_error_raises(self):
        self.routes[listing_url("protocol")] = json.dumps([{"type": "file", "name": "types.go"}])
        self.routes[f"{RAW}/stellar/stellar-rpc/{RPC_TAG}/protocol/types.go"] = requests.Timeout("timed out")

        with self.assertRaisesRegex(RuntimeError, "protocol/types.go failed: timed out"):
            self.extract()

    def test_protocol_directory_structs_are_used(self):
        self.routes[listing_url("protocol")] = json.dumps([{"type": "file", "name": "types.go"}])
        self.routes[f"{RAW}/stellar/stellar-rpc/{RPC_TAG}/protocol/types.go"] = (
            "type GetFeeStatsRequest struct {\n\tWindow uint32 `json:\"window\"`\n}\n\n"
            "type GetFeeStatsResult struct {\n\tInclusionFee string `json:\"inclusionFee\"`\n}\n"
        )
        self.routes[protocol_url("getFeeStats")] = "package protocol\n"

        fee_stats = self.extract()["methods"]["getFeeStats"]

        self.assertEqual([p["name"] for p in fee_stats["parameters"]["required"]], ["window"])
        self.assertEqual([f["name"] for f in fee_stats["response"]["fields"]], ["inclusionFee"])


class RequestStructTest(ExtractorTestCase):
    def test_empty_request_struct_yields_no_parameters(self):
        methods = self.extract()["methods"]

        self.assertEqual(methods["getHealth"]["parameters"], {"required": [], "optional": []})
        self.assertEqual([p["name"] for p in methods["getEvents"]["parameters"]["required"]], ["startLedger"])
        self.assertEqual([p["name"] for p in methods["getEvents"]["parameters"]["optional"]], ["limit"])

    def test_request_struct_missing_in_every_source_raises_naming_the_method(self):
        self.routes[protocol_url("getLedgers")] = (
            "type GetLedgersResponse struct {\n\tLatestLedger uint32 `json:\"latestLedger\"`\n}\n"
        )

        with self.assertRaisesRegex(RuntimeError, "getLedgers: request struct GetLedgersRequest not found"):
            self.extract()


class LocalProtocolFilesTest(unittest.TestCase):
    def test_unreadable_local_file_raises(self):
        cases = {
            "invalid UTF-8": lambda path: path.write_bytes(b"type GetHealthResponse \xff\xfe struct {}\n"),
            "directory named like a Go file": lambda path: path.mkdir(),
        }
        for description, create in cases.items():
            with self.subTest(case=description), tempfile.TemporaryDirectory() as checkout:
                protocol_dir = Path(checkout) / "protocols" / "rpc"
                protocol_dir.mkdir(parents=True)
                create(protocol_dir / "get_health.go")

                with self.assertRaisesRegex(RuntimeError, "Failed to read local protocol file .*get_health.go"):
                    extractor.ResponseStructParser(Path(checkout))

    def test_readable_local_files_are_loaded(self):
        with tempfile.TemporaryDirectory() as checkout:
            protocol_dir = Path(checkout) / "protocols" / "rpc"
            protocol_dir.mkdir(parents=True)
            (protocol_dir / "get_health.go").write_text(
                "type GetHealthResponse struct {\n\tStatus string `json:\"status\"`\n}\n", encoding="utf-8"
            )

            parser = extractor.ResponseStructParser(Path(checkout))

        self.assertEqual([f["name"] for f in parser.parse_response_struct("getHealth")["fields"]], ["status"])


class MethodSetTest(unittest.TestCase):
    def test_full_set_passes(self):
        extractor.check_method_set(extractor.KNOWN_METHODS)

    def test_missing_method_raises_naming_it(self):
        found = set(extractor.KNOWN_METHODS) - {"getLedgers"}
        with self.assertRaisesRegex(RuntimeError, "missing getLedgers"):
            extractor.check_method_set(found)

    def test_extra_method_raises_naming_it(self):
        found = set(extractor.KNOWN_METHODS) | {"getFoo"}
        with self.assertRaisesRegex(RuntimeError, "extra getFoo"):
            extractor.check_method_set(found)


class ReleaseSelectionTest(ExtractorTestCase):
    """The extractor selects stellar-rpc and go-stellar-sdk releases through rpc_releases."""

    def serve(self, repo, pages):
        self.release_lists.clear()
        self.release_lists.update(release_routes(repo, pages))

    def test_selects_newest_stable_release(self):
        fetcher = extractor.GitHubFetcher()
        lookups = (
            (GO_STELLAR_SDK_REPO, fetcher.latest_go_stellar_sdk_version),
            (STELLAR_RPC_REPO, lambda: fetcher.resolve_rpc_version(None)),
        )
        for repo, lookup in lookups:
            for description, pages, expected in SELECTION_CASES:
                with self.subTest(repo=repo, case=description):
                    self.serve(repo, pages)
                    self.assertEqual(lookup(), expected)

    def test_raises_when_no_release_qualifies(self):
        fetcher = extractor.GitHubFetcher()
        lookups = (
            (GO_STELLAR_SDK_REPO, fetcher.latest_go_stellar_sdk_version),
            (STELLAR_RPC_REPO, lambda: fetcher.resolve_rpc_version(None)),
        )
        for repo, lookup in lookups:
            for description, pages, pattern in SELECTION_FAILURE_CASES:
                with self.subTest(repo=repo, case=description):
                    self.serve(repo, pages)
                    with self.assertRaisesRegex(ReleaseLookupError, pattern):
                        lookup()

    def test_release_request_error_raises(self):
        self.release_lists.clear()
        fetcher = extractor.GitHubFetcher()

        with self.assertRaisesRegex(ReleaseLookupError, "HTTP Error 404"):
            fetcher.latest_go_stellar_sdk_version()

    def test_rpc_version_override(self):
        self.serve(STELLAR_RPC_REPO, [[release("v29.0.0-rc.1", prerelease=True), release("v28.0.1")]])
        fetcher = extractor.GitHubFetcher()

        self.assertEqual(fetcher.resolve_rpc_version("v29.0.0-rc.1"), "v29.0.0-rc.1")
        with self.assertRaisesRegex(ReleaseLookupError, "no release tagged v27.9.9"):
            fetcher.resolve_rpc_version("v27.9.9")


if __name__ == "__main__":
    unittest.main()
