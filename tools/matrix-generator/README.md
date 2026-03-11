# Compatibility Matrix Generator

Generates compatibility matrices that document how completely the Stellar PHP SDK covers the Stellar ecosystem APIs and standards. The output is a set of Markdown files in the `compatibility/` directory at the repository root.

## Overview

There are three independent generators, one per domain:

| Generator | What it compares | Output |
|-----------|-----------------|--------|
| **Horizon** | SDK RequestBuilder classes vs. Horizon REST API endpoints | `compatibility/horizon/COMPATIBILITY_MATRIX.md` |
| **RPC** | SDK SorobanServer class vs. Stellar RPC JSON-RPC methods | `compatibility/rpc/RPC_COMPATIBILITY_MATRIX.md` |
| **SEP** | SDK implementations vs. 19 Stellar Ecosystem Proposals | `compatibility/sep/SEP-XXXX_COMPATIBILITY_MATRIX.md` (one per SEP) |

Each generator reads the SDK source tree, fetches the latest upstream specification from GitHub (Horizon router, RPC handler code, or SEP documents), and produces a coverage percentage with a detailed breakdown.

## Requirements

Python 3.10+ (standard library only, no third-party packages).

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
# 1. Extract method specs from the stellar-rpc repo (updates rpc_methods.json)
python tools/matrix-generator/rpc/extract_rpc_methods.py

# 2. Generate the matrix
python tools/matrix-generator/rpc/generate_rpc_matrix.py
```

`extract_rpc_methods.py` options:
- `--rpc-version VERSION` -- extract from a specific stellar-rpc release
- `--token TOKEN` -- GitHub token for higher rate limits
- `--output PATH` -- custom output path for the JSON spec
- `--verbose` -- enable verbose output

`generate_rpc_matrix.py` options:
- `--rpc-data PATH` -- path to `rpc_methods.json` (default: `rpc/rpc_methods.json`)
- `--output PATH` -- custom output file path
- `--verbose` -- enable verbose output

### SEP

Generate a single SEP matrix:

```bash
python tools/matrix-generator/sep/generate_sep_matrix.py --sep 10
```

Generate all 19 supported SEPs:

```bash
python tools/matrix-generator/sep/generate_sep_matrix.py --sep 01 02 05 06 07 08 09 10 11 12 24 30 31 38 45 46 47 48 53
```

Options:
- `--sep N [N ...]` -- one or more SEP numbers to analyze (e.g. `01`, `10 12 24`)
- `--list` -- list all SEPs with implemented analyzers
- `--output PATH` -- custom output directory
- `--sdk-root PATH` -- override SDK root (auto-detected by default)

## File Structure

```
tools/matrix-generator/
  horizon/
    generate_horizon_matrix.py   # Horizon endpoint comparator
    horizon_params.py            # Horizon query parameter definitions
  rpc/
    extract_rpc_methods.py       # Extracts RPC specs from GitHub
    generate_rpc_matrix.py       # RPC method comparator
  sep/
    generate_sep_matrix.py       # SEP analyzers (all 19 in one file)
```

Output goes to:

```
compatibility/
  horizon/COMPATIBILITY_MATRIX.md
  rpc/RPC_COMPATIBILITY_MATRIX.md
  sep/SEP-XXXX_COMPATIBILITY_MATRIX.md  (x19)
```

## How It Works

1. **SDK version** is read from `Soneso/StellarSDK/StellarSDK.php` (`VERSION_NR` constant).
2. **Upstream specs** are fetched from GitHub (Horizon router files, RPC handler source, SEP Markdown documents).
3. **SDK source** is scanned using regex pattern matching against the PHP files under `Soneso/StellarSDK/`.
4. **Coverage** is computed per endpoint/method/feature and rendered into Markdown tables.

## When to Regenerate

Regenerate the matrices as part of each SDK release to ensure they reflect the current version number and any coverage changes. The generators are also useful during development to verify that new SDK features are correctly detected.
