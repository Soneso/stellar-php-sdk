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

## Batch 19

### XdrRevokeSponsorshipOperation — encode() uses nullability check instead of discriminant switch
- **File**: `Soneso/StellarSDK/Xdr/XdrRevokeSponsorshipOperation.php`
- **Bug**: encode() checks `if ($this->ledgerKey)` / `else if ($this->signer)` instead of switching on the discriminant `$this->type` to control encoding
- **Impact**: Low — functionally equivalent in normal usage, but would encode the wrong arm if both fields were accidentally set (same pattern as XdrAccountMergeResult in Batch 12, XdrClaimableBalanceEntryExt in Batch 15, XdrLiquidityPoolParameters in Batch 18)
- **Fixed by**: Generator uses proper switch on discriminant to control encoding

### XdrRevokeSponsorshipOperation — private field visibility
- **File**: `Soneso/StellarSDK/Xdr/XdrRevokeSponsorshipOperation.php`
- **Bug**: `$type`, `$ledgerKey`, `$signer` were all `private` with getters/setters; generated version uses `public`
- **Impact**: None — public access is additive; getters/setters preserved
- **Fixed by**: Generator uses `public` consistently

### XdrLedgerEntryChange — missing restored getter/setter
- **File**: `Soneso/StellarSDK/Xdr/XdrLedgerEntryChange.php`
- **Bug**: Had getters/setters for `type`, `created`, `updated`, `removed`, and `state` but NOT for `restored` — the field was only accessible via public property
- **Impact**: None — public property access worked; inconsistency with other fields only
- **Fixed by**: Generator produces consistent getters/setters for all union arms

### XdrMemo — private field visibility
- **File**: `Soneso/StellarSDK/Xdr/XdrMemo.php`
- **Bug**: `$type`, `$text`, `$id`, `$hash`, `$returnHash` were all `private` with getters/setters; generated base uses `public`
- **Impact**: None — public access is additive; getters/setters preserved
- **Fixed by**: Generated base class uses `public` consistently

### XdrMemo — missing string max-length in generated base
- **File**: `Soneso/StellarSDK/Xdr/XdrMemoBase.php`
- **Bug**: Hand-written code used `XdrEncoder::string($this->getText(), static::VALUE_TEXT_MAX_SIZE)` with max-length 28; generated base uses `XdrEncoder::string($this->text)` without the limit
- **Impact**: Low — max-length is defense-in-depth validation; the network rejects oversized memos anyway. Same known generator-wide limitation as XdrDataEntry (Batch 16) and XdrSignedPayload (Batch 17)
- **Status**: Known generator limitation, not yet fixed

### XdrSorobanAuthorizedFunction — inconsistent discriminant access
- **File**: `Soneso/StellarSDK/Xdr/XdrSorobanAuthorizedFunction.php`
- **Bug**: Hand-written code used `$this->type->value` (direct property) in encode/decode switch; generated base uses `$this->type->getValue()` (method call)
- **Impact**: None — both yield the same integer value (same pattern as XdrSCSpecUDTUnionCaseV0 in Batch 18)
- **Fixed by**: Generator uses consistent `->getValue()` method call throughout

## Batch 20

### XdrSorobanTransactionMeta — copy-paste bug in encode() for diagnosticEvents
- **File**: `Soneso/StellarSDK/Xdr/XdrSorobanTransactionMeta.php`
- **Bug**: encode() iterated over `$this->events` twice — both for the `events` array AND the `diagnosticEvents` array — instead of iterating `$this->diagnosticEvents` for the second loop. The decode() was correct (decoded `XdrDiagnosticEvent` for the second array), so decoded data would re-encode differently.
- **Impact**: High — produces corrupt XDR for any SorobanTransactionMeta with diagnostic events different from regular events. Encode/decode round-trip would fail.
- **Fixed by**: Generator correctly iterates `$this->diagnosticEvents` for the second array

### XdrManageOfferResult — encode() uses nullability check instead of discriminant switch
- **File**: `Soneso/StellarSDK/Xdr/XdrManageOfferResult.php`
- **Bug**: encode() checks `if ($this->success !== null && XdrManageOfferResultCode::SUCCESS == $this->code->getValue())` — a combined null-check and discriminant comparison — instead of a clean switch on the discriminant
- **Impact**: Low — functionally equivalent, but the double check is redundant (same pattern as previous batches)
- **Status**: Not fixed in this batch (XdrManageOfferResult has a class naming mismatch — XDR spec name is `ManageSellOfferResult`)

