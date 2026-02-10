# SEP-0007 (URI Scheme to facilitate delegated signing) Compatibility Matrix

**Generated:** 2026-02-10 12:45:15

**SEP Version:** 2.1.0

**SEP Status:** Active

**SDK Version:** 1.9.3

**SEP URL:** https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0007.md

## SEP Summary

This Stellar Ecosystem Proposal introduces a URI Scheme that can be used to
generate a URI that will serve as a request to sign a transaction. The URI
(request) will typically be signed by the user’s trusted wallet where she
stores her secret key(s).

## Overall Coverage

**Total Coverage:** 100% (31/31 fields)

- ✅ **Implemented:** 31/31
- ❌ **Not Implemented:** 0/31

**Required Fields:** 100% (18/18)

**Optional Fields:** 100% (13/13)

## Implementation Status

✅ **Implemented**

### Implementation Files

- `Soneso/StellarSDK/SEP/URIScheme/URISchemeError.php`
- `Soneso/StellarSDK/SEP/URIScheme/URIScheme.php`
- `Soneso/StellarSDK/SEP/URIScheme/SubmitUriSchemeTransactionResponse.php`

### Key Classes

- **`URISchemeError`**: Implements utility methods for SEP-007 - URI Scheme to facilitate delegated signing
- **`URIScheme`**: Implements utility methods for SEP-007 - URI Scheme to facilitate delegated signing
- **`SubmitUriSchemeTransactionResponse`**: Implements utility methods for SEP-007 - URI Scheme to facilitate delegated signing

## Coverage by Section

| Section | Coverage | Required Coverage | Implemented | Total |
|---------|----------|-------------------|-------------|-------|
| URI Operations | 100% | 100% | 2 | 2 |
| TX Operation Parameters | 100% | 100% | 5 | 5 |
| PAY Operation Parameters | 100% | 100% | 6 | 6 |
| Common Parameters | 100% | 100% | 4 | 4 |
| Validation Features | 100% | 100% | 11 | 11 |
| Signature Features | 100% | 100% | 3 | 3 |

## Detailed Field Comparison

### URI Operations

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `tx` | ✓ | ✅ | `generateSignTransactionURI` | Transaction operation - Request to sign a transaction |
| `pay` | ✓ | ✅ | `generatePayOperationURI` | Payment operation - Request to pay a specific address |

### TX Operation Parameters

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `xdr` | ✓ | ✅ | `xdrParameterName` | Base64 encoded TransactionEnvelope XDR |
| `replace` |  | ✅ | `replaceParameterName` | URL-encoded field replacement using Txrep (SEP-0011) format |
| `callback` |  | ✅ | `callbackParameterName` | URL for transaction submission callback |
| `pubkey` |  | ✅ | `publicKeyParameterName` | Stellar public key to specify which key should sign |
| `chain` |  | ✅ | `chainParameterName` | Nested SEP-0007 URL for transaction chaining |

### PAY Operation Parameters

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `destination` | ✓ | ✅ | `destinationParameterName` | Stellar account ID or payment address to receive payment |
| `amount` |  | ✅ | `amountParameterName` | Amount to send |
| `asset_code` |  | ✅ | `assetCodeParameterName` | Asset code for the payment (e.g., USD, BTC) |
| `asset_issuer` |  | ✅ | `assetIssuerParameterName` | Stellar account ID of asset issuer |
| `memo` |  | ✅ | `memoParameterName` | Memo value to attach to transaction |
| `memo_type` |  | ✅ | `memoTypeParameterName` | Type of memo (MEMO_TEXT, MEMO_ID, MEMO_HASH, MEMO_RETURN) |

### Common Parameters

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `msg` |  | ✅ | `messageParameterName` | Message for the user (max 300 characters) |
| `network_passphrase` |  | ✅ | `networkPassphraseParameterName` | Network passphrase for the transaction |
| `origin_domain` |  | ✅ | `originDomainParameterName` | Fully qualified domain name of the service originating the request |
| `signature` |  | ✅ | `signatureParameterName` | Signature of the URL for verification |

### Validation Features

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `validate_uri_scheme` | ✓ | ✅ | `generateSignTransactionURI` | Validate that URI starts with web+stellar: |
| `validate_operation_type` | ✓ | ✅ | `generateSignTransactionURI` | Validate operation type is tx or pay |
| `validate_xdr_parameter` | ✓ | ✅ | `generateSignTransactionURI` | Validate XDR parameter for tx operation |
| `validate_destination_parameter` | ✓ | ✅ | `generatePayOperationURI` | Validate destination parameter for pay operation |
| `validate_stellar_address` | ✓ | ✅ | `checkUIRSchemeIsValid` | Validate Stellar addresses (account IDs, muxed accounts, contract IDs) |
| `validate_asset_code` | ✓ | ✅ | `generatePayOperationURI` | Validate asset code length and format |
| `validate_memo_type` | ✓ | ✅ | `generatePayOperationURI` | Validate memo type is one of allowed types |
| `validate_memo_value` | ✓ | ✅ | `generatePayOperationURI` | Validate memo value based on memo type |
| `validate_message_length` | ✓ | ✅ | `generateSignTransactionURI` | Validate message parameter length (max 300 chars) |
| `validate_origin_domain` | ✓ | ✅ | `checkUIRSchemeIsValid` | Validate origin_domain is fully qualified domain name |
| `validate_chain_nesting` | ✓ | ✅ | `generateSignTransactionURI` | Validate chain parameter nesting depth (max 7 levels) |

### Signature Features

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `sign_uri` | ✓ | ✅ | `signURI` | Sign a SEP-0007 URI with a keypair |
| `verify_signature` | ✓ | ✅ | `verify` | Verify URI signature with a public key |
| `verify_signed_uri` | ✓ | ✅ | `checkUIRSchemeIsValid` | Verify signed URI by fetching signing key from origin domain TOML |

## Implementation Gaps

🎉 **No gaps found!** All fields are implemented.

## Recommendations

✅ The SDK has full compatibility with SEP-0007!

## Legend

- ✅ **Implemented**: Field is implemented in SDK
- ❌ **Not Implemented**: Field is missing from SDK
- ✓ **Required**: Field is required by SEP specification
- (blank) **Optional**: Field is optional
