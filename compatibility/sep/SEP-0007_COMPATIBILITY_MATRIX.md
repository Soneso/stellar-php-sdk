# SEP-07: URI Scheme to facilitate delegated signing

**Status:** ✅ Supported  
**SDK Version:** 1.9.6  
**Generated:** 2026-04-03 21:56 UTC  
**Spec:** [https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0007.md](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0007.md)

## Overall Coverage

**Total Coverage:** 100.0% (20/20 fields)

- ✅ **Implemented:** 20/20
- ❌ **Not Implemented:** 0/20

## Coverage by Section

| Section | Coverage | Implemented | Total |
|---------|----------|-------------|-------|
| URI Operations | 100.0% | 2 | 2 |
| TX Operation Parameters | 100.0% | 5 | 5 |
| PAY Operation Parameters | 100.0% | 6 | 6 |
| Common Parameters | 100.0% | 4 | 4 |
| Signature Features | 100.0% | 3 | 3 |

## URI Operations

Generate and submit SEP-7 URIs

| Feature | Status | Notes |
|---------|--------|-------|
| `tx` | ✅ Supported | `URIScheme.generateSignTransactionURI()` |
| `pay` | ✅ Supported | `URIScheme.generatePayOperationURI()` |

## TX Operation Parameters

Parameters for the tx (sign transaction) operation

| Feature | Status | Notes |
|---------|--------|-------|
| `xdr` | ✅ Supported | `Required. URIScheme::xdrParameterName` |
| `replace` | ✅ Supported | `URIScheme::replaceParameterName` |
| `callback` | ✅ Supported | `URIScheme::callbackParameterName` |
| `pubkey` | ✅ Supported | `URIScheme::publicKeyParameterName` |
| `chain` | ✅ Supported | `URIScheme::chainParameterName` |

## PAY Operation Parameters

Parameters for the pay (payment request) operation

| Feature | Status | Notes |
|---------|--------|-------|
| `destination` | ✅ Supported | `Required. URIScheme::destinationParameterName` |
| `amount` | ✅ Supported | `URIScheme::amountParameterName` |
| `asset_code` | ✅ Supported | `URIScheme::assetCodeParameterName` |
| `asset_issuer` | ✅ Supported | `URIScheme::assetIssuerParameterName` |
| `memo` | ✅ Supported | `URIScheme::memoParameterName` |
| `memo_type` | ✅ Supported | `URIScheme::memoTypeParameterName` |

## Common Parameters

Parameters shared by tx and pay operations

| Feature | Status | Notes |
|---------|--------|-------|
| `msg` | ✅ Supported | `URIScheme::messageParameterName` |
| `network_passphrase` | ✅ Supported | `URIScheme::networkPassphraseParameterName` |
| `origin_domain` | ✅ Supported | `URIScheme::originDomainParameterName` |
| `signature` | ✅ Supported | `URIScheme::signatureParameterName` |

## Signature Features

URI signing and verification

| Feature | Status | Notes |
|---------|--------|-------|
| `sign_uri` | ✅ Supported | `URIScheme.signURI()` |
| `verify_signed_uri` | ✅ Supported | `URIScheme.checkUIRSchemeIsValid()` |
| `sign_and_submit` | ✅ Supported | `URIScheme.signAndSubmitTransaction()` |
