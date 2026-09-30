"""Network fakes and release fixtures shared by the matrix generator tests.

No test touches the network: urllib.request.urlopen is replaced by FakeUrlopen,
which serves a fixed map of URLs and answers every other URL with HTTP 404.
"""

from __future__ import annotations

import email.message
import json
import urllib.error
from typing import Any, Optional

API_BASE = "https://api.github.com"


class FakeUrlopenResponse:
    def __init__(self, body: bytes, link: Optional[str]):
        self._body = body
        self.headers = email.message.Message()
        if link:
            self.headers["Link"] = link

    def read(self) -> bytes:
        return self._body

    def __enter__(self) -> "FakeUrlopenResponse":
        return self

    def __exit__(self, *exc_info: Any) -> bool:
        return False


class FakeUrlopen:
    """Replacement for urllib.request.urlopen.

    A route value is an exception instance (raised), a (body, link_header) tuple, or
    a body alone: bytes are served as-is, str as UTF-8, anything else as JSON.
    """

    def __init__(self, routes: dict[str, Any]):
        self.routes = routes
        self.requests: list[Any] = []

    @property
    def urls(self) -> list[str]:
        return [request.full_url for request in self.requests]

    def __call__(self, request: Any, timeout: Optional[float] = None) -> FakeUrlopenResponse:
        self.requests.append(request)
        url = request.full_url
        if url not in self.routes:
            raise urllib.error.HTTPError(url, 404, "Not Found", email.message.Message(), None)
        route = self.routes[url]
        if isinstance(route, BaseException):
            raise route
        body, link = route if isinstance(route, tuple) else (route, None)
        if isinstance(body, str):
            body = body.encode("utf-8")
        elif not isinstance(body, bytes):
            body = json.dumps(body).encode("utf-8")
        return FakeUrlopenResponse(body, link)


def release(
    tag: str,
    *,
    draft: bool = False,
    prerelease: bool = False,
    published_at: str = "2026-08-27T18:40:46Z",
    repo: str = "stellar/stellar-rpc",
) -> dict[str, Any]:
    """One entry of a GitHub release list; drafts carry a null published_at like the API."""
    return {
        "tag_name": tag,
        "draft": draft,
        "prerelease": prerelease,
        "created_at": "2026-08-14T09:00:00Z",
        "published_at": None if draft else published_at,
        "html_url": f"https://github.com/{repo}/releases/tag/{tag}",
    }


def releases_url(repo: str, page: int = 1) -> str:
    url = f"{API_BASE}/repos/{repo}/releases?per_page=100"
    return url if page == 1 else f"{url}&page={page}"


def release_routes(repo: str, pages: list[list[dict[str, Any]]]) -> dict[str, Any]:
    """Serve *pages* as the paginated release list of *repo*, linked by Link rel="next"."""
    routes: dict[str, Any] = {}
    for index, page in enumerate(pages, start=1):
        link = None
        if index < len(pages):
            link = (
                f'<{releases_url(repo, index + 1)}>; rel="next", '
                f'<{releases_url(repo, len(pages))}>; rel="last"'
            )
        routes[releases_url(repo, index)] = (page, link)
    return routes


# (description, release list pages, expected selected tag). Every case holds the
# expected release plus one entry that a weaker rule would select instead.
SELECTION_CASES = [
    ("rpcclient tag listed first", [[release("rpcclient-v24.0.0"), release("v28.0.1")]], "v28.0.1"),
    ("rc tag flagged prerelease", [[release("v29.0.0-rc.1", prerelease=True), release("v28.0.1")]], "v28.0.1"),
    ("rc tag without the prerelease flag", [[release("v29.0.0-rc.1"), release("v28.0.1")]], "v28.0.1"),
    ("suffix-less tag flagged prerelease", [[release("v30.0.0", prerelease=True), release("v28.0.1")]], "v28.0.1"),
    ("draft with null published_at", [[release("v31.0.0", draft=True), release("v28.0.1")]], "v28.0.1"),
    ("creation order is not semver order",
     [[release("v26.0.1"), release("v28.0.1"), release("v27.0.0")]], "v28.0.1"),
    ("numeric version components", [[release("v9.0.0"), release("v10.0.0")]], "v10.0.0"),
    ("higher version on the second page", [[release("v28.0.1")], [release("v28.1.0")]], "v28.1.0"),
]

# (description, release list pages, expected error message pattern).
SELECTION_FAILURE_CASES = [
    ("empty list", [[]], "no stable vX.Y.Z release"),
    ("only prereleases, drafts and foreign tags",
     [[release("v29.0.0-rc.1", prerelease=True), release("v30.0.0", draft=True), release("rpcclient-v24.0.0")]],
     "no stable vX.Y.Z release"),
]
