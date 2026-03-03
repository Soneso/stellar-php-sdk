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
