# Stellar RPC Compatibility Matrix

## Overview

This document provides a comprehensive compatibility matrix for the Stellar PHP SDK's implementation of the Stellar RPC (Soroban RPC) API. The RPC uses JSON-RPC 2.0 protocol to enable interaction with Soroban smart contracts on the Stellar network.

**Last Updated:** October 16, 2025
**SDK Version:** 1.8.6
**RPC Version:** v23.0.4
**Protocol Version:** 23

### What is Stellar RPC?

Stellar RPC (also called Soroban RPC) is a JSON-RPC 2.0 interface for interacting with Soroban smart contracts on the Stellar network. Unlike Horizon (the REST API for Stellar classic operations), RPC is specifically designed for:

- **Smart Contract Interactions:** Simulating, sending, and querying smart contract transactions
- **Contract State Access:** Direct ledger entry inspection for contract data and code
- **Event Streaming:** Filtering and querying contract events
- **Transaction Lifecycle:** Managing the full lifecycle of Soroban transactions

The RPC interface is simpler and more focused than Horizon, consisting of only 12 methods compared to Horizon's 52+ REST endpoints.

---

## Executive Summary

### Coverage Statistics

- **Total RPC Methods:** 12
- **Fully Supported:** 12 methods (100.0%)
- **Partially Supported:** 0 methods (0.0%)
- **Not Supported:** 0 method (0.0%)
- **Overall Implementation:** 12/12 methods (100.0%)

### Parameter Coverage

- **Required Parameters:** 8/8 supported (100.0%)
- **Optional Parameters:** 6/6 supported (100.0%)
- **Total Parameters:** 14/14 supported (100.0%)

### Response Field Coverage

- **Total Response Fields:** 126
- **Supported Fields:** 126
- **Coverage:** 100.0%

### Key Strengths

1. **Complete Core Functionality:** All essential RPC methods are implemented
2. **High-Value Methods Fully Supported:**
   - `getHealth` - Node health monitoring
   - `getNetwork` - Network configuration
   - `getVersionInfo` - Version information
   - `getFeeStats` - Fee statistics
   - `getLatestLedger` - Current ledger state
   - `getLedgerEntries` - Ledger entry inspection
   - `getLedgers` - Historical ledger ranges
   - `getEvents` - Event filtering
   - `getTransaction` - Transaction details
   - `getTransactions` - Transaction listing
   - `sendTransaction` - Transaction submission
   - `simulateTransaction` - Transaction simulation

3. **Strong Core Operations:** Transaction simulation, submission, and retrieval work well
4. **Protocol Version Support:** Full support for Protocol 21, 22, and 23 features
5. **Rich Helper Methods:** Additional utilities for contract loading and data extraction

### Current Achievement

✅ **100% API Coverage Achieved**
- All 12 RPC methods fully implemented
- All request parameters supported (14/14)
- All response fields available (126/126)
- Complete Protocol 21, 22, and 23 support

### Maintenance Recommendations

1. **Ongoing:** Monitor for new RPC methods in future protocol versions
2. **As Needed:** Update implementation when RPC specification changes
3. **Enhancement:** Consider adding JSON format parsing for xdrFormat parameter
4. **Documentation:** Continue improving examples and best practices

---

## Matrix Legend

| Symbol | Status | Description |
|--------|--------|-------------|
| ✅ | **Fully Supported** | All required and optional parameters implemented; all response fields available |
| ⚠️ | **Partially Supported** | Core functionality works but missing some optional parameters or response fields |
| ❌ | **Not Supported** | Method not implemented in the SDK |

---

## Compatibility Matrix

