# SEP-51: XDR-JSON Encoding

SEP-51 defines a standard mapping between Stellar's XDR binary format and a stable JSON form. The mapping covers every XDR primitive (integers, opaque bytes, strings, arrays, enums, structs, discriminated unions, optionals) and the Stellar-specific types layered on top of XDR (StrKey-encoded addresses, Asset, AssetCode, MuxedAccount, ClaimableBalanceID, SignerKey).

XDR is compact and unambiguous on the wire, but it is not human-readable. SEP-51 fills that gap: any tool that speaks JSON can inspect, log, diff, or hand-edit a Stellar payload without re-implementing the XDR decoder. The mapping is deterministic per spec, so two SDKs that both follow SEP-51 produce structurally equal JSON for the same input bytes.

Use SEP-51 when:
- Logging or pretty-printing transactions, ledger entries, contract events.
- Exchanging Stellar payloads with services or tooling that prefer JSON over base64 XDR.
- Diffing two transactions or contract values without a binary diff tool.
- Hand-editing test fixtures or small payloads in a JSON editor.

Use binary base64 XDR when wire size matters, when you submit transactions to Horizon or Soroban RPC, or when you store envelopes for cryptographic verification.

See the [SEP-51 specification](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0051.md) for the full encoding rules.

## Quick Start

> Every example below assumes Composer's autoloader has been loaded (`require __DIR__ . '/vendor/autoload.php';` or equivalent). The autoload line is omitted from each fenced block for readability.

Every PHP XDR class that participates in SEP-51 carries the same four methods: `toJsonValue()`, `fromJsonValue($value)`, `toJson()`, `fromJson($json)`. The `*Value` pair returns or accepts a native PHP structure (string, int, array, stdClass-like array); the plain `toJson` / `fromJson` pair handles the JSON string boundary.

Decode a base64 transaction envelope, then re-emit it as JSON:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;

$base64 = 'AAAAAgAAAADmmSZkwY3163TMouB2TY8MljqXw2IxVYTGyvDrR6YtAAAqmmQAABpuAAAAAQAAAAAAAAAAAAAAAQAAAAAAAAAYAAAAAQAAAAEAAAAAAAAAAQAAAAAAAAABAAAAAAAAAAAAAAABAAAABgAAAAHXkotywnA8z+r365/0701QSlWouXn8m0UOoshCtNHOYQAAABQAAAABAAI9fQAAAAAAAAD4AAAAAAAqmgAAAAABR6YtAAAAAEArDtxbqUI+CsdkRmV0lFhVt0wyB7fyrmmkM6Fr35wpPcK8WKcXeKTl4BQ+akE14MZtpaea9LMdhXopaW3pJA0E';

$envelope = XdrTransactionEnvelope::fromBase64Xdr($base64);
$json = $envelope->toJson();

$decoded = json_decode($json, true);
echo $decoded['tx']['tx']['source_account'] . PHP_EOL;
echo $decoded['tx']['tx']['fee'] . PHP_EOL;
```
<!-- expected: GDTJSJTEYGG7L23UZSROA5SNR4GJMOUXYNRDCVMEY3FPB22HUYWQBZIA
2792036
-->

The inverse direction takes JSON back to the binary form:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrAsset;

$json = '"native"';
$asset = XdrAsset::fromJson($json);
echo $asset->toBase64Xdr() . PHP_EOL;
```
<!-- expected: AAAAAA==
-->

## Supported Types

SEP-51 covers four families of types:

1. **XDR primitives.** 32-bit and 64-bit signed and unsigned integers, hyper integers (64-bit) and unsigned hyper, booleans, fixed and variable opaque, strings, fixed and variable arrays.
2. **XDR composites.** Enums, structs, discriminated unions (void and non-void arms), optional values.
3. **Stellar-specific types.** StrKey-encoded addresses (G, C, M, B, L, T, X, P prefixes), Asset, AssetCode4 / AssetCode12, MuxedAccount, ClaimableBalanceID, SignerKey, ContractID.
4. **Composite envelopes.** TransactionEnvelope and every nested struct, including operation bodies, contract host functions, and Soroban authorization entries.

