# SEP-06: Deposit and Withdrawal API

**Status:** ✅ Supported  
**SDK Version:** 1.9.6  
**Generated:** 2026-04-03 21:56 UTC  
**Spec:** [https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md)

## Overall Coverage

**Total Coverage:** 100.0% (100/100 fields)

- ✅ **Implemented:** 100/100
- ❌ **Not Implemented:** 0/100

## Coverage by Section

| Section | Coverage | Implemented | Total |
|---------|----------|-------------|-------|
| Deposit Endpoints | 100.0% | 2 | 2 |
| Deposit Request Parameters | 100.0% | 15 | 15 |
| Deposit Response Fields | 100.0% | 8 | 8 |
| Withdraw Endpoints | 100.0% | 2 | 2 |
| Withdraw Request Parameters | 100.0% | 17 | 17 |
| Withdraw Response Fields | 100.0% | 10 | 10 |
| Info Endpoint | 100.0% | 1 | 1 |
| Info Response Fields | 100.0% | 8 | 8 |
| Fee Endpoint | 100.0% | 1 | 1 |
| Transaction Endpoints | 100.0% | 3 | 3 |
| Transaction Fields | 100.0% | 33 | 33 |

## Deposit Endpoints

Deposit API endpoints

| Feature | Status | Notes |
|---------|--------|-------|
| `deposit` | ✅ Supported | `TransferServerService.deposit()` |
| `deposit_exchange` | ✅ Supported | `TransferServerService.depositExchange()` |

## Deposit Request Parameters

Parameters for GET /deposit

| Feature | Status | Notes |
|---------|--------|-------|
| `asset_code` | ✅ Supported | `Required. DepositRequest.$assetCode` |
| `account` | ✅ Supported | `Required. DepositRequest.$account` |
| `memo_type` | ✅ Supported | `DepositRequest.$memoType` |
| `memo` | ✅ Supported | `DepositRequest.$memo` |
| `email_address` | ✅ Supported | `DepositRequest.$emailAddress` |
| `type` | ✅ Supported | `DepositRequest.$type` |
| `wallet_name` | ✅ Supported | `DepositRequest.$walletName` |
| `wallet_url` | ✅ Supported | `DepositRequest.$walletUrl` |
| `lang` | ✅ Supported | `DepositRequest.$lang` |
| `on_change_callback` | ✅ Supported | `DepositRequest.$onChangeCallback` |
| `amount` | ✅ Supported | `DepositRequest.$amount` |
| `country_code` | ✅ Supported | `DepositRequest.$countryCode` |
| `claimable_balance_supported` | ✅ Supported | `DepositRequest.$claimableBalanceSupported` |
| `customer_id` | ✅ Supported | `DepositRequest.$customerId` |
| `location_id` | ✅ Supported | `DepositRequest.$locationId` |

## Deposit Response Fields

Fields returned by GET /deposit

| Feature | Status | Notes |
|---------|--------|-------|
| `how` | ✅ Supported | `Required. DepositResponse.$how` |
| `id` | ✅ Supported | `DepositResponse.$id` |
| `eta` | ✅ Supported | `DepositResponse.$eta` |
| `min_amount` | ✅ Supported | `DepositResponse.$minAmount` |
| `max_amount` | ✅ Supported | `DepositResponse.$maxAmount` |
| `fee_fixed` | ✅ Supported | `DepositResponse.$feeFixed` |
| `fee_percent` | ✅ Supported | `DepositResponse.$feePercent` |
| `extra_info` | ✅ Supported | `DepositResponse.$extraInfo` |

## Withdraw Endpoints

Withdrawal API endpoints

| Feature | Status | Notes |
|---------|--------|-------|
| `withdraw` | ✅ Supported | `TransferServerService.withdraw()` |
| `withdraw_exchange` | ✅ Supported | `TransferServerService.withdrawExchange()` |

## Withdraw Request Parameters

Parameters for GET /withdraw

| Feature | Status | Notes |
|---------|--------|-------|
| `asset_code` | ✅ Supported | `Required. WithdrawRequest.$assetCode` |
| `type` | ✅ Supported | `Required. WithdrawRequest.$type` |
| `dest` | ✅ Supported | `WithdrawRequest.$dest` |
| `dest_extra` | ✅ Supported | `WithdrawRequest.$destExtra` |
| `account` | ✅ Supported | `WithdrawRequest.$account` |
| `memo` | ✅ Supported | `WithdrawRequest.$memo` |
| `memo_type` | ✅ Supported | `WithdrawRequest.$memoType` |
| `wallet_name` | ✅ Supported | `WithdrawRequest.$walletName` |
| `wallet_url` | ✅ Supported | `WithdrawRequest.$walletUrl` |
| `lang` | ✅ Supported | `WithdrawRequest.$lang` |
| `on_change_callback` | ✅ Supported | `WithdrawRequest.$onChangeCallback` |
| `amount` | ✅ Supported | `WithdrawRequest.$amount` |
| `country_code` | ✅ Supported | `WithdrawRequest.$countryCode` |
| `refund_memo` | ✅ Supported | `WithdrawRequest.$refundMemo` |
| `refund_memo_type` | ✅ Supported | `WithdrawRequest.$refundMemoType` |
| `customer_id` | ✅ Supported | `WithdrawRequest.$customerId` |
| `location_id` | ✅ Supported | `WithdrawRequest.$locationId` |

## Withdraw Response Fields

Fields returned by GET /withdraw

