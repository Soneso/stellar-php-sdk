# SEP-31 (Cross-Border Payments API) Compatibility Matrix

**Generated:** 2026-02-10 13:01:00

**SEP Version:** 3.1.0

**SEP Status:** Active

**SDK Version:** 1.9.3

**SEP URL:** https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-31.md

## SEP Summary

This SEP defines a protocol for enabling payments between two financial
accounts that exist outside the Stellar network.

## Overall Coverage

**Total Coverage:** 100% (68/68 fields)

- ✅ **Implemented:** 68/68
- ❌ **Not Implemented:** 0/68

**Required Fields:** 100% (16/16)

**Optional Fields:** 100% (52/52)

## Implementation Status

✅ **Implemented**

### Implementation Files

- `Soneso/StellarSDK/SEP/CrossBorderPayments/SEP31FeeDetailsDetails.php`
- `Soneso/StellarSDK/SEP/CrossBorderPayments/SEP31TransactionResponse.php`
- `Soneso/StellarSDK/SEP/CrossBorderPayments/SEP12TypesInfo.php`
- `Soneso/StellarSDK/SEP/CrossBorderPayments/SEP31BadRequestException.php`
- `Soneso/StellarSDK/SEP/CrossBorderPayments/CrossBorderPaymentsService.php`
- `Soneso/StellarSDK/SEP/CrossBorderPayments/SEP31UnknownResponseException.php`
- `Soneso/StellarSDK/SEP/CrossBorderPayments/SEP31ReceiveAssetInfo.php`
- `Soneso/StellarSDK/SEP/CrossBorderPayments/SEP31TransactionCallbackNotSupportedException.php`
- `Soneso/StellarSDK/SEP/CrossBorderPayments/SEP31CustomerInfoNeededException.php`
- `Soneso/StellarSDK/SEP/CrossBorderPayments/SEP31FeeDetails.php`
- `Soneso/StellarSDK/SEP/CrossBorderPayments/SEP31PostTransactionsRequest.php`
- `Soneso/StellarSDK/SEP/CrossBorderPayments/SEP31InfoResponse.php`
- `Soneso/StellarSDK/SEP/CrossBorderPayments/SEP31TransactionInfoNeededException.php`
- `Soneso/StellarSDK/SEP/CrossBorderPayments/SEP31Refunds.php`
- `Soneso/StellarSDK/SEP/CrossBorderPayments/SEP31RefundPayment.php`
- `Soneso/StellarSDK/SEP/CrossBorderPayments/SEP31TransactionNotFoundException.php`
- `Soneso/StellarSDK/SEP/CrossBorderPayments/SEP31PostTransactionsResponse.php`

### Key Classes

- **`SEP31FeeDetailsDetails`**
- **`SEP31TransactionResponse`**
- **`SEP12TypesInfo`**
- **`SEP31BadRequestException`**
- **`CrossBorderPaymentsService`**
- **`SEP31UnknownResponseException`**
- **`SEP31ReceiveAssetInfo`**
- **`SEP31TransactionCallbackNotSupportedException`**
- **`SEP31CustomerInfoNeededException`**
- **`SEP31FeeDetails`**
- **`SEP31PostTransactionsRequest`**
- **`SEP31InfoResponse`**
- **`SEP31TransactionInfoNeededException`**
- **`SEP31Refunds`**
- **`SEP31RefundPayment`**
- **`SEP31TransactionNotFoundException`**
- **`SEP31PostTransactionsResponse`**

## Coverage by Section

| Section | Coverage | Required Coverage | Implemented | Total |
|---------|----------|-------------------|-------------|-------|
| Info Endpoint | 100% | 100% | 1 | 1 |
| Info Request Parameters | 100% | 0% | 1 | 1 |
| Info Response Fields | 100% | 100% | 12 | 12 |
| Post Transactions Endpoint | 100% | 100% | 1 | 1 |
| Post Transactions Request Parameters | 100% | 100% | 12 | 12 |
| Post Transactions Response Fields | 100% | 100% | 4 | 4 |
| Get Transaction Endpoint | 100% | 100% | 1 | 1 |
| Transaction Fields | 100% | 100% | 24 | 24 |
| Transaction Status Values | 100% | 100% | 10 | 10 |
| Callback Endpoint | 100% | 0% | 1 | 1 |
| Patch Transaction Endpoint | 100% | 0% | 1 | 1 |

