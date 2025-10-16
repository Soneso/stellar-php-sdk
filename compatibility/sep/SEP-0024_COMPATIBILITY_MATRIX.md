# SEP-0024 (Hosted Deposit and Withdrawal) Compatibility Matrix

**Generated:** 2025-10-16 15:08:23

**SEP Version:** N/A
**SEP Status:** Active
**SEP URL:** https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md

## SEP Summary

This SEP defines the standard way for anchors and wallets to interact on behalf
of users. This improves user experience by allowing wallets and other clients
to interact with anchors directly without the user needing to leave the wallet
to go to the anchor's site. It is based on [SEP-0006](sep-0006.md), but only
supports the interactive flow, and cleans up or removes confusing artifacts. If
you are updating from SEP-0006 see the
[changes from SEP-6](#changes-from-SEP-6) at the bottom of this document.

## Overall Coverage

**Total Coverage:** 100% (94/94 fields)

- ✅ **Implemented:** 94/94
- ❌ **Not Implemented:** 0/94

**Required Fields:** 100% (24/24)

**Optional Fields:** 100% (70/70)

## Implementation Status

✅ **Implemented**

### Implementation Files

- `Soneso/StellarSDK/SEP/Interactive/SEP24FeeResponse.php`
- `Soneso/StellarSDK/SEP/Interactive/Refund.php`
- `Soneso/StellarSDK/SEP/Interactive/AnchorTransactionsRequestBuilder.php`
- `Soneso/StellarSDK/SEP/Interactive/Sep24PostRequestBuilder.php`
- `Soneso/StellarSDK/SEP/Interactive/SEP24InfoResponse.php`
- `Soneso/StellarSDK/SEP/Interactive/SEP24TransactionsResponse.php`
- `Soneso/StellarSDK/SEP/Interactive/InfoRequestBuilder.php`
- `Soneso/StellarSDK/SEP/Interactive/SEP24TransactionNotFoundException.php`
- `Soneso/StellarSDK/SEP/Interactive/FeeRequestBuilder.php`
- `Soneso/StellarSDK/SEP/Interactive/AnchorTransactionRequestBuilder.php`
- `Soneso/StellarSDK/SEP/Interactive/InteractiveService.php`
- `Soneso/StellarSDK/SEP/Interactive/FeatureFlags.php`
- `Soneso/StellarSDK/SEP/Interactive/RequestErrorException.php`
- `Soneso/StellarSDK/SEP/Interactive/SEP24WithdrawRequest.php`
- `Soneso/StellarSDK/SEP/Interactive/RefundPayment.php`
- `Soneso/StellarSDK/SEP/Interactive/SEP24InteractiveResponse.php`
- `Soneso/StellarSDK/SEP/Interactive/SEP24DepositAsset.php`
- `Soneso/StellarSDK/SEP/Interactive/SEP24AuthenticationRequiredException.php`
- `Soneso/StellarSDK/SEP/Interactive/SEP24TransactionsRequest.php`
- `Soneso/StellarSDK/SEP/Interactive/SEP24WithdrawAsset.php`
- `Soneso/StellarSDK/SEP/Interactive/FeeEndpointInfo.php`
- `Soneso/StellarSDK/SEP/Interactive/SEP24FeeRequest.php`
- `Soneso/StellarSDK/SEP/Interactive/SEP24TransactionResponse.php`
- `Soneso/StellarSDK/SEP/Interactive/SEP24DepositRequest.php`
- `Soneso/StellarSDK/SEP/Interactive/SEP24TransactionRequest.php`
- `Soneso/StellarSDK/SEP/Interactive/SEP24Transaction.php`

### Key Classes

- **`SEP24FeeResponse`**
- **`Refund`**
- **`AnchorTransactionsRequestBuilder`**
- **`Sep24PostRequestBuilder`**
- **`SEP24InfoResponse`**
- **`SEP24TransactionsResponse`**
- **`InfoRequestBuilder`**
- **`SEP24TransactionNotFoundException`**
- **`FeeRequestBuilder`**
- **`AnchorTransactionRequestBuilder`**
- **`InteractiveService`**
- **`FeatureFlags`**
- **`RequestErrorException`**
- **`SEP24WithdrawRequest`**
- **`RefundPayment`**
- **`SEP24InteractiveResponse`**
- **`SEP24DepositAsset`**
- **`SEP24AuthenticationRequiredException`**
- **`SEP24TransactionsRequest`**
- **`SEP24WithdrawAsset`**
- **`FeeEndpointInfo`**
- **`SEP24FeeRequest`**
- **`SEP24TransactionResponse`**
- **`SEP24DepositRequest`**
- **`SEP24TransactionRequest`**
- **`SEP24Transaction`**

## Coverage by Section

| Section | Coverage | Required Coverage | Implemented | Total |
|---------|----------|-------------------|-------------|-------|
| Info Endpoint | 100% | 100% | 1 | 1 |
| Info Response Fields | 100% | 100% | 4 | 4 |
| Deposit Asset Fields | 100% | 100% | 6 | 6 |
| Withdraw Asset Fields | 100% | 100% | 6 | 6 |
| Fee Endpoint Info Fields | 100% | 100% | 2 | 2 |
| Feature Flags Fields | 100% | 0% | 2 | 2 |
| Fee Endpoint | 100% | 0% | 1 | 1 |
| Interactive Deposit Endpoint | 100% | 100% | 1 | 1 |
| Deposit Request Parameters | 100% | 100% | 12 | 12 |
| Interactive Withdraw Endpoint | 100% | 100% | 1 | 1 |
| Withdraw Request Parameters | 100% | 100% | 11 | 11 |
| Interactive Response Fields | 100% | 100% | 3 | 3 |
| Transaction Endpoints | 100% | 100% | 2 | 2 |
| Transaction Fields | 100% | 100% | 30 | 30 |
| Transaction Status Values | 100% | 100% | 12 | 12 |

## Detailed Field Comparison

### Info Endpoint

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `info_endpoint` | ✓ | ✅ | `info` | GET /info - Provides anchor capabilities and supported assets for interactive deposits/withdrawals |

### Info Response Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `deposit` | ✓ | ✅ | - | Map of asset codes to deposit asset information |
| `withdraw` | ✓ | ✅ | - | Map of asset codes to withdraw asset information |
| `fee` |  | ✅ | - | Fee endpoint information object |
| `features` |  | ✅ | - | Feature flags object |

### Deposit Asset Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `enabled` | ✓ | ✅ | - | Whether deposits are enabled for this asset |
| `min_amount` |  | ✅ | - | Minimum deposit amount |
| `max_amount` |  | ✅ | - | Maximum deposit amount |
| `fee_fixed` |  | ✅ | - | Fixed deposit fee |
| `fee_percent` |  | ✅ | - | Percentage deposit fee |
| `fee_minimum` |  | ✅ | - | Minimum deposit fee |

### Withdraw Asset Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `enabled` | ✓ | ✅ | - | Whether withdrawals are enabled for this asset |
| `min_amount` |  | ✅ | - | Minimum withdrawal amount |
| `max_amount` |  | ✅ | - | Maximum withdrawal amount |
| `fee_fixed` |  | ✅ | - | Fixed withdrawal fee |
| `fee_percent` |  | ✅ | - | Percentage withdrawal fee |
| `fee_minimum` |  | ✅ | - | Minimum withdrawal fee |

### Fee Endpoint Info Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `enabled` | ✓ | ✅ | - | Whether fee endpoint is available |
| `authentication_required` |  | ✅ | - | Whether authentication is required for fee endpoint |

### Feature Flags Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `account_creation` |  | ✅ | - | Whether anchor supports creating accounts |
| `claimable_balances` |  | ✅ | - | Whether anchor supports claimable balances |

### Fee Endpoint

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `fee_endpoint` |  | ✅ | `fee` | GET /fee - Calculates fees for a deposit or withdrawal operation (optional) |

### Interactive Deposit Endpoint

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `interactive_deposit` | ✓ | ✅ | `deposit` | POST /transactions/deposit/interactive - Initiates an interactive deposit transaction |

### Deposit Request Parameters

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `asset_code` | ✓ | ✅ | - | Code of the Stellar asset the user wants to receive |
| `asset_issuer` |  | ✅ | - | Issuer of the Stellar asset (optional if anchor is issuer) |
| `source_asset` |  | ✅ | - | Off-chain asset user wants to deposit (in SEP-38 format) |
| `amount` |  | ✅ | - | Amount of asset to deposit |
| `quote_id` |  | ✅ | - | ID from SEP-38 quote (for asset exchange) |
| `account` |  | ✅ | - | Stellar or muxed account for receiving deposit |
| `memo` |  | ✅ | - | Memo value for transaction identification |
| `memo_type` |  | ✅ | - | Type of memo (text, id, or hash) |
| `wallet_name` |  | ✅ | - | Name of wallet for user communication |
| `wallet_url` |  | ✅ | - | URL to link in transaction notifications |
| `lang` |  | ✅ | - | Language code for UI and messages (RFC 4646) |
| `claimable_balance_supported` |  | ✅ | - | Whether client supports claimable balances |

### Interactive Withdraw Endpoint

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `interactive_withdraw` | ✓ | ✅ | `withdraw` | POST /transactions/withdraw/interactive - Initiates an interactive withdrawal transaction |

### Withdraw Request Parameters

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `asset_code` | ✓ | ✅ | - | Code of the Stellar asset user wants to send |
| `asset_issuer` |  | ✅ | - | Issuer of the Stellar asset (optional if anchor is issuer) |
| `destination_asset` |  | ✅ | - | Off-chain asset user wants to receive (in SEP-38 format) |
| `amount` |  | ✅ | - | Amount of asset to withdraw |
| `quote_id` |  | ✅ | - | ID from SEP-38 quote (for asset exchange) |
| `account` |  | ✅ | - | Stellar or muxed account that will send the withdrawal |
| `memo` |  | ✅ | - | Memo for identifying the withdrawal transaction |
| `memo_type` |  | ✅ | - | Type of memo (text, id, or hash) |
| `wallet_name` |  | ✅ | - | Name of wallet for user communication |
| `wallet_url` |  | ✅ | - | URL to link in transaction notifications |
| `lang` |  | ✅ | - | Language code for UI and messages (RFC 4646) |

### Interactive Response Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `id` | ✓ | ✅ | - | Unique transaction identifier |
| `url` | ✓ | ✅ | - | URL for interactive flow popup/iframe |
| `type` | ✓ | ✅ | - | Always "interactive_customer_info_needed" for SEP-24 |

### Transaction Endpoints

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `transactions` | ✓ | ✅ | `transactions` | GET /transactions - Retrieves transaction history for authenticated account |
| `transaction` | ✓ | ✅ | `transaction` | GET /transaction - Retrieves details for a single transaction |

### Transaction Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `id` | ✓ | ✅ | - | Unique transaction identifier |
| `kind` | ✓ | ✅ | - | Kind of transaction (deposit or withdrawal) |
| `status` | ✓ | ✅ | - | Current status of the transaction |
| `more_info_url` | ✓ | ✅ | - | URL with additional transaction information |
| `started_at` | ✓ | ✅ | - | When transaction was created (ISO 8601) |
| `status_eta` |  | ✅ | - | Estimated seconds until status changes |
| `kyc_verified` |  | ✅ | - | Whether KYC has been verified for this transaction |
| `amount_in` |  | ✅ | - | Amount received by anchor |
| `amount_in_asset` |  | ✅ | - | Asset received by anchor (SEP-38 format) |
| `amount_out` |  | ✅ | - | Amount sent by anchor to user |
| `amount_out_asset` |  | ✅ | - | Asset delivered to user (SEP-38 format) |
| `amount_fee` |  | ✅ | - | Total fee charged for transaction |
| `amount_fee_asset` |  | ✅ | - | Asset in which fees are calculated (SEP-38 format) |
| `quote_id` |  | ✅ | - | ID of SEP-38 quote used for this transaction |
| `completed_at` |  | ✅ | - | When transaction completed (ISO 8601) |
| `updated_at` |  | ✅ | - | When transaction status last changed (ISO 8601) |
| `user_action_required_by` |  | ✅ | - | Deadline for user action (ISO 8601) |
| `stellar_transaction_id` |  | ✅ | - | Hash of the Stellar transaction |
| `external_transaction_id` |  | ✅ | - | Identifier from external system |
| `message` |  | ✅ | - | Human-readable message about transaction |
| `refunded` |  | ✅ | - | Whether transaction was refunded (deprecated) |
| `refunds` |  | ✅ | - | Refund information object |
| `from` |  | ✅ | - | Source address (Stellar for withdrawals, external for deposits) |
| `to` |  | ✅ | - | Destination address (Stellar for deposits, external for withdrawals) |
| `deposit_memo` |  | ✅ | - | Memo for deposit to Stellar address |
| `deposit_memo_type` |  | ✅ | - | Type of deposit memo |
| `claimable_balance_id` |  | ✅ | - | ID of claimable balance for deposit |
| `withdraw_anchor_account` |  | ✅ | - | Anchor's Stellar account for withdrawal payment |
| `withdraw_memo` |  | ✅ | - | Memo for withdrawal to anchor account |
| `withdraw_memo_type` |  | ✅ | - | Type of withdraw memo |

### Transaction Status Values

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `incomplete` | ✓ | ✅ | - | Customer information still being collected via interactive flow |
| `pending_user_transfer_start` | ✓ | ✅ | - | Waiting for user to send funds (deposits) |
| `pending_anchor` | ✓ | ✅ | - | Anchor processing the transaction |
| `completed` | ✓ | ✅ | - | Transaction completed successfully |
| `pending_user_transfer_complete` |  | ✅ | - | User transfer detected, awaiting confirmations |
| `pending_external` |  | ✅ | - | Transaction being processed by external system |
| `pending_stellar` |  | ✅ | - | Transaction submitted to Stellar network |
| `pending_trust` |  | ✅ | - | User needs to establish trustline |
| `pending_user` |  | ✅ | - | Waiting for user action (e.g., accepting claimable balance) |
| `error` |  | ✅ | - | Transaction encountered an error |
| `refunded` |  | ✅ | - | Transaction refunded |
| `expired` |  | ✅ | - | Transaction expired before completion |

## Implementation Gaps

🎉 **No gaps found!** All fields are implemented.

## Recommendations

✅ The SDK has full compatibility with SEP-0024!

## Legend

- ✅ **Implemented**: Field is implemented in SDK
- ❌ **Not Implemented**: Field is missing from SDK
- ✓ **Required**: Field is required by SEP specification
- (blank) **Optional**: Field is optional
