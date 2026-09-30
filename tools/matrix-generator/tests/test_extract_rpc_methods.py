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
from rpc_releases import STELLAR_RPC_REPO, ReleaseLookupError  # noqa: E402
from support import (  # noqa: E402
    SELECTION_CASES,
    SELECTION_FAILURE_CASES,
    FakeUrlopen,
    release,
    release_routes,
)

RPC_TAG = "v28.0.1"
GO_SDK_TAG = "v0.7.2"
RAW = "https://raw.githubusercontent.com"


def rpc_url(path):
    return f"{RAW}/stellar/stellar-rpc/{RPC_TAG}/{path}"


def handler_url(file_name):
    return rpc_url(f"{extractor.METHODS_DIR}/{file_name}")


def protocol_url(method_name, ref=GO_SDK_TAG):
    return f"{RAW}/{extractor.GO_STELLAR_SDK_REPO}/{ref}/protocols/rpc/{extractor.KNOWN_METHODS[method_name]}"


def go_mod(requirement=f"github.com/stellar/go-stellar-sdk {GO_SDK_TAG}"):
    return f"module github.com/stellar/stellar-rpc\n\nrequire (\n\t{requirement}\n)\n"


def jsonrpc_source(method_names):
    """Registrations in the jsonrpc.go layout of stellar-rpc v22.1.2 and later."""
    return "".join(f"\t\t\tmethodName: protocol.{name[0].upper()}{name[1:]}MethodName,\n" for name in method_names)


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
    """stellar-rpc v28.0.1: go.mod pinning GO_SDK_TAG, jsonrpc.go registrations, handlers.

    go-stellar-sdk serves its protocol files at GO_SDK_TAG only and no release list.
    """
    routes: dict[str, Any] = {
        rpc_url("go.mod"): go_mod(),
        rpc_url(extractor.JSONRPC_FILE): jsonrpc_source(extractor.KNOWN_METHODS),
    }
    for method_name, file_name in extractor.KNOWN_METHODS.items():
        routes[handler_url(file_name)] = f"// {method_name} handler\npackage methods\n"
        routes[protocol_url(method_name)] = protocol_source(method_name)
    return routes


def release_list_routes():
    return release_routes(STELLAR_RPC_REPO, [[release("rpcclient-v24.0.0"), release(RPC_TAG)]])


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
        stderr = io.StringIO()
        with mock.patch.object(sys, "argv", argv), \
                contextlib.redirect_stdout(io.StringIO()), \
                contextlib.redirect_stderr(stderr):
            return extractor.main(), output, stderr.getvalue()

    def test_full_method_set_is_written(self):
        exit_code, output, _ = self.run_main()

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

        exit_code, output, _ = self.run_main()

        self.assertEqual(exit_code, 1)
        self.assertFalse(output.exists())

    def test_registration_mismatch_writes_no_json_and_names_the_methods(self):
        registered = [name for name in extractor.KNOWN_METHODS if name != "getLedgers"] + ["getFoo"]
        self.routes[rpc_url(extractor.JSONRPC_FILE)] = jsonrpc_source(registered)

        exit_code, output, stderr = self.run_main()

        self.assertEqual(exit_code, 1)
        self.assertFalse(output.exists())
        self.assertIn("(missing: getLedgers; extra: getFoo)", stderr)

    def test_rpc_version_override_absent_from_release_list_writes_no_json(self):
        exit_code, output, _ = self.run_main("--rpc-version", "v27.9.9")

        self.assertEqual(exit_code, 1)
        self.assertFalse(output.exists())


class HandlerFetchTest(ExtractorTestCase):
    def test_missing_handler_file_raises_naming_it(self):
        del self.routes[handler_url("get_fee_stats.go")]

        with self.assertRaisesRegex(RuntimeError, "get_fee_stats.go failed: 404"):
            self.extract()


class GoStellarSdkPinTest(ExtractorTestCase):
    def test_pseudo_version_reads_the_definitions_at_its_commit(self):
        commit = "6181cdf8bda5"
        self.routes[rpc_url("go.mod")] = go_mod(f"github.com/stellar/go-stellar-sdk v0.6.1-0.20260625225930-{commit}")
        for method_name in extractor.KNOWN_METHODS:
            self.routes[protocol_url(method_name, commit)] = protocol_source(method_name)

        self.assertEqual(
            self.extract()["metadata"]["protocol_definitions"],
            f"https://github.com/stellar/go-stellar-sdk/tree/{commit}/protocols/rpc",
        )

    def test_go_mod_without_go_stellar_sdk_raises(self):
        self.routes[rpc_url("go.mod")] = go_mod("github.com/stellar/go v0.0.0-20250818235326-815d6a25c539")

        with self.assertRaisesRegex(RuntimeError, "v28.0.1 go.mod has no github.com/stellar/go-stellar-sdk requirement"):
            self.extract()


