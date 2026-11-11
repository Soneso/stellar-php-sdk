#!/usr/bin/env python3
"""Generate the embedded Stellar Asset Contract spec from a pinned upstream file.

Usage: generate_sac_spec.py [--pin PATH] [--root DIR] [--source FILE]

The pin (default: pin.json beside this script) names the upstream repository,
commit, file and sha256, the template and the output. Template and output
paths resolve against the root (default: two directories above this script).
The spec is read from --source when given, else downloaded from the raw URL of
the pinned commit. A digest other than the pinned one, a missing placeholder
value, or a download or file error exits 1 and writes nothing. The output has
no timestamp, so a run at the same pin reproduces it byte for byte.

Python 3.9 or later, standard library only.
"""

import argparse
import base64
import hashlib
import http.client
import json
import os
import re
import sys
import tempfile
import urllib.error
import urllib.request
from typing import Dict, List, Optional

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
DEFAULT_PIN = os.path.join(SCRIPT_DIR, "pin.json")
DEFAULT_ROOT = os.path.dirname(os.path.dirname(SCRIPT_DIR))
RAW_URL = "https://raw.githubusercontent.com/{repository}/{commit}/{file}"
DOWNLOAD_TIMEOUT_SECONDS = 60
PIN_KEYS = ("repository", "commit", "file", "sha256", "template", "output")
COMMIT_PATTERN = re.compile(r"[0-9a-f]{40}")
SHA256_PATTERN = re.compile(r"[0-9a-f]{64}")


class GenerationError(Exception):
    """A failure that ends the run with exit status 1 and nothing written."""


def load_pin(path: str) -> Dict[str, str]:
    """Read the pin and check its keys and the format of commit and sha256."""
    try:
        with open(path, "r", encoding="utf-8") as handle:
            pin = json.load(handle)
    except (OSError, UnicodeDecodeError) as error:
        raise GenerationError("cannot read pin {}: {}".format(path, error)) from error
    except ValueError as error:
        raise GenerationError("pin {} is not valid JSON: {}".format(path, error)) from error
    if not isinstance(pin, dict):
        raise GenerationError("pin {} must hold a JSON object".format(path))
    for key in PIN_KEYS:
        value = pin.get(key)
        if not isinstance(value, str) or value == "":
            raise GenerationError("pin {} needs a non-empty string '{}'".format(path, key))
    if COMMIT_PATTERN.fullmatch(pin["commit"]) is None:
        raise GenerationError(
            "pin {}: commit must be 40 lowercase hex characters, got '{}'".format(path, pin["commit"]))
    if SHA256_PATTERN.fullmatch(pin["sha256"]) is None:
        raise GenerationError(
            "pin {}: sha256 must be 64 lowercase hex characters, got '{}'".format(path, pin["sha256"]))
    return {key: pin[key] for key in PIN_KEYS}


def read_source(path: str) -> bytes:
    """Read the spec bytes from a local file."""
    try:
        with open(path, "rb") as handle:
            return handle.read()
    except OSError as error:
        raise GenerationError("cannot read source {}: {}".format(path, error)) from error


def download(url: str) -> bytes:
    """Download the spec bytes from the raw URL of the pinned commit."""
    try:
        with urllib.request.urlopen(url, timeout=DOWNLOAD_TIMEOUT_SECONDS) as response:
            return response.read()
    except (urllib.error.URLError, http.client.HTTPException, OSError, ValueError) as error:
        raise GenerationError("cannot download {}: {}".format(url, error)) from error


def render(template: str, values: Dict[str, str]) -> str:
    """Fill every {{NAME}} placeholder; a leftover "{{" is an error."""
    text = template
    for name, value in values.items():
        text = text.replace("{{" + name + "}}", value)
    if "{{" in text:
        line = text.count("\n", 0, text.index("{{")) + 1
        raise GenerationError("template leaves an unfilled placeholder at output line {}".format(line))
    return text


def write_atomically(path: str, text: str) -> None:
    """Write UTF-8 with LF line endings through a temporary file and os.replace."""
    data = text.replace("\r\n", "\n").encode("utf-8")
    directory = os.path.dirname(path) or "."
    temp_path = None
    try:
        handle, temp_path = tempfile.mkstemp(prefix=".sac-spec-", dir=directory)
        with os.fdopen(handle, "wb") as stream:
            stream.write(data)
        # mkstemp creates the file readable by the owner only; give the output the
        # permissions a plain file creation would.
        umask = os.umask(0)
        os.umask(umask)
        os.chmod(temp_path, 0o666 & ~umask)
        os.replace(temp_path, path)
        temp_path = None
    except OSError as error:
        raise GenerationError("cannot write output {}: {}".format(path, error)) from error
    finally:
        if temp_path is not None and os.path.exists(temp_path):
            os.remove(temp_path)


def generate(pin_path: str, root: str, source: Optional[str]) -> str:
    """Run one generation and return the output path."""
    pin = load_pin(pin_path)
    if source is not None:
        spec = read_source(source)
    else:
        spec = download(RAW_URL.format(repository=pin["repository"], commit=pin["commit"], file=pin["file"]))

    digest = hashlib.sha256(spec).hexdigest()
    if digest != pin["sha256"]:
        raise GenerationError(
            "sha256 mismatch for {}: expected {}, got {}".format(pin["file"], pin["sha256"], digest))

    template_path = os.path.join(root, pin["template"])
    output_path = os.path.join(root, pin["output"])
    try:
        with open(template_path, "r", encoding="utf-8", newline="") as handle:
            template = handle.read()
    except (OSError, UnicodeDecodeError) as error:
        raise GenerationError("cannot read template {}: {}".format(template_path, error)) from error

    text = render(template, {
        "REPOSITORY": pin["repository"],
        "FILE": pin["file"],
        "COMMIT": pin["commit"],
        "SHA256": digest,
        "SIZE": "{:,}".format(len(spec)),
        "BASE64": base64.b64encode(spec).decode("ascii"),
    })
    write_atomically(output_path, text)
    return output_path


def main(argv: Optional[List[str]] = None) -> int:
    """Parse the arguments, generate, and return the exit status."""
    parser = argparse.ArgumentParser(description="Generate the embedded Stellar Asset Contract spec.")
    parser.add_argument("--pin", default=DEFAULT_PIN, help="pin file (default: pin.json beside this script)")
    parser.add_argument("--root", default=DEFAULT_ROOT,
                        help="directory the pin paths resolve against (default: the repository root)")
    parser.add_argument("--source", default=None, help="read the spec from this file instead of downloading it")
    args = parser.parse_args(argv)
    try:
        output_path = generate(args.pin, args.root, args.source)
    except GenerationError as error:
        print("error: {}".format(error), file=sys.stderr)
        return 1
    print("wrote {}".format(output_path))
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