| Feature | Status | Notes |
|---------|--------|-------|
| `account_id` | ✅ Supported | `Required. WithdrawResponse.$accountId` |
| `memo_type` | ✅ Supported | `WithdrawResponse.$memoType` |
| `memo` | ✅ Supported | `WithdrawResponse.$memo` |
| `id` | ✅ Supported | `Required. WithdrawResponse.$id` |
| `eta` | ✅ Supported | `WithdrawResponse.$eta` |
| `min_amount` | ✅ Supported | `WithdrawResponse.$minAmount` |
| `max_amount` | ✅ Supported | `WithdrawResponse.$maxAmount` |
| `fee_fixed` | ✅ Supported | `WithdrawResponse.$feeFixed` |
| `fee_percent` | ✅ Supported | `WithdrawResponse.$feePercent` |
| `extra_info` | ✅ Supported | `WithdrawResponse.$extraInfo` |

## Info Endpoint

Anchor capabilities and asset information

| Feature | Status | Notes |
|---------|--------|-------|
| `info_endpoint` | ✅ Supported | `TransferServerService.info()` |

## Info Response Fields

Fields returned by GET /info

| Feature | Status | Notes |
|---------|--------|-------|
| `deposit` | ✅ Supported | `Required. InfoResponse.$depositAssets` |
| `withdraw` | ✅ Supported | `Required. InfoResponse.$withdrawAssets` |
| `deposit-exchange` | ✅ Supported | `InfoResponse.$depositExchangeAssets` |
| `withdraw-exchange` | ✅ Supported | `InfoResponse.$withdrawExchangeAssets` |
| `fee` | ✅ Supported | `InfoResponse.$feeInfo` |
| `transactions` | ✅ Supported | `InfoResponse.$transactionsInfo` |
| `transaction` | ✅ Supported | `InfoResponse.$transactionInfo` |
| `features` | ✅ Supported | `InfoResponse.$featureFlags` |

## Fee Endpoint

Fee calculation (deprecated)

| Feature | Status | Notes |
|---------|--------|-------|
| `fee_endpoint` | ✅ Supported | `TransferServerService.fee()` |

## Transaction Endpoints

Transaction query and update endpoints

| Feature | Status | Notes |
|---------|--------|-------|
| `transactions` | ✅ Supported | `TransferServerService.transactions()` |
| `transaction` | ✅ Supported | `TransferServerService.transaction()` |
| `patch_transaction` | ✅ Supported | `TransferServerService.patchTransaction()` |

## Transaction Fields

Fields in the transaction object

| Feature | Status | Notes |
|---------|--------|-------|
| `id` | ✅ Supported | `Required. AnchorTransaction.$id` |
| `kind` | ✅ Supported | `Required. AnchorTransaction.$kind` |
| `status` | ✅ Supported | `Required. AnchorTransaction.$status` |
| `started_at` | ✅ Supported | `Required. AnchorTransaction.$startedAt` |
| `status_eta` | ✅ Supported | `AnchorTransaction.$statusEta` |
| `more_info_url` | ✅ Supported | `AnchorTransaction.$moreInfoUrl` |
| `amount_in` | ✅ Supported | `AnchorTransaction.$amountIn` |
| `amount_in_asset` | ✅ Supported | `AnchorTransaction.$amountInAsset` |
| `amount_out` | ✅ Supported | `AnchorTransaction.$amountOut` |
| `amount_out_asset` | ✅ Supported | `AnchorTransaction.$amountOutAsset` |
| `amount_fee` | ✅ Supported | `AnchorTransaction.$amountFee` |
| `amount_fee_asset` | ✅ Supported | `AnchorTransaction.$amountFeeAsset` |
| `fee_details` | ✅ Supported | `AnchorTransaction.$feeDetails` |
| `quote_id` | ✅ Supported | `AnchorTransaction.$quoteId` |
| `from` | ✅ Supported | `AnchorTransaction.$from` |
| `to` | ✅ Supported | `AnchorTransaction.$to` |
| `deposit_memo` | ✅ Supported | `AnchorTransaction.$depositMemo` |
| `deposit_memo_type` | ✅ Supported | `AnchorTransaction.$depositMemoType` |
| `withdraw_anchor_account` | ✅ Supported | `AnchorTransaction.$withdrawAnchorAccount` |
| `withdraw_memo` | ✅ Supported | `AnchorTransaction.$withdrawMemo` |
| `withdraw_memo_type` | ✅ Supported | `AnchorTransaction.$withdrawMemoType` |
| `updated_at` | ✅ Supported | `AnchorTransaction.$updatedAt` |
| `completed_at` | ✅ Supported | `AnchorTransaction.$completedAt` |
| `user_action_required_by` | ✅ Supported | `AnchorTransaction.$userActionRequiredBy` |
| `stellar_transaction_id` | ✅ Supported | `AnchorTransaction.$stellarTransactionId` |
| `external_transaction_id` | ✅ Supported | `AnchorTransaction.$externalTransactionId` |
| `message` | ✅ Supported | `AnchorTransaction.$message` |
| `refunded` | ✅ Supported | `AnchorTransaction.$refunded` |
| `refunds` | ✅ Supported | `AnchorTransaction.$refunds` |
| `required_info_message` | ✅ Supported | `AnchorTransaction.$requiredInfoMessage` |
| `required_info_updates` | ✅ Supported | `AnchorTransaction.$requiredInfoUpdates` |
| `instructions` | ✅ Supported | `AnchorTransaction.$instructions` |
| `claimable_balance_id` | ✅ Supported | `AnchorTransaction.$claimableBalanceId` |