Per-type status with file:line evidence is tracked in [`compatibility/sep/SEP-0051_COMPATIBILITY_MATRIX.md`](../../compatibility/sep/SEP-0051_COMPATIBILITY_MATRIX.md). The matrix is generated; do not hand-edit it.

## Numeric Encoding

XDR has 32-bit and 64-bit integer types and the wider 128-bit / 256-bit composite parts used by Soroban contracts. SEP-51 encodes them as follows:

| XDR type | JSON form | Range |
|---|---|---|
| `Int` (32-bit signed) | JSON number | `-2^31` to `2^31-1` |
| `UnsignedInt` (32-bit unsigned) | JSON number | `0` to `2^32-1` |
| `Hyper` (64-bit signed) | JSON string of base-10 digits | `-2^63` to `2^63-1` |
| `UnsignedHyper` (64-bit unsigned) | JSON string of base-10 digits | `0` to `2^64-1` |
| 128-bit signed (`Int128Parts`) | JSON string of base-10 digits | `-2^127` to `2^127-1` |
| 128-bit unsigned (`UInt128Parts`) | JSON string of base-10 digits | `0` to `2^128-1` |
| 256-bit signed (`Int256Parts`) | JSON string of base-10 digits | `-2^255` to `2^255-1` |
| 256-bit unsigned (`UInt256Parts`) | JSON string of base-10 digits | `0` to `2^256-1` |

