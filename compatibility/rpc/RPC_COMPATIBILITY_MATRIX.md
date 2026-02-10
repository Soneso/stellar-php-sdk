# Stellar RPC Compatibility Matrix

## Overview

This document provides a comprehensive compatibility matrix for the Stellar PHP SDK's implementation of the Stellar RPC (Soroban RPC) API. The RPC uses JSON-RPC 2.0 protocol to enable interaction with Soroban smart contracts on the Stellar network.

**Last Updated:** February 10, 2026

**SDK Version:** 1.9.3

**RPC Version:** v25.0.0

**Protocol Version:** 25

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

- **Required Parameters:** 7/7 supported (100.0%)
- **Optional Parameters:** 4/4 supported (100.0%)
- **Total Parameters:** 11/11 supported (100.0%)

### Response Field Coverage

- **Total Response Fields:** 106
- **Supported Fields:** 106
- **Coverage:** 100.0%

### Key Strengths

1. **Complete Method Coverage:** All 12 RPC methods are fully implemented
2. **Full Parameter Support:** All required and optional parameters supported
3. **Complete Response Handling:** All response fields properly parsed and accessible
4. **Protocol Version Support:** Full support for Protocol 21 through 25 features
5. **Rich Helper Methods:** Additional utilities for contract loading and data extraction

### Priority Gaps

### Recommendations

The PHP SDK provides complete coverage of the Stellar RPC API.

For enhanced functionality, consider:
1. Adding response field validation and type checking
2. Implementing additional helper utilities for common operations
3. Adding comprehensive error handling for edge cases

---

## Matrix Legend

| Symbol | Status | Description |
|--------|--------|-------------|
| ✅ | **Fully Supported** | All required and optional parameters implemented; all response fields available |
| ⚠️ | **Partially Supported** | Core functionality works but missing some optional parameters or response fields |
| ❌ | **Not Supported** | Method not implemented in the SDK |

---

## Compatibility Matrix

| RPC Method | Status | SDK Method | Request Class | Response Class | Req Params | Opt Params | Response Coverage | Notes |
|------------|--------|------------|---------------|----------------|------------|------------|-------------------|-------|
| getHealth | ✅ Fully Supported | getHealth | - | GetHealthResponse | 0/0 | 0/0 | 4/4 (100.0%) | Fully implemented |
| getNetwork | ✅ Fully Supported | getNetwork | - | GetNetworkResponse | 0/0 | 0/0 | 3/3 (100.0%) | Fully implemented |
| getVersionInfo | ✅ Fully Supported | getVersionInfo | - | GetVersionInfoResponse | 0/0 | 0/0 | 5/5 (100.0%) | Fully implemented |
| getFeeStats | ✅ Fully Supported | getFeeStats | - | GetFeeStatsResponse | 0/0 | 0/0 | 19/19 (100.0%) | Fully implemented |
| getLatestLedger | ✅ Fully Supported | getLatestLedger | - | GetLatestLedgerResponse | 0/0 | 0/0 | 6/6 (100.0%) | Fully implemented |
| getLedgerEntries | ✅ Fully Supported | getLedgerEntries | - | GetLedgerEntriesResponse | 1/1 | 0/0 | 7/7 (100.0%) | Fully implemented |
| getLedgers | ✅ Fully Supported | getLedgers | GetLedgersRequest | GetLedgersResponse | 1/1 | 0/0 | 11/11 (100.0%) | Fully implemented |
| getEvents | ✅ Fully Supported | getEvents | GetEventsRequest | GetEventsResponse | 1/1 | 2/2 | 17/17 (100.0%) | Fully implemented |
| getTransaction | ✅ Fully Supported | getTransaction | - | GetTransactionResponse | 1/1 | 0/0 | 5/5 (100.0%) | Fully implemented |
| getTransactions | ✅ Fully Supported | getTransactions | GetTransactionsRequest | GetTransactionsResponse | 1/1 | 0/0 | 7/7 (100.0%) | Fully implemented |
| sendTransaction | ✅ Fully Supported | sendTransaction | - | SendTransactionResponse | 1/1 | 0/0 | 6/6 (100.0%) | Fully implemented |
| simulateTransaction | ✅ Fully Supported | simulateTransaction | SimulateTransactionRequest | SimulateTransactionResponse | 1/1 | 2/2 | 16/16 (100.0%) | Fully implemented |

