# SEP-0011 (Txrep: human-readable low-level representation of Stellar transactions) Compatibility Matrix

**Generated:** 2026-01-06 16:36:06

**SEP Version:** 1.1.0

**SEP Status:** Active

**SDK Version:** 1.9.1

**SEP URL:** https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0011.md

## SEP Summary

Txrep is a human-readable representation of Stellar transactions that functions
like an assembly language for XDR.

## Overall Coverage

**Total Coverage:** 100% (50/50 fields)

- ✅ **Implemented:** 50/50
- ❌ **Not Implemented:** 0/50

**Required Fields:** 100% (50/50)

## Implementation Status

✅ **Implemented**

### Implementation Files

- `Soneso/StellarSDK/SEP/TxRep/TxRep.php`

### Key Classes

- **`TxRep`**

## Coverage by Section

| Section | Coverage | Required Coverage | Implemented | Total |
|---------|----------|-------------------|-------------|-------|
| Asset Encoding | 100% | 100% | 3 | 3 |
| Decoding Features | 100% | 100% | 8 | 8 |
| Encoding Features | 100% | 100% | 8 | 8 |
| Format Features | 100% | 100% | 5 | 5 |
| Operation Types | 100% | 100% | 26 | 26 |

## Detailed Field Comparison

### Asset Encoding

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `encode_native_asset` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64` | Encode native XLM asset in txrep format |
| `encode_alphanumeric4_asset` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64` | Encode 4-character alphanumeric asset |
| `encode_alphanumeric12_asset` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64` | Encode 12-character alphanumeric asset |

### Decoding Features

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `decode_transaction` | ✓ | ✅ | `transactionEnvelopeXdrBase64FromTxRep` | Parse txrep text format to transaction envelope XDR |
| `decode_fee_bump_transaction` | ✓ | ✅ | `transactionEnvelopeXdrBase64FromTxRep` | Parse fee bump transaction from txrep format |
| `decode_source_account` | ✓ | ✅ | `transactionEnvelopeXdrBase64FromTxRep` | Parse source account (including muxed accounts) |
| `decode_memo` | ✓ | ✅ | `transactionEnvelopeXdrBase64FromTxRep` | Parse all memo types from txrep |
| `decode_operations` | ✓ | ✅ | `transactionEnvelopeXdrBase64FromTxRep` | Parse all Stellar operation types from txrep |
| `decode_preconditions` | ✓ | ✅ | `transactionEnvelopeXdrBase64FromTxRep` | Parse transaction preconditions from txrep |
| `decode_signatures` | ✓ | ✅ | `transactionEnvelopeXdrBase64FromTxRep` | Parse transaction signatures from txrep |
| `decode_soroban_data` | ✓ | ✅ | `transactionEnvelopeXdrBase64FromTxRep` | Parse Soroban transaction data from txrep |

### Encoding Features

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `encode_transaction` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64` | Convert transaction envelope XDR to txrep text format |
| `encode_fee_bump_transaction` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64` | Convert fee bump transaction envelope to txrep format |
| `encode_source_account` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64` | Encode source account (including muxed accounts) |
| `encode_memo` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64` | Encode all memo types (NONE, TEXT, ID, HASH, RETURN) |
| `encode_operations` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64` | Encode all Stellar operation types |
| `encode_preconditions` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64` | Encode transaction preconditions (time bounds, ledger bounds, min seq num, etc.) |
| `encode_signatures` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64` | Encode transaction signatures |
| `encode_soroban_data` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64` | Encode Soroban transaction data (resources, footprint, etc.) |

### Format Features

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `comment_support` | ✓ | ✅ | `TxRep format implementation` | Support for comments in txrep format |
| `dot_notation` | ✓ | ✅ | `TxRep format implementation` | Use dot notation for nested structures |
| `array_indexing` | ✓ | ✅ | `TxRep format implementation` | Support array indexing in txrep format |
| `hex_encoding` | ✓ | ✅ | `TxRep format implementation` | Hexadecimal encoding for binary data |
| `string_escaping` | ✓ | ✅ | `TxRep format implementation` | Proper string escaping with double quotes |

### Operation Types

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `create_account` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode CREATE_ACCOUNT operation |
| `payment` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode PAYMENT operation |
| `path_payment_strict_receive` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode PATH_PAYMENT_STRICT_RECEIVE operation |
| `path_payment_strict_send` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode PATH_PAYMENT_STRICT_SEND operation |
| `manage_sell_offer` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode MANAGE_SELL_OFFER operation |
| `manage_buy_offer` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode MANAGE_BUY_OFFER operation |
| `create_passive_sell_offer` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode CREATE_PASSIVE_SELL_OFFER operation |
| `set_options` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode SET_OPTIONS operation |
| `change_trust` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode CHANGE_TRUST operation |
| `allow_trust` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode ALLOW_TRUST operation |
| `account_merge` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode ACCOUNT_MERGE operation |
| `manage_data` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode MANAGE_DATA operation |
| `bump_sequence` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode BUMP_SEQUENCE operation |
| `create_claimable_balance` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode CREATE_CLAIMABLE_BALANCE operation |
| `claim_claimable_balance` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode CLAIM_CLAIMABLE_BALANCE operation |
| `begin_sponsoring_future_reserves` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode BEGIN_SPONSORING_FUTURE_RESERVES operation |
| `end_sponsoring_future_reserves` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode END_SPONSORING_FUTURE_RESERVES operation |
| `revoke_sponsorship` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode REVOKE_SPONSORSHIP operation |
| `clawback` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode CLAWBACK operation |
| `clawback_claimable_balance` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode CLAWBACK_CLAIMABLE_BALANCE operation |
| `set_trust_line_flags` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode SET_TRUST_LINE_FLAGS operation |
| `liquidity_pool_deposit` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode LIQUIDITY_POOL_DEPOSIT operation |
| `liquidity_pool_withdraw` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode LIQUIDITY_POOL_WITHDRAW operation |
| `invoke_host_function` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode INVOKE_HOST_FUNCTION operation (Soroban) |
| `extend_footprint_ttl` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode EXTEND_FOOTPRINT_TTL operation (Soroban) |
| `restore_footprint` | ✓ | ✅ | `fromTransactionEnvelopeXdrBase64, transactionEnvelopeXdrBase64FromTxRep` | Encode/decode RESTORE_FOOTPRINT operation (Soroban) |

## Implementation Gaps

🎉 **No gaps found!** All fields are implemented.

## Recommendations

✅ The SDK has excellent compatibility with SEP-0011!

## Legend

- ✅ **Implemented**: Field is implemented in SDK
- ❌ **Not Implemented**: Field is missing from SDK
- ✓ **Required**: Field is required by SEP specification
- (blank) **Optional**: Field is optional
