# Stellar PHP SDK - Horizon API Compatibility Matrix

## Document Metadata

| Property | Value |
|----------|-------|
| **SDK Version** | 1.8.6 |
| **Compatible Horizon Version** | 23.0.0 (stellar-) |
| **Last Updated** | October 16, 2025 |
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
| **Fully Supported** | 49 | 98.0% |
| **Partially Supported** | 0 | 0.0% |
| **Not Supported** | 0 | 0.0% |
| **Deprecated** | 1 | 2.0% |
| **Streaming Endpoints** | 29 | 100% |
| **Streaming Support** | 29 | 100.0% |

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
| `/accounts` | GET | ✅ | `accounts()->execute()()`<br/>`AccountsRequestBuilder` | Full implementation with all features supported |
| `/accounts/{account_id}` | GET | ✅ | `accounts()->account($accountId)()`<br/>`AccountsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported. Supports streaming via streamAccount() method |
| `/accounts/{account_id}/data/{key}` | GET | ✅ | `accounts()->accountData($accountId, $key)()`<br/>`AccountsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported. Returns single data entry value for an account. Supports streaming via streamAccountData() method |
| `/accounts/{account_id}/effects` | GET | ✅ | `effects()->forAccount($accountId)->execute()()`<br/>`EffectsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/accounts/{account_id}/offers` | GET | ✅ | `offers()->forAccount($accountId)->execute()()`<br/>`OffersRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/accounts/{account_id}/operations` | GET | ✅ | `operations()->forAccount($accountId)->execute()()`<br/>`OperationsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/accounts/{account_id}/payments` | GET | ✅ | `payments()->forAccount($accountId)->execute()()`<br/>`PaymentsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/accounts/{account_id}/trades` | GET | ✅ | `trades()->forAccount($accountId)->execute()()`<br/>`TradesRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/accounts/{account_id}/transactions` | GET | ✅ | `transactions()->forAccount($accountId)->execute()()`<br/>`TransactionsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |

### Assets (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/assets` | GET | ✅ | `assets()->execute()()`<br/>`AssetsRequestBuilder` | Full implementation with all features supported |

### Claimable Balances (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/claimable_balances` | GET | ✅ | `claimableBalances()->execute()()`<br/>`ClaimableBalancesRequestBuilder` | Full implementation with all features supported |
| `/claimable_balances/{claimable_balance_id}/operations` | GET | ✅ | `operations()->forClaimableBalance($id)->execute()()`<br/>`OperationsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/claimable_balances/{claimable_balance_id}/transactions` | GET | ✅ | `transactions()->forClaimableBalance($id)->execute()()`<br/>`TransactionsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/claimable_balances/{id}` | GET | ✅ | `claimableBalances()->claimableBalance($id)()`<br/>`ClaimableBalancesRequestBuilder` | Full implementation with all features supported |

### Effects (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/effects` | GET | ✅ | `effects()->execute()()`<br/>`EffectsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |

### Fee Stats (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/fee_stats` | GET | ✅ | `feeStats()->getFeeStats()()`<br/>`FeeStatsRequestBuilder` | Full implementation with all features supported |

### Friendbot (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/friendbot` | GET | ✅ | `FriendBot::fundTestAccount($accountId)()`<br/>`FriendBot` | Full implementation with all features supported. Testnet/Futurenet utility for funding test accounts. Use FriendBot for testnet, FuturenetFriendBot for futurenet |

### Health (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/health` | GET | ✅ | `health()->getHealth()()`<br/>`HealthRequestBuilder` | Full implementation with all features supported. Health check endpoint for monitoring and load balancers |

