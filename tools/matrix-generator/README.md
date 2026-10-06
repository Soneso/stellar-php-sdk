# Compatibility Matrix Generator

Generates compatibility matrices that document how completely the Stellar PHP SDK covers the Stellar ecosystem APIs and standards. The output is a set of Markdown files in the `compatibility/` directory at the repository root.

## Overview

There are three independent generators, one per domain:

| Generator | What it compares | Output |
|-----------|-----------------|--------|
| **Horizon** | SDK RequestBuilder classes vs. Horizon REST API endpoints | `compatibility/horizon/COMPATIBILITY_MATRIX.md` |
| **RPC** | SDK SorobanServer class vs. Stellar RPC JSON-RPC methods | `compatibility/rpc/RPC_COMPATIBILITY_MATRIX.md` |
| **SEP** | SDK implementations vs. 23 Stellar Ecosystem Proposals | `compatibility/sep/SEP-XXXX_COMPATIBILITY_MATRIX.md` (one per SEP) |

Each generator reads the SDK source tree, fetches the upstream specification from GitHub (Horizon router, RPC handler code, or SEP documents), and produces a coverage percentage with a detailed breakdown. The SEP checklists are part of the generator. The SEP-23 items come from the SEP document: the key types of its version-byte table and its test vectors. From each SEP document the generator reads the preamble `Version` and `Status`, printed as `SEP Version` and `SEP Status` in the matrix header.

Every script exits non-zero and writes no file when an upstream fetch fails. The same applies to:

- a SEP preamble without a `Status` line (SEP generator)
- a SEP-23 document without a readable version-byte table, `## Tests` section, or valid and invalid test case lists (SEP generator)
- a SEP-23 key type the analyzer has no PHP names for (SEP generator)
- a missing `VersionByte` or `StrKey` class, or a mapped constant or method absent from it (SEP generator)
- a mapped `VersionByte` constant that is not an integer literal or shift expression (SEP generator)
- an unreadable `VersionByte`, `StrKey` or StrKey unit test file (SEP generator)
- an unreadable SEP-51 evidence file, or an evidence pattern that matches no line of it (SEP generator)
- an RPC method without a response definition, or whose response embeds a struct that no fetched go-stellar-sdk protocol file declares (RPC extractor)
- a stellar-rpc release whose `go.mod` pins no go-stellar-sdk module, or whose `jsonrpc.go` method registrations differ from the extractor's method list (RPC extractor)
- a missing `SorobanServer.php` or `Soroban/Responses` directory in the SDK (RPC generator)
- an unreadable SDK version (all three generators)

## Requirements

Python 3.10+. The generators use the standard library only; `rpc/extract_rpc_methods.py` also needs the `requests` package.

## Usage

All commands are run from the repository root.

### Horizon

```bash
python tools/matrix-generator/horizon/generate_horizon_matrix.py
```

Options:
- `--horizon-version VERSION` -- compare against a specific Horizon release (e.g. `v25.0.0`)
- `--output PATH` -- custom output file path
- `--verbose` -- enable debug logging

### RPC

The RPC generator has a two-step workflow:

```bash
# 1. Extract method specs from the stellar-rpc repo (writes rpc/data/rpc_methods.json)
python tools/matrix-generator/rpc/extract_rpc_methods.py

# 2. Generate the matrix
python tools/matrix-generator/rpc/generate_rpc_matrix.py
```

By default the extractor uses the newest stable stellar-rpc release: the highest `vX.Y.Z` tag by semver among releases that are neither drafts nor prereleases. It reads the request and response definitions from the go-stellar-sdk version that the release's `go.mod` pins, and compares its method list with the methods that the release's `jsonrpc.go` registers. Releases before v25.0.0 pin no go-stellar-sdk module, so an override to one of them fails. The generator cites the release the extractor recorded, with its published date and URL.

`extract_rpc_methods.py` options:
- `--rpc-version VERSION` -- extract from a specific stellar-rpc release tagged `vX.Y.Z` or `vX.Y.Z-suffix`; it must exist and must not be a draft (a prerelease is accepted)
- `--token TOKEN` -- GitHub token for higher rate limits
- `--output PATH` -- custom output path for the JSON spec
- `--verbose` -- enable verbose output

`generate_rpc_matrix.py` options:
- `--rpc-data PATH` -- path to `rpc_methods.json` (default: `rpc/data/rpc_methods.json`)
- `--output PATH` -- custom output file path
- `--verbose` -- enable verbose output

### SEP

Generate a single SEP matrix:

```bash
python tools/matrix-generator/sep/generate_sep_matrix.py --sep 10
```

Generate all supported SEPs (currently 23 SEP analyzers):

```bash
python tools/matrix-generator/sep/generate_sep_matrix.py --all
```

Or a specific subset:

```bash
python tools/matrix-generator/sep/generate_sep_matrix.py --sep 10 51
```

Options:
- `--sep N [N ...]` -- one or more SEP numbers to analyze (e.g. `01`, `10 12 24`)
- `--all` -- generate matrices for all supported SEPs
- `--list` -- list all SEPs with implemented analyzers
- `--output PATH` -- custom output directory
- `--sdk-root PATH` -- override SDK root (auto-detected by default)

## File Structure

```
tools/matrix-generator/
  sdk_version.py                 # SDK version lookup shared by the three generators
  horizon/
    generate_horizon_matrix.py   # Horizon endpoint comparator
    horizon_params.py            # Horizon query parameter definitions
  rpc/
    extract_rpc_methods.py       # Extracts RPC specs from GitHub
    generate_rpc_matrix.py       # RPC method comparator
    rpc_releases.py              # stellar-rpc release lookup
  sep/
    generate_sep_matrix.py       # SEP analyzers (all 23 in one file)
  tests/                         # unittest suite, no network access
```

Output goes to:

```
compatibility/
  horizon/COMPATIBILITY_MATRIX.md
  rpc/RPC_COMPATIBILITY_MATRIX.md
  sep/SEP-XXXX_COMPATIBILITY_MATRIX.md  (x23)
```

## How It Works

1. **SDK version** is read from `Soneso/StellarSDK/StellarSDK.php` (`VERSION_NR` constant).
2. **Upstream specs** are fetched from GitHub (Horizon router files, RPC handler source, SEP Markdown documents).
3. **SDK source** is scanned using regex pattern matching against the PHP files under `Soneso/StellarSDK/`. The SEP-23 analyzer also reads `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php`.
4. **Coverage** is computed per endpoint/method/feature and rendered into Markdown tables.

## Tests

```bash
python3 -m unittest discover -s tools/matrix-generator/tests
```

## When to Regenerate

Regenerate the matrices as part of each SDK release to ensure they reflect the current version number and any coverage changes. The generators are also useful during development to verify that new SDK features are correctly detected.
