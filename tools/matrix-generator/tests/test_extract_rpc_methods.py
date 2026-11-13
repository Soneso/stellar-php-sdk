"""Release lookups, method registrations and failure behavior of rpc/extract_rpc_methods.py."""

import contextlib
import http.client
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
import requests.adapters  # noqa: E402
import urllib3  # noqa: E402

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
RPCV1_FILE = "cmd/stellar-rpc/internal/rpcv1/jsonrpc.go"
INTERNAL_FILE = "cmd/stellar-rpc/internal/jsonrpc.go"

# Copies of the stellar-rpc registration files at v30.0.0 and v30.0.1.
FIXTURES_DIR = Path(__file__).resolve().parent / "fixtures"
V30_0_0_SOURCE = (FIXTURES_DIR / "stellar-rpc-v30.0.0-jsonrpc.go").read_text(encoding="utf-8")
V30_0_1_SOURCE = (FIXTURES_DIR / "stellar-rpc-v30.0.1-rpcv1-jsonrpc.go").read_text(encoding="utf-8")
# The methods that stellar-rpc v30.0.0 and v30.0.1 register, in source order.
SERVED_METHODS_V30 = [
    "getHealth", "getEvents", "getNetwork", "getVersionInfo", "getLatestLedger", "getLedgers",
    "getLedgerEntries", "getTransaction", "getTransactions", "sendTransaction",
    "simulateTransaction", "getFeeStats",
]


def rpc_url(path, tag=RPC_TAG):
    return f"{RAW}/stellar/stellar-rpc/{tag}/{path}"


def handler_url(file_name):
    return rpc_url(f"{extractor.METHODS_DIR}/{file_name}")


def protocol_url(method_name, ref=GO_SDK_TAG):
    return f"{RAW}/{extractor.GO_STELLAR_SDK_REPO}/{ref}/protocols/rpc/{extractor.KNOWN_METHODS[method_name]}"


def go_mod(requirement=f"github.com/stellar/go-stellar-sdk {GO_SDK_TAG}"):
    return f"module github.com/stellar/stellar-rpc\n\nrequire (\n\t{requirement}\n)\n"