| RPC Method | Status | SDK Method | Request Class | Response Class | Req Params | Opt Params | Response Coverage | Priority | Notes |
|------------|--------|------------|---------------|----------------|------------|------------|-------------------|----------|-------|
| getHealth | ✅ Fully Supported | getHealth | - | GetHealthResponse | 0/0 | 0/0 | 4/4 (100.0%) | Low | Fully implemented |
| getNetwork | ✅ Fully Supported | getNetwork | - | GetNetworkResponse | 0/0 | 0/0 | 3/3 (100.0%) | Low | Fully implemented |
| getVersionInfo | ✅ Fully Supported | getVersionInfo | - | GetVersionInfoResponse | 0/0 | 0/0 | 5/5 (100.0%) | Low | Fully implemented |
| getFeeStats | ✅ Fully Supported | getFeeStats | - | GetFeeStatsResponse | 0/0 | 0/0 | 19/19 (100.0%) | Low | Fully implemented |
| getLatestLedger | ✅ Fully Supported | getLatestLedger | - | GetLatestLedgerResponse | 0/0 | 0/0 | 3/3 (100.0%) | Low | Fully implemented |
| getLedgerEntries | ✅ Fully Supported | getLedgerEntries | - | GetLedgerEntriesResponse | 1/1 | 0/0 | 7/7 (100.0%) | Low | Fully implemented |
| getLedgers | ✅ Fully Supported | getLedgers | GetLedgersRequest | GetLedgersResponse | 1/1 | 1/1 | 11/11 (100.0%) | Low | Fully implemented |
| getEvents | ✅ Fully Supported | getEvents | GetEventsRequest | GetEventsResponse | 2/2 | 2/2 | 17/17 (100.0%) | Low | Fully implemented |
| getTransaction | ✅ Fully Supported | getTransaction | - | GetTransactionResponse | 1/1 | 0/0 | 16/16 (100.0%) | Low | Fully implemented |
| getTransactions | ✅ Fully Supported | getTransactions | GetTransactionsRequest | GetTransactionsResponse | 1/1 | 1/1 | 19/19 (100.0%) | Low | Fully implemented |
| sendTransaction | ✅ Fully Supported | sendTransaction | - | SendTransactionResponse | 1/1 | 0/0 | 6/6 (100.0%) | Low | Fully implemented |
| simulateTransaction | ✅ Fully Supported | simulateTransaction | SimulateTransactionRequest | SimulateTransactionResponse | 1/1 | 2/2 | 16/16 (100.0%) | Low | Fully implemented |

---

## Detailed Method Analysis

### getHealth

**Status:** ✅ Fully Supported
**Priority:** Low
**Introduced:** v21.0.0
**Last Modified:** v21.0.0

#### Parameter Coverage

- **Required:** 0/0
- **Optional:** 0/0

#### Response Field Coverage

- **Total Fields:** 4
- **Supported:** 4
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

---

### getNetwork

**Status:** ✅ Fully Supported
**Priority:** Low
**Introduced:** v21.0.0
**Last Modified:** v21.5.0

#### Parameter Coverage

- **Required:** 0/0
- **Optional:** 0/0

#### Response Field Coverage

- **Total Fields:** 3
- **Supported:** 3
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

---

### getVersionInfo

**Status:** ✅ Fully Supported
**Priority:** Low
**Introduced:** v21.1.0
**Last Modified:** v23.0.0

#### Parameter Coverage

- **Required:** 0/0
- **Optional:** 0/0

#### Response Field Coverage

- **Total Fields:** 5
- **Supported:** 5
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

---

### getFeeStats

**Status:** ✅ Fully Supported
**Priority:** Low
**Introduced:** v21.0.0
**Last Modified:** v21.0.0

#### Parameter Coverage

- **Required:** 0/0
- **Optional:** 0/0

#### Response Field Coverage

- **Total Fields:** 19
- **Supported:** 19
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

---

### getLatestLedger

**Status:** ✅ Fully Supported
**Priority:** Low
**Introduced:** v21.0.0
**Last Modified:** v21.0.0

#### Parameter Coverage

- **Required:** 0/0
- **Optional:** 0/0

#### Response Field Coverage

- **Total Fields:** 3
- **Supported:** 3
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

---

### getLedgerEntries

**Status:** ✅ Fully Supported
**Priority:** Low
**Introduced:** v21.0.0
**Last Modified:** v23.0.0

#### Parameter Coverage

- **Required:** 1/1
- **Optional:** 0/0

#### Response Field Coverage

- **Total Fields:** 7
- **Supported:** 7
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

---

### getLedgers

**Status:** ✅ Fully Supported
**Priority:** Low
**Introduced:** v21.0.0
**Last Modified:** v23.0.3

#### Parameter Coverage

- **Required:** 1/1
- **Optional:** 1/1

#### Response Field Coverage

- **Total Fields:** 11
- **Supported:** 11
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

---

### getEvents

**Status:** ✅ Fully Supported
**Priority:** Low
**Introduced:** v21.0.0
**Last Modified:** v23.0.3

#### Parameter Coverage

- **Required:** 2/2
- **Optional:** 2/2

#### Response Field Coverage

- **Total Fields:** 17
- **Supported:** 17
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

---

### getTransaction

**Status:** ✅ Fully Supported
**Priority:** Low
**Introduced:** v21.0.0
**Last Modified:** v23.0.0

#### Parameter Coverage

