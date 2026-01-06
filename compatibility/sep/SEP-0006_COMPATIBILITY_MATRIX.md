# SEP-0006 (Deposit and Withdrawal API) Compatibility Matrix

**Generated:** 2026-01-06 16:36:04

**SEP Version:** N/A

**SEP Status:** Active (Interactive components are deprecated in favor of SEP-24)

**SDK Version:** 1.9.1

**SEP URL:** https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md

## SEP Summary

This SEP defines the standard way for anchors and wallets to interact on behalf
of users. This improves user experience by allowing wallets and other clients
to interact with anchors directly without the user needing to leave the wallet
to go to the anchor's site.

Please note that this SEP provides a normalized interface specification that
allows wallets and other services to interact with anchors _programmatically_.
[SEP-24](sep-0024.md) was created to support use cases where the anchor may
want to interact with users _interactively_ using a popup opened within the
wallet application.

## Overall Coverage

**Total Coverage:** 100% (95/95 fields)

- ✅ **Implemented:** 95/95
- ❌ **Not Implemented:** 0/95

**Required Fields:** 100% (23/23)

**Optional Fields:** 100% (72/72)

## Implementation Status

✅ **Implemented**

### Implementation Files

- `Soneso/StellarSDK/SEP/TransferServerService/CustomerInformationStatusResponse.php`
- `Soneso/StellarSDK/SEP/TransferServerService/CustomerInformationNeededException.php`
- `Soneso/StellarSDK/SEP/TransferServerService/ExtraInfo.php`
- `Soneso/StellarSDK/SEP/TransferServerService/AnchorFeatureFlags.php`
- `Soneso/StellarSDK/SEP/TransferServerService/DepositAsset.php`
- `Soneso/StellarSDK/SEP/TransferServerService/FeeDetailsDetails.php`
- `Soneso/StellarSDK/SEP/TransferServerService/AnchorTransactionsInfo.php`
- `Soneso/StellarSDK/SEP/TransferServerService/AnchorTransactionsRequestBuilder.php`
- `Soneso/StellarSDK/SEP/TransferServerService/AnchorTransaction.php`
- `Soneso/StellarSDK/SEP/TransferServerService/WithdrawRequestBuilder.php`
- `Soneso/StellarSDK/SEP/TransferServerService/AuthenticationRequiredException.php`
- `Soneso/StellarSDK/SEP/TransferServerService/DepositInstruction.php`
- `Soneso/StellarSDK/SEP/TransferServerService/InfoRequestBuilder.php`
- `Soneso/StellarSDK/SEP/TransferServerService/FeeRequest.php`
- `Soneso/StellarSDK/SEP/TransferServerService/FeeRequestBuilder.php`
- `Soneso/StellarSDK/SEP/TransferServerService/DepositExchangeRequestBuilder.php`
- `Soneso/StellarSDK/SEP/TransferServerService/CustomerInformationStatusException.php`
- `Soneso/StellarSDK/SEP/TransferServerService/TransferServerService.php`
- `Soneso/StellarSDK/SEP/TransferServerService/AnchorTransactionRequestBuilder.php`
- `Soneso/StellarSDK/SEP/TransferServerService/WithdrawResponse.php`
- `Soneso/StellarSDK/SEP/TransferServerService/WithdrawExchangeRequestBuilder.php`
- `Soneso/StellarSDK/SEP/TransferServerService/AnchorTransactionInfo.php`
- `Soneso/StellarSDK/SEP/TransferServerService/TransactionRefundPayment.php`
- `Soneso/StellarSDK/SEP/TransferServerService/DepositRequestBuilder.php`
- `Soneso/StellarSDK/SEP/TransferServerService/InfoResponse.php`
- `Soneso/StellarSDK/SEP/TransferServerService/AnchorTransactionsResponse.php`
- `Soneso/StellarSDK/SEP/TransferServerService/WithdrawExchangeAsset.php`
- `Soneso/StellarSDK/SEP/TransferServerService/CustomerInformationNeededResponse.php`
- `Soneso/StellarSDK/SEP/TransferServerService/AnchorTransactionsRequest.php`
- `Soneso/StellarSDK/SEP/TransferServerService/TransactionRefunds.php`
- `Soneso/StellarSDK/SEP/TransferServerService/AnchorTransactionResponse.php`
- `Soneso/StellarSDK/SEP/TransferServerService/WithdrawRequest.php`
- `Soneso/StellarSDK/SEP/TransferServerService/AnchorFeeInfo.php`
- `Soneso/StellarSDK/SEP/TransferServerService/WithdrawExchangeRequest.php`
- `Soneso/StellarSDK/SEP/TransferServerService/AnchorTransactionRequest.php`
- `Soneso/StellarSDK/SEP/TransferServerService/DepositExchangeRequest.php`
- `Soneso/StellarSDK/SEP/TransferServerService/DepositExchangeAsset.php`
- `Soneso/StellarSDK/SEP/TransferServerService/FeeResponse.php`
- `Soneso/StellarSDK/SEP/TransferServerService/AnchorField.php`
- `Soneso/StellarSDK/SEP/TransferServerService/FeeDetails.php`
- `Soneso/StellarSDK/SEP/TransferServerService/WithdrawAsset.php`
- `Soneso/StellarSDK/SEP/TransferServerService/DepositRequest.php`
- `Soneso/StellarSDK/SEP/TransferServerService/PatchTransactionRequest.php`
- `Soneso/StellarSDK/SEP/TransferServerService/DepositResponse.php`