### XdrManageOfferSuccessResult — silent XDR corruption from instanceof guard
- **File**: `Soneso/StellarSDK/Xdr/XdrManageOfferSuccessResult.php`
- **Bug**: encode() uses `if ($offerClaimed instanceof XdrClaimAtom)` to conditionally encode array elements (same pattern as Batch 7/9/12)
- **Impact**: Low — in practice the array always contains correct types
- **Fixed by**: Generator encodes all array elements unconditionally

### XdrManageOfferSuccessResult — private field visibility
- **File**: `Soneso/StellarSDK/Xdr/XdrManageOfferSuccessResult.php`
- **Bug**: `$offersClaimed` and `$offer` were `private` with no setters; generated version uses `public` with getters/setters
- **Impact**: None — public access is additive
- **Fixed by**: Generator uses `public` consistently

### XdrManageOfferSuccessResultOffer — private field visibility
- **File**: `Soneso/StellarSDK/Xdr/XdrManageOfferSuccessResultOffer.php`
- **Bug**: `$effect` and `$offer` were `private` with getters only (no setters); generated version uses `public` with getters/setters
- **Impact**: None — public access is additive
- **Fixed by**: Generator uses `public` consistently

### XdrClaimOfferAtomV0 — signed/unsigned mismatch for offerID
- **File**: `Soneso/StellarSDK/Xdr/XdrClaimOfferAtomV0.php`
- **Bug**: `offerId` encoded with `unsignedInteger64`/`readUnsignedInteger64`, but XDR spec defines `int64 offerID` (signed). Same pattern as XdrClaimOfferAtom (Batch 3), XdrLedgerKeyOffer (Batch 5), XdrManageBuyOfferOperation/XdrManageSellOfferOperation (Batch 13)
- **Impact**: Low — values within signed range encode identically
- **Fixed by**: Generator uses `integer64`/`readInteger64`

### XdrClaimOfferAtomV0 — private field visibility
- **File**: `Soneso/StellarSDK/Xdr/XdrClaimOfferAtomV0.php`
- **Bug**: All 6 fields were `private` with getters only (no setters); generated version uses `public` with getters/setters
- **Impact**: None — public access is additive
- **Fixed by**: Generator uses `public` consistently

## Batch 21

### XdrPreconditions — private field visibility
- **File**: `Soneso/StellarSDK/Xdr/XdrPreconditions.php`
- **Bug**: All 3 fields (`type`, `timeBounds`, `v2`) were `private`; generated version uses `public`
- **Impact**: None — public access is additive; getters/setters remain available
- **Fixed by**: Generator uses `public` consistently

### XdrPreconditions — if/else if encode pattern instead of switch/case
- **File**: `Soneso/StellarSDK/Xdr/XdrPreconditions.php`
- **Bug**: Hand-written encode()/decode() used `if`/`else if` on `getValue()` instead of `switch`/`case`; also omitted the NONE arm (harmless but inconsistent)
- **Impact**: Low — functionally equivalent, but switch/case is clearer and consistent with all other unions
- **Fixed by**: Generator uses switch/case consistently

### XdrPreconditions — missing toBase64Xdr/fromBase64Xdr
- **File**: `Soneso/StellarSDK/Xdr/XdrPreconditions.php`
- **Bug**: Hand-written version lacked `toBase64Xdr()` and `fromBase64Xdr()` convenience methods
- **Impact**: Low — callers must manually encode/decode base64
- **Fixed by**: Generator adds these methods to all types

### XdrTransactionMeta — instanceof guard in encode (case 0)
- **File**: `Soneso/StellarSDK/Xdr/XdrTransactionMeta.php`
- **Bug**: Hand-written encode() for case 0 used `if ($val instanceof XdrOperationMeta)` inside foreach, silently skipping non-matching entries instead of encoding all items
- **Impact**: Medium — if array contained wrong types, bytes would be silently missing (count encoded but items skipped)
- **Fixed by**: Generator encodes all items unconditionally

### XdrTransactionMetaV3 — instanceof guards in encode (3 arrays)
- **File**: `Soneso/StellarSDK/Xdr/XdrTransactionMetaV3.php`
- **Bug**: Hand-written encode() used `instanceof` guards inside all 3 foreach loops (txChangesBefore, operations, txChangesAfter), silently skipping non-matching entries
- **Impact**: Medium — count/items mismatch if wrong types present in arrays
- **Fixed by**: Generator encodes all items unconditionally

