# SEP-51 method-collision audit

**Date:** 2026-05-04
**Branch baseline:** main @ HEAD
**Methodology:** grep over every PHP file under `Soneso/StellarSDK/Xdr/` for the SEP-51 method names that the implementation will introduce on every XDR class.

## Audit scope

Files audited: 482 PHP files under `Soneso/StellarSDK/Xdr/` (the full tree, including `TxRepHelper.php`).

Methods searched for:
- `toJson`
- `fromJson`
- `toJsonValue`
- `fromJsonValue`
- `toJsonString`
- `fromJsonString`

## Audit command

```bash
grep -rn \
  'function toJson\|function fromJson\|function toJsonValue\|function fromJsonValue\|function toJsonString\|function fromJsonString' \
  Soneso/StellarSDK/Xdr/
```

## Result

The grep produced zero matches (exit code 1). No XDR class under `Soneso/StellarSDK/Xdr/` declares any of the six SEP-51 method names today.

## Disposition

No collisions found. No renames or `@deprecated` aliases are required before SEP-51 method emission begins. D6 in the implementation plan can proceed without precondition cleanup.

## Re-audit triggers

This audit is valid against the baseline tree at `main` HEAD on the date above. Re-run the audit if any of the following occurs before SEP-51 method emission begins:

- New hand-written class is added under `Soneso/StellarSDK/Xdr/` that declares any of the six method names.
- Generator regen produces new `*Base.php` files declaring any of the six method names (this should not happen; the generator is the entity adding these, but a stale generator state could conceivably produce them).
- An external contributor merges a PR that adds JSON serialization on an XDR type via a name in the SEP-51 set.

## Notes on related serialization methods

The audit deliberately scopes to the six SEP-51 method names and does NOT flag the following pre-existing serialization methods, which are unrelated to SEP-51:

- `encode()` / `decode()`: XDR binary serialization, present on every XDR class as the existing canonical XDR codec; SEP-51 does not collide with these.
- `toBase64Xdr()` / `fromBase64Xdr()`: base64-wrapped XDR codec, present on most XDR classes; SEP-51 does not collide with these.
- `toXdr*()` / `fromXdr*()` family: XDR-side helpers that carry no JSON semantics; SEP-51 does not collide with these.
- `TxRepHelper.php`: TxRep-formatted serialization, separate Q2 deliverable on a different branch; SEP-51 does not collide.
