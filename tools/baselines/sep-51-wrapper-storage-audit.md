# SEP-51 Cat-B wrapper storage-form audit

This document records the runtime storage form (raw bytes vs. 64-character
hex string) of every `(parent_type, field_name)` pair registered in
`tools/xdr-generator/generator/sep51_field_overrides.rb` whose kind is
`:strkey`. The SEP-51 emission pipeline reads `$this->fieldName` at the
moment `toJsonValue` runs; the storage form determines whether the override
must use the `encodeXxxIdHex` (hex-input) or `encodeXxxId` (raw-input)
StrKey method. The discriminator is set by the field-overrides registry
entry's `encoding: :hex | :raw`.

The audit traces each pair to:

- the `*Base.php` decode line that establishes the field's initial form
  when the runtime instance is the bare base class.
- the wrapper `*.php` decode override (if any) that re-establishes the
  field's form when the runtime instance is the wrapper subclass. Wrappers
  always extend their bases via `extends`; runtime instances of these
  Cat-B types are the wrappers, so the wrapper decode path is what
  determines the form `$this->fieldName` carries when SEP-51 emits.
- the storage form actually present in `$this->fieldName` at SEP-51 time.
- the `encoding` setting registered in `sep51_field_overrides.rb`.

Encode-path lines (`hex2bin(...)`) are noted for context only — they are
inputs to the outbound XDR serialisation, not determinants of the inbound
storage form.

| Site | Base file:line (decode) | Wrapper file:line (decode) | Storage form | encoding setting |
|------|-------------------------|----------------------------|--------------|------------------|
| `XdrLiquidityPoolDepositOperationBase.liquidityPoolID` | `XdrLiquidityPoolDepositOperationBase.php:36` (`readOpaqueFixed(32)` -> raw) | `XdrLiquidityPoolDepositOperation.php:28` (override -> `bin2hex(...)` -> hex) | hex | `:hex` |
| `XdrLiquidityPoolWithdrawOperationBase.liquidityPoolID` | `XdrLiquidityPoolWithdrawOperationBase.php:33` (`readOpaqueFixed(32)` -> raw) | `XdrLiquidityPoolWithdrawOperation.php:27` (override -> `bin2hex(...)` -> hex) | hex | `:hex` |
| `XdrTrustlineAssetBase.liquidityPoolID` | `XdrTrustlineAssetBase.php:53` (`readOpaqueFixed(32)` -> raw) | `XdrTrustlineAsset.php` (no decode override; `hex2bin(...)` at line 52 is in the encode/forLiquidityPoolId helper path, not the decode path) | raw | `:raw` |
| `XdrLedgerKeyLiquidityPool.liquidityPoolID` | `XdrLedgerKeyLiquidityPool.php:22` (`readOpaqueFixed(32)` -> raw; no base/wrapper split for this type) | n/a | raw | `:raw` |
| `XdrLiquidityPoolEntry.liquidityPoolID` | `XdrLiquidityPoolEntry.php:25` (`readOpaqueFixed(32)` -> raw; no base/wrapper split for this type) | n/a | raw | `:raw` |
| `XdrClaimLiquidityAtom.liquidityPoolID` | `XdrClaimLiquidityAtom.php:36` (`readOpaqueFixed(32)` -> raw; no base/wrapper split for this type) | n/a | raw | `:raw` |
| `XdrHashIDPreimageRevokeID.liquidityPoolID` | `XdrHashIDPreimageRevokeID.php:37` (`readOpaqueFixed(32)` -> raw; no base/wrapper split for this type) | n/a | raw | `:raw` |
| `XdrConfigUpgradeSetKeyBase.contractID` | `XdrConfigUpgradeSetKeyBase.php:25` (`readOpaqueFixed(32)` -> raw) | `XdrConfigUpgradeSetKey.php:14` (override -> `bin2hex(...)` -> hex) | hex | `:hex` |

## Type-level (stellar_json_overrides.rb) hex-form covered sites

These sites are not in `sep51_field_overrides.rb` because their parent type
has a bespoke type-level override in `stellar_json_overrides.rb`; the audit
is recorded here for completeness because the same hex-vs-raw discriminator
governs which StrKey method the override emits.