### XdrTransactionMetaV4 — instanceof guards in encode (5 arrays)
- **File**: `Soneso/StellarSDK/Xdr/XdrTransactionMetaV4.php`
- **Bug**: Hand-written encode() used `instanceof` guards inside all 5 foreach loops (txChangesBefore, operations, txChangesAfter, events, diagnosticEvents)
- **Impact**: Medium — count/items mismatch if wrong types present in arrays
- **Fixed by**: Generator encodes all items unconditionally

## Batch 22

### XdrClaimPredicate — private field visibility
- **File**: `Soneso/StellarSDK/Xdr/XdrClaimPredicate.php`
- **Bug**: All 6 fields were `private`; generated version uses `public`
- **Impact**: None — public access is additive; getters/setters remain available
- **Fixed by**: Generator uses `public` consistently

### XdrClaimPredicate — instanceof guards in AND/OR encode
- **File**: `Soneso/StellarSDK/Xdr/XdrClaimPredicate.php`
- **Bug**: Hand-written encode() used `instanceof XdrClaimPredicate` guards inside AND/OR foreach loops, silently skipping non-matching entries
- **Impact**: Medium — count/items mismatch if wrong types present in arrays
- **Fixed by**: Generator encodes all items unconditionally

### XdrClaimPredicate — NOT arm decode reads as array instead of optional
- **File**: `Soneso/StellarSDK/Xdr/XdrClaimPredicate.php`
- **Bug**: Hand-written decode() for NOT arm read a size + loop (array pattern) then took `$notPredicates[0]`; generated version uses optional pattern (present flag + conditional decode). Both produce identical XDR bytes, but the array pattern is incorrect for an XDR optional pointer
- **Impact**: Low — functionally equivalent; correct XDR pattern is optional
- **Fixed by**: Generator uses proper optional (present flag + conditional) pattern

### XdrClaimPredicate — missing toBase64Xdr/fromBase64Xdr
- **File**: `Soneso/StellarSDK/Xdr/XdrClaimPredicate.php`
- **Bug**: Hand-written version lacked convenience methods
- **Impact**: Low — callers must manually encode/decode base64
- **Fixed by**: Generator adds these methods to all types

### XdrTrustLineEntry — missing toBase64Xdr/fromBase64Xdr
- **File**: `Soneso/StellarSDK/Xdr/XdrTrustLineEntry.php`
- **Bug**: Hand-written version lacked convenience methods
- **Impact**: Low — callers must manually encode/decode base64
- **Fixed by**: Generator adds these methods to all types

## Batch 23

### XdrLedgerEntryData — inconsistent discriminant access (`.value` vs `.getValue()`)
- **File**: `Soneso/StellarSDK/Xdr/XdrLedgerEntryData.php`
- **Bug**: Hand-written encode()/decode() used `$this->type->value` (direct property) instead of `$this->type->getValue()` (method call)
- **Impact**: None — both yield the same integer value (same pattern as XdrSCSpecUDTUnionCaseV0 in Batch 18, XdrSorobanAuthorizedFunction in Batch 19)
- **Fixed by**: Generator uses consistent `->getValue()` method call throughout

### XdrLedgerEntryData — inconsistent field casing `trustline` vs `trustLine`
- **File**: `Soneso/StellarSDK/Xdr/XdrLedgerEntryData.php`
- **Bug**: Hand-written code used `$trustline` (lowercase L); XDR spec field name is `trustLine` (camelCase). No external code accessed the property directly.
- **Impact**: None — only internal references; getters/setters use `getTrustLine()`/`setTrustLine()` consistently
- **Fixed by**: Generator uses consistent camelCase `$trustLine`

### XdrContractCostParams — no new bugs
- **File**: `Soneso/StellarSDK/Xdr/XdrContractCostParams.php`
- **Note**: Hand-written code was functionally correct. Generator only changes are cosmetic (formatting, `array_push` → `[]`, `static` return type on `fromBase64Xdr`)

## Batch 24

### XdrConfigSettingEntry — inconsistent discriminant access (`.value` vs `.getValue()`)
- **File**: `Soneso/StellarSDK/Xdr/XdrConfigSettingEntry.php`
- **Bug**: Hand-written encode() used `$this->configSettingID->value` (direct property) instead of `$this->configSettingID->getValue()` (method call); decode() used raw `$v` integer directly in switch instead of `->getValue()`
- **Impact**: None — both yield the same integer value (same pattern as previous batches)
- **Fixed by**: Generator uses consistent `->getValue()` method call throughout