## Detailed Field Comparison

### Info Endpoint

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `info_endpoint` | ✓ | ✅ | `info` | GET /info - Provides information about supported receiving assets, fees, and KYC requirements |

### Info Request Parameters

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `lang` |  | ✅ | - | Language code (ISO 639-1) for human-readable error codes and field descriptions. Defaults to en. |

### Info Response Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `receive` | ✓ | ✅ | - | Object containing entries for each supported Stellar asset |
| `sep12` | ✓ | ✅ | - | Object containing sender and receiver SEP-12 type definitions |
| `min_amount` |  | ✅ | - | Minimum amount (no limit if not specified) |
| `max_amount` |  | ✅ | - | Maximum amount (no limit if not specified) |
| `fee_fixed` |  | ✅ | - | Fixed fee in units of the Stellar asset |
| `fee_percent` |  | ✅ | - | Percentage fee in percentage points |
| `sender_sep12_type` |  | ✅ | - | (deprecated) Value of type parameter for sender SEP-12 GET /customer request |
| `receiver_sep12_type` |  | ✅ | - | (deprecated) Value of type parameter for receiver SEP-12 GET /customer request |
| `fields` |  | ✅ | - | (deprecated) Per-transaction parameters required in POST /transactions requests |
| `quotes_supported` |  | ✅ | - | Whether the anchor can deliver off-chain assets via SEP-38 quote exchange |
| `quotes_required` |  | ✅ | - | Whether the anchor requires a SEP-38 quote for receiving this asset |
| `funding_methods` |  | ✅ | - | Array of methods the anchor supports for receiving funds |

### Post Transactions Endpoint

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `post_transactions` | ✓ | ✅ | `postTransactions` | POST /transactions - Initiates a cross-border payment transaction |

### Post Transactions Request Parameters

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `amount` | ✓ | ✅ | - | Amount of the Stellar asset to send to the Receiving Anchor |
| `asset_code` | ✓ | ✅ | - | Code of the asset the Sending Anchor intends to send |
| `asset_issuer` |  | ✅ | - | Issuer of the Stellar asset the Sending Anchor intends to send |
| `destination_asset` |  | ✅ | - | Off-chain asset the Receiving Anchor will deliver (SEP-38 format) |
| `quote_id` |  | ✅ | - | ID returned from a SEP-38 POST /quote response |
| `sender_id` |  | ✅ | - | SEP-12 customer ID for the Sending Client |
| `receiver_id` |  | ✅ | - | SEP-12 customer ID for the Receiving Client |
| `fields` |  | ✅ | - | (deprecated) Object containing values requested by the Receiving Anchor in GET /info |
| `lang` |  | ✅ | - | Language code (ISO 639-1) for human-readable error codes and field descriptions |
| `refund_memo` |  | ✅ | - | Memo the Receiving Anchor must use when sending refund payments back |
| `refund_memo_type` |  | ✅ | - | Type of the refund memo (id, text, or hash) |
| `funding_method` |  | ✅ | - | Method for transferring/settling assets, must match /info response |

### Post Transactions Response Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `id` | ✓ | ✅ | - | Persistent identifier to check the status of this payment transaction |
| `stellar_account_id` |  | ✅ | - | Stellar account to send payment to |
| `stellar_memo_type` |  | ✅ | - | Type of memo to attach to the Stellar payment (text, hash, or id) |
| `stellar_memo` |  | ✅ | - | Memo to attach to the Stellar payment |

### Get Transaction Endpoint

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `get_transaction` | ✓ | ✅ | `getTransaction` | GET /transactions/:id - Retrieves information on a specific transaction |

