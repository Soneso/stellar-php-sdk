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

## Batch 7

### XdrPathPaymentStrictReceiveOperation / XdrPathPaymentStrictSendOperation — silent XDR corruption from instanceof guard
- **Files**: `Soneso/StellarSDK/Xdr/XdrPathPaymentStrictReceiveOperation.php`, `Soneso/StellarSDK/Xdr/XdrPathPaymentStrictSendOperation.php`
- **Bug**: encode() writes `integer32(count($this->path))` as the array length, then uses `if ($asset instanceof XdrAsset)` to conditionally encode each element — if a non-XdrAsset element were present, the encoded count would exceed the number of encoded items, producing corrupt XDR
- **Impact**: Low — in practice the array always contains XdrAsset instances, but the guard masks type errors rather than failing loudly
- **Fixed by**: Generator encodes all array elements unconditionally, letting PHP's type system catch errors

## Batch 8

### XdrContractCostType — decode() is instance method instead of static
- **File**: `Soneso/StellarSDK/Xdr/XdrContractCostType.php`
- **Bug**: `decode()` is an instance method (`public function decode`) instead of `public static function decode`
- **Impact**: Low — decode() was never called anywhere in the codebase
- **Fixed by**: Generator produces correct static decode method

### XdrTrustLineFlags — decode() returns wrong type
- **File**: `Soneso/StellarSDK/Xdr/XdrTrustLineFlags.php`
- **Bug**: `decode()` returns `XdrOperationType` and creates `new XdrOperationType($value)` instead of `XdrTrustLineFlags`
- **Impact**: Low — decode() was never called anywhere in the codebase (same pattern as XdrMemoType bug in Batch 1)
- **Fixed by**: Generator produces correct `XdrTrustLineFlags::decode()` returning `new XdrTrustLineFlags($value)`

### XdrContractCostType — missing 25 enum constants
- **File**: `Soneso/StellarSDK/Xdr/XdrContractCostType.php`
- **Bug**: Hand-written code had 44 constants (up to VerifyEcdsaSecp256r1Sig=44), missing 25 BLS12-381 constants (Bls12381EncodeFp=45 through Bls12381FrInv=69)
- **Impact**: Medium — missing constants could cause unhandled cases for BLS12-381 operations
- **Fixed by**: Generator produces all 70 constants from the XDR spec

## Batch 9

### XdrOperationMeta / XdrTransactionMetaV1 / XdrTransactionMetaV2 — silent XDR corruption from instanceof guard
- **Files**: `Soneso/StellarSDK/Xdr/XdrOperationMeta.php`, `Soneso/StellarSDK/Xdr/XdrTransactionMetaV1.php`, `Soneso/StellarSDK/Xdr/XdrTransactionMetaV2.php`
- **Bug**: encode() writes `integer32(count($array))` as the array length, then uses `if ($val instanceof XdrLedgerEntryChange)` or `if ($val instanceof XdrOperationMeta)` to conditionally encode each element — if a non-matching element were present, the encoded count would exceed the number of encoded items, producing corrupt XDR
- **Impact**: Low — in practice the arrays always contain correct types, but the guard masks type errors rather than failing loudly (same pattern as PathPayment bug in Batch 7)
- **Fixed by**: Generator encodes all array elements unconditionally

### XdrCreateClaimableBalanceOperation — silent XDR corruption from instanceof guard
- **File**: `Soneso/StellarSDK/Xdr/XdrCreateClaimableBalanceOperation.php`
- **Bug**: Same instanceof guard pattern in encode() for `$claimants` array
- **Impact**: Low — same pattern as above
- **Fixed by**: Generator encodes all array elements unconditionally