### XdrConfigSettingEntry — missing toBase64Xdr/fromBase64Xdr
- **File**: `Soneso/StellarSDK/Xdr/XdrConfigSettingEntry.php`
- **Bug**: Hand-written version lacked convenience methods
- **Impact**: Low — callers must manually encode/decode base64
- **Fixed by**: Generator adds these methods to all types

## Batch 25

### XdrContractCodeEntryExt — inner struct naming: `XdrContractCodeEntryExtV1` renamed to `XdrContractCodeEntryV1`
- **File**: `Soneso/StellarSDK/Xdr/XdrContractCodeEntryExt.php`
- **Bug**: Hand-written code named the v1 arm struct `XdrContractCodeEntryExtV1`; generator names it `XdrContractCodeEntryV1` (derived from parent struct `ContractCodeEntry` + arm `v1`)
- **Impact**: None — no external code referenced `XdrContractCodeEntryExtV1` directly. The old file becomes orphaned.
- **Fixed by**: Generator produces correctly-named `XdrContractCodeEntryV1` class

### XdrContractCodeEntryExt — missing toBase64Xdr/fromBase64Xdr
- **File**: `Soneso/StellarSDK/Xdr/XdrContractCodeEntryExt.php`
- **Bug**: Hand-written version lacked convenience methods
- **Impact**: Low — callers must manually encode/decode base64
- **Fixed by**: Generator adds these methods to all types

## Batch 26

### XdrSetOptionsOperation — `optionalUnsignedInteger`/`optionalString` replaced with explicit pattern
- **File**: `Soneso/StellarSDK/Xdr/XdrSetOptionsOperation.php`
- **Bug**: Not a bug — hand-written used convenience methods (`optionalUnsignedInteger`, `optionalString`), generator uses explicit if/else presence-flag pattern. Byte-identical output.
- **Impact**: None
- **Note**: Generator does not pass max length to `readString()` for `homeDomain` (defined as `string<32>`). Network validates on creation, so no functional impact, but loses client-side length validation.

### XdrPathPaymentResultSuccess — `instanceof` guard in encode
- **File**: `Soneso/StellarSDK/Xdr/XdrPathPaymentResultSuccess.php`
- **Bug**: Hand-written encode had `if ($val instanceof XdrClaimAtom)` guard that silently skips non-matching array entries
- **Impact**: Defensive but masks type errors — if a non-XdrClaimAtom element is in the array, it's silently dropped rather than causing an error
- **Fixed by**: Generator encodes all array elements without instanceof guard

### XdrSetOptionsOperation — missing toBase64Xdr/fromBase64Xdr
- **File**: `Soneso/StellarSDK/Xdr/XdrSetOptionsOperation.php`
- **Bug**: Hand-written version lacked convenience methods
- **Impact**: Low
- **Fixed by**: Generator adds these methods

### XdrPathPaymentResultSuccess — missing toBase64Xdr/fromBase64Xdr + setters
- **File**: `Soneso/StellarSDK/Xdr/XdrPathPaymentResultSuccess.php`
- **Bug**: Hand-written version lacked convenience methods and setters
- **Impact**: Low
- **Fixed by**: Generator adds these methods

## Batch 27

### XdrPreconditionsV2 — `instanceof` guard in encode
- **File**: `Soneso/StellarSDK/Xdr/XdrPreconditionsV2.php`
- **Bug**: Hand-written encode had `if ($extraSigner instanceof XdrSignerKey)` guard that silently skips non-matching array entries
- **Impact**: Masks type errors — non-XdrSignerKey elements silently dropped
- **Fixed by**: Generated Base encodes all array elements without instanceof guard

### XdrPreconditionsV2 — first wrapper type generated
- **File**: `Soneso/StellarSDK/Xdr/XdrPreconditionsV2.php` + `XdrPreconditionsV2Base.php`
- **Note**: First struct using the BASE_WRAPPER_TYPES pattern. Wrapper provides no-arg constructor with all defaults; Base has the generated encode/decode logic.

## Batch 28

### XdrPathPaymentStrictReceiveResult — proper switch/case replaces if/else
- **File**: `Soneso/StellarSDK/Xdr/XdrPathPaymentStrictReceiveResult.php`
- **Bug**: Hand-written encode used `if ($this->success !== null) ... else if ($this->noIssuer !== null)` which doesn't respect the discriminant — it encodes based on which field is set rather than which case the discriminant indicates
- **Impact**: Low — in practice, fields are set correctly corresponding to the code
- **Fixed by**: Generated code uses proper `switch ($this->code->getValue())` pattern

