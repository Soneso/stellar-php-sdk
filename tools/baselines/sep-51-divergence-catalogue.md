# SEP-51 divergence catalogue (py-stellar-base v14.0.0)

**Date:** 2026-05-04
**Reference implementation audited:** py-stellar-base v14.0.0 at `/Users/chris/projects/Stellar/py-stellar-base/stellar_sdk/xdr/`
**Specification:** SEP-0051 v2.0.1

This catalogue records every known deviation between the reference implementation (py-stellar-base v14.0.0), the SEP-0051 specification, and the PHP SDK. The PHP SDK follows the specification at every divergence point EXCEPT where explicitly noted as a chosen divergence from spec (currently entry (7) only). Each entry documents the citation in py-stellar-base, the spec rule, and the PHP SDK choice.

The catalogue is consumed by the implementation's cross-SDK gate: corpus entries with `divergence_reason` set are excluded from cross-SDK structural-equality assertions and instead validated against `spec_reference_json` directly. For entries with a `spec_reference_json`, the PHP SDK output must equal that value (which encodes either the spec-conformant string for PHP-follows-spec entries, or the PHP-chosen output for entry (7)-style spec-divergence entries).

---

## Divergence (1) — AssetCode4 and AssetCode12 raw-ASCII decode crash on non-ASCII bytes

**py-stellar-base citations:**

`stellar_sdk/xdr/asset_code4.py:72-73`
> ```python
> def to_json_dict(self) -> str:
>     return self.asset_code4.rstrip(b"\x00").decode("ascii")
> ```

`stellar_sdk/xdr/asset_code12.py:72-74`
> ```python
> def to_json_dict(self) -> str:
>     trimmed = self.asset_code12.rstrip(b"\x00")
>     return self.asset_code12[: max(len(trimmed), 5)].decode("ascii")
> ```

**Spec rule:** SEP-0051 v2.0.1 §XDR Data Types > String — the String type is encoded with the JSON String escape ladder (printable ASCII unescaped; control bytes and bytes >= 0x80 escaped as `\xHH`). Both AssetCode4 and AssetCode12 inherit the String semantics at the typedef level per SEP-51; the spec-conformant escape ladder applies identically to each.

**Divergence reason:** py-stellar-base v14 raw-ASCII decode crashes on non-ASCII for AssetCode4 AND AssetCode12 alike. The `.decode("ascii")` call on either type raises `UnicodeDecodeError` on any byte >= 0x80, where the spec calls for an escape-ladder string emission that always succeeds on any byte sequence.

**PHP SDK choice:** spec — apply the SEP-51 String escape ladder via `XdrJsonHelper::escapeString` after right-trimming trailing NUL bytes (and right-padding to 5 NULs for AssetCode12 per spec line 758-762). Bytes 0x80..0xFF emit as `\xHH` escapes.

---

## Divergence (2) — `$schema` input handling absent in py

**py-stellar-base citation:** absent. No `from_json_dict` method anywhere under `stellar_sdk/xdr/` strips a `$schema` key before validating the input shape; verified via `grep -rn '\$schema' stellar_sdk/xdr/` returning zero matches.

**Spec rule:** SEP-0051 v2.0.1 §JSON Schema — input objects MAY carry a top-level `$schema` key for tooling annotation; consumers MUST silently strip the key before dispatching the remaining shape. Output JSON MUST NOT emit `$schema`.

**Divergence reason:** py would reject any input object containing a `$schema` key with an "unknown discriminator" or "extra field" error path because no implementation strips the key before validation.

**PHP SDK choice:** spec — every `fromJsonValue` for unions and structs strips a top-level `$schema` key as the first dispatch step; an input consisting solely of `$schema` (becomes empty after strip) is invalid SEP-51 and throws `\InvalidArgumentException` at the shape-validation step. `toJsonValue` never emits `$schema`.

---

## Divergence (3) — `XdrAssetCode` union renders as object instead of bare string