class RegistrationTest(ExtractorTestCase):
    def test_string_literal_registrations_raise(self):
        self.routes[rpc_url(extractor.JSONRPC_FILE)] = '\t\t\tmethodName: "getHealth",\n'

        with self.assertRaisesRegex(RuntimeError, "No protocol.<Name>MethodName registrations found in jsonrpc.go"):
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

    def test_response_struct_missing_in_every_source_raises_naming_the_method(self):
        self.routes[protocol_url("getFeeStats")] = (
            "type GetFeeStatsRequest struct {\n\tLimit uint `json:\"limit\"`\n}\n"
        )

        with self.assertRaisesRegex(RuntimeError, "getFeeStats: no response struct GetFeeStatsResponse in"):
            self.extract()

    def test_response_struct_without_tagged_fields_raises(self):
        self.routes[protocol_url("getNetwork")] = (
            "type GetNetworkRequest struct{}\n\ntype GetNetworkResponse struct {\n\tPassphrase string\n}\n"
        )

        with self.assertRaisesRegex(RuntimeError, "getNetwork: response struct GetNetworkResponse has no"):
            self.extract()


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


class EmbeddedStructTest(ExtractorTestCase):
    def set_transaction_response(self, details=""):
        self.routes[protocol_url("getTransaction")] = (
            "type GetTransactionRequest struct {\n\tHash string `json:\"hash\"`\n}\n\n"
            "type GetTransactionResponse struct {\n\tLatestLedger uint32 `json:\"latestLedger\"`\n"
            "\tTransactionDetails\n\tLedgerCloseTime int64 `json:\"createdAt,string\"`\n}\n"
        )
        self.routes[protocol_url("getTransactions")] = protocol_source("getTransactions") + details

    def test_struct_embedded_from_another_protocol_file_contributes_its_fields(self):
        self.set_transaction_response(
            "\ntype TransactionDetails struct {\n\tStatus string `json:\"status\"`\n\tLedger uint32 `json:\"ledger\"`\n}\n"
        )

        fields = self.extract()["methods"]["getTransaction"]["response"]["fields"]

        self.assertEqual([f["name"] for f in fields], ["latestLedger", "status", "ledger", "createdAt"])

    def test_embedded_struct_missing_from_the_protocol_files_raises(self):
        self.set_transaction_response()

        with self.assertRaisesRegex(RuntimeError, "getTransaction: embedded struct TransactionDetails not found"):
            self.extract()


class MethodSetTest(unittest.TestCase):
    def test_full_set_passes(self):
        extractor.check_method_set(extractor.KNOWN_METHODS)

    def test_missing_method_raises_naming_it(self):
        found = set(extractor.KNOWN_METHODS) - {"getLedgers"}
        with self.assertRaisesRegex(RuntimeError, "missing: getLedgers"):
            extractor.check_method_set(found)

    def test_extra_method_raises_naming_it(self):
        found = set(extractor.KNOWN_METHODS) | {"getFoo"}
        with self.assertRaisesRegex(RuntimeError, "extra: getFoo"):
            extractor.check_method_set(found)


class ReleaseSelectionTest(ExtractorTestCase):
    """The extractor selects the stellar-rpc release through rpc_releases."""

    def serve(self, repo, pages):
        self.release_lists.clear()
        self.release_lists.update(release_routes(repo, pages))

    def test_selects_newest_stable_release(self):
        fetcher = extractor.GitHubFetcher()
        for description, pages, expected in SELECTION_CASES:
            with self.subTest(case=description):
                self.serve(STELLAR_RPC_REPO, pages)
                self.assertEqual(fetcher.resolve_rpc_version(None), expected)

    def test_raises_when_no_release_qualifies(self):
        fetcher = extractor.GitHubFetcher()
        for description, pages, pattern in SELECTION_FAILURE_CASES:
            with self.subTest(case=description):
                self.serve(STELLAR_RPC_REPO, pages)
                with self.assertRaisesRegex(ReleaseLookupError, pattern):
                    fetcher.resolve_rpc_version(None)

    def test_release_request_error_raises(self):
        self.release_lists.clear()
        fetcher = extractor.GitHubFetcher()

        with self.assertRaisesRegex(ReleaseLookupError, "HTTP Error 404"):
            fetcher.resolve_rpc_version(None)

    def test_rpc_version_override(self):
        self.serve(STELLAR_RPC_REPO, [[release("v29.0.0-rc.1", prerelease=True), release("v28.0.1")]])
        fetcher = extractor.GitHubFetcher()

        self.assertEqual(fetcher.resolve_rpc_version("v29.0.0-rc.1"), "v29.0.0-rc.1")
        with self.assertRaisesRegex(ReleaseLookupError, "no release tagged v27.9.9"):
            fetcher.resolve_rpc_version("v27.9.9")


if __name__ == "__main__":
    unittest.main()