64-bit and wider integers are emitted as strings on output because JSON numbers cannot reliably round-trip past 2^53 in JavaScript clients. On input the PHP SDK accepts base-10 digit strings for all integer types and (for backward compatibility with implementations that rely on JavaScript's number type) accepts a JSON number for `Hyper` and `UnsignedHyper` as well. Larger types (128-bit and 256-bit) require strings on input.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrJsonHelper;

echo XdrJsonHelper::int64ToString(2792036) . PHP_EOL;
echo XdrJsonHelper::stringToInt64('29059748724737') . PHP_EOL;
echo XdrJsonHelper::int128PartsToString('0', '1000000') . PHP_EOL;
$parts = XdrJsonHelper::stringToInt128Parts('-170141183460469231731687303715884105728');
echo $parts['hi'] . ' / ' . $parts['lo'] . PHP_EOL;
```
<!-- expected: 2792036
29059748724737
1000000
-9223372036854775808 / 0
-->

## Per-type Details

The numeric and Stellar-specific encodings each get their own section above and below; the remaining XDR primitive and composite forms are summarised here. Every rule corresponds 1:1 to a subsection of SEP-0051's Specification.

### Boolean

The XDR `bool` type maps to the JSON literal `true` or `false`. There is no string variant. Inside an `SCVal` the boolean payload is wrapped in the discriminated-union object `{"bool": true}`; the inner value is still the bare JSON literal.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrSCVal;

$true = XdrSCVal::forBool(true);
$false = XdrSCVal::forBool(false);
echo $true->toJson() . PHP_EOL;
echo $false->toJson() . PHP_EOL;
```
<!-- expected: {"bool":true}
{"bool":false}
-->

### Opaque (Fixed and Variable)

Both fixed-length (`opaque[N]`) and variable-length (`opaque<>`) XDR opaque types render as a lowercase hex string. Length is implied by the schema for fixed-length opaques and by the encoded byte count for variable-length opaques; SEP-51 does not add an outer wrapper. `XdrJsonHelper::bytesToHex` and `XdrJsonHelper::hexToBytes` perform the round-trip.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrDecoratedSignature;

$sig = new XdrDecoratedSignature("\x01\x02\x03\x04", "\xab\xcd\xef");
echo $sig->toJson() . PHP_EOL;
```
<!-- expected: {"hint":"01020304","signature":"abcdef"}
-->

### Arrays (Fixed and Variable)

Both fixed-length and variable-length XDR arrays render as JSON arrays. Element order is preserved verbatim; SEP-51 does not reorder array elements during canonicalisation, so the producer's emission order is part of the contract.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrSCVal;

$vec = XdrSCVal::forVec([
    XdrSCVal::forU32(1),
    XdrSCVal::forU32(2),
    XdrSCVal::forU32(3),
]);
echo $vec->toJson() . PHP_EOL;
```
<!-- expected: {"vec":[{"u32":1},{"u32":2},{"u32":3}]}
-->

### Enum

An XDR enum member renders as a snake_case string. The encoder strips the longest shared prefix across the enum's members before applying the camelCase-to-snake_case rewrite, so the JSON form drops redundant per-enum scoping. For example `SCValType` has members `SCV_BOOL`, `SCV_VOID`, `SCV_U32`, ...; the shared `SCV_` prefix is removed and the remainder lowercased to give `bool`, `void`, `u32`, etc.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrSCValType;

echo XdrSCValType::BOOL()->toJson() . PHP_EOL;
echo XdrSCValType::VOID()->toJson() . PHP_EOL;
echo XdrSCValType::U32()->toJson() . PHP_EOL;
echo XdrSCValType::SCV_CONTRACT_INSTANCE()->toJson() . PHP_EOL;
```
<!-- expected: "bool"
"void"
"u32"
"contract_instance"
-->

### Struct

An XDR struct renders as a JSON object whose keys are the struct field names converted to snake_case. The PHP SDK preserves field declaration order in `toJsonValue`; consumers that need a deterministic byte form can pipe the output through `XdrJsonHelper::canonicalJson` to sort keys lexicographically. `TtlEntry { keyHash: Hash, liveUntilLedgerSeq: uint32 }` round-trips as the two-key object shown below.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrTTLEntry;

$entry = new XdrTTLEntry(str_repeat("\x01", 32), 1);
$json = $entry->toJson();
echo $json . PHP_EOL;

$decoded = XdrTTLEntry::fromJson($json);
echo $decoded->getLiveUntilLedgerSeq() . PHP_EOL;
```
<!-- expected: {"key_hash":"0101010101010101010101010101010101010101010101010101010101010101","live_until_ledger_seq":1}
1
-->

### `$schema` passthrough

SEP-0051 says any JSON object SHOULD allow a `$schema` key for tooling annotation, but never require one. The PHP SDK's `fromJsonValue` strips a top-level `$schema` from any object before dispatching to field decoding, so JSON produced by a tool that injected the annotation still decodes cleanly. `toJsonValue` never emits `$schema`; the annotation is purely an input convenience.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrTTLEntry;

$annotated = '{"$schema":"https://stellar.org/schema/xdr-json/main/TtlEntry.json","key_hash":"0202020202020202020202020202020202020202020202020202020202020202","live_until_ledger_seq":42}';

$entry = XdrTTLEntry::fromJson($annotated);
echo $entry->toJson() . PHP_EOL;
```
<!-- expected: {"key_hash":"0202020202020202020202020202020202020202020202020202020202020202","live_until_ledger_seq":42}
-->

## Stellar-Specific Types

### StrKey-encoded addresses

Every Stellar address type that has a StrKey representation is emitted as the StrKey string, never as raw bytes:

| Type | Prefix | Contents |
|---|---|---|
| `AccountID`, `PublicKey`, `SignerKey ed25519` | `G` | 32-byte ed25519 public key |
| `ContractID`, `SCAddress contract` | `C` | 32-byte contract id |
| `MuxedAccount muxed`, `SCAddress muxed_account` | `M` | 32-byte ed25519 + 8-byte id |
| `ClaimableBalanceID`, `SCAddress claimable_balance` | `B` | 1-byte type + 32-byte hash |
| `PoolID`, `SCAddress liquidity_pool` | `L` | 32-byte pool id |
| `SignerKey pre_auth_tx` | `T` | 32-byte tx hash |
| `SignerKey hash_x` | `X` | 32-byte hash |
| `SignerKey ed25519_signed_payload`, `SignedPayload` | `P` | packed ed25519 + payload bytes |

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrMuxedAccount;

$json = '"GDTJSJTEYGG7L23UZSROA5SNR4GJMOUXYNRDCVMEY3FPB22HUYWQBZIA"';
$muxed = XdrMuxedAccount::fromJson($json);
echo $muxed->toJsonValue() . PHP_EOL;
```
<!-- expected: GDTJSJTEYGG7L23UZSROA5SNR4GJMOUXYNRDCVMEY3FPB22HUYWQBZIA
-->

### Asset

`Asset` is a discriminated union with three arms. The native arm renders as the bare string `"native"`; credit assets render as a single-key object whose key encodes the alphanum width:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrAsset;

$native = XdrAsset::fromJson('"native"');
echo $native->toJson() . PHP_EOL;

$credit = XdrAsset::fromJson(
    '{"credit_alphanum4":{"asset_code":"USDC","issuer":"GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN"}}'
);
echo $credit->toJson() . PHP_EOL;
```
<!-- expected: "native"
{"credit_alphanum4":{"asset_code":"USDC","issuer":"GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN"}}
-->

### AssetCode (escape ladder)

`AssetCode4` and `AssetCode12` carry up to 4 or 12 raw bytes that are nominally ASCII but may contain non-printable or non-ASCII bytes. SEP-51 emits them through the String escape ladder so that any byte sequence is representable as a printable JSON string:

- Bytes `0x20`..`0x7E` (printable ASCII) emit verbatim, except `\\` becomes `\\\\`.
- `0x00` becomes `\\0`, `0x09` becomes `\\t`, `0x0A` becomes `\\n`, `0x0D` becomes `\\r`.
- All other bytes emit as `\\xNN` with two lowercase hex digits.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrJsonHelper;

echo XdrJsonHelper::escapeString("USDC") . PHP_EOL;
echo XdrJsonHelper::escapeString("\xc3\x9c\x00\x01") . PHP_EOL;
echo bin2hex(XdrJsonHelper::unescapeString('\\xc3\\x9c\\0\\x01')) . PHP_EOL;
```
<!-- expected: USDC
\xc3\x9c\0\x01
c39c0001
-->

### ClaimableBalanceID

`ClaimableBalanceID` emits as a `B`-prefixed StrKey containing the 1-byte type discriminant followed by the 32-byte balance hash:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrClaimableBalanceID;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceIDType;

$cb = new XdrClaimableBalanceID(
    new XdrClaimableBalanceIDType(XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0),
    str_repeat('aa', 32)
);
echo $cb->toJson() . PHP_EOL;
```
<!-- expected: "BAAKVKVKVKVKVKVKVKVKVKVKVKVKVKVKVKVKVKVKVKVKVKVKVKVKVKTLVE"
-->

### SignerKey

`SignerKey` is a four-arm union; each arm renders as the strkey for that key kind (`G`, `T`, `X`, or `P`):

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrSignerKey;

$g = XdrSignerKey::fromJson('"GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ"');
echo $g->getType()->getValue() . PHP_EOL;

$t = XdrSignerKey::fromJson('"TBQWCYLBMFQWCYLBMFQWCYLBMFQWCYLBMFQWCYLBMFQWCYLBMFQWCMKO"');
echo $t->getType()->getValue() . PHP_EOL;
```
<!-- expected: 0
1
-->

## Round-trip Semantics

There are two notions of equality for SEP-51 round-trips:

- **Byte equality.** Two JSON strings are byte-identical: same key order, same whitespace, same number formatting. Practical SEP-51 producers do not all emit the same byte sequence — JSON object key order is not specified, and small whitespace differences are common.
- **Structural equality.** Two JSON values decode to the same logical structure: same keys, same scalar values, same array order. This is the contract SEP-51 actually guarantees.

`XdrJsonHelper::canonicalJson` normalises a JSON string to a deterministic byte form by lexicographically sorting object keys at every level and stripping insignificant whitespace. After canonical normalisation, structural equality coincides with byte equality.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrJsonHelper;

$a = '{"b":1,"a":2}';
$b = '{"a":2,"b":1}';
echo XdrJsonHelper::canonicalJson($a) . PHP_EOL;
echo XdrJsonHelper::canonicalJson($b) . PHP_EOL;
echo (XdrJsonHelper::canonicalJson($a) === XdrJsonHelper::canonicalJson($b) ? 'equal' : 'differ') . PHP_EOL;
```
<!-- expected: {"a":2,"b":1}
{"a":2,"b":1}
equal
-->

A round-trip through XDR is lossless: parsing a base64 envelope to PHP objects, emitting JSON, parsing the JSON back to PHP objects, and re-encoding to base64 must yield the original bytes.

## Pitfalls

### Void-arm strings versus optional null

A union arm whose payload is `void` renders as a bare string equal to the arm name (for example `"none"` for the `MEMO_NONE` arm of `Memo`). An optional value that is `null` renders as the JSON literal `null`. They are not interchangeable: `"none"` is the value for the void arm, `null` is the absence of a value. Confusing the two on input produces an `InvalidArgumentException` from `fromJsonValue` rather than silent corruption.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrMemo;

$memo = XdrMemo::fromJson('"none"');
echo $memo->getType()->getValue() . PHP_EOL;
```
<!-- expected: 0
-->

## API Reference

Every SEP-51-aware XDR class in `Soneso\StellarSDK\Xdr` carries the same four methods:

```php
public function toJsonValue(): mixed;
public static function fromJsonValue(mixed $value): static;
public function toJson(): string;
public static function fromJson(string $json): static;
```

The semantics:

- `toJsonValue` returns the value-level PHP representation: a string for strkey-rendered types, an int for 32-bit integers, an associative array for structs, a single-key array for non-void union arms, `null` for null optionals, and the bare arm name for void union arms.
- `fromJsonValue` accepts the inverse of `toJsonValue`. Input shape errors throw `InvalidArgumentException` with a bounded preview of the offending value (control bytes are escaped to prevent log injection).
- `toJson` is `json_encode($this->toJsonValue(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)`. It throws `JsonException` on encode failure.
- `fromJson` is `static::fromJsonValue(json_decode($json, true, 512, JSON_THROW_ON_ERROR))`. It throws `JsonException` on malformed input and `InvalidArgumentException` on shape mismatch.

`Soneso\StellarSDK\Xdr\XdrJsonHelper` exposes the lower-level primitives that every type's `toJsonValue` / `fromJsonValue` delegate to: `escapeString`, `unescapeString`, `bytesToHex`, `hexToBytes`, the integer conversion helpers (`int64ToString`, `stringToInt64`, `uint64ToString`, `stringToUint64`, `int128PartsToString`, `stringToInt128Parts`, the `uint128` and `int256` / `uint256` equivalents), `canonicalJson`, `ksortRecursive`, and `safePreview`.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrJsonHelper;

echo XdrJsonHelper::bytesToHex("\x00\xff\x10") . PHP_EOL;
echo bin2hex(XdrJsonHelper::hexToBytes('00ff10')) . PHP_EOL;
echo XdrJsonHelper::safePreview("hello\x07world", 16) . PHP_EOL;
```
<!-- expected: 00ff10
00ff10
hello\x07world
-->

## Related SEPs

- [SEP-23](sep-23.md) — StrKey encoding, the underlying address-string format SEP-51 uses for Stellar-specific types.

## Further Reading

- [SEP-51 specification](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0051.md)
- [`compatibility/sep/SEP-0051_COMPATIBILITY_MATRIX.md`](../../compatibility/sep/SEP-0051_COMPATIBILITY_MATRIX.md) — per-type status with file:line evidence (generated).

---

[Back to SEP Overview](README.md)
