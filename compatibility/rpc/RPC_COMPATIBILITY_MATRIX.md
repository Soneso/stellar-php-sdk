# Soroban RPC vs PHP SDK Compatibility Matrix

**RPC Version:** v27.0.0 (released 2026-06-11)  
**RPC Source:** [v27.0.0](https://github.com/stellar/stellar-rpc/releases/tag/v27.0.0)  
**SDK Version:** 1.10.0  
**Generated:** 2026-06-18 22:26 UTC

## Overall Coverage

**Coverage:** 91.7%

- **Fully Supported:** 11/12
- **Partially Supported:** 1/12
- **Not Supported:** 0/12

## Method Comparison

### Transaction Methods

| RPC Method | Status | SDK Method | Response Class | Notes |
|------------|--------|------------|----------------|-------|
| getTransaction | Full | `getTransaction($transactionId)` | GetTransactionResponse | Full support including protocol 22+ txHash, protocol 23+ events, diagnosticEventsXdr. |
| getTransactions | Full | `getTransactions($request)` | GetTransactionsResponse | Full pagination support with cursor and limit. |
| sendTransaction | Full | `sendTransaction($transaction)` | SendTransactionResponse | Full support including diagnosticEventsXdr and errorResultXdr. |
| simulateTransaction | Partial | `simulateTransaction($request)` | SimulateTransactionResponse | Missing params: useUpgradedAuth |

### Ledger Methods

| RPC Method | Status | SDK Method | Response Class | Notes |
|------------|--------|------------|----------------|-------|
| getLatestLedger | Full | `getLatestLedger()` | GetLatestLedgerResponse | Returns id, protocolVersion, sequence, closeTime, headerXdr, metadataXdr. |
| getLedgerEntries | Full | `getLedgerEntries($base64EncodedKeys)` | GetLedgerEntriesResponse | Supports up to 200 keys, returns entries with TTL info. |
| getLedgers | Full | `getLedgers($request)` | GetLedgersResponse | Full pagination support with cursor and limit. |

### Event Methods

| RPC Method | Status | SDK Method | Response Class | Notes |
|------------|--------|------------|----------------|-------|
| getEvents | Full | `getEvents($request)` | GetEventsResponse | Full support including endLedger, filters, pagination, cursor. |

### Network Info Methods

| RPC Method | Status | SDK Method | Response Class | Notes |
|------------|--------|------------|----------------|-------|
| getFeeStats | Full | `getFeeStats()` | GetFeeStatsResponse | Full support for sorobanInclusionFee and inclusionFee statistics. |
| getHealth | Full | `getHealth()` | GetHealthResponse | Full support for status, ledgerRetentionWindow, oldestLedger, latestLedger. |
| getNetwork | Full | `getNetwork()` | GetNetworkResponse | Returns friendbotUrl (optional), passphrase, and protocolVersion. |
| getVersionInfo | Full | `getVersionInfo()` | GetVersionInfoResponse | Protocol 22+ compliant (camelCase fields; also reads snake_case for backward compat). |

## Parameter Coverage

Detailed breakdown of parameter support per method.

| RPC Method | RPC Params | SDK Params | Missing |
|------------|------------|------------|---------|
| getEvents | 4 | 5 | - |
| getFeeStats | 0 | 0 | - |
| getHealth | 0 | 0 | - |
| getLatestLedger | 0 | 0 | - |
| getLedgerEntries | 1 | 1 | - |
| getLedgers | 2 | 4 | - |
| getNetwork | 0 | 0 | - |
| getTransaction | 1 | 1 | - |
| getTransactions | 2 | 4 | - |
| getVersionInfo | 0 | 0 | - |
| sendTransaction | 1 | 1 | - |
| simulateTransaction | 4 | 4 | useUpgradedAuth |

## Response Field Coverage

Detailed breakdown of response field support per method.

| RPC Method | RPC Fields | SDK Fields | Missing |
|------------|------------|------------|---------|
| getEvents | 6 | 7 | - |
| getFeeStats | 3 | 4 | - |
| getHealth | 4 | 5 | - |
| getLatestLedger | 6 | 7 | - |
| getLedgerEntries | 2 | 3 | - |
| getLedgers | 6 | 7 | - |
| getNetwork | 3 | 4 | - |
| getTransaction | 5 | 16 | - |
| getTransactions | 6 | 7 | - |
| getVersionInfo | 5 | 6 | - |
| sendTransaction | 6 | 7 | - |
| simulateTransaction | 8 | 9 | - |

## Legend

| Status | Description |
|--------|-------------|
| Full | Method implemented with all required parameters and response fields |
| Partial | Basic functionality present, missing some optional parameters or response fields |
| Missing | Method not implemented in SDK |
