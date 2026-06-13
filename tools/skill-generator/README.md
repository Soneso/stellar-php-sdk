# Skill Generator

PHP script that generates the agent-skill API reference file
(`skills/stellar-php-sdk/references/api_reference.md`) from the SDK source via
reflection.

## What it does

Extracts the public API of `soneso/stellar-php-sdk` into a compact,
signature-only Markdown reference. The output is consumed by the
`stellar-php-sdk` agent skill so AI coding agents can look up class, method,
constant, and property signatures without reading the raw source.

For each public class, interface, trait, and enum it emits the declaration
header (with `extends` / `implements`), public constants, public properties,
and public methods declared on the type itself (inherited members are not
repeated). Types are grouped into buckets driven by their namespace: core,
crypto, requests, responses, soroban, sep, util, exceptions.

## Requirements

- PHP 8.1+
- Composer dependencies installed (`composer install` from the repository
  root). The script loads `vendor/autoload.php` and reflects the autoloaded
  classes.

## Usage

Run from the repository root:

```bash
php tools/skill-generator/generate_api_reference.php
```

Output is written to
`skills/stellar-php-sdk/references/api_reference.md` (overwriting the previous
generation). Paths are derived from the script location, so it can be invoked
from anywhere.

After regenerating, rebuild the skill archive so the bundled zip matches the
new reference content:

```bash
cd skills
rm -f stellar-php-sdk.zip
cd stellar-php-sdk && zip -r ../stellar-php-sdk.zip . -x "*.DS_Store"
```

## When to regenerate

Regenerate whenever the SDK's public API surface changes:

- New SEP implementation, public class, method, constant, or property in any
  non-XDR namespace
- A type moved between namespaces
- A signature changed (parameter, type, default, return type) or a member
  renamed, deprecated, or removed

Stale generation does not break the SDK, but the agent skill will offer
out-of-date guidance.

## What gets scanned

- **Scanned source**: a recursive walk of `Soneso/StellarSDK/`. A file is
  included when it declares a `class`, `interface`, `trait`, or `enum` whose
  namespace is autoloadable.
- **Excluded namespaces**: `Soneso\StellarSDK\Xdr\` (the generated XDR layer is
  documented separately in the skill's `xdr.md`).
- **Excluded members**: non-public constants, properties, and methods; magic
  methods other than `__construct`; members inherited from a parent class or
  interface (only members declared on the type itself are emitted).
- **Type simplification**: parameter, property, and return types are rendered
  with short class names; nullable types use the `?` prefix; common base
  exceptions and PHP interfaces (`Throwable`, `Stringable`, `JsonSerializable`,
  etc.) are omitted from the header to reduce noise.

## Output format

Each type produces a section like:

```
## class SorobanAuthorizationEntry
SorobanCredentials $credentials
SorobanAuthorizedInvocation $rootInvocation
__construct(SorobanCredentials $credentials, SorobanAuthorizedInvocation $rootInvocation)
buildPreimage(Network $network): XdrHashIDPreimage
sign(KeyPair $signer, Network $network, ?int $signatureExpirationLedger = null, ?string $forAddress = null): void
static withDelegates(SorobanAuthorizationEntry $source, int $signatureExpirationLedger, array $delegates = []): SorobanAuthorizationEntry
```

The script prints class/method counts to stderr on completion. It is
reflection-based, so a class that fails to autoload is reported as a warning on
stderr and skipped rather than aborting the run.