### XdrPathPaymentStrictSendResult — same if/else issue
- **File**: `Soneso/StellarSDK/Xdr/XdrPathPaymentStrictSendResult.php`
- **Bug**: Same as StrictReceive — encode used if/else on fields instead of switch on discriminant
- **Fixed by**: Generated code uses proper switch pattern

### XdrPathPaymentStrictReceiveResult/StrictSendResult — missing break in decode NO_ISSUER case
- **File**: Both hand-written files
- **Bug**: `decode()` switch had missing `break` after the NO_ISSUER case, causing unintended fall-through
- **Fixed by**: Generated code has proper break statements on all cases

### XdrOperationResultTest — dead-code no-arg constructor calls
- **File**: `Soneso/StellarSDKTests/Unit/Xdr/XdrOperationResultTest.php`
- **Bug**: 4 test methods created `new XdrPathPaymentStrictReceiveResult()` / `new XdrPathPaymentStrictSendResult()` objects that were never used (dead code). These masked the fact that a no-arg constructor on a union type leaves the discriminant property uninitialized.
- **Impact**: Tests passed by coincidence — the unused objects were never encoded/decoded
- **Fixed by**: Removed the 4 dead-code lines

## Batch 29

### XdrContractCodeEntryExtV1 — generated identically
- **File**: `Soneso/StellarSDK/Xdr/XdrContractCodeEntryExtV1.php`
- **Note**: Hand-written code matched generator output exactly. No bugs found. Removed from SKIP_TYPES.

### XdrContractEventBodyV0 — generated identically
- **File**: `Soneso/StellarSDK/Xdr/XdrContractEventBodyV0.php`
- **Note**: Hand-written code matched generator output exactly. No bugs found. Removed from SKIP_TYPES. (instanceof guard in encode was the only difference, but file matched because generator also produces without the guard)

### XdrContractEventBody — BLOCKED: inner struct naming mismatch
- **File**: `Soneso/StellarSDK/Xdr/XdrContractEventBody.php`
- **Issue**: Generator names the anonymous inner struct `XdrContractEventV0` but hand-written code uses `XdrContractEventBodyV0`. Need name override `"ContractEventV0" => "XdrContractEventBodyV0"` to align.
- **Status**: Kept in SKIP_TYPES pending name override fix

### XdrSignerKeyType — BLOCKED: enum constant naming mismatch
- **File**: `Soneso/StellarSDK/Xdr/XdrSignerKeyType.php`
- **Issue**: Generator uses full XDR constant names (`SIGNER_KEY_TYPE_ED25519`, `SIGNER_KEY_TYPE_PRE_AUTH_TX`, etc.) but SDK uses short names (`ED25519`, `PRE_AUTH_TX`, etc.). 30+ callsites reference short names. Also has an unused `MUXED_ED25519 = 0x100` constant not in the XDR spec.
- **Status**: Kept in SKIP_TYPES — generator needs enum constant name shortening feature or per-type constant name overrides

### XdrAllowTrustOperationAsset — BLOCKED: asset code padding behavior
- **File**: `Soneso/StellarSDK/Xdr/XdrAllowTrustOperationAsset.php`
- **Issue**: Hand-written code uses `opaqueFixed($value, 4, true)` to null-pad short asset codes (e.g., "USD" → "USD\0") and `readOpaqueFixedString(4)` to strip trailing nulls on decode. Generator uses `opaqueFixed($value, 4)` which throws on shorter-than-expected values. Also has `fromAlphaNumAssetCode()` factory method not reproducible by generator.
- **Status**: Kept in SKIP_TYPES — generator needs opaque-fixed padding support or a wrapper pattern

### XdrManageDataOperation — BLOCKED: double optional encoding
- **File**: `Soneso/StellarSDK/Xdr/XdrManageDataOperation.php`
- **Issue**: SDK's `XdrDataValue` class bakes in the optional presence flag (`DataValue*`). Generated ManageDataOp adds an EXTERNAL optional flag (`integer32(1)` + `XdrDataValue::encode()`) ON TOP of XdrDataValue's internal flag, producing double-encoded presence bytes. Wire-incompatible.
- **Status**: Kept in SKIP_TYPES — requires either: (a) adding `XdrDataValue` to TYPE_OVERRIDES as `string`, or (b) refactoring XdrDataValue to not include the optional flag