**py-stellar-base citation:** `stellar_sdk/xdr/asset_code.py:104-111`
> ```python
> def to_json_dict(self):
>     if self.type == AssetType.ASSET_TYPE_CREDIT_ALPHANUM4:
>         assert self.asset_code4 is not None
>         return {"credit_alphanum4": self.asset_code4.to_json_dict()}
>     if self.type == AssetType.ASSET_TYPE_CREDIT_ALPHANUM12:
>         assert self.asset_code12 is not None
>         return {"credit_alphanum12": self.asset_code12.to_json_dict()}
>     raise ValueError(f"Unknown type in AssetCode: {self.type}")
> ```

**Spec rule:** SEP-0051 v2.0.1 §Stellar-Specific Types lines 723-725 — AssetCode is rendered as a bare String containing the inner code; the discriminant arm is dispatched on the input's byte length on decode (1..4 bytes = AssetCode4 path; 5..12 bytes = AssetCode12 path).

**Divergence reason:** py emits a single-key object with the discriminant prefix as key, treating AssetCode as a generic externally-tagged union. The spec calls for a bare-string emission with length-based dispatch.

**PHP SDK choice:** spec — there is no `XdrAssetCode` class in PHP (the typedef collapses to two separate optional fields on each consuming struct, e.g. `XdrAllowTrustOperationAssetBase.{assetCode4,assetCode12}`). Each consuming site emits whichever inner field is non-null as a single string; from-side dispatches by trimmed-byte-length.

---

## Divergence (4) — `XdrContractID` typedef renders as hex via Opaque

**py-stellar-base citation:** `stellar_sdk/xdr/contract_id.py:68-69`
> ```python
> def to_json_dict(self):
>     return self.contract_id.to_json_dict()
> ```
The inner `Hash.to_json_dict()` delegates to `Opaque.to_json_dict` which emits hex (per `stellar_sdk/xdr/base.py:397-398`).

**Spec rule:** SEP-0051 v2.0.1 §Stellar-Specific Types > Address Types — every appearance of `ContractID` (whether inside an SCAddress or as a standalone typedef site) renders as a C-strkey starting with the `C` prefix.

**Divergence reason:** py routes the standalone typedef through Opaque hex emission instead of strkey. The reference implementation's choice yields a hex string at consuming sites outside SCAddress (e.g. `XdrConfigUpgradeSetKey.contractID`), where the spec calls for C-strkey at every site.

