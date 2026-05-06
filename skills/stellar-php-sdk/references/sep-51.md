# SEP-51: XDR-JSON Encoding

**Purpose:** Convert any Stellar XDR type to and from a SEP-0051-conformant JSON form. Covers every XDR primitive plus the Stellar-specific types (StrKey-encoded addresses, Asset, AssetCode, MuxedAccount, ClaimableBalanceID, SignerKey).
**Prerequisites:** None. Most callers only need the four methods on the target XDR class.
**SDK Namespace:** `Soneso\StellarSDK\Xdr`
**Note:** SEP-51 is currently Draft status (v2.0.1).

## Table of Contents

- [The four-method contract](#the-four-method-contract)
- [Quick round-trip](#quick-round-trip)
- [Numeric encoding (64-bit and wider)](#numeric-encoding-64-bit-and-wider)
- [String escape ladder](#string-escape-ladder)
- [StrKey-encoded types](#strkey-encoded-types)
- [Asset and credit alphanum](#asset-and-credit-alphanum)
- [Discriminated unions](#discriminated-unions)
- [Optionals (null vs void arm)](#optionals-null-vs-void-arm)
- [Canonical JSON normalisation](#canonical-json-normalisation)
- [Errors and validation](#errors-and-validation)

## The four-method contract

Every XDR class under `Soneso\StellarSDK\Xdr\` carries:

```php
public function toJsonValue(): mixed;
public static function fromJsonValue(mixed $value): static;
public function toJson(): string;
public static function fromJson(string $json): static;
```

`toJson` is `json_encode(toJsonValue(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)`. `fromJson` is `static::fromJsonValue(json_decode($json, true, 512, JSON_THROW_ON_ERROR))`. Use the `*Value` pair when composing or piping; use the plain `toJson` / `fromJson` pair at API boundaries. Lower-level primitives shared by every type live on `Soneso\StellarSDK\Xdr\XdrJsonHelper`.

## Quick round-trip

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;

$envelope = XdrTransactionEnvelope::fromBase64Xdr($base64);
$json     = $envelope->toJson();

$decoded  = XdrTransactionEnvelope::fromJson($json);
$base642  = $decoded->toBase64Xdr();   // === $base64
```

`fromBase64Xdr` and `toBase64Xdr` live on every XDR class for the binary boundary; `toJson` and `fromJson` are the SEP-51 boundary. The two pairs compose freely.

## Numeric encoding (64-bit and wider)

| Type | JSON form | Range |
|---|---|---|
| `Int` | number | `-2^31` to `2^31-1` |
| `UnsignedInt` | number | `0` to `2^32-1` |
| `Hyper` | base-10 string (also accepts number on input) | `-2^63` to `2^63-1` |
| `UnsignedHyper` | base-10 string (also accepts number on input) | `0` to `2^64-1` |
| `Int128Parts` | base-10 string | `-2^127` to `2^127-1` |
| `UInt128Parts` | base-10 string | `0` to `2^128-1` |
| `Int256Parts` | base-10 string | `-2^255` to `2^255-1` |
| `UInt256Parts` | base-10 string | `0` to `2^256-1` |

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrJsonHelper;

XdrJsonHelper::int64ToString(2792036);             // "2792036"
XdrJsonHelper::stringToInt64('29059748724737');    // 29059748724737 (PHP int)
XdrJsonHelper::stringToUint64('18446744073709551615'); // wraps to PHP signed int (-1)
$parts = XdrJsonHelper::stringToInt128Parts('-170141183460469231731687303715884105728');
// $parts === ['hi' => '-9223372036854775808', 'lo' => '0']
```

PHP int is 64-bit signed; `stringToUint64` reinterprets values above `PHP_INT_MAX` as signed via two's-complement subtraction so they can be stored in PHP int slots. Pass values above `PHP_INT_MAX` as strings; never as PHP ints.

## String escape ladder

The `escapeString` / `unescapeString` pair implements the SEP-51 String type for any byte sequence. Bytes 0x20..0x7E (excluding `\\`) emit verbatim; 0x00 / 0x09 / 0x0A / 0x0D have short escapes (`\\0`, `\\t`, `\\n`, `\\r`); `\\` becomes `\\\\`; everything else emits as `\\xNN` with two lowercase hex digits.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrJsonHelper;

XdrJsonHelper::escapeString("USDC");                  // "USDC"
XdrJsonHelper::escapeString("\xc3\x9c\x00");          // "\\xc3\\x9c\\0"
XdrJsonHelper::unescapeString('\\xc3\\x9c\\0');       // raw bytes: 0xC3 0x9C 0x00
```

Uppercase hex escapes are rejected on input — the SEP-51 mapping is canonical lowercase.

## StrKey-encoded types

| Type | Prefix | PHP class |
|---|---|---|
| `AccountID`, `PublicKey` | `G` | `XdrAccountID`, `XdrPublicKey` |
| `ContractID` | `C` | bare-typedef sites; full class is `XdrSCAddress` |
| `MuxedAccount` (muxed arm), `MuxedEd25519Account` | `M` | `XdrMuxedAccount`, `XdrMuxedEd25519Account` |
| `ClaimableBalanceID` | `B` | `XdrClaimableBalanceID` |
| `PoolID` | `L` | `XdrPoolID` |
| `SignerKey pre_auth_tx` | `T` | `XdrSignerKey` |
| `SignerKey hash_x` | `X` | `XdrSignerKey` |
| `SignerKey ed25519_signed_payload`, `SignedPayload` | `P` | `XdrSignerKey`, `XdrSignedPayload` |

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrMuxedAccount;
use Soneso\StellarSDK\Xdr\XdrSignerKey;

$muxed = XdrMuxedAccount::fromJson('"GDTJSJTEYGG7L23UZSROA5SNR4GJMOUXYNRDCVMEY3FPB22HUYWQBZIA"');
$muxed->toJsonValue(); // "GDTJSJTEY..."

$signer = XdrSignerKey::fromJson('"TAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAB7"');
$signer->getType()->getValue(); // SIGNER_KEY_TYPE_PRE_AUTH_TX
```

Decode dispatch is by strkey prefix; encode dispatch is by the union discriminant.

## Asset and credit alphanum

`Asset` is a discriminated union; the native arm is the bare string `"native"`; credit arms are single-key objects:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrAsset;

XdrAsset::fromJson('"native"');
// {"credit_alphanum4":{"asset_code":"USDC","issuer":"G..."}}
XdrAsset::fromJson(
    '{"credit_alphanum4":{"asset_code":"USDC","issuer":"GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN"}}'
);
```

```php
// WRONG: native arm wrapped as a single-key object
XdrAsset::fromJson('{"native":null}');
// CORRECT: native arm is a bare string
XdrAsset::fromJson('"native"');
```

`asset_code` itself is a string emitted through the escape ladder. AssetCode4 trims trailing NULs; AssetCode12 also trims but pads back to a minimum of 5 bytes per spec — the PHP SDK rejects an all-NUL AssetCode12 rather than emit a degenerate 5-NUL output.

## Discriminated unions

Non-void arms emit as a single-key object whose key is the lowercase, prefix-stripped arm identifier. Void arms emit as the bare arm name string. Input dispatch first strips a top-level `$schema` key (allowed for tooling annotation), then dispatches on the remaining shape.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrMemo;

XdrMemo::fromJson('"none"');                        // void arm: bare string
XdrMemo::fromJson('{"text":"hello"}');              // non-void arm: single-key object
XdrMemo::fromJson('{"id":"42"}');                   // 64-bit hyper as string

// $schema is stripped before dispatch
XdrMemo::fromJson('{"$schema":"...","text":"hello"}');
```

`$schema` is never emitted on output.

## Optionals (null vs void arm)

A nullable XDR field renders as `null` when absent. This is distinct from a union's void-arm string (e.g. `"none"` for `MEMO_NONE`):

```php
<?php declare(strict_types=1);

// inside a struct:
//   "source_account": null     -> the Optional<MuxedAccount> field is absent
//   "memo":           "none"   -> the Memo union is on its MEMO_NONE void arm
```

```php
// WRONG: void arm decoded from null
XdrMemo::fromJson('null');
// CORRECT: void arm is the bare arm-name string
XdrMemo::fromJson('"none"');
```

`null` and `"none"` are not interchangeable. Confusing them on input throws `InvalidArgumentException` from `fromJsonValue`.

## Canonical JSON normalisation

`XdrJsonHelper::canonicalJson($json)` returns a deterministic byte form: object keys sorted lexicographically at every level, no insignificant whitespace, list element order preserved. Used to obtain a deterministic byte form for snapshot comparison.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrJsonHelper;

XdrJsonHelper::canonicalJson('{"b":1,"a":2}'); // '{"a":2,"b":1}'
XdrJsonHelper::canonicalJson('{"a":2,"b":1}'); // '{"a":2,"b":1}'
```

`ksortRecursive` is exposed for callers that already hold a decoded value and want canonicalisation without the encode round-trip.

## Errors and validation

Every `fromJsonValue` throws `\InvalidArgumentException` on shape errors with `XdrJsonHelper::safePreview($value)` of the offending input embedded in the message. `safePreview` truncates to 80 bytes by default and replaces ASCII control bytes with their `\\xHH` form so logged messages are not vulnerable to ANSI-escape injection.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrAsset;

try {
    XdrAsset::fromJson('"unknown_arm"');
} catch (\InvalidArgumentException $e) {
    // "Unknown XdrAsset bare string: unknown_arm"
}
```

`fromJson` (the JSON-string entry point) throws `\JsonException` on malformed JSON before reaching `fromJsonValue`.
