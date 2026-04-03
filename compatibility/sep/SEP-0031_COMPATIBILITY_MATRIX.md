# SEP-31: Cross-Border Payments API

**Status:** ✅ Supported  
**SDK Version:** 1.9.6  
**Generated:** 2026-04-03 21:56 UTC  
**Spec:** [https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0031.md](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0031.md)

## Overall Coverage

**Total Coverage:** 100.0% (71/71 fields)

- ✅ **Implemented:** 71/71
- ❌ **Not Implemented:** 0/71

## Coverage by Section

| Section | Coverage | Implemented | Total |
|---------|----------|-------------|-------|
| Service Endpoints | 100.0% | 5 | 5 |
| Info Response Fields | 100.0% | 1 | 1 |
| Receive Asset Info Fields | 100.0% | 11 | 11 |
| SEP-12 Types Info Fields | 100.0% | 2 | 2 |
| POST /transactions Request Fields | 100.0% | 12 | 12 |
| POST /transactions Response Fields | 100.0% | 4 | 4 |
| Transaction Response Fields | 100.0% | 24 | 24 |
| Refunds Fields | 100.0% | 3 | 3 |
| Refund Payment Fields | 100.0% | 3 | 3 |
| Fee Details Fields | 100.0% | 3 | 3 |
| Fee Details Breakdown Fields | 100.0% | 3 | 3 |

## Service Endpoints

CrossBorderPaymentsService API methods

| Feature | Status | Notes |
|---------|--------|-------|
| `GET /info` | ✅ Supported | `CrossBorderPaymentsService.info()` |
| `POST /transactions` | ✅ Supported | `CrossBorderPaymentsService.postTransactions()` |
| `GET /transactions/:id` | ✅ Supported | `CrossBorderPaymentsService.getTransaction()` |
| `PATCH /transactions/:id` | ✅ Supported | `CrossBorderPaymentsService.patchTransaction()` |
| `PUT /transactions/:id/callback` | ✅ Supported | `CrossBorderPaymentsService.putTransactionCallback()` |

## Info Response Fields

SEP31InfoResponse properties

| Feature | Status | Notes |
|---------|--------|-------|
| `receive` | ✅ Supported | `Required. SEP31InfoResponse.$receiveAssets` |

## Receive Asset Info Fields

SEP31ReceiveAssetInfo properties

| Feature | Status | Notes |
|---------|--------|-------|
| `sep12` | ✅ Supported | `SEP31ReceiveAssetInfo.$sep12Info` |
| `min_amount` | ✅ Supported | `SEP31ReceiveAssetInfo.$minAmount` |
| `max_amount` | ✅ Supported | `SEP31ReceiveAssetInfo.$maxAmount` |
| `fee_fixed` | ✅ Supported | `SEP31ReceiveAssetInfo.$feeFixed` |
| `fee_percent` | ✅ Supported | `SEP31ReceiveAssetInfo.$feePercent` |
| `sender_sep12_type` | ✅ Supported | `SEP31ReceiveAssetInfo.$senderSep12Type` |
| `receiver_sep12_type` | ✅ Supported | `SEP31ReceiveAssetInfo.$receiverSep12Type` |
| `fields` | ✅ Supported | `SEP31ReceiveAssetInfo.$fields` |
| `quotes_supported` | ✅ Supported | `SEP31ReceiveAssetInfo.$quotesSupported` |
| `quotes_required` | ✅ Supported | `SEP31ReceiveAssetInfo.$quotesRequired` |
| `funding_methods` | ✅ Supported | `SEP31ReceiveAssetInfo.$fundingMethods` |

## SEP-12 Types Info Fields

SEP12TypesInfo properties

| Feature | Status | Notes |
|---------|--------|-------|
| `sender` | ✅ Supported | `Required. SEP12TypesInfo.$senderTypes` |
| `receiver` | ✅ Supported | `Required. SEP12TypesInfo.$receiverTypes` |

## POST /transactions Request Fields

SEP31PostTransactionsRequest properties

| Feature | Status | Notes |
|---------|--------|-------|
| `amount` | ✅ Supported | `Required. SEP31PostTransactionsRequest.$amount` |
| `asset_code` | ✅ Supported | `Required. SEP31PostTransactionsRequest.$assetCode` |
| `asset_issuer` | ✅ Supported | `SEP31PostTransactionsRequest.$assetIssuer` |
| `destination_asset` | ✅ Supported | `SEP31PostTransactionsRequest.$destinationAsset` |
| `quote_id` | ✅ Supported | `SEP31PostTransactionsRequest.$quoteId` |
| `sender_id` | ✅ Supported | `SEP31PostTransactionsRequest.$senderId` |
| `receiver_id` | ✅ Supported | `SEP31PostTransactionsRequest.$receiverId` |
| `fields` | ✅ Supported | `SEP31PostTransactionsRequest.$fields` |
| `lang` | ✅ Supported | `SEP31PostTransactionsRequest.$lang` |
| `refund_memo` | ✅ Supported | `SEP31PostTransactionsRequest.$refundMemo` |
| `refund_memo_type` | ✅ Supported | `SEP31PostTransactionsRequest.$refundMemoType` |
| `funding_method` | ✅ Supported | `SEP31PostTransactionsRequest.$fundingMethod` |