### Key Classes

- **`CustomerInformationStatusResponse`**
- **`CustomerInformationNeededException`**
- **`ExtraInfo`**
- **`AnchorFeatureFlags`**
- **`DepositAsset`**
- **`FeeDetailsDetails`**
- **`AnchorTransactionsInfo`**
- **`AnchorTransactionsRequestBuilder`**
- **`AnchorTransaction`**
- **`WithdrawRequestBuilder`**
- **`AuthenticationRequiredException`**
- **`DepositInstruction`**
- **`InfoRequestBuilder`**
- **`FeeRequest`**
- **`FeeRequestBuilder`**
- **`DepositExchangeRequestBuilder`**
- **`CustomerInformationStatusException`**
- **`TransferServerService`**
- **`AnchorTransactionRequestBuilder`**
- **`WithdrawResponse`**
- **`WithdrawExchangeRequestBuilder`**
- **`AnchorTransactionInfo`**
- **`TransactionRefundPayment`**
- **`DepositRequestBuilder`**
- **`InfoResponse`**
- **`AnchorTransactionsResponse`**
- **`WithdrawExchangeAsset`**
- **`CustomerInformationNeededResponse`**
- **`AnchorTransactionsRequest`**
- **`TransactionRefunds`**
- **`AnchorTransactionResponse`**
- **`WithdrawRequest`**
- **`AnchorFeeInfo`**
- **`WithdrawExchangeRequest`**
- **`AnchorTransactionRequest`**
- **`DepositExchangeRequest`**
- **`DepositExchangeAsset`**
- **`FeeResponse`**
- **`AnchorField`**
- **`FeeDetails`**
- **`WithdrawAsset`**
- **`DepositRequest`**
- **`PatchTransactionRequest`**
- **`DepositResponse`**

## Coverage by Section

| Section | Coverage | Required Coverage | Implemented | Total |
|---------|----------|-------------------|-------------|-------|
| Deposit Endpoints | 100% | 100% | 2 | 2 |
| Deposit Request Parameters | 100% | 100% | 15 | 15 |
| Deposit Response Fields | 100% | 100% | 8 | 8 |
| Withdraw Endpoints | 100% | 100% | 2 | 2 |
| Withdraw Request Parameters | 100% | 100% | 17 | 17 |
| Withdraw Response Fields | 100% | 100% | 10 | 10 |
| Info Endpoint | 100% | 100% | 1 | 1 |
| Info Response Fields | 100% | 100% | 8 | 8 |
| Transaction Endpoints | 100% | 100% | 3 | 3 |
| Transaction Fields | 100% | 100% | 16 | 16 |
| Transaction Status Values | 100% | 100% | 12 | 12 |
| Fee Endpoint | 100% | 0% | 1 | 1 |

## Detailed Field Comparison

### Deposit Endpoints

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `deposit` | ✓ | ✅ | `deposit` | Initiates a deposit transaction for on-chain assets |
| `deposit_exchange` |  | ✅ | `depositExchange` | Initiates a deposit with asset exchange (SEP-38 integration) |

