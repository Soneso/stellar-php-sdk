# SEP-0002 (Federation protocol) Compatibility Matrix

**Generated:** 2026-02-03 15:20:26

**SEP Version:** N/A

**SEP Status:** Final

**SDK Version:** 1.9.2

**SEP URL:** https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0002.md

## SEP Summary

The Stellar federation protocol maps Stellar addresses to more information
about a given user. It’s a way for Stellar client software to resolve
email-like addresses such as `name*yourdomain.com` into account IDs like:
`GCCVPYFOHY7ZB7557JKENAX62LUAPLMGIWNZJAFV2MITK6T32V37KEJU`. Stellar addresses
provide an easy way for users to share payment details by using a syntax that
interoperates across different domains and providers.

## Overall Coverage

**Total Coverage:** 100% (10/10 fields)

- ✅ **Implemented:** 10/10
- ❌ **Not Implemented:** 0/10

**Required Fields:** 100% (6/6)

**Optional Fields:** 100% (4/4)

## Implementation Status

✅ **Implemented**

### Implementation Files

- `Soneso/StellarSDK/SEP/Federation/Federation.php`
- `Soneso/StellarSDK/SEP/Federation/FederationRequestBuilder.php`
- `Soneso/StellarSDK/SEP/Federation/FederationResponse.php`

### Key Classes

- **`Federation`**
- **`FederationRequestBuilder`**
- **`FederationResponse`**

## Coverage by Section

| Section | Coverage | Required Coverage | Implemented | Total |
|---------|----------|-------------------|-------------|-------|
| Request Parameters | 100% | 100% | 2 | 2 |
| Request Types | 100% | 100% | 4 | 4 |
| Response Fields | 100% | 100% | 4 | 4 |

## Detailed Field Comparison

### Request Parameters

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `q` | ✓ | ✅ | `q` | String to look up (stellar address, account ID, or transaction ID) |
| `type` | ✓ | ✅ | `type` | Type of lookup (name, id, txid, or forward) |

### Request Types

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `name` | ✓ | ✅ | `resolveStellarAddress` | returns the federation record for the given Stellar address. |
| `forward` |  | ✅ | `resolveForward` | Used for forwarding the payment on to a different network or different financial institution. The ot... |
| `id` | ✓ | ✅ | `resolveStellarAccountId` | returns the federation record of the Stellar address associated with the given account ID. In some c... |
| `txid` |  | ✅ | `resolveStellarTransactionId` | returns the federation record of the sender of the transaction if known by the server. Example: `htt... |

### Response Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `stellar_address` | ✓ | ✅ | `stellarAddress` | stellar address |
| `account_id` | ✓ | ✅ | `accountId` | Stellar public key / account ID |
| `memo_type` |  | ✅ | `memoType` | type of memo to attach to transaction, one of text, id or hash |
| `memo` |  | ✅ | `memo` | value of memo to attach to transaction, for hash this should be base64-encoded. This field should al... |

## Implementation Gaps

🎉 **No gaps found!** All fields are implemented.

## Recommendations

✅ The SDK has full compatibility with SEP-0002!

## Legend

- ✅ **Implemented**: Field is implemented in SDK
- ❌ **Not Implemented**: Field is missing from SDK
- ✓ **Required**: Field is required by SEP specification
- (blank) **Optional**: Field is optional
