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

## Batch 10

_(No new bugs — 7 types generated cleanly)_

## Batch 11

### XdrCreateAccountResult — decode() uses wrong discriminant type
- **File**: `Soneso/StellarSDK/Xdr/XdrCreateAccountResult.php`
- **Bug**: Hand-written code used `XdrOperationResultCode` as the discriminant type instead of `XdrCreateAccountResultCode` — same wrong-type pattern as XdrMemoType (Batch 1) and XdrTrustLineFlags (Batch 8)
- **Impact**: Medium — tests were written against the buggy type and passed because both types are int-based enums with overlapping wire values
- **Fixed by**: Generator produces correct `XdrCreateAccountResultCode` discriminant; tests updated to match

## Batch 12

### XdrInvokeHostFunctionResult — double concatenation in encode()
- **File**: `Soneso/StellarSDK/Xdr/XdrInvokeHostFunctionResult.php`
- **Bug**: `$bytes .= $bytes .= XdrEncoder::opaqueFixed($this->success, 32)` — the double `.=` causes the entire $bytes string to be doubled on the SUCCESS arm
- **Impact**: High — produces corrupt (doubled) XDR output for successful InvokeHostFunction results. Only survived because tests round-trip through decode which reads the first valid portion and ignores trailing bytes.
- **Fixed by**: Generator produces correct single `$bytes .= XdrEncoder::opaqueFixed($this->success, 32)`

### XdrInflationResult — silent XDR corruption from instanceof guard
- **File**: `Soneso/StellarSDK/Xdr/XdrInflationResult.php`
- **Bug**: encode() uses `if ($val instanceof XdrInflationPayout)` to conditionally encode array elements (same pattern as Batch 7/9)
- **Impact**: Low — in practice the array always contains XdrInflationPayout instances
- **Fixed by**: Generator encodes all array elements unconditionally

### XdrAccountMergeResult — encode() uses nullability check instead of discriminant
- **File**: `Soneso/StellarSDK/Xdr/XdrAccountMergeResult.php`
- **Bug**: encode() checks `if ($this->sourceAccountBalance !== null)` instead of switching on the discriminant code
- **Impact**: Low — functionally equivalent in normal usage, but would silently skip encoding if balance were null with SUCCESS code
- **Fixed by**: Generator uses proper switch on discriminant to control encoding

## Batch 13

### XdrManageBuyOfferOperation / XdrManageSellOfferOperation — spurious argument in decode()
- **Files**: `Soneso/StellarSDK/Xdr/XdrManageBuyOfferOperation.php`, `Soneso/StellarSDK/Xdr/XdrManageSellOfferOperation.php`
- **Bug**: `$xdr->readBigInteger64($xdr)` passes spurious `$xdr` argument (same pattern as XdrCreatePassiveSellOfferOperation in Batch 3)
- **Impact**: Low — PHP silently ignores extra arguments
- **Fixed by**: Generator produces `$xdr->readBigInteger64()` (no extra arg)

### XdrManageBuyOfferOperation / XdrManageSellOfferOperation — signed/unsigned mismatch for offerID
- **Files**: `Soneso/StellarSDK/Xdr/XdrManageBuyOfferOperation.php`, `Soneso/StellarSDK/Xdr/XdrManageSellOfferOperation.php`
- **Bug**: `offerID` encoded with `unsignedInteger64`/`readUnsignedInteger64`, but XDR spec defines `int64 offerID` (signed)
- **Impact**: Low — values within signed range encode identically (same pattern as XdrClaimOfferAtom in Batch 3, XdrLedgerKeyOffer in Batch 5)
- **Fixed by**: Generator uses `integer64`/`readInteger64`

## Batch 14

_(No new bugs — 6 types generated cleanly. XdrSequenceNumber getValue() callers migrated to public property access.)_

## Batch 15

### XdrClaimableBalanceEntryExt — encode() uses nullability check instead of discriminant switch
- **File**: `Soneso/StellarSDK/Xdr/XdrClaimableBalanceEntryExt.php`
- **Bug**: encode() checks `if ($this->v1 !== null)` instead of switching on the discriminant to control encoding
- **Impact**: Low — functionally equivalent in normal usage, but would silently encode v1 even if discriminant is 0 when v1 was accidentally set (same pattern as XdrAccountMergeResult in Batch 12)
- **Fixed by**: Generator uses proper switch on discriminant to control encoding

### XdrClaimableBalanceEntryExt — missing getV1()/setV1() accessors
- **File**: `Soneso/StellarSDK/Xdr/XdrClaimableBalanceEntryExt.php`
- **Bug**: Hand-written code had public `$v1` property but no getter/setter methods, inconsistent with other ext unions
- **Impact**: None — public property access still works
- **Fixed by**: Generator produces consistent getV1()/setV1() accessors

### XdrTransactionResultExt — private discriminant field
- **File**: `Soneso/StellarSDK/Xdr/XdrTransactionResultExt.php`
- **Bug**: `$discriminant` was `private` instead of `public`, inconsistent with all other ext union types
- **Impact**: None — getter `getDiscriminant()` existed
- **Fixed by**: Generator uses `public int $discriminant` consistently

