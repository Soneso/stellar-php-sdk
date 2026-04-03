# Horizon API vs PHP SDK Compatibility Matrix

**Horizon Version:** v25.1.0 (released 2026-03-19)  
**Horizon Source:** [v25.1.0](https://github.com/stellar/stellar-horizon/releases/tag/v25.1.0)  
**SDK Version:** 1.9.6  
**Generated:** 2026-04-03 21:55:52 UTC

**Horizon Endpoints Discovered:** 52  
**Public API Endpoints (in matrix):** 50

> **Note:** 2 endpoints intentionally excluded from the matrix:
> - `GET /paths` - Deprecated - use /paths/strict-receive and /paths/strict-send
> - `POST /friendbot` - Redundant - GET method is used instead

## Overall Coverage

**Coverage:** 100.0% (50/50 public API endpoints)

- **Fully Supported:** 50/50
- **Partially Supported:** 0/50
- **Not Supported:** 0/50

## Coverage by Category

| Category | Coverage | Supported | Not Supported | Total |
|----------|----------|-----------|---------------|-------|
| accounts | 100.0% | 9 | 0 | 9 |
| assets | 100.0% | 1 | 0 | 1 |
| claimable balances | 100.0% | 4 | 0 | 4 |
| effects | 100.0% | 1 | 0 | 1 |
| friendbot | 100.0% | 1 | 0 | 1 |
| ledgers | 100.0% | 6 | 0 | 6 |
| liquidity pools | 100.0% | 6 | 0 | 6 |
| network | 100.0% | 1 | 0 | 1 |
| offers | 100.0% | 3 | 0 | 3 |
| operations | 100.0% | 3 | 0 | 3 |
| order book | 100.0% | 1 | 0 | 1 |
| paths | 100.0% | 2 | 0 | 2 |
| payments | 100.0% | 1 | 0 | 1 |
| root | 100.0% | 2 | 0 | 2 |
| trades | 100.0% | 2 | 0 | 2 |
| transactions | 100.0% | 7 | 0 | 7 |

## Streaming Support

**Coverage:** 100.0%

- Streaming endpoints: 31
- Supported: 31

## Detailed Endpoint Comparison

### Accounts

| Endpoint | Method | Status | SDK Method | Streaming | Notes |
|----------|--------|--------|------------|-----------|-------|
| `/accounts` | GET | Full | `AccountsRequestBuilder::execute()` |  | - |
| `/accounts/{account_id}` | GET | Full | `AccountsRequestBuilder::account()` | Yes | AccountsRequestBuilder::streamAccount() |
| `/accounts/{account_id}/data/{key}` | GET | Full | `AccountsRequestBuilder::accountData()` | Yes | AccountsRequestBuilder::streamAccountData() |
| `/accounts/{account_id}/offers` | GET | Full | `OffersRequestBuilder::forAccount()::execute()` | Yes | OffersRequestBuilder::forAccount()::stream() |
| `/accounts/{account_id}/effects` | GET | Full | `EffectsRequestBuilder::forAccount()::execute()` | Yes | EffectsRequestBuilder::forAccount()::stream() |
| `/accounts/{account_id}/operations` | GET | Full | `OperationsRequestBuilder::forAccount()::execute()` | Yes | OperationsRequestBuilder::forAccount()::stream() |
| `/accounts/{account_id}/payments` | GET | Full | `PaymentsRequestBuilder::forAccount()::execute()` | Yes | PaymentsRequestBuilder::forAccount()::stream() |
| `/accounts/{account_id}/trades` | GET | Full | `TradesRequestBuilder::forAccount()::execute()` | Yes | TradesRequestBuilder::forAccount()::stream() |
| `/accounts/{account_id}/transactions` | GET | Full | `TransactionsRequestBuilder::forAccount()::execute()` | Yes | TransactionsRequestBuilder::forAccount()::stream() |

### Assets

| Endpoint | Method | Status | SDK Method | Streaming | Notes |
|----------|--------|--------|------------|-----------|-------|
| `/assets` | GET | Full | `AssetsRequestBuilder::execute()` |  | - |

### Claimable Balances

| Endpoint | Method | Status | SDK Method | Streaming | Notes |
|----------|--------|--------|------------|-----------|-------|
| `/claimable_balances` | GET | Full | `ClaimableBalancesRequestBuilder::execute()` |  | - |
| `/claimable_balances/{id}` | GET | Full | `ClaimableBalancesRequestBuilder::claimableBalance()` |  | No streaming |
| `/claimable_balances/{claimable_balance_id}/operations` | GET | Full | `OperationsRequestBuilder::forClaimableBalance()::execute()` | Yes | OperationsRequestBuilder::forClaimableBalance()::stream() |
| `/claimable_balances/{claimable_balance_id}/transactions` | GET | Full | `TransactionsRequestBuilder::forClaimableBalance()::execute()` | Yes | TransactionsRequestBuilder::forClaimableBalance()::stream() |

### Effects

| Endpoint | Method | Status | SDK Method | Streaming | Notes |
|----------|--------|--------|------------|-----------|-------|
| `/effects` | GET | Full | `EffectsRequestBuilder::execute()` | Yes | EffectsRequestBuilder::stream() |

### Friendbot

| Endpoint | Method | Status | SDK Method | Streaming | Notes |
|----------|--------|--------|------------|-----------|-------|
| `/friendbot` | GET | Full | `StellarSDK::friendBot()` |  | External friendbot URL |

### Ledgers

| Endpoint | Method | Status | SDK Method | Streaming | Notes |
|----------|--------|--------|------------|-----------|-------|
| `/ledgers` | GET | Full | `LedgersRequestBuilder::execute()` | Yes | LedgersRequestBuilder::stream() |
| `/ledgers/{ledger_id}` | GET | Full | `LedgersRequestBuilder::ledger()` |  | No streaming |
| `/ledgers/{ledger_id}/transactions` | GET | Full | `TransactionsRequestBuilder::forLedger()::execute()` | Yes | TransactionsRequestBuilder::forLedger()::stream() |
| `/ledgers/{ledger_id}/effects` | GET | Full | `EffectsRequestBuilder::forLedger()::execute()` | Yes | EffectsRequestBuilder::forLedger()::stream() |
| `/ledgers/{ledger_id}/operations` | GET | Full | `OperationsRequestBuilder::forLedger()::execute()` | Yes | OperationsRequestBuilder::forLedger()::stream() |
| `/ledgers/{ledger_id}/payments` | GET | Full | `PaymentsRequestBuilder::forLedger()::execute()` | Yes | PaymentsRequestBuilder::forLedger()::stream() |

### Liquidity Pools

| Endpoint | Method | Status | SDK Method | Streaming | Notes |
|----------|--------|--------|------------|-----------|-------|
| `/liquidity_pools` | GET | Full | `LiquidityPoolsRequestBuilder::execute()` |  | - |
| `/liquidity_pools/{liquidity_pool_id}` | GET | Full | `LiquidityPoolsRequestBuilder::forPoolId()` |  | No streaming |
| `/liquidity_pools/{liquidity_pool_id}/operations` | GET | Full | `OperationsRequestBuilder::forLiquidityPool()::execute()` | Yes | OperationsRequestBuilder::forLiquidityPool()::stream() |
| `/liquidity_pools/{liquidity_pool_id}/transactions` | GET | Full | `TransactionsRequestBuilder::forLiquidityPool()::execute()` | Yes | TransactionsRequestBuilder::forLiquidityPool()::stream() |
| `/liquidity_pools/{liquidity_pool_id}/effects` | GET | Full | `EffectsRequestBuilder::forLiquidityPool()::execute()` | Yes | EffectsRequestBuilder::forLiquidityPool()::stream() |
| `/liquidity_pools/{liquidity_pool_id}/trades` | GET | Full | `TradesRequestBuilder::forLiquidityPool()::execute()` | Yes | TradesRequestBuilder::forLiquidityPool()::stream() |

### Network

| Endpoint | Method | Status | SDK Method | Streaming | Notes |
|----------|--------|--------|------------|-----------|-------|
| `/fee_stats` | GET | Full | `FeeStatsRequestBuilder::getFeeStats()` |  | - |

### Offers

| Endpoint | Method | Status | SDK Method | Streaming | Notes |
|----------|--------|--------|------------|-----------|-------|
| `/offers` | GET | Full | `OffersRequestBuilder::execute()` | Yes | OffersRequestBuilder::stream() |
| `/offers/{offer_id}` | GET | Full | `OffersRequestBuilder::offer()` |  | No streaming |
| `/offers/{offer_id}/trades` | GET | Full | `TradesRequestBuilder::forOffer()::execute()` | Yes | TradesRequestBuilder::forOffer()::stream() |

### Operations

| Endpoint | Method | Status | SDK Method | Streaming | Notes |
|----------|--------|--------|------------|-----------|-------|
| `/operations` | GET | Full | `OperationsRequestBuilder::execute()` | Yes | OperationsRequestBuilder::stream() |
| `/operations/{id}` | GET | Full | `OperationsRequestBuilder::operation()` |  | No streaming |
| `/operations/{op_id}/effects` | GET | Full | `EffectsRequestBuilder::forOperation()::execute()` | Yes | EffectsRequestBuilder::forOperation()::stream() |

### Order Book

| Endpoint | Method | Status | SDK Method | Streaming | Notes |
|----------|--------|--------|------------|-----------|-------|
| `/order_book` | GET | Full | `OrderBookRequestBuilder::execute()` | Yes | OrderBookRequestBuilder::stream() |

### Paths

| Endpoint | Method | Status | SDK Method | Streaming | Notes |
|----------|--------|--------|------------|-----------|-------|
| `/paths/strict-receive` | GET | Full | `StrictReceivePathsRequestBuilder::execute()` |  | - |
| `/paths/strict-send` | GET | Full | `StrictSendPathsRequestBuilder::execute()` |  | - |

### Payments

| Endpoint | Method | Status | SDK Method | Streaming | Notes |
|----------|--------|--------|------------|-----------|-------|
| `/payments` | GET | Full | `PaymentsRequestBuilder::execute()` | Yes | PaymentsRequestBuilder::stream() |

### Root

| Endpoint | Method | Status | SDK Method | Streaming | Notes |
|----------|--------|--------|------------|-----------|-------|
| `/health` | GET | Full | `HealthRequestBuilder::getHealth()` |  | - |
| `/` | GET | Full | `StellarSDK (configuration)` |  | Via SDK initialization |

### Trades

| Endpoint | Method | Status | SDK Method | Streaming | Notes |
|----------|--------|--------|------------|-----------|-------|
| `/trades` | GET | Full | `TradesRequestBuilder::execute()` | Yes | TradesRequestBuilder::stream() |
| `/trade_aggregations` | GET | Full | `TradeAggregationsRequestBuilder::execute()` |  | - |

### Transactions

| Endpoint | Method | Status | SDK Method | Streaming | Notes |
|----------|--------|--------|------------|-----------|-------|
| `/transactions` | GET | Full | `TransactionsRequestBuilder::execute()` | Yes | TransactionsRequestBuilder::stream() |
| `/transactions/{tx_id}` | GET | Full | `TransactionsRequestBuilder::transaction()` |  | No streaming |
| `/transactions/{tx_id}/effects` | GET | Full | `EffectsRequestBuilder::forTransaction()::execute()` | Yes | EffectsRequestBuilder::forTransaction()::stream() |
| `/transactions/{tx_id}/operations` | GET | Full | `OperationsRequestBuilder::forTransaction()::execute()` | Yes | OperationsRequestBuilder::forTransaction()::stream() |
| `/transactions/{tx_id}/payments` | GET | Full | `PaymentsRequestBuilder::forTransaction()::execute()` | Yes | PaymentsRequestBuilder::forTransaction()::stream() |
| `/transactions` | POST | Full | `SubmitTransactionRequestBuilder::execute()` |  | - |
| `/transactions_async` | POST | Full | `SubmitAsyncTransactionRequestBuilder::execute()` |  | - |

## Query Parameter Support

**Filter Parameters Coverage:** 39/39 (100.0%)

## Legend

- **Full** - Complete implementation with all features
- **Partial** - Basic functionality with some limitations
- **Missing** - Endpoint not implemented