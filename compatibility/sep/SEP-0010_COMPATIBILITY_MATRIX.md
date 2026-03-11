# SEP-10: Stellar Web Authentication

**Status:** ✅ Supported  
**SDK Version:** 1.9.5  
**Generated:** 2026-03-11 21:41 UTC  
**Spec:** [https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0010.md](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0010.md)

## Overall Coverage

**Total Coverage:** 100.0% (19/19 fields)

- ✅ **Implemented:** 19/19
- ❌ **Not Implemented:** 0/19

## Coverage by Section

| Section | Coverage | Implemented | Total |
|---------|----------|-------------|-------|
| Authentication Flow | 100.0% | 3 | 3 |
| Challenge Features | 100.0% | 4 | 4 |
| Challenge Validation | 100.0% | 10 | 10 |
| Response Models | 100.0% | 2 | 2 |

## Authentication Flow

Web authentication client methods

| Feature | Status | Notes |
|---------|--------|-------|
| `fromDomain` | ✅ Supported | `WebAuth.fromDomain()` |
| `jwtToken` | ✅ Supported | `WebAuth.jwtToken()` |
| `setGracePeriod` | ✅ Supported | `WebAuth.setGracePeriod()` |

## Challenge Features

SEP-10 challenge transaction features supported via jwtToken() parameters

| Feature | Status | Notes |
|---------|--------|-------|
| `memo_support` | ✅ Supported | `WebAuth.jwtToken($memo)` |
| `home_domain` | ✅ Supported | `WebAuth.jwtToken($homeDomain)` |
| `client_domain` | ✅ Supported | `WebAuth.jwtToken($clientDomain)` |
| `client_domain_signing` | ✅ Supported | `WebAuth.jwtToken($clientDomainKeyPair, $clientDomainSigningCallback)` |

## Challenge Validation

Challenge transaction validation checks (each error class = one validation)

| Feature | Status | Notes |
|---------|--------|-------|
| `home_domain_validation` | ✅ Supported | `ChallengeValidationErrorInvalidHomeDomain` |
| `web_auth_domain_validation` | ✅ Supported | `ChallengeValidationErrorInvalidWebAuthDomain` |
| `source_account_validation` | ✅ Supported | `ChallengeValidationErrorInvalidSourceAccount` |
| `signature_verification` | ✅ Supported | `ChallengeValidationErrorInvalidSignature` |
| `timebounds_validation` | ✅ Supported | `ChallengeValidationErrorInvalidTimeBounds` |
| `sequence_number_validation` | ✅ Supported | `ChallengeValidationErrorInvalidSeqNr` |
| `operation_type_validation` | ✅ Supported | `ChallengeValidationErrorInvalidOperationType` |
| `memo_type_validation` | ✅ Supported | `ChallengeValidationErrorInvalidMemoType` |
| `memo_value_validation` | ✅ Supported | `ChallengeValidationErrorInvalidMemoValue` |
| `memo_muxed_conflict` | ✅ Supported | `ChallengeValidationErrorMemoAndMuxedAccount` |

## Response Models

Challenge and token response handling

| Feature | Status | Notes |
|---------|--------|-------|
| `ChallengeResponse` | ✅ Supported | `ChallengeResponse` |
| `SubmitCompletedChallengeResponse` | ✅ Supported | `SubmitCompletedChallengeResponse` |
