# SEP-24: Hosted Deposit and Withdrawal

**Status:** ✅ Supported  
**SDK Version:** 1.9.6  
**Generated:** 2026-04-03 21:56 UTC  
**Spec:** [https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md)

## Overall Coverage

**Total Coverage:** 100.0% (82/82 fields)

- ✅ **Implemented:** 82/82
- ❌ **Not Implemented:** 0/82

## Coverage by Section

| Section | Coverage | Implemented | Total |
|---------|----------|-------------|-------|
| Service Endpoints | 100.0% | 6 | 6 |
| Deposit Request Parameters | 100.0% | 16 | 16 |
| Withdraw Request Parameters | 100.0% | 17 | 17 |
| Interactive Response Fields | 100.0% | 3 | 3 |
| Info Response Fields | 100.0% | 4 | 4 |
| Transactions Request Parameters | 100.0% | 6 | 6 |
| Transaction Fields | 100.0% | 30 | 30 |

## Service Endpoints

InteractiveService API methods

| Feature | Status | Notes |
|---------|--------|-------|
| `GET /info` | ✅ Supported | `InteractiveService.info()` |
| `POST /transactions/deposit/interactive` | ✅ Supported | `InteractiveService.deposit()` |
| `POST /transactions/withdraw/interactive` | ✅ Supported | `InteractiveService.withdraw()` |
| `GET /transaction` | ✅ Supported | `InteractiveService.transaction()` |
| `GET /transactions` | ✅ Supported | `InteractiveService.transactions()` |
| `GET /fee` | ✅ Supported | `InteractiveService.fee()` |

## Deposit Request Parameters

Parameters for POST /transactions/deposit/interactive

| Feature | Status | Notes |
|---------|--------|-------|
| `asset_code` | ✅ Supported | `Required. SEP24DepositRequest.$assetCode` |
| `asset_issuer` | ✅ Supported | `SEP24DepositRequest.$assetIssuer` |
| `source_asset` | ✅ Supported | `SEP24DepositRequest.$sourceAsset` |
| `amount` | ✅ Supported | `SEP24DepositRequest.$amount` |
| `quote_id` | ✅ Supported | `SEP24DepositRequest.$quoteId` |
| `account` | ✅ Supported | `SEP24DepositRequest.$account` |
| `memo` | ✅ Supported | `SEP24DepositRequest.$memo` |
| `memo_type` | ✅ Supported | `SEP24DepositRequest.$memoType` |
| `wallet_name` | ✅ Supported | `SEP24DepositRequest.$walletName` |
| `wallet_url` | ✅ Supported | `SEP24DepositRequest.$walletUrl` |
| `lang` | ✅ Supported | `SEP24DepositRequest.$lang` |
| `claimable_balance_supported` | ✅ Supported | `SEP24DepositRequest.$claimableBalanceSupported` |
| `customer_id` | ✅ Supported | `SEP24DepositRequest.$customerId` |
| `kyc_fields` | ✅ Supported | `SEP24DepositRequest.$kycFields` |
| `custom_fields` | ✅ Supported | `SEP24DepositRequest.$customFields` |
| `custom_files` | ✅ Supported | `SEP24DepositRequest.$customFiles` |

## Withdraw Request Parameters

Parameters for POST /transactions/withdraw/interactive

| Feature | Status | Notes |
|---------|--------|-------|
| `asset_code` | ✅ Supported | `Required. SEP24WithdrawRequest.$assetCode` |
| `asset_issuer` | ✅ Supported | `SEP24WithdrawRequest.$assetIssuer` |
| `destination_asset` | ✅ Supported | `SEP24WithdrawRequest.$destinationAsset` |
| `amount` | ✅ Supported | `SEP24WithdrawRequest.$amount` |
| `quote_id` | ✅ Supported | `SEP24WithdrawRequest.$quoteId` |
| `account` | ✅ Supported | `SEP24WithdrawRequest.$account` |
| `memo` | ✅ Supported | `SEP24WithdrawRequest.$memo` |
| `memo_type` | ✅ Supported | `SEP24WithdrawRequest.$memoType` |
| `wallet_name` | ✅ Supported | `SEP24WithdrawRequest.$walletName` |
| `wallet_url` | ✅ Supported | `SEP24WithdrawRequest.$walletUrl` |
| `lang` | ✅ Supported | `SEP24WithdrawRequest.$lang` |
| `refund_memo` | ✅ Supported | `SEP24WithdrawRequest.$refundMemo` |
| `refund_memo_type` | ✅ Supported | `SEP24WithdrawRequest.$refundMemoType` |
| `customer_id` | ✅ Supported | `SEP24WithdrawRequest.$customerId` |
| `kyc_fields` | ✅ Supported | `SEP24WithdrawRequest.$kycFields` |
| `custom_fields` | ✅ Supported | `SEP24WithdrawRequest.$customFields` |
| `custom_files` | ✅ Supported | `SEP24WithdrawRequest.$customFiles` |