- **Required:** 1/1
- **Optional:** 0/0

#### Response Field Coverage

- **Total Fields:** 16
- **Supported:** 16
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

---

### getTransactions

**Status:** ✅ Fully Supported
**Priority:** Low
**Introduced:** v21.4.0
**Last Modified:** v23.0.0

#### Parameter Coverage

- **Required:** 1/1
- **Optional:** 1/1

#### Response Field Coverage

- **Total Fields:** 19
- **Supported:** 19
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

---

### sendTransaction

**Status:** ✅ Fully Supported
**Priority:** Low
**Introduced:** v21.0.0
**Last Modified:** v21.5.0

#### Parameter Coverage

- **Required:** 1/1
- **Optional:** 0/0

#### Response Field Coverage

- **Total Fields:** 6
- **Supported:** 6
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

---

### simulateTransaction

**Status:** ✅ Fully Supported
**Priority:** Low
**Introduced:** v21.0.0
**Last Modified:** v23.0.4

#### Parameter Coverage

- **Required:** 1/1
- **Optional:** 2/2

#### Response Field Coverage

- **Total Fields:** 16
- **Supported:** 16
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

---

---

## Gap Analysis

### Summary

✅ **No gaps identified** - The Stellar PHP SDK provides complete coverage of the Soroban RPC API as of version 1.8.6.

### Coverage Breakdown

| Category | Status | Coverage |
|----------|--------|----------|
| RPC Methods | ✅ Complete | 12/12 (100%) |
| Required Parameters | ✅ Complete | 8/8 (100%) |
| Optional Parameters | ✅ Complete | 6/6 (100%) |
| Response Fields | ✅ Complete | 126/126 (100%) |

### Future Considerations

While current coverage is complete, the SDK team should monitor for:
- New RPC methods introduced in future protocol versions
- Changes to existing method signatures or response structures
- Deprecation notices from the Stellar RPC team
- Community requests for additional helper methods or utilities

---

## Document Information

### Generation Details
- **Generated On**: 2025-10-16 17:07:27 UTC
- **Data Sources**: 
  - Stellar RPC: Official Stellar RPC documentation
  - PHP SDK: Source code analysis of Stellar PHP SDK v1.8.6

### How to Use This Matrix

1. **Check Method Support**: Look up the RPC method you need to see its implementation status.
2. **Review Missing Features**: If a method is partially supported, check the detailed analysis for specifics.
3. **Plan Workarounds**: For unsupported or partial methods, you may need additional XDR parsing.
4. **Check Helper Methods**: The SDK provides many helper methods for common operations.

### Contributing

Help improve SDK coverage! If you need an unsupported or partially supported feature:
1. Check the [GitHub Issues](https://github.com/Soneso/stellar-php-sdk/issues) for existing requests
2. Submit a feature request with your use case
3. Consider contributing an implementation via pull request

### Reference Documentation

- **RPC API Reference:** https://developers.stellar.org/docs/data/rpc/api-reference
- **Soroban Documentation:** https://developers.stellar.org/docs/smart-contracts
- **PHP SDK Repository:** https://github.com/Soneso/stellar-php-sdk
- **Java SDK (Reference):** https://github.com/stellar/java-stellar-sdk

---

## Summary

The Stellar PHP SDK provides **excellent coverage** of the Soroban RPC API with **12 out of 12 methods implemented (100.0%)**. The implementation focuses on core functionality with strong XDR support and helpful utility methods.

### Key Takeaways

✅ **Strengths:**
- All critical RPC methods implemented and functional
- Robust XDR encoding/decoding throughout
- Helpful utilities for common operations
- Full protocol 21, 22, and 23 support
- Production-ready transaction lifecycle
- Strong error handling

✅ **Current Status:**
- Full API coverage achieved (100%)
- All 12 RPC methods fully implemented
- All request parameters supported
- All response fields available

🎯 **Recommended Next Steps:**
1. Monitor for new RPC methods in future protocol versions
2. Keep documentation up-to-date with RPC specification changes
3. Consider adding JSON format support if beneficial (xdrFormat parameter)
4. Continue documenting protocol-specific features and best practices

The SDK is **production-ready** for Soroban smart contract development with **complete RPC API coverage**. All methods, parameters, and response fields are fully implemented and available for use.

---

**Document Version:** 2.1
**Generated:** October 16, 2025
**Maintainer:** Stellar PHP SDK Team
**License:** Apache 2.0

For questions or contributions, visit: https://github.com/Soneso/stellar-php-sdk