### Ledgers (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/ledgers` | GET | ✅ | `ledgers()->execute()()`<br/>`LedgersRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/ledgers/{ledger_id}` | GET | ✅ | `ledgers()->ledger($ledgerSeq)()`<br/>`LedgersRequestBuilder` | Full implementation with all features supported |
| `/ledgers/{ledger_id}/effects` | GET | ✅ | `effects()->forLedger($ledgerSeq)->execute()()`<br/>`EffectsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/ledgers/{ledger_id}/operations` | GET | ✅ | `operations()->forLedger($ledgerSeq)->execute()()`<br/>`OperationsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/ledgers/{ledger_id}/payments` | GET | ✅ | `payments()->forLedger($ledgerSeq)->execute()()`<br/>`PaymentsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/ledgers/{ledger_id}/transactions` | GET | ✅ | `transactions()->forLedger($ledgerSeq)->execute()()`<br/>`TransactionsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |

### Liquidity Pools (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/liquidity_pools` | GET | ✅ | `liquidityPools()->execute()()`<br/>`LiquidityPoolsRequestBuilder` | Full implementation with all features supported |
| `/liquidity_pools/{liquidity_pool_id}` | GET | ✅ | `liquidityPools()->forPoolId($poolId)()`<br/>`LiquidityPoolsRequestBuilder` | Full implementation with all features supported |
| `/liquidity_pools/{liquidity_pool_id}/effects` | GET | ✅ | `effects()->forLiquidityPool($poolId)->execute()()`<br/>`EffectsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/liquidity_pools/{liquidity_pool_id}/operations` | GET | ✅ | `operations()->forLiquidityPool($poolId)->execute()()`<br/>`OperationsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/liquidity_pools/{liquidity_pool_id}/trades` | GET | ✅ | `trades()->forLiquidityPool($poolId)->execute()()`<br/>`TradesRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/liquidity_pools/{liquidity_pool_id}/transactions` | GET | ✅ | `transactions()->forLiquidityPool($poolId)->execute()()`<br/>`TransactionsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |

### Offers (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/offers` | GET | ✅ | `offers()->execute()()`<br/>`OffersRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/offers/{offer_id}` | GET | ✅ | `offers()->offer($offerId)()`<br/>`OffersRequestBuilder` | Full implementation with all features supported |

### Operations (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/operations` | GET | ✅ | `operations()->execute()()`<br/>`OperationsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/operations/{id}` | GET | ✅ | `operations()->operation($operationId)()`<br/>`OperationsRequestBuilder` | Full implementation with all features supported |
| `/operations/{op_id}/effects` | GET | ✅ | `effects()->forOperation($operationId)->execute()()`<br/>`EffectsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |

### Order Book (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/order_book` | GET | ✅ | `orderBook()->forBuyingAsset($asset)->forSellingAsset($asset)->execute()()`<br/>`OrderBookRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |

### Paths (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/paths` | GET | 🔄 | `findPaths()->execute()()`<br/>`FindPathsRequestBuilder` | Parameter: source_assets |
| `/paths/strict-receive` | GET | ✅ | `findStrictReceivePaths()->execute()()`<br/>`StrictReceivePathsRequestBuilder` | Full implementation with all features supported |
| `/paths/strict-send` | GET | ✅ | `findStrictSendPaths()->execute()()`<br/>`StrictSendPathsRequestBuilder` | Full implementation with all features supported |

### Payments (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/payments` | GET | ✅ | `payments()->execute()()`<br/>`PaymentsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |

### Root (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/` | GET | ✅ | `root()()`<br/>`RootRequestBuilder` | Full implementation with all features supported |

### Trades (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/trade_aggregations` | GET | ✅ | `tradeAggregations()->execute()()`<br/>`TradeAggregationsRequestBuilder` | Full implementation with all features supported |
| `/trades` | GET | ✅ | `trades()->execute()()`<br/>`TradesRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |

### Transactions (100.0% coverage)

| Endpoint | Method | Status | SDK Implementation | Missing Features/Notes |
|----------|--------|--------|-------------------|------------------------|
| `/transactions` | GET | ✅ | `transactions()->execute()()`<br/>`TransactionsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/transactions` | POST | ✅ | `submitTransaction($transaction)()`<br/>`SubmitTransactionRequestBuilder` | Full implementation with all features supported. Synchronous transaction submission |
| `/transactions/{tx_id}` | GET | ✅ | `transactions()->transaction($transactionId)()`<br/>`TransactionsRequestBuilder` | Full implementation with all features supported |
| `/transactions/{tx_id}/effects` | GET | ✅ | `effects()->forTransaction($transactionId)->execute()()`<br/>`EffectsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/transactions/{tx_id}/operations` | GET | ✅ | `operations()->forTransaction($transactionId)->execute()()`<br/>`OperationsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/transactions/{tx_id}/payments` | GET | ✅ | `payments()->forTransaction($transactionId)->execute()()`<br/>`PaymentsRequestBuilder`<br/>✅ Streaming | Full implementation with all features supported |
| `/transactions_async` | POST | ✅ | `submitAsyncTransaction($transaction)()`<br/>`SubmitAsyncTransactionRequestBuilder` | Full implementation with all features supported. Asynchronous transaction submission |

---

## Implementation Gaps by Priority

## Streaming Support Analysis

**Overall Streaming Coverage**: 100.0% (29/29 endpoints)

✅ All implemented endpoints that support streaming in Horizon have streaming support in the SDK!

---

## Document Information

### Generation Details
- **Generated On**: 2025-10-16 17:06:53 UTC
- **Data Sources**: 
  - Horizon API: Official Stellar Horizon documentation
  - PHP SDK: Source code analysis of Stellar PHP SDK v1.8.6

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