def jsonrpc_source(method_names):
    """A methodName handler list in the internal/jsonrpc.go layout of stellar-rpc v22.1.2 to v30.0.0."""
    entries = "".join(
        f"\t\t{{\n\t\t\tmethodName: protocol.{name[0].upper()}{name[1:]}MethodName,\n\t\t}},\n" for name in method_names
    )
    return f"\thandlers := []struct {{\n\t\tmethodName string\n\t}}{{\n{entries}\t}}\n"


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
    """stellar-rpc v28.0.1: go.mod pinning GO_SDK_TAG, the internal/jsonrpc.go handler list, handlers.

    rpcv1/jsonrpc.go answers 404 like every URL without a route. go-stellar-sdk serves its
    protocol files at GO_SDK_TAG only and no release list.
    """
    routes: dict[str, Any] = {
        rpc_url("go.mod"): go_mod(),
        rpc_url(INTERNAL_FILE): jsonrpc_source(extractor.KNOWN_METHODS),
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
        self.routes[rpc_url(INTERNAL_FILE)] = jsonrpc_source(registered)

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
        self.routes[rpc_url(INTERNAL_FILE)] = (
            '\thandlers := []struct {\n\t\tmethodName string\n\t}{\n\t\t{methodName: "getHealth"},\n\t}\n'
        )

        with self.assertRaises(RuntimeError) as context:
            self.extract()

        self.assertIn(f'{INTERNAL_FILE} at {RPC_TAG}: unrecognized method registration entry: '
                      '{methodName: "getHealth"}', str(context.exception))

    def test_registration_file_of_either_form_yields_the_known_methods(self):
        # The internal file of the rpcv1 case registers one method and would fail the method set check.
        for name, rpcv1, internal in (("rpcv1", V30_0_1_SOURCE, jsonrpc_source(["getHealth"])),
                                      ("internal", 404, V30_0_0_SOURCE)):
            with self.subTest(file=name):
                self.routes[rpc_url(RPCV1_FILE)] = rpcv1
                self.routes[rpc_url(INTERNAL_FILE)] = internal

                self.assertEqual(set(self.extract()["methods"]), set(extractor.KNOWN_METHODS))


class FailingBody(io.RawIOBase):
    """A response body whose read fails with the given error."""

    def __init__(self, error):
        super().__init__()
        self.error = error

    def readable(self):
        return True

    def readinto(self, buffer):
        raise self.error


class RawGitHub(requests.adapters.HTTPAdapter):
    """Serves raw files by URL through the requests response and body-read path.

    A response is (status, body) or an exception that the transport raises; a body that
    is an exception fails the body read. A URL without a response raises KeyError.
    """

    def __init__(self, responses):
        super().__init__()
        self.responses = responses
        self.requested = []

    def send(self, request, **kwargs):
        self.requested.append(request.url)
        response = self.responses[request.url]
        if isinstance(response, Exception):
            raise response
        status, body = response
        stream = FailingBody(body) if isinstance(body, Exception) else io.BytesIO(body)
        return self.build_response(request, urllib3.HTTPResponse(
            body=stream, status=status, reason=http.HTTPStatus(status).phrase, preload_content=False))


RPCV1_URL = rpc_url(RPCV1_FILE, "v30.0.1")
INTERNAL_URL = rpc_url(INTERNAL_FILE, "v30.0.1")


class RegistrationFetchTest(unittest.TestCase):
    """The registration file is read at the tag; the internal path only after HTTP 404 at that tag."""

    def fetch(self, responses, tag="v30.0.1"):
        fetcher = extractor.GitHubFetcher(token="test-token")
        self.github = RawGitHub(responses)
        fetcher.session.mount("https://", self.github)
        return fetcher.fetch_registration_source(tag)

    def test_reads_the_rpcv1_file(self):
        result = self.fetch({RPCV1_URL: (200, V30_0_1_SOURCE.encode()), INTERNAL_URL: (200, b"internal source")})

        self.assertEqual(result, (RPCV1_FILE, V30_0_1_SOURCE))
        self.assertEqual(self.github.requested, [RPCV1_URL])

    def test_falls_back_to_the_internal_file_of_the_same_tag_on_404(self):
        rpcv1_url, internal_url = rpc_url(RPCV1_FILE, "v30.0.0"), rpc_url(INTERNAL_FILE, "v30.0.0")

        result = self.fetch({rpcv1_url: (404, b"404: Not Found"), internal_url: (200, V30_0_0_SOURCE.encode()),
                             RPCV1_URL: (200, b"rpcv1 source of another tag")}, tag="v30.0.0")

        self.assertEqual(result, (INTERNAL_FILE, V30_0_0_SOURCE))
        self.assertEqual(self.github.requested, [rpcv1_url, internal_url])

    def test_any_other_failure_on_either_path_raises_naming_the_tag_and_url(self):
        failures = {
            "500 Server Error: Internal Server Error": (500, b""),
            "429 Client Error: Too Many Requests": (429, b""),
            "403 Client Error: Forbidden": (403, b"rate limit exceeded"),
            "connection refused": requests.ConnectionError("connection refused"),
            "read timed out": requests.ReadTimeout("read timed out"),
            "IncompleteRead(7 bytes read, 100 more expected)": (200, http.client.IncompleteRead(b"package", 100)),
            "Connection reset by peer": (200, ConnectionResetError(54, "Connection reset by peer")),
            "'utf-8' codec can't decode": (200, b"package rpcv1 \xff"),
        }
        for failing_url, earlier_requests in ((RPCV1_URL, []), (INTERNAL_URL, [RPCV1_URL])):
            for cause, failure in failures.items():
                with self.subTest(url=failing_url, failure=cause):
                    responses = {RPCV1_URL: (404, b""), INTERNAL_URL: (200, V30_0_0_SOURCE.encode())}
                    responses[failing_url] = failure

                    with self.assertRaises(RuntimeError) as context:
                        self.fetch(responses)

                    message = str(context.exception)
                    self.assertTrue(message.startswith(f"Failed to read {failing_url} for stellar-rpc tag v30.0.1: "),
                                    message)
                    self.assertIn(cause, message)
                    self.assertEqual(self.github.requested, earlier_requests + [failing_url])

    def test_both_paths_absent_names_both_urls(self):
        with self.assertRaises(RuntimeError) as context:
            self.fetch({RPCV1_URL: (404, b""), INTERNAL_URL: (404, b"")})

        self.assertEqual(str(context.exception), "No JSON-RPC method registration file for stellar-rpc tag v30.0.1: "
                                                 f"HTTP 404 for {RPCV1_URL} and {INTERNAL_URL}")
        self.assertEqual(self.github.requested, [RPCV1_URL, INTERNAL_URL])


class RegistrationParsingTest(unittest.TestCase):
    """registered_methods() reads the one registration block of either form and rejects anything else."""

    def assert_rejected(self, source, *parts):
        with self.assertRaises(RuntimeError) as context:
            extractor.registered_methods(source, "rpcv1/jsonrpc.go at v30.0.1")
        for part in ("rpcv1/jsonrpc.go at v30.0.1",) + parts:
            self.assertIn(part, str(context.exception))

    def edited(self, source, old, new):
        self.assertEqual(source.count(old), 1, old)
        return source.replace(old, new)

    def test_both_forms_register_the_served_methods(self):
        for name, source in (("v30.0.0", V30_0_0_SOURCE), ("v30.0.1", V30_0_1_SOURCE)):
            with self.subTest(fixture=name):
                self.assertEqual(extractor.registered_methods(source, name), SERVED_METHODS_V30)
                extractor.check_method_set(extractor.registered_methods(source, name))

    def test_comments_and_literals_in_the_block_are_neither_entries_nor_brackets(self):
        cases = {
            "v30.0.1": (V30_0_1_SOURCE, "\t\tprotocol.GetFeeStatsMethodName: {",
                        "\t\t// protocol.GetFooMethodName: {},\n"
                        "\t\t/* protocol.GetBarMethodName: {}, */ protocol.GetFeeStatsMethodName: {"),
            "v30.0.0": (V30_0_0_SOURCE, "longName:             toSnakeCase(protocol.GetHealthMethodName),",
                        'longName:             "get_health) // }", // ignored },'),
        }
        for name, (source, old, new) in cases.items():
            with self.subTest(fixture=name):
                self.assertEqual(extractor.registered_methods(self.edited(source, old, new), name), SERVED_METHODS_V30)

    def test_unrecognized_entry_is_named(self):
        cases = {
            "v30.0.1": (V30_0_1_SOURCE, "protocol.GetFeeStatsMethodName: {", '"getFeeStats": {'),
            "v30.0.0": (V30_0_0_SOURCE, "methodName:           protocol.GetFeeStatsMethodName,",
                        'methodName: "getFeeStats",'),
        }
        for name, (source, old, new) in cases.items():
            with self.subTest(fixture=name):
                self.assert_rejected(self.edited(source, old, new), "unrecognized method registration entry", new)

    def test_handler_without_exactly_one_method_name_is_named(self):
        old = "\t\t\tmethodName: protocol.GetHealthMethodName,\n"
        for count in (0, 2):
            with self.subTest(method_names=count):
                self.assert_rejected(self.edited(V30_0_0_SOURCE, old, old * count),
                                     "unrecognized method registration entry", "methods.NewHealthCheck(")

    def test_method_name_reference_outside_the_block_is_named(self):
        reference = "protocol.GetLedgerStateMethodName"
        cases = {
            "v30.0.1": (V30_0_1_SOURCE, "\tspecs = jsonrpc.LimitsByMethod{\n",
                        f"\tspecs = append(specs, jsonrpc.HandlerSpec{{MethodName: {reference}}})\n"),
            "v30.0.0": (V30_0_0_SOURCE, "\thandlersMap := handler.Map{}\n",
                        f"\thandlers[0].methodName = {reference}\n"),
        }
        for name, (source, anchor, added) in cases.items():
            for spelling in (reference, "protocol .\n\t\tGetLedgerStateMethodName"):
                with self.subTest(fixture=name, spelling=spelling):
                    self.assert_rejected(self.edited(source, anchor, added.replace(reference, spelling) + anchor),
                                         f"method name reference outside the registration block: {reference}")
            with self.subTest(fixture=name, spelling="comments and literals"):
                quoted = (f"\t// {reference} LimitsByMethod{{\n\t/* {reference} */\n"
                          f'\t_ = "{reference} LimitsByMethod{{" + `[]struct {{ methodName string }}{{ {reference}`\n')
                self.assertEqual(extractor.registered_methods(self.edited(source, anchor, quoted + anchor), name),
                                 SERVED_METHODS_V30)

    def test_empty_unclosed_or_not_exactly_one_block_raises(self):
        for source, part in (
                ("specs = jsonrpc.LimitsByMethod{\n}.Apply(specs)\n", "the method registration block is empty"),
                ("specs = jsonrpc.LimitsByMethod{\n\tprotocol.GetHealthMethodName: {},\n", "block is not closed"),
                ("package rpcv1\n", "holds 0 method registration blocks"),
                (V30_0_1_SOURCE + V30_0_0_SOURCE, "holds 2 method registration blocks")):
            with self.subTest(expected=part):
                self.assert_rejected(source, part)


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