## POST /transactions Response Fields

SEP31PostTransactionsResponse properties

| Feature | Status | Notes |
|---------|--------|-------|
| `id` | ✅ Supported | `Required. SEP31PostTransactionsResponse.$id` |
| `stellar_account_id` | ✅ Supported | `SEP31PostTransactionsResponse.$stellarAccountId` |
| `stellar_memo_type` | ✅ Supported | `SEP31PostTransactionsResponse.$stellarMemoType` |
| `stellar_memo` | ✅ Supported | `SEP31PostTransactionsResponse.$stellarMemo` |

## Transaction Response Fields

SEP31TransactionResponse properties

| Feature | Status | Notes |
|---------|--------|-------|
| `id` | ✅ Supported | `Required. SEP31TransactionResponse.$id` |
| `status` | ✅ Supported | `Required. SEP31TransactionResponse.$status` |
| `status_eta` | ✅ Supported | `SEP31TransactionResponse.$statusEta` |
| `status_message` | ✅ Supported | `SEP31TransactionResponse.$statusMessage` |
| `amount_in` | ✅ Supported | `SEP31TransactionResponse.$amountIn` |
| `amount_in_asset` | ✅ Supported | `SEP31TransactionResponse.$amountInAsset` |
| `amount_out` | ✅ Supported | `SEP31TransactionResponse.$amountOut` |
| `amount_out_asset` | ✅ Supported | `SEP31TransactionResponse.$amountOutAsset` |
| `amount_fee` | ✅ Supported | `SEP31TransactionResponse.$amountFee` |
| `amount_fee_asset` | ✅ Supported | `SEP31TransactionResponse.$amountFeeAsset` |
| `fee_details` | ✅ Supported | `SEP31TransactionResponse.$feeDetails` |
| `quote_id` | ✅ Supported | `SEP31TransactionResponse.$quoteId` |
| `stellar_account_id` | ✅ Supported | `SEP31TransactionResponse.$stellarAccountId` |
| `stellar_memo_type` | ✅ Supported | `SEP31TransactionResponse.$stellarMemoType` |
| `stellar_memo` | ✅ Supported | `SEP31TransactionResponse.$stellarMemo` |
| `started_at` | ✅ Supported | `SEP31TransactionResponse.$startedAt` |
| `updated_at` | ✅ Supported | `SEP31TransactionResponse.$updatedAt` |
| `completed_at` | ✅ Supported | `SEP31TransactionResponse.$completedAt` |
| `stellar_transaction_id` | ✅ Supported | `SEP31TransactionResponse.$stellarTransactionId` |
| `external_transaction_id` | ✅ Supported | `SEP31TransactionResponse.$externalTransactionId` |
| `refunded` | ✅ Supported | `SEP31TransactionResponse.$refunded` |
| `refunds` | ✅ Supported | `SEP31TransactionResponse.$refunds` |
| `required_info_message` | ✅ Supported | `SEP31TransactionResponse.$requiredInfoMessage` |
| `required_info_updates` | ✅ Supported | `SEP31TransactionResponse.$requiredInfoUpdates` |

## Refunds Fields

SEP31Refunds properties

| Feature | Status | Notes |
|---------|--------|-------|
| `amount_refunded` | ✅ Supported | `Required. SEP31Refunds.$amountRefunded` |
| `amount_fee` | ✅ Supported | `Required. SEP31Refunds.$amountFee` |
| `payments` | ✅ Supported | `Required. SEP31Refunds.$payments` |

## Refund Payment Fields

SEP31RefundPayment properties

| Feature | Status | Notes |
|---------|--------|-------|
| `id` | ✅ Supported | `Required. SEP31RefundPayment.$id` |
| `amount` | ✅ Supported | `Required. SEP31RefundPayment.$amount` |
| `fee` | ✅ Supported | `Required. SEP31RefundPayment.$fee` |

## Fee Details Fields

SEP31FeeDetails properties

| Feature | Status | Notes |
|---------|--------|-------|
| `total` | ✅ Supported | `Required. SEP31FeeDetails.$total` |
| `asset` | ✅ Supported | `Required. SEP31FeeDetails.$asset` |
| `details` | ✅ Supported | `SEP31FeeDetails.$details` |

## Fee Details Breakdown Fields

SEP31FeeDetailsDetails properties

| Feature | Status | Notes |
|---------|--------|-------|
| `name` | ✅ Supported | `Required. SEP31FeeDetailsDetails.$name` |
| `amount` | ✅ Supported | `Required. SEP31FeeDetailsDetails.$amount` |
| `description` | ✅ Supported | `SEP31FeeDetailsDetails.$description` |