### Deposit Request Parameters

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `asset_code` | ✓ | ✅ | - | Code of the on-chain asset the user wants to receive |
| `account` | ✓ | ✅ | - | Stellar account ID of the user |
| `memo` |  | ✅ | - | Value of memo to attach to transaction |
| `memo_type` |  | ✅ | - | Type of memo to attach to transaction (text, id, or hash) |
| `email_address` |  | ✅ | - | Email address of the user (for notifications) |
| `type` |  | ✅ | - | Type of deposit method (e.g., bank_account, cash, mobile_money) |
| `wallet_name` |  | ✅ | - | Name of the wallet the user is using |
| `wallet_url` |  | ✅ | - | URL of the wallet the user is using |
| `lang` |  | ✅ | - | Language code for response messages (ISO 639-1) |
| `on_change_callback` |  | ✅ | - | URL for anchor to send callback when transaction status changes |
| `amount` |  | ✅ | - | Amount of on-chain asset the user wants to receive |
| `country_code` |  | ✅ | - | Country code of the user (ISO 3166-1 alpha-3) |
| `claimable_balance_supported` |  | ✅ | - | Whether the client supports receiving claimable balances |
| `customer_id` |  | ✅ | - | ID of the customer from SEP-12 KYC process |
| `location_id` |  | ✅ | - | ID of the physical location for cash pickup |

### Deposit Response Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `how` | ✓ | ✅ | - | Instructions for how to deposit the asset |
| `id` |  | ✅ | - | Persistent transaction identifier |
| `eta` |  | ✅ | - | Estimated seconds until deposit completes |
| `min_amount` |  | ✅ | - | Minimum deposit amount |
| `max_amount` |  | ✅ | - | Maximum deposit amount |
| `fee_fixed` |  | ✅ | - | Fixed fee for deposit |
| `fee_percent` |  | ✅ | - | Percentage fee for deposit |
| `extra_info` |  | ✅ | - | Additional information about the deposit |

### Withdraw Endpoints

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `withdraw` | ✓ | ✅ | `withdraw` | Initiates a withdrawal transaction for off-chain assets |
| `withdraw_exchange` |  | ✅ | `withdrawExchange` | Initiates a withdrawal with asset exchange (SEP-38 integration) |

### Withdraw Request Parameters

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `asset_code` | ✓ | ✅ | - | Code of the on-chain asset the user wants to send |
| `type` | ✓ | ✅ | - | Type of withdrawal method (e.g., bank_account, cash, mobile_money) |
| `dest` |  | ✅ | - | Destination for withdrawal (bank account number, etc.) |
| `dest_extra` |  | ✅ | - | Extra information for destination (routing number, etc.) |
| `account` |  | ✅ | - | Stellar account ID of the user |
| `memo` |  | ✅ | - | Memo to identify the user if account is shared |
| `memo_type` |  | ✅ | - | Type of memo (text, id, or hash) |
| `wallet_name` |  | ✅ | - | Name of the wallet the user is using |
| `wallet_url` |  | ✅ | - | URL of the wallet the user is using |
| `lang` |  | ✅ | - | Language code for response messages (ISO 639-1) |
| `on_change_callback` |  | ✅ | - | URL for anchor to send callback when transaction status changes |
| `amount` |  | ✅ | - | Amount of on-chain asset the user wants to send |
| `country_code` |  | ✅ | - | Country code of the user (ISO 3166-1 alpha-3) |
| `refund_memo` |  | ✅ | - | Memo to use for refund transaction if withdrawal fails |
| `refund_memo_type` |  | ✅ | - | Type of refund memo (text, id, or hash) |
| `customer_id` |  | ✅ | - | ID of the customer from SEP-12 KYC process |
| `location_id` |  | ✅ | - | ID of the physical location for cash pickup |

### Withdraw Response Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `account_id` | ✓ | ✅ | - | Stellar account to send withdrawn assets to |
| `memo_type` | ✓ | ✅ | - | Type of memo to attach to transaction |
| `memo` |  | ✅ | - | Value of memo to attach to transaction |
| `id` | ✓ | ✅ | - | Persistent transaction identifier |
| `eta` |  | ✅ | - | Estimated seconds until withdrawal completes |
| `min_amount` |  | ✅ | - | Minimum withdrawal amount |
| `max_amount` |  | ✅ | - | Maximum withdrawal amount |
| `fee_fixed` |  | ✅ | - | Fixed fee for withdrawal |
| `fee_percent` |  | ✅ | - | Percentage fee for withdrawal |
| `extra_info` |  | ✅ | - | Additional information about the withdrawal |