### Transaction Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `id` | ✓ | ✅ | - | The ID returned from the POST /transactions request |
| `status` | ✓ | ✅ | - | The status of the transaction |
| `status_eta` |  | ✅ | - | Estimated number of seconds until a status change is expected |
| `status_message` |  | ✅ | - | Human-readable message describing the status of the transaction |
| `amount_in` |  | ✅ | - | Amount of the Stellar asset received or to be received by the Receiving Anchor |
| `amount_in_asset` |  | ✅ | - | Asset received or to be received (SEP-38 Asset Identification Format) |
| `amount_out` |  | ✅ | - | Amount sent or to be sent by the Receiving Anchor to the Receiving Client |
| `amount_out_asset` |  | ✅ | - | Asset delivered to the Receiving Client (SEP-38 Asset Identification Format) |
| `amount_fee` |  | ✅ | - | (deprecated) Amount of fee charged by the Receiving Anchor |
| `amount_fee_asset` |  | ✅ | - | (deprecated) Asset in which fees are calculated (SEP-38 format) |
| `fee_details` |  | ✅ | - | Detailed fee breakdown object with total, asset, and optional details array |
| `quote_id` |  | ✅ | - | ID of the quote used to create this transaction |
| `stellar_account_id` |  | ✅ | - | Receiving Anchor Stellar account for payment |
| `stellar_memo_type` |  | ✅ | - | Type of memo to attach to the Stellar payment |
| `stellar_memo` |  | ✅ | - | Memo to attach to the Stellar payment |
| `started_at` |  | ✅ | - | Start date and time of transaction (UTC ISO 8601) |
| `updated_at` |  | ✅ | - | Date and time of transaction reaching the current status (UTC ISO 8601) |
| `completed_at` |  | ✅ | - | Completion date and time of transaction (UTC ISO 8601) |
| `stellar_transaction_id` |  | ✅ | - | Stellar network transaction hash of the transfer that initiated the payment |
| `external_transaction_id` |  | ✅ | - | ID of the transaction on external network that completes the payment |
| `refunded` |  | ✅ | - | (deprecated) Whether the transaction was refunded in full |
| `refunds` |  | ✅ | - | Object describing on-chain refund details (amount_refunded, amount_fee, payments) |
| `required_info_message` |  | ✅ | - | Human-readable message indicating errors that require updated information |
| `required_info_updates` |  | ✅ | - | Fields that require update values from the Sending Anchor |

### Transaction Status Values

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `pending_sender` | ✓ | ✅ | - | Awaiting payment from Sending Anchor via Stellar network |
| `pending_stellar` | ✓ | ✅ | - | Transaction submitted to Stellar network but not yet confirmed |
| `pending_customer_info_update` | ✓ | ✅ | - | KYC information needs updating via SEP-12 |
| `pending_transaction_info_update` |  | ✅ | - | Transaction fields need updating (deprecated, use SEP-12) |
| `pending_receiver` | ✓ | ✅ | - | Payment being processed by Receiving Anchor |
| `pending_external` | ✓ | ✅ | - | Payment submitted to external network but not yet confirmed |
| `completed` | ✓ | ✅ | - | Funds successfully delivered to the Receiving Client |
| `refunded` |  | ✅ | - | Funds refunded to Sending Anchor (see refunds object) |
| `expired` |  | ✅ | - | Transaction abandoned by Sending Anchor or quote expired |
| `error` |  | ✅ | - | Catch-all for unspecified errors (check status_message for details) |

### Callback Endpoint

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `put_callback` |  | ✅ | `putTransactionCallback` | PUT /transactions/:id/callback - Registers callback URL for status change notifications |

### Patch Transaction Endpoint

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `patch_transaction` |  | ✅ | `patchTransaction` | PATCH /transactions/:id - (deprecated) Updates transaction fields for pending_transaction_info_updat... |

## Implementation Gaps

🎉 **No gaps found!** All fields are implemented.

## Recommendations

✅ The SDK has full compatibility with SEP-31!

## Legend

- ✅ **Implemented**: Field is implemented in SDK
- ❌ **Not Implemented**: Field is missing from SDK
- ✓ **Required**: Field is required by SEP specification
- (blank) **Optional**: Field is optional