## Batch 16

### XdrLedgerKeyAccount — private field visibility
- **File**: `Soneso/StellarSDK/Xdr/XdrLedgerKeyAccount.php`
- **Bug**: `$accountID` was `private` instead of `public`, inconsistent with other generated types
- **Impact**: None — getter `getAccountID()` existed; no code accessed the property directly
- **Fixed by**: Generated base class uses `public` consistently; wrapper preserves `forAccountId()` helper

### XdrDataEntry — missing string max-length validation on dataName
- **File**: `Soneso/StellarSDK/Xdr/XdrDataEntry.php`
- **Bug**: Hand-written code passed max-length 64 to `XdrEncoder::string()` and `readString()` for `dataName`; generated version omits the constraint
- **Impact**: Low — max-length is a defense-in-depth validation; the network rejects oversized names anyway. This is a known generator-wide limitation (string/opaque max-lengths not propagated).
- **Status**: Known generator limitation, not yet fixed

## Batch 17

### XdrSignedPayload — missing opaque max-length validation on payload decode
- **File**: `Soneso/StellarSDK/Xdr/XdrSignedPayload.php`
- **Bug**: Hand-written code passed max-length 64 to `readOpaqueVariable(64)` for `payload`; generated version omits the constraint
- **Impact**: Low — same known generator-wide limitation as XdrDataEntry above (max-lengths not propagated for string/opaque fields)
- **Status**: Known generator limitation, not yet fixed

### XdrSignedPayload — private field visibility
- **File**: `Soneso/StellarSDK/Xdr/XdrSignedPayload.php`
- **Bug**: `$ed25519` and `$payload` were `private` in hand-written code; generated version uses `public`
- **Impact**: None — getters/setters preserved; public access is additive
- **Fixed by**: Generator uses `public` consistently

## Batch 18

### XdrSCError — incorrect void arms for SCE_WASM_VM through SCE_AUTH
- **File**: `Soneso/StellarSDK/Xdr/XdrSCError.php`
- **Bug**: Hand-written code treated `SCE_WASM_VM`, `SCE_CONTEXT`, `SCE_STORAGE`, `SCE_OBJECT`, `SCE_CRYPTO`, `SCE_EVENTS`, `SCE_BUDGET`, `SCE_VALUE`, and `SCE_AUTH` as void union arms, but the XDR spec defines them all as sharing `SCErrorCode code` via fall-through semantics (only `SCE_CONTRACT` has `uint32 contractCode`)
- **Impact**: High — encode/decode produces corrupt XDR for any SCError with these types, silently dropping the error code
- **Fixed by**: Generator correctly groups all fall-through arms with `SCErrorCode code`

### XdrLiquidityPoolParameters — encode() uses nullability check instead of discriminant switch
- **File**: `Soneso/StellarSDK/Xdr/XdrLiquidityPoolParameters.php`
- **Bug**: encode() checks `if ($this->constantProduct !== null)` instead of switching on the discriminant type to control encoding
- **Impact**: Low — functionally equivalent in normal usage, but would silently encode constantProduct even if discriminant were wrong (same pattern as XdrAccountMergeResult in Batch 12, XdrClaimableBalanceEntryExt in Batch 15)
- **Fixed by**: Generator uses proper switch on discriminant to control encoding

### XdrClaimant — private field visibility
- **File**: `Soneso/StellarSDK/Xdr/XdrClaimant.php`
- **Bug**: `$v0` was `private` with getter/setter; generated version uses `public`
- **Impact**: None — public access is additive
- **Fixed by**: Generator uses `public` consistently

### XdrClaimAtom — private field visibility
- **File**: `Soneso/StellarSDK/Xdr/XdrClaimAtom.php`
- **Bug**: `$v0`, `$orderBook`, and `$liquidityPool` were `private` with getters/setters; generated version uses `public`
- **Impact**: None — public access is additive
- **Fixed by**: Generator uses `public` consistently

### XdrCreateClaimableBalanceResult — no-arg constructor pattern
- **File**: `Soneso/StellarSDK/Xdr/XdrCreateClaimableBalanceResult.php`
- **Bug**: Constructor took no arguments (discriminant set via public property), no getter/setter accessors, and encode used `if ($this->balanceID !== null)` null-check instead of discriminant-based switch
- **Impact**: Low — functionally works but inconsistent with standard union patterns
- **Fixed by**: Generator produces typed discriminant constructor with proper switch-based encode

### XdrSCSpecUDTUnionCaseV0 — inconsistent encode/decode discriminant access
- **File**: `Soneso/StellarSDK/Xdr/XdrSCSpecUDTUnionCaseV0.php`
- **Bug**: encode() used `$this->type->value` (direct property) while decode() used `$type->getValue()` (method call) for discriminant access
- **Impact**: None — both yield the same integer value
- **Fixed by**: Generator uses consistent `->getValue()` method call throughout
