# SEP-47: Contract Interface Discovery

**Status:** ✅ Supported  
**SDK Version:** 1.9.6  
**Generated:** 2026-04-03 21:56 UTC  
**Spec:** [https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0047.md](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0047.md)

## Overall Coverage

**Total Coverage:** 100.0% (3/3 fields)

- ✅ **Implemented:** 3/3
- ❌ **Not Implemented:** 0/3

## Coverage by Section

| Section | Coverage | Implemented | Total |
|---------|----------|-------------|-------|
| SEP Discovery | 100.0% | 3 | 3 |

## SEP Discovery

Discovering which SEPs a contract implements via metadata

| Feature | Status | Notes |
|---------|--------|-------|
| `SorobanContractParser.parseContractByteCode` | ✅ Supported | `SorobanContractParser.parseContractByteCode()` |
| `SorobanContractInfo.supportedSeps` | ✅ Supported | `SorobanContractInfo.$supportedSeps` |
| `SorobanContractInfo.metaEntries` | ✅ Supported | `SorobanContractInfo.$metaEntries` |