**PHP SDK choice:** spec — the only bare-ContractID consuming site outside SCAddress is `XdrConfigUpgradeSetKeyBase.contractID`. The implementation registers a `sep51_field_overrides.rb` entry that emits C-strkey at that site (with `encoding: :hex` reflecting the wrapper's hex-string storage form per `XdrConfigUpgradeSetKey.php:14`).

---

## Divergence (5) — Standalone `SignerKeyEd25519SignedPayload` renders as struct dict instead of P-strkey

**py-stellar-base citation:** `stellar_sdk/xdr/signer_key_ed25519_signed_payload.py:89-93`
> ```python
> def to_json_dict(self) -> dict:
>     return {
>         "ed25519": self.ed25519.to_json_dict(),
>         "payload": Opaque.to_json_dict(self.payload),
>     }
> ```

**Spec rule:** SEP-0051 v2.0.1 line 664 (under Stellar-Specific Types > Address Types > P-strkey) — `SignerKey` arms encode as their corresponding strkey; the `SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD` arm encodes as P-strkey over the packed ed25519 + payload buffer.

**Divergence reason:** py exposes a standalone `SignerKeyEd25519SignedPayload` class whose `to_json_dict` emits a struct dictionary; the spec calls for P-strkey emission at every site where this type appears. Note that py's `SignerKey.to_json_dict` correctly emits P-strkey on the union arm at `signer_key.py:148-154`; the divergence is scoped to the standalone class only.

**PHP SDK choice:** spec — `XdrSignedPayload` (Cat A standalone, the PHP equivalent) emits P-strkey via `StrKey::encodeXdrSignedPayload($this)`. The `XdrSignerKey.ed25519_signed_payload` arm delegates to `XdrSignedPayload`'s P-strkey encoding. Both sites match spec.

---

## Divergence (6) — `OperationResultCode` and `TransactionResultCode` retain `op`/`tx` prefix in py

**py-stellar-base citation:** `stellar_sdk/xdr/operation_result_code.py:11-19`
> ```python
> _OPERATION_RESULT_CODE_MAP = {
>     0: "opinner",
>     -1: "opbad_auth",
>     -2: "opno_account",
>     -3: "opnot_supported",
>     -4: "optoo_many_subentries",
>     -5: "opexceeded_work_limit",
>     -6: "optoo_many_sponsoring",
> }
> ```

The `_TRANSACTION_RESULT_CODE_MAP` in `transaction_result_code.py` follows the same pattern with `tx` prefixes.

**Spec rule:** SEP-0051 v2.0.1 §XDR Data Types > Enum — enum members are emitted as the prefix-stripped lowercase identifier matching the rs-stellar-xdr canonical algorithm. The `OP_` and `TX_` prefixes that exist in the .x IDL definitions should be stripped per the prefix-stripping algorithm.

**Divergence reason:** py keeps the `op`/`tx` prefix in its emission; this is consistent within py's identifier scheme but diverges from the rs-stellar-xdr canonical algorithm and from spec. PHP-side identifiers under `Soneso/StellarSDK/Xdr/XdrOperationResultCode.php` (verified at lines 11-17) and `Soneso/StellarSDK/Xdr/XdrTransactionResultCode.php` already strip these prefixes at codegen-name level.

**PHP SDK choice:** spec — emit bare member names without prefixes. Algorithm output for OperationResultCode: `[inner, bad_auth, no_account, not_supported, too_many_subentries, exceeded_work_limit, too_many_sponsoring]`. Cross-checked against rs-stellar-xdr canonical output during Phase 0 acceptance.

---

## Divergence (7) — AssetCode12 empty-input behaviour

**py-stellar-base citation:** `stellar_sdk/xdr/asset_code12.py:72-74`
> ```python
> def to_json_dict(self) -> str:
>     trimmed = self.asset_code12.rstrip(b"\x00")
>     return self.asset_code12[: max(len(trimmed), 5)].decode("ascii")
> ```
For an all-NUL 12-byte input, `len(trimmed) == 0`, `max(0, 5) == 5`, so py emits 5 NUL bytes (`"\x00\x00\x00\x00\x00"` raw, `"\\u0000\\u0000\\u0000\\u0000\\u0000"` JSON-escaped).

**Spec rule:** SEP-0051 v2.0.1 §Stellar-Specific Types > AssetCode12 (sep-0051.md:758-762) reads verbatim:
> The `AssetCode12` type should be truncated removing all trailing zero bytes down to and including the 6th byte, ensuring that irrespective of how many zero bytes exist, the resulting encoded string represents at least 5-bytes so as to distinguish it uniquely from any value encoded for `AssetCode4`. Bytes should be encoded according to the [String](#string) XDR data type.

This mandates a minimum 5-byte output even for a fully-trimmed-empty input. py's 5-NUL emission for an all-NUL AssetCode12 is therefore spec-compliant. PHP's throw is a divergence FROM SPEC, not from py — entry (7) is one of the few catalogue items where the PHP SDK intentionally diverges from the spec rather than from py.

**Divergence reason:** PHP SDK chooses to throw `\InvalidArgumentException` on a fully-trimmed-empty AssetCode12 rather than emit 5 NUL bytes per spec. Rationale: an all-NUL AssetCode12 carries no semantic value; failing fast is preferable to a degenerate output. (Note: combined with divergence (1), AssetCode12 with non-ASCII bytes still requires the spec escape ladder; this entry only covers the all-NUL trimmed-empty case.)

**PHP SDK choice:** chosen divergence — throw `\InvalidArgumentException` on a fully-trimmed-empty AssetCode12. This is documented in the corpus as a chosen divergence (the test fixture asserts the throw rather than a 5-NUL output).

---

## Re-validation cadence

This catalogue is valid against py-stellar-base v14.0.0. Per the post-merge maintenance contract, the catalogue is re-validated on every minor SDK release of py-stellar-base. Drift is surfaced via `tools/sep-51-fixtures/refresh_corpus.sh` and routed to the maintainer.
