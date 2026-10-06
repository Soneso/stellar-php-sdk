"""Release list fetch and selection in rpc/rpc_releases.py."""

import sys
import unittest
import urllib.error
from pathlib import Path
from unittest import mock

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "rpc"))

import rpc_releases  # noqa: E402
from rpc_releases import STELLAR_RPC_REPO, ReleaseLookupError, resolve_release  # noqa: E402
from support import (  # noqa: E402
    SELECTION_CASES,
    SELECTION_FAILURE_CASES,
    FakeUrlopen,
    release,
    release_routes,
    releases_url,
)


def serve(routes):
    fake = FakeUrlopen(routes)
    return fake, mock.patch("urllib.request.urlopen", fake)


class NewestStableSelectionTest(unittest.TestCase):
    def test_selects_newest_stable_release(self):
        for description, pages, expected in SELECTION_CASES:
            with self.subTest(case=description):
                _, patch = serve(release_routes(STELLAR_RPC_REPO, pages))
                with patch:
                    self.assertEqual(resolve_release(STELLAR_RPC_REPO, None, None).tag, expected)

    def test_raises_when_no_release_qualifies(self):
        for description, pages, pattern in SELECTION_FAILURE_CASES:
            with self.subTest(case=description):
                _, patch = serve(release_routes(STELLAR_RPC_REPO, pages))
                with patch, self.assertRaisesRegex(ReleaseLookupError, pattern):
                    resolve_release(STELLAR_RPC_REPO, None, None)

    def test_follows_link_header_with_100_per_page(self):
        fake, patch = serve(release_routes(STELLAR_RPC_REPO, [[release("v28.0.1")], [release("v28.1.0")]]))
        with patch:
            resolve_release(STELLAR_RPC_REPO, None, None)
        self.assertEqual(fake.urls, [releases_url(STELLAR_RPC_REPO, 1), releases_url(STELLAR_RPC_REPO, 2)])

    def test_record_fields_come_from_the_selected_release(self):
        entry = release("v28.0.1", published_at="2026-08-27T18:40:46Z")
        _, patch = serve(release_routes(STELLAR_RPC_REPO, [[release("v28.0.0"), entry]]))
        with patch:
            record = resolve_release(STELLAR_RPC_REPO, None, None)
        self.assertEqual(
            record,
            rpc_releases.Release(
                tag="v28.0.1",
                published_date="2026-08-27",
                html_url="https://github.com/stellar/stellar-rpc/releases/tag/v28.0.1",
            ),
        )

    def test_sends_token_as_bearer(self):
        fake, patch = serve(release_routes(STELLAR_RPC_REPO, [[release("v28.0.1")]]))
        with patch:
            resolve_release(STELLAR_RPC_REPO, None, "test-token")
        self.assertEqual(fake.requests[0].get_header("Authorization"), "Bearer test-token")


class InvalidResponseTest(unittest.TestCase):
    def assert_lookup_fails(self, route, pattern):
        _, patch = serve({releases_url(STELLAR_RPC_REPO): route})
        with patch, self.assertRaisesRegex(ReleaseLookupError, pattern):
            resolve_release(STELLAR_RPC_REPO, None, None)

    def test_object_body(self):
        self.assert_lookup_fails({"message": "Not Found"}, "expected a list of releases")

    def test_body_that_is_not_json(self):
        self.assert_lookup_fails(b"<html>rate limited</html>", "invalid JSON")

    def test_entry_without_draft_flag(self):
        entry = release("v28.0.1")
        del entry["draft"]
        self.assert_lookup_fails([entry], "invalid release entry")

    def test_request_error(self):
        error = urllib.error.HTTPError(releases_url(STELLAR_RPC_REPO), 403, "rate limit exceeded", None, None)
        self.assert_lookup_fails(error, "failed: HTTP Error 403")

    def test_error_on_second_page(self):
        routes = release_routes(STELLAR_RPC_REPO, [[release("v28.0.1")], [release("v28.1.0")]])
        routes[releases_url(STELLAR_RPC_REPO, 2)] = urllib.error.URLError("connection reset")
        _, patch = serve(routes)
        with patch, self.assertRaisesRegex(ReleaseLookupError, "connection reset"):
            resolve_release(STELLAR_RPC_REPO, None, None)

    def test_selected_release_without_published_at(self):
        entry = release("v28.0.1")
        entry["published_at"] = None
        self.assert_lookup_fails([entry], "no valid published_at")


class OverrideTest(unittest.TestCase):
    PAGES = [[
        release("rpcclient-v24.0.0"),
        release("v29.0.0-rc.1", prerelease=True),
        release("v30.0.0", draft=True),
        release("v28.0.1"),
    ]]

    def resolve(self, tag):
        _, patch = serve(release_routes(STELLAR_RPC_REPO, self.PAGES))
        with patch:
            return resolve_release(STELLAR_RPC_REPO, tag, None)

    def test_present_stable_release(self):
        self.assertEqual(self.resolve("v28.0.1").tag, "v28.0.1")

    def test_prerelease_is_allowed(self):
        self.assertEqual(self.resolve("v29.0.0-rc.1").tag, "v29.0.0-rc.1")

    def test_absent_release_fails(self):
        with self.assertRaisesRegex(ReleaseLookupError, "no release tagged v27.9.9"):
            self.resolve("v27.9.9")

    def test_draft_fails(self):
        with self.assertRaisesRegex(ReleaseLookupError, "v30.0.0 is a draft"):
            self.resolve("v30.0.0")

    def test_tag_outside_the_release_tag_form_fails(self):
        with self.assertRaisesRegex(
            ReleaseLookupError,
            "'rpcclient-v24.0.0' is not a stellar/stellar-rpc release tag of the form vX.Y.Z or vX.Y.Z-suffix",
        ):
            self.resolve("rpcclient-v24.0.0")


if __name__ == "__main__":
    unittest.main()