| Site | Base decode | Wrapper decode | Storage form | encoder used |
|------|-------------|----------------|--------------|--------------|
| `XdrSCAddressBase.contractId` (SC_ADDRESS_TYPE_CONTRACT arm) | `XdrSCAddressBase.php:54` (`readOpaqueFixed(32)` -> raw) | `XdrSCAddress.php:103` (override -> `bin2hex(...)` -> hex) | hex | `StrKey::encodeContractIdHex` |
| `XdrSCAddressBase.liquidityPoolId` (SC_ADDRESS_TYPE_LIQUIDITY_POOL arm) | `XdrSCAddressBase.php:63` (`readOpaqueFixed(32)` -> raw) | `XdrSCAddress.php:109` (override -> `bin2hex(...)` -> hex) | hex | `StrKey::encodeLiquidityPoolIdHex` |
| `XdrClaimableBalanceIDBase.hash` (CLAIMABLE_BALANCE_ID_TYPE_V0 arm) | base reads via `readOpaqueFixed(32)` -> raw (verified `XdrClaimableBalanceIDBase.php`) | `XdrClaimableBalanceID.php:43` (override -> `bin2hex(...)` -> hex) | hex | hex-decoded back to bytes via `hex2bin($this->hash)` then `StrKey::encodeClaimableBalanceId` over the assembled 33-byte buffer (`"\x00" . $hash_bytes`) |
| `XdrAccountIDBase.accountID->ed25519` (PUBLIC_KEY_TYPE_ED25519) | inner `XdrPublicKey::decode` reads `readOpaqueFixed(32)` -> raw | wrapper does not override the inner XdrPublicKey decode | raw | `StrKey::encodeAccountId` |
| `XdrMuxedAccountBase.ed25519` (KEY_TYPE_ED25519 arm) | `XdrMuxedAccountBase.php:39` (`readOpaqueFixed(32)` -> raw) | `XdrMuxedAccount.php:42` (override constructs from `readOpaqueFixed(32)` directly into `ed25519` slot, still raw) | raw | `StrKey::encodeAccountId` |
| `XdrMuxedAccountMed25519Base.ed25519` | `XdrMuxedAccountMed25519Base.php:26` (`readOpaqueFixed(32)` -> raw) | wrapper does not override decode | raw | `StrKey::encodeMuxedAccountId` over the 40-byte `ed25519 || id (uint64-be)` pack |

## Audit rules recap

- **Default:** when a wrapper does not override `decode()`, or its override
  preserves the field unchanged after `parent::decode()`, the storage form
  equals the base's. The base reads opaque-fixed 32-byte data via
  `readOpaqueFixed(32)` and stores raw bytes by default.
- **Hex-storing wrappers:** when a wrapper's `decode()` runs `bin2hex(...)`
  on the field after `parent::decode()` (or instead of it, by inlining the
  base's decoded value into a `bin2hex` call), the storage form is hex.
- **Encode-path is not a determinant.** `hex2bin(...)` calls in encode
  paths or constructor helpers (such as `XdrTrustlineAsset.php:52`) are
  recorded as evidence of the developer's hex/raw assumption but do not
  change what `$this->fieldName` carries at SEP-51 emission time. The
  decode path is the sole determinant.
- **Per-arm storage forms.** All `:strkey` field-override sites in this
  audit have a single storage form across all paths reaching them.
  Wrappers with conditional bin2hex application across discriminant arms
  (none currently exist among the registered fields) would require
  per-arm splits in the override registry.

## Reviewer verification protocol

1. For each row, open the cited base/wrapper file at the cited line and
   confirm the decode statement matches the recorded form.
2. Confirm the `encoding` value in `sep51_field_overrides.rb` matches the
   storage form column.
3. Confirm StrKey method picked by the codegen (`encodeXxxIdHex` for hex
   storage, `encodeXxxId` for raw storage) matches the encoder column.
4. For type-level overrides, the same verification applies to
   `stellar_json_overrides.rb`'s SCAddress/ClaimableBalanceID/etc. rules.

The eight strkey rows above are exhaustive; any new `:strkey` field
added to `sep51_field_overrides.rb` later requires this table to be
extended in lockstep, with a base/wrapper file:line citation and an
`:hex`/`:raw` storage-form decision recorded alongside the registry row.