## Interactive Response Fields

Fields returned by POST deposit/withdraw

| Feature | Status | Notes |
|---------|--------|-------|
| `type` | ✅ Supported | `Required. SEP24InteractiveResponse.$type` |
| `url` | ✅ Supported | `Required. SEP24InteractiveResponse.$url` |
| `id` | ✅ Supported | `Required. SEP24InteractiveResponse.$id` |

## Info Response Fields

Fields returned by GET /info

| Feature | Status | Notes |
|---------|--------|-------|
| `deposit` | ✅ Supported | `SEP24InfoResponse.$depositAssets` |
| `withdraw` | ✅ Supported | `SEP24InfoResponse.$withdrawAssets` |
| `fee` | ✅ Supported | `SEP24InfoResponse.$feeEndpointInfo` |
| `features` | ✅ Supported | `SEP24InfoResponse.$featureFlags` |

## Transactions Request Parameters

Parameters for GET /transactions

| Feature | Status | Notes |
|---------|--------|-------|
| `asset_code` | ✅ Supported | `Required. SEP24TransactionsRequest.$assetCode` |
| `no_older_than` | ✅ Supported | `SEP24TransactionsRequest.$noOlderThan` |
| `limit` | ✅ Supported | `SEP24TransactionsRequest.$limit` |
| `kind` | ✅ Supported | `SEP24TransactionsRequest.$kind` |
| `paging_id` | ✅ Supported | `SEP24TransactionsRequest.$pagingId` |
| `lang` | ✅ Supported | `SEP24TransactionsRequest.$lang` |

## Transaction Fields

Fields in the SEP-24 transaction object

| Feature | Status | Notes |
|---------|--------|-------|
| `id` | ✅ Supported | `Required. SEP24Transaction.$id` |
| `kind` | ✅ Supported | `Required. SEP24Transaction.$kind` |
| `status` | ✅ Supported | `Required. SEP24Transaction.$status` |
| `status_eta` | ✅ Supported | `SEP24Transaction.$statusEta` |
| `kyc_verified` | ✅ Supported | `SEP24Transaction.$kycVerified` |
| `more_info_url` | ✅ Supported | `SEP24Transaction.$moreInfoUrl` |
| `amount_in` | ✅ Supported | `SEP24Transaction.$amountIn` |
| `amount_in_asset` | ✅ Supported | `SEP24Transaction.$amountInAsset` |
| `amount_out` | ✅ Supported | `SEP24Transaction.$amountOut` |
| `amount_out_asset` | ✅ Supported | `SEP24Transaction.$amountOutAsset` |
| `amount_fee` | ✅ Supported | `SEP24Transaction.$amountFee` |
| `amount_fee_asset` | ✅ Supported | `SEP24Transaction.$amountFeeAsset` |
| `quote_id` | ✅ Supported | `SEP24Transaction.$quoteId` |
| `started_at` | ✅ Supported | `Required. SEP24Transaction.$startedAt` |
| `completed_at` | ✅ Supported | `SEP24Transaction.$completedAt` |
| `updated_at` | ✅ Supported | `SEP24Transaction.$updatedAt` |
| `user_action_required_by` | ✅ Supported | `SEP24Transaction.$userActionRequiredBy` |
| `stellar_transaction_id` | ✅ Supported | `SEP24Transaction.$stellarTransactionId` |
| `external_transaction_id` | ✅ Supported | `SEP24Transaction.$externalTransactionId` |
| `message` | ✅ Supported | `SEP24Transaction.$message` |
| `refunded` | ✅ Supported | `SEP24Transaction.$refunded` |
| `refunds` | ✅ Supported | `SEP24Transaction.$refunds` |
| `from` | ✅ Supported | `SEP24Transaction.$from` |
| `to` | ✅ Supported | `SEP24Transaction.$to` |
| `deposit_memo` | ✅ Supported | `SEP24Transaction.$depositMemo` |
| `deposit_memo_type` | ✅ Supported | `SEP24Transaction.$depositMemoType` |
| `claimable_balance_id` | ✅ Supported | `SEP24Transaction.$claimableBalanceId` |
| `withdraw_anchor_account` | ✅ Supported | `SEP24Transaction.$withdrawAnchorAccount` |
| `withdraw_memo` | ✅ Supported | `SEP24Transaction.$withdrawMemo` |
| `withdraw_memo_type` | ✅ Supported | `SEP24Transaction.$withdrawMemoType` |