### Info Endpoint

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `info_endpoint` | ✓ | ✅ | `info` | Provides anchor capabilities and asset information |

### Info Response Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `deposit` | ✓ | ✅ | - | Map of asset codes to deposit asset information |
| `withdraw` | ✓ | ✅ | - | Map of asset codes to withdraw asset information |
| `fee` |  | ✅ | - | Fee endpoint information |
| `transactions` |  | ✅ | - | Transaction history endpoint information |
| `transaction` |  | ✅ | - | Single transaction endpoint information |
| `features` |  | ✅ | - | Feature flags supported by the anchor |
| `deposit-exchange` |  | ✅ | - | Map of asset codes to deposit-exchange asset information |
| `withdraw-exchange` |  | ✅ | - | Map of asset codes to withdraw-exchange asset information |

### Transaction Endpoints

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `transactions` | ✓ | ✅ | `transactions` | Retrieves transaction history for an account |
| `transaction` | ✓ | ✅ | `transaction` | Retrieves details for a single transaction |
| `patch_transaction` |  | ✅ | `patchTransaction` | Updates transaction fields (for debugging/testing) |

### Transaction Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `id` | ✓ | ✅ | - | Unique transaction identifier |
| `kind` | ✓ | ✅ | - | Kind of transaction (deposit, withdrawal, deposit-exchange, withdrawal-exchange) |
| `status` | ✓ | ✅ | - | Current status of the transaction |
| `started_at` | ✓ | ✅ | - | When transaction was created (ISO 8601) |
| `status_eta` |  | ✅ | - | Estimated seconds until status changes |
| `amount_in` |  | ✅ | - | Amount received by anchor |
| `amount_out` |  | ✅ | - | Amount sent by anchor to user |
| `amount_fee` |  | ✅ | - | Total fee charged for transaction |
| `from` |  | ✅ | - | Stellar account that initiated the transaction |
| `to` |  | ✅ | - | Stellar account receiving the transaction |
| `external_transaction_id` |  | ✅ | - | Identifier from external system |
| `stellar_transaction_id` |  | ✅ | - | Hash of the Stellar transaction |
| `message` |  | ✅ | - | Human-readable message about transaction |
| `refunded` |  | ✅ | - | Whether transaction was refunded |
| `refunds` |  | ✅ | - | Refund information if applicable |
| `completed_at` |  | ✅ | - | When transaction completed (ISO 8601) |

### Transaction Status Values

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `incomplete` | ✓ | ✅ | - | Deposit/withdrawal has not yet been submitted |
| `pending_user_transfer_start` | ✓ | ✅ | - | Waiting for user to initiate off-chain transfer |
| `pending_anchor` | ✓ | ✅ | - | Anchor is processing the transaction |
| `completed` | ✓ | ✅ | - | Transaction completed successfully |
| `pending_user_transfer_complete` |  | ✅ | - | Off-chain transfer has been initiated |
| `pending_external` |  | ✅ | - | Waiting for external action (banking system, etc.) |
| `pending_stellar` |  | ✅ | - | Stellar transaction has been submitted |
| `pending_trust` |  | ✅ | - | User needs to add trustline for asset |
| `pending_user` |  | ✅ | - | Waiting for user action (accepting claimable balance) |
| `error` |  | ✅ | - | Transaction failed with error |
| `refunded` |  | ✅ | - | Transaction refunded |
| `expired` |  | ✅ | - | Transaction expired without completion |

### Fee Endpoint

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `fee_endpoint` |  | ✅ | `fee` | Calculates fees for a deposit or withdrawal operation |

## Implementation Gaps

🎉 **No gaps found!** All fields are implemented.

## Recommendations

✅ The SDK has full compatibility with SEP-0006!

## Legend

- ✅ **Implemented**: Field is implemented in SDK
- ❌ **Not Implemented**: Field is missing from SDK
- ✓ **Required**: Field is required by SEP specification
- (blank) **Optional**: Field is optional