---

## Detailed Method Analysis

### getHealth

**Status:** ✅ Fully Supported

#### Parameter Coverage

- **Required:** 0/0
- **Optional:** 0/0

#### Response Field Coverage

- **Total Fields:** 4
- **Supported:** 4
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

### getNetwork

**Status:** ✅ Fully Supported

#### Parameter Coverage

- **Required:** 0/0
- **Optional:** 0/0

#### Response Field Coverage

- **Total Fields:** 3
- **Supported:** 3
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

### getVersionInfo

**Status:** ✅ Fully Supported

#### Parameter Coverage

- **Required:** 0/0
- **Optional:** 0/0

#### Response Field Coverage

- **Total Fields:** 5
- **Supported:** 5
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

### getFeeStats

**Status:** ✅ Fully Supported

#### Parameter Coverage

- **Required:** 0/0
- **Optional:** 0/0

#### Response Field Coverage

- **Total Fields:** 19
- **Supported:** 19
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

### getLatestLedger

**Status:** ✅ Fully Supported

#### Parameter Coverage

- **Required:** 0/0
- **Optional:** 0/0

#### Response Field Coverage

- **Total Fields:** 6
- **Supported:** 6
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

### getLedgerEntries

**Status:** ✅ Fully Supported

#### Parameter Coverage

- **Required:** 1/1
- **Optional:** 0/0

#### Response Field Coverage

- **Total Fields:** 7
- **Supported:** 7
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

### getLedgers

**Status:** ✅ Fully Supported

#### Parameter Coverage

- **Required:** 1/1
- **Optional:** 0/0

#### Response Field Coverage

- **Total Fields:** 11
- **Supported:** 11
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

### getEvents

**Status:** ✅ Fully Supported

#### Parameter Coverage

- **Required:** 1/1
- **Optional:** 2/2

#### Response Field Coverage

- **Total Fields:** 17
- **Supported:** 17
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

### getTransaction

**Status:** ✅ Fully Supported

#### Parameter Coverage

- **Required:** 1/1
- **Optional:** 0/0

#### Response Field Coverage

- **Total Fields:** 5
- **Supported:** 5
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

### getTransactions

**Status:** ✅ Fully Supported

#### Parameter Coverage

- **Required:** 1/1
- **Optional:** 0/0

#### Response Field Coverage

- **Total Fields:** 7
- **Supported:** 7
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

### sendTransaction

**Status:** ✅ Fully Supported

#### Parameter Coverage

- **Required:** 1/1
- **Optional:** 0/0

#### Response Field Coverage

- **Total Fields:** 6
- **Supported:** 6
- **Coverage:** 100.0%

#### Implementation Notes

- All parameters and response fields implemented

### simulateTransaction

**Status:** ✅ Fully Supported

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

## Document Information

### Generation Details
- **Generated On**: 2026-02-10 13:21:25 UTC
- **Data Sources**: 
  - Stellar RPC: Extracted from stellar-rpc Go source code (github.com/stellar/stellar-rpc)
  - PHP SDK: Source code analysis of Stellar PHP SDK v1.9.3

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

---

## Summary

The Stellar PHP SDK provides **full coverage** of the Soroban RPC API with **12 out of 12 methods implemented (100.0%)**.

### Key Takeaways

- All RPC methods fully implemented
- All parameters and response fields supported
- Full Protocol 21 through 25 support
- Production-ready for Soroban smart contract development

---

**Generated:** February 10, 2026

**Maintainer:** Stellar PHP SDK Team

**License:** Apache 2.0

For questions or contributions, visit: https://github.com/Soneso/stellar-php-sdk
