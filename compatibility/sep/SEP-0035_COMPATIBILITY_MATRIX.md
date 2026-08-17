# SEP-35: Operation IDs

**Status:** ✅ Supported  
**SDK Version:** 1.12.0  
**Generated:** 2026-08-17 04:13 UTC  
**Spec:** [https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0035.md](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0035.md)

## Overall Coverage

**Total Coverage:** 100.0% (7/7 fields)

- ✅ **Implemented:** 7/7
- ❌ **Not Implemented:** 0/7

## Coverage by Section

| Section | Coverage | Implemented | Total |
|---------|----------|-------------|-------|
| ID Encoding | 100.0% | 3 | 3 |
| Cursor and Range Helpers | 100.0% | 4 | 4 |

## ID Encoding

SEP-35 ID construction and 64-bit integer encoding on TOID

| Feature | Status | Notes |
|---------|--------|-------|
| `constructor` | ✅ Supported | `TOID.__construct()` |
| `toInt64` | ✅ Supported | `TOID.toInt64()` |
| `fromInt64` | ✅ Supported | `TOID.fromInt64()` |

## Cursor and Range Helpers

SEP-35 pagination cursor and ledger range utilities

| Feature | Status | Notes |
|---------|--------|-------|
| `incrementOperationIndex` | ✅ Supported | `TOID.incrementOperationIndex()` |
| `afterLedger` | ✅ Supported | `TOID.afterLedger()` |
| `ledgerRangeInclusive` | ✅ Supported | `TOID.ledgerRangeInclusive()` |
| `TOIDRange` | ✅ Supported | `TOIDRange` |
