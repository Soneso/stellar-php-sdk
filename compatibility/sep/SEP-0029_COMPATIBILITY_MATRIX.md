# SEP-29: Account Memo Requirements

**Status:** ✅ Supported  
**SDK Version:** 1.14.0  
**Generated:** 2026-09-23 03:59 UTC  
**Spec:** [https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0029.md](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0029.md)

## Overall Coverage

**Total Coverage:** 100.0% (15/15 fields)

- ✅ **Implemented:** 15/15
- ❌ **Not Implemented:** 0/15

## Coverage by Section

| Section | Coverage | Implemented | Total |
|---------|----------|-------------|-------|
| Memo requirement flag | 100.0% | 2 | 2 |
| Sender-side check | 100.0% | 8 | 8 |
| Submission integration | 100.0% | 5 | 5 |

## Memo requirement flag

The data entry an account uses to declare that incoming payments need a memo

| Feature | Status | Notes |
|---------|--------|-------|
| `config.memo_required` | ✅ Supported | `StellarSDK.checkMemoRequired()` |
| `Setting the flag` | ✅ Supported | `ManageDataOperationBuilder` |

## Sender-side check

What the sender inspects before submitting a transaction

| Feature | Status | Notes |
|---------|--------|-------|
| `PAYMENT` | ✅ Supported | `StellarSDK.checkMemoRequired()` |
| `PATH_PAYMENT_STRICT_SEND` | ✅ Supported | `StellarSDK.checkMemoRequired()` |
| `PATH_PAYMENT_STRICT_RECEIVE` | ✅ Supported | `StellarSDK.checkMemoRequired()` |
| `MERGE_ACCOUNT` | ✅ Supported | `StellarSDK.checkMemoRequired()` |
| `Multiplexed destinations exempt` | ✅ Supported | `StellarSDK.checkMemoRequired()` |
| `Memo present skips the check` | ✅ Supported | `StellarSDK.checkMemoRequired()` |
| `Fee bump inner transaction` | ✅ Supported | `StellarSDK.checkMemoRequired()` |
| `Unknown destination skipped` | ✅ Supported | `StellarSDK.checkMemoRequired()` |

## Submission integration

The check as part of transaction submission

| Feature | Status | Notes |
|---------|--------|-------|
| `submitTransaction` | ✅ Supported | `StellarSDK.submitTransaction()` |
| `submitAsyncTransaction` | ✅ Supported | `StellarSDK.submitAsyncTransaction()` |
| `submitTransactionEnvelopeXdrBase64` | ✅ Supported | `StellarSDK.submitTransactionEnvelopeXdrBase64()` |
| `submitAsyncTransactionEnvelopeXdrBase64` | ✅ Supported | `StellarSDK.submitAsyncTransactionEnvelopeXdrBase64()` |
| `AccountRequiresMemoException` | ✅ Supported | `AccountRequiresMemoException` |
