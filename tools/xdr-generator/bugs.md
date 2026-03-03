# Hand-Written Code Bugs

Bugs discovered in existing hand-written XDR types during generator comparison.

## Batch 1

### XdrMemoType.php — decode() returns wrong type
- **File**: `Soneso/StellarSDK/Xdr/XdrMemoType.php`
- **Bug**: `decode()` returns `XdrEnvelopeType` and creates `new XdrEnvelopeType($value)` instead of `XdrMemoType`
- **Impact**: Low — a `XdrEnvelopeType` is structurally identical (same int-based enum pattern), so encoding/decoding still works, but the return type is wrong
- **Fixed by**: Generator now produces correct `XdrMemoType::decode()` returning `new XdrMemoType($value)`
