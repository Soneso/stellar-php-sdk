# Stellar PHP SDK - Horizon API Compatibility Matrix

## Document Metadata

| Property | Value |
|----------|-------|
| **SDK Version** | 1.9.2 |
| **Compatible Horizon Version** | v25.0.0 (09e138ce) |
| **Last Updated** | February 03, 2026 |
| **Overall Coverage** | 100.0% (Fully Supported) |
| **Total Endpoints Analyzed** | 50 |

## Matrix Legend

| Symbol | Status | Description |
|--------|--------|-------------|
| ✅ | **Fully Supported** | Complete implementation with all parameters and features |
| ⚠️ | **Partially Supported** | Basic functionality works but some parameters or features are missing |
| ❌ | **Not Supported** | Endpoint not implemented in SDK |
| 🔄 | **Deprecated** | Endpoint is deprecated in Horizon API |

### Status Details

- **✅ Fully Supported**: All endpoint parameters are implemented, streaming is supported where applicable, and the implementation matches Horizon specification completely.
- **⚠️ Partially Supported**: Core functionality is available but missing optional parameters, streaming support, or advanced features.
- **❌ Not Supported**: The endpoint is not implemented in the SDK. Developers must use direct HTTP calls.
- **🔄 Deprecated**: The Horizon API has deprecated this endpoint. Use the recommended alternative instead.

---

## Executive Summary

### Key Statistics

| Metric | Count | Percentage |
|--------|-------|------------|
| **Total Horizon Endpoints** | 50 | 100% |
| **Fully Supported** | 50 | 100.0% |
| **Partially Supported** | 0 | 0.0% |
| **Not Supported** | 0 | 0.0% |
| **Deprecated** | 0 | 0.0% |
| **Streaming Endpoints** | 30 | 100% |
| **Streaming Support** | 30 | 100.0% |

### Coverage Highlights

**Strong Areas:**
- ✅ **Accounts**: 100% category coverage (9/9 endpoints supported)
- ✅ **Transactions**: 100% category coverage (7/7 endpoints supported)
- ✅ **Ledgers**: 100% category coverage (6/6 endpoints supported)
- ✅ **Liquidity_Pools**: 100% category coverage (6/6 endpoints supported)
- ✅ **Claimable_Balances**: 100% category coverage (4/4 endpoints supported)

**Areas Needing Attention:**

---

## Detailed Compatibility Matrix

### Accounts (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/accounts` | GET | ✅ | `accounts()->execute()`<br/>`AccountsRequestBuilder` | Full implementation with all features supported |
| `/accounts/{account_id}` | GET | ✅ | `accounts()->streamAccount($accountId, $callback)`<br/>`AccountsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/accounts/{account_id}/data/{key}` | GET | ✅ | `accounts()->streamAccountData($accountId, $key, $callback)`<br/>`AccountsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/accounts/{account_id}/effects` | GET | ✅ | `effects()->forAccount($accountId)->execute()`<br/>`EffectsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/accounts/{account_id}/offers` | GET | ✅ | `offers()->forAccount($accountId)->execute()`<br/>`OffersRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/accounts/{account_id}/operations` | GET | ✅ | `operations()->forAccount($accountId)->execute()`<br/>`OperationsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/accounts/{account_id}/payments` | GET | ✅ | `payments()->forAccount($accountId)->execute()`<br/>`PaymentsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/accounts/{account_id}/trades` | GET | ✅ | `trades()->forAccount($accountId)->execute()`<br/>`TradesRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/accounts/{account_id}/transactions` | GET | ✅ | `transactions()->forAccount($accountId)->execute()`<br/>`TransactionsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |

### Assets (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/assets` | GET | ✅ | `assets()->execute()`<br/>`AssetsRequestBuilder` | Full implementation with all features supported |

### Claimable Balances (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/claimable_balances` | GET | ✅ | `claimableBalances()->execute()`<br/>`ClaimableBalancesRequestBuilder` | Full implementation with all features supported |
| `/claimable_balances/{claimable_balance_id}/operations` | GET | ✅ | `operations()->forClaimableBalance($claimableBalanceId)->execute()`<br/>`OperationsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/claimable_balances/{claimable_balance_id}/transactions` | GET | ✅ | `transactions()->forClaimableBalance($claimableBalanceId)->execute()`<br/>`TransactionsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/claimable_balances/{id}` | GET | ✅ | `claimableBalances()->claimableBalance($claimableBalanceId)`<br/>`ClaimableBalancesRequestBuilder` | Full implementation with all features supported |

### Effects (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/effects` | GET | ✅ | `effects()->execute()`<br/>`EffectsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |

### Fee Stats (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/fee_stats` | GET | ✅ | `feeStats()->getFeeStats()`<br/>`FeeStatsRequestBuilder` | Full implementation with all features supported |

### Friendbot (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/friendbot` | GET | ✅ | `FriendBot::fundTestAccount($accountId)`<br/>`FriendBot` | Full implementation with all features supported. Utility class for funding test accounts. Also available: FuturenetFriendBot, CustomFriendBot |

### Health (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/health` | GET | ✅ | `health()->getHealth()`<br/>`HealthRequestBuilder` | Full implementation with all features supported |