### XdrInnerTransactionResultPair — `$$` variable typo + wrong return type
- **File**: `Soneso/StellarSDK/Xdr/XdrInnerTransactionResultPair.php`
- **Bug**: Line 29 has `$$transactionHashBytes` (double dollar — PHP variable variable), and `fromBase64Xdr()` returns `XdrInnerTransactionResult` instead of `XdrInnerTransactionResultPair`
- **Impact**: Medium — `$$` creates an accidental variable variable; wrong return type means callers get unexpected type
- **Status**: Not yet fixed (type still in SKIP_TYPES)

## Batch 30

### XdrSCContractInstance — generated with optional array fix
- **File**: `Soneso/StellarSDK/Xdr/XdrSCContractInstance.php`
- **Note**: Required generator fix for optional array encoding/decoding (presence flag handling in Array case for `is_optional` fields). Wire-compatible with hand-written version.

### XdrLiquidityPoolBody — inner struct name override fix
- **File**: `Soneso/StellarSDK/Xdr/XdrLiquidityPoolBody.php`
- **Note**: Required fixing name_overrides.rb key from `LiquidityPoolEntryBodyConstantProduct` to `LiquidityPoolEntryConstantProduct`

### XdrInnerTransactionResultResult — hand-written encode was wire-incompatible
- **File**: `Soneso/StellarSDK/Xdr/XdrInnerTransactionResultResult.php`
- **Bug**: Hand-written encode() always encoded `count($this->operations)` followed by all operations regardless of the result code — error codes like `BAD_AUTH` or `TOO_EARLY` have void arms in XDR and should produce no payload beyond the discriminant
- **Impact**: High — produces corrupt XDR when encoding error result codes (extra bytes appended)
- **Fixed by**: Generator uses proper switch/case for encode, matching the existing correct decode logic

### XdrSCEnvMetaEntry — BLOCKED: XDR spec changed
- **File**: `Soneso/StellarSDK/Xdr/XdrSCEnvMetaEntry.php`
- **Issue**: XDR spec changed `interfaceVersion` from `uint64` to struct `{uint32 protocol, uint32 preRelease}`. Hand-written code is outdated. 3 callers expect `int`.
- **Status**: Kept in SKIP_TYPES — requires dedicated migration of callers

## Batch 31

### XdrTransactionResultResult — encode() used switch on discriminant but was missing void cases
- **File**: `Soneso/StellarSDK/Xdr/XdrTransactionResultResult.php`
- **Bug**: Hand-written encode() used switch with only SUCCESS/FAILED and FEE_BUMP_INNER_SUCCESS/FEE_BUMP_INNER_FAILED cases. 15 error codes (TOO_EARLY through SOROBAN_INVALID) fell through to default without explicit void handling. Also used `instanceof XdrOperationResult` guard in encode.
- **Impact**: Low — default/break handled the void cases correctly, but explicit handling is clearer
- **Fixed by**: Generator produces explicit void cases for all 15 error discriminants

## Batch 32

### XdrContractEventBody — inner struct naming mismatch
- **File**: `Soneso/StellarSDK/Xdr/XdrContractEventBody.php`
- **Issue**: Generator referenced `XdrContractEventV0` but SDK uses `XdrContractEventBodyV0`
- **Fixed by**: Added name override `"ContractEventV0" => "XdrContractEventBodyV0"`

## Batch 33

### XdrSCSpecEntry — converted to BASE_WRAPPER pattern
- **File**: `Soneso/StellarSDK/Xdr/XdrSCSpecEntry.php`
- **Issue**: 6 factory methods blocked direct regeneration
- **Fixed by**: BASE_WRAPPER pattern — generated base handles encode/decode, wrapper preserves factory methods
- **Also regenerated**: `XdrContractEventBodyV0.php` — removed instanceof guard in encode, added base64 helpers

## Batch 34

### XdrSCSpecTypeDef — converted to BASE_WRAPPER pattern
- **File**: `Soneso/StellarSDK/Xdr/XdrSCSpecTypeDef.php`
- **Issue**: 25 factory methods (7 parameterized + 18 primitive) blocked direct regeneration
- **Fixed by**: BASE_WRAPPER pattern — generated base handles encode/decode with all 19 void + 7 arm cases, wrapper preserves all factory methods
