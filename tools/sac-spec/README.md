# Stellar Asset Contract spec

A Stellar Asset Contract (SAC) is built into the host and has no wasm, so its spec
cannot be read from the ledger. `generate_sac_spec.py` embeds the spec that
`stellar/stellar-asset-contract-spec` publishes as `stellar-asset-spec.xdr`, a
sequence of XDR `SCSpecEntry` values, into
`Soneso/StellarSDK/Soroban/Contract/SACSpec.php`. `SACClient::specEntries()` decodes
it, and `SorobanClient::forClientOptions()` uses it for a SAC instance.

The generated file is never edited by hand.

## Files

- `pin.json`: the upstream `repository`, `commit`, `file` and `sha256`, the `template`
  and the `output`. The template and output paths are relative to the repository root.
- `SACSpec.php.template`: the output with the placeholders `{{REPOSITORY}}`, `{{FILE}}`,
  `{{COMMIT}}`, `{{SHA256}}`, `{{SIZE}}` and `{{BASE64}}`. Everything specific to this
  repository lives in the pin and the template; the script itself is language-neutral.
- `generate_sac_spec.py`: the generator, Python 3.9 or later, standard library only.
- `tests/`: offline tests of the generator.

## Running

```sh
python3 tools/sac-spec/generate_sac_spec.py [--pin PATH] [--root DIR] [--source FILE]
```

Without `--source` the script downloads the file from the raw URL of the pinned commit,
`https://raw.githubusercontent.com/<repository>/<commit>/<file>`, with a 60-second
timeout. With `--source` it reads a local copy. The pin defaults to `pin.json` beside the
script, and the root to the repository root.

A sha256 other than the pinned one prints both digests to stderr, writes nothing, and
exits 1. A placeholder left unfilled, a download error, or a file error also exits 1 with
nothing written. The output carries no timestamp, so a run at the same pin reproduces the
committed file byte for byte.

The tests run offline:

```sh
python3 -m unittest discover -s tools/sac-spec/tests
```

## Bumping the pin

1. Pick the upstream commit and compute the sha256 of `stellar-asset-spec.xdr` at that
   commit, for example with `sha256sum` on the downloaded file.
2. Set `commit` and `sha256` in `pin.json`.
3. Run the generator, then the unit suite as `AGENTS.md` describes.

`Soneso/StellarSDKTests/Unit/Soroban/SACSpecTest.php` asserts the size of the spec and
its function and event names. A changed size or a changed function or event set fails
that test until the same commit updates its expectations.