### Ledgers (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/ledgers` | GET | ✅ | `ledgers()->execute()`<br/>`LedgersRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/ledgers/{ledger_id}` | GET | ✅ | `ledgers()->ledger($ledgerSequence)`<br/>`LedgersRequestBuilder` | Full implementation with all features supported |
| `/ledgers/{ledger_id}/effects` | GET | ✅ | `effects()->forLedger($ledgerSeq)->execute()`<br/>`EffectsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/ledgers/{ledger_id}/operations` | GET | ✅ | `operations()->forLedger($ledgerSeq)->execute()`<br/>`OperationsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/ledgers/{ledger_id}/payments` | GET | ✅ | `payments()->forLedger($ledgerSeq)->execute()`<br/>`PaymentsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/ledgers/{ledger_id}/transactions` | GET | ✅ | `transactions()->forLedger($ledgerSeq)->execute()`<br/>`TransactionsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |

### Liquidity Pools (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/liquidity_pools` | GET | ✅ | `liquidityPools()->execute()`<br/>`LiquidityPoolsRequestBuilder` | Full implementation with all features supported |
| `/liquidity_pools/{liquidity_pool_id}` | GET | ✅ | `liquidityPools()->forPoolId($liquidityPoolID)->execute()`<br/>`LiquidityPoolsRequestBuilder` | Full implementation with all features supported |
| `/liquidity_pools/{liquidity_pool_id}/effects` | GET | ✅ | `effects()->forLiquidityPool($liquidityPoolId)->execute()`<br/>`EffectsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/liquidity_pools/{liquidity_pool_id}/operations` | GET | ✅ | `operations()->forLiquidityPool($liquidityPoolId)->execute()`<br/>`OperationsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/liquidity_pools/{liquidity_pool_id}/trades` | GET | ✅ | `trades()->forLiquidityPool($liquidityPoolId)->execute()`<br/>`TradesRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/liquidity_pools/{liquidity_pool_id}/transactions` | GET | ✅ | `transactions()->forLiquidityPool($liquidityPoolId)->execute()`<br/>`TransactionsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |

### Offers (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/offers` | GET | ✅ | `offers()->execute()`<br/>`OffersRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/offers/{offer_id}` | GET | ✅ | `offers()->offer($offerId)`<br/>`OffersRequestBuilder` | Full implementation with all features supported |

### Operations (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/operations` | GET | ✅ | `operations()->execute()`<br/>`OperationsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/operations/{id}` | GET | ✅ | `operations()->operation($operationId)`<br/>`OperationsRequestBuilder` | Full implementation with all features supported |
| `/operations/{op_id}/effects` | GET | ✅ | `effects()->forOperation($operationId)->execute()`<br/>`EffectsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |

### Order Book (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/order_book` | GET | ✅ | `orderBook()->execute()`<br/>`OrderBookRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |

### Paths (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/paths` | GET | ✅ | `findPaths()->execute()`<br/>`FindPathsRequestBuilder` | Full implementation with all features supported |
| `/paths/strict-receive` | GET | ✅ | `strictReceivePaths()->execute()`<br/>`StrictReceivePathsRequestBuilder` | Full implementation with all features supported |
| `/paths/strict-send` | GET | ✅ | `strictSendPaths()->execute()`<br/>`StrictSendPathsRequestBuilder` | Full implementation with all features supported |

### Payments (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/payments` | GET | ✅ | `payments()->execute()`<br/>`PaymentsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |

### Root (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/` | GET | ✅ | `root()->execute()`<br/>`RootRequestBuilder` | Full implementation with all features supported |

### Trades (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/trade_aggregations` | GET | ✅ | `tradeAggregations()->execute()`<br/>`TradeAggregationsRequestBuilder` | Full implementation with all features supported |
| `/trades` | GET | ✅ | `trades()->forOffer($offerId)->execute()`<br/>`TradesRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |

### Transactions (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/transactions` | GET | ✅ | `transactions()->execute()`<br/>`TransactionsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/transactions` | POST | ✅ | `submitTransaction($transaction)`<br/>`SubmitTransactionRequestBuilder` | Full implementation with all features supported |
| `/transactions/{tx_id}` | GET | ✅ | `transactions()->transaction($transactionId)`<br/>`TransactionsRequestBuilder` | Full implementation with all features supported |
| `/transactions/{tx_id}/effects` | GET | ✅ | `effects()->forTransaction($transactionId)->execute()`<br/>`EffectsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/transactions/{tx_id}/operations` | GET | ✅ | `operations()->forTransaction($transactionId)->execute()`<br/>`OperationsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/transactions/{tx_id}/payments` | GET | ✅ | `payments()->forTransaction($transactionId)->execute()`<br/>`PaymentsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/transactions_async` | POST | ✅ | `submitAsyncTransaction($transaction)`<br/>`SubmitAsyncTransactionRequestBuilder` | Full implementation with all features supported |

---

## Implementation Gaps by Priority

## Streaming Support Analysis

**Overall Streaming Coverage**: 100.0% (30/30 endpoints)

✅ All implemented endpoints that support streaming in Horizon have streaming support in the SDK!

---

## Document Information

### Generation Details
- **Generated On**: 2026-02-03 16:20:24 UTC
- **Data Sources**: 
  - Horizon API: Official Stellar Horizon documentation
  - PHP SDK: Source code analysis of Stellar PHP SDK v1.9.2

### How to Use This Matrix

1. **Check Endpoint Support**: Look up the endpoint you need in the detailed matrix to see its implementation status.
2. **Review Missing Features**: If an endpoint is partially supported, check the "Missing Features/Notes" column for details.
3. **Plan Workarounds**: For unsupported endpoints, you'll need to make direct HTTP calls to Horizon.
4. **Consider Alternatives**: For deprecated endpoints, use the recommended alternatives listed in the notes.

### Contributing

Help improve SDK coverage! If you need an unsupported endpoint:
1. Check the [GitHub Issues](https://github.com/Soneso/stellar-php-sdk/issues) for existing requests
2. Submit a feature request with your use case
3. Consider contributing an implementation via pull request
