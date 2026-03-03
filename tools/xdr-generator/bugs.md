# Hand-Written Code Bugs

Bugs discovered in existing hand-written XDR types during generator comparison.

## Batch 1

### XdrMemoType.php — decode() returns wrong type
- **File**: `Soneso/StellarSDK/Xdr/XdrMemoType.php`
- **Bug**: `decode()` returns `XdrEnvelopeType` and creates `new XdrEnvelopeType($value)` instead of `XdrMemoType`
- **Impact**: Low — a `XdrEnvelopeType` is structurally identical (same int-based enum pattern), so encoding/decoding still works, but the return type is wrong
- **Fixed by**: Generator now produces correct `XdrMemoType::decode()` returning `new XdrMemoType($value)`

## Batch 2

### Missing enum constants in hand-written types
- **XdrLiquidityPoolDepositResultCode**: Missing 3 constants — `LINE_FULL = -5`, `BAD_PRICE = -6`, `POOL_FULL = -7`
- **XdrOperationResultCode**: Missing 1 constant — `TOO_MANY_SPONSORING = -6`
- **XdrSetOptionsResultCode**: Missing 1 constant — `AUTH_REVOCABLE_REQUIRED = -10`
- **Impact**: Medium — missing error codes could cause unhandled cases in switch statements
- **Fixed by**: Generator produces all constants from the XDR spec

### Bugs in types deferred to later batches (not yet fixed)
- **XdrTrustLineFlags**: `decode()` returns `XdrOperationType` instead of `XdrTrustLineFlags` (same pattern as XdrMemoType bug)
- **XdrInvokeHostFunctionResultCode**: `decode()` is an instance method instead of static
- **XdrContractCostType**: `decode()` is an instance method instead of static

## Batch 3

### XdrClaimOfferAtom — signed/unsigned mismatch for offerID
- **File**: `Soneso/StellarSDK/Xdr/XdrClaimOfferAtom.php`
- **Bug**: `offerID` encoded with `unsignedInteger64`/`readUnsignedInteger64`, but XDR spec defines `int64 offerID` (signed)
- **Impact**: Low — values within signed range encode identically
- **Fixed by**: Generator uses `integer64`/`readInteger64`

### XdrCreatePassiveSellOfferOperation — spurious argument in decode()
- **File**: `Soneso/StellarSDK/Xdr/XdrCreatePassiveSellOfferOperation.php`
- **Bug**: `$xdr->readBigInteger64($xdr)` passes spurious `$xdr` argument
- **Impact**: Low — PHP silently ignores extra arguments
- **Fixed by**: Generator produces `$xdr->readBigInteger64()` (no extra arg)

### XdrSimplePaymentResult — wrong destination type
- **File**: `Soneso/StellarSDK/Xdr/XdrSimplePaymentResult.php`
- **Bug**: `$destination` typed as `XdrMuxedAccount`, but XDR spec defines `AccountID destination`
- **Impact**: Medium — incorrect XDR wire encoding for this field
- **Fixed by**: Generator uses `XdrAccountID` per the spec

## Batch 5

### XdrLedgerKeyOffer — signed/unsigned mismatch for offerID
- **File**: `Soneso/StellarSDK/Xdr/XdrLedgerKeyOffer.php`
- **Bug**: `offerID` encoded with `unsignedInteger64`/`readUnsignedInteger64`, but XDR spec defines `int64 offerID` (signed)
- **Impact**: Low — values within signed range encode identically (same pattern as XdrClaimOfferAtom)
- **Fixed by**: Generator uses `integer64`/`readInteger64`

### XdrLedgerKeyData — missing max-length validation on string encode/decode
- **File**: `Soneso/StellarSDK/Xdr/XdrLedgerKeyData.php`
- **Bug**: `getDataName()` return type annotated as `int|string` (should be `string`); also `XdrEncoder::string($this->dataName, 64)` passes max-length 64 but generator omits the limit parameter
- **Impact**: Low — return type annotation is wrong but harmless; max-length validation is a defense-in-depth guard only

## Batch 6

### XdrAllowTrustOperation — signed/unsigned mismatch for authorize field
- **File**: `Soneso/StellarSDK/Xdr/XdrAllowTrustOperation.php`
- **Bug**: `authorized` encoded with `integer32`/`readInteger32` (signed), but XDR spec defines `uint32 authorize` (unsigned)
- **Impact**: Low — valid authorize values (0, 1, 2) encode identically in signed vs unsigned
- **Fixed by**: Generator uses `unsignedInteger32`/`readUnsignedInteger32`

### XdrSetTrustLineFlagsOperation — field name mismatch
- **File**: `Soneso/StellarSDK/Xdr/XdrSetTrustLineFlagsOperation.php`
- **Bug**: Field named `$accountID` instead of `$trustor` (XDR spec: `AccountID trustor`)
- **Impact**: None — field name is internal, getter name `getAccountID()` preserved via override

### XdrSCMetaV0 — field name mismatch
- **File**: `Soneso/StellarSDK/Xdr/XdrSCMetaV0.php`
- **Bug**: Field named `$value` instead of `$val` (XDR spec: `string val<>`)
- **Impact**: None — field name is internal, getter/property access preserved via override
