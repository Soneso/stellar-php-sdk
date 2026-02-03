# SEP-0010 (Stellar Web Authentication) Compatibility Matrix

**Generated:** 2026-02-03 15:20:29

**SEP Version:** 3.4.1

**SEP Status:** Active

**SDK Version:** 1.9.2

**SEP URL:** https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0010.md

## SEP Summary

This SEP defines the standard way for clients such as wallets or exchanges to
create authenticated web sessions on behalf of a user who holds a Stellar
account. A wallet may want to authenticate with any web service which requires
a Stellar account ownership verification, for example, to upload KYC
information to an anchor in an authenticated way as described in
[SEP-12](sep-0012.md).

This SEP also supports authenticating users of shared, omnibus, or pooled
Stellar accounts. Clients can use [memos](#memos) or
[muxed accounts](#muxed-accounts) to distinguish users or sub-accounts of
shared accounts.

## Overall Coverage

**Total Coverage:** 100% (24/24 features)

- ✅ **Implemented:** 24/24
- ❌ **Not Implemented:** 0/24

**Required Features:** 100% (19/19)

**Optional Features:** 100% (5/5)

> **Note:** 2 server-side feature(s) are excluded from coverage calculation. These features are implemented on the authentication server, not in client SDKs.

## Implementation Status

✅ **Implemented**

### Implementation Files

- `Soneso/StellarSDK/SEP/WebAuth/ChallengeValidationError.php`
- `Soneso/StellarSDK/SEP/WebAuth/SubmitCompletedChallengeResponse.php`
- `Soneso/StellarSDK/SEP/WebAuth/ChallengeValidationErrorInvalidHomeDomain.php`
- `Soneso/StellarSDK/SEP/WebAuth/ChallengeValidationErrorInvalidWebAuthDomain.php`
- `Soneso/StellarSDK/SEP/WebAuth/ChallengeValidationErrorInvalidSourceAccount.php`
- `Soneso/StellarSDK/SEP/WebAuth/ChallengeValidationErrorMemoAndMuxedAccount.php`
- `Soneso/StellarSDK/SEP/WebAuth/SubmitCompletedChallengeTimeoutResponseException.php`
- `Soneso/StellarSDK/SEP/WebAuth/ChallengeValidationErrorInvalidSignature.php`
- `Soneso/StellarSDK/SEP/WebAuth/ChallengeRequestErrorResponse.php`
- `Soneso/StellarSDK/SEP/WebAuth/ChallengeValidationErrorInvalidSeqNr.php`
- `Soneso/StellarSDK/SEP/WebAuth/ChallengeResponse.php`
- `Soneso/StellarSDK/SEP/WebAuth/SubmitCompletedChallengeErrorResponseException.php`
- `Soneso/StellarSDK/SEP/WebAuth/ChallengeValidationErrorInvalidTimeBounds.php`
- `Soneso/StellarSDK/SEP/WebAuth/ChallengeValidationErrorInvalidMemoType.php`
- `Soneso/StellarSDK/SEP/WebAuth/ChallengeValidationErrorInvalidMemoValue.php`
- `Soneso/StellarSDK/SEP/WebAuth/ChallengeRequestBuilder.php`
- `Soneso/StellarSDK/SEP/WebAuth/SubmitCompletedChallengeUnknownResponseException.php`
- `Soneso/StellarSDK/SEP/WebAuth/ChallengeValidationErrorInvalidOperationType.php`
- `Soneso/StellarSDK/SEP/WebAuth/WebAuth.php`

### Key Classes

- **`ChallengeValidationError`**
- **`SubmitCompletedChallengeResponse`**
- **`ChallengeValidationErrorInvalidHomeDomain`**
- **`ChallengeValidationErrorInvalidWebAuthDomain`**
- **`ChallengeValidationErrorInvalidSourceAccount`**
- **`ChallengeValidationErrorMemoAndMuxedAccount`**
- **`SubmitCompletedChallengeTimeoutResponseException`**
- **`ChallengeValidationErrorInvalidSignature`**
- **`ChallengeRequestErrorResponse`**
- **`ChallengeValidationErrorInvalidSeqNr`**
- **`ChallengeResponse`**
- **`SubmitCompletedChallengeErrorResponseException`**
- **`ChallengeValidationErrorInvalidTimeBounds`**
- **`ChallengeValidationErrorInvalidMemoType`**
- **`ChallengeValidationErrorInvalidMemoValue`**
- **`ChallengeRequestBuilder`**
- **`SubmitCompletedChallengeUnknownResponseException`**
- **`ChallengeValidationErrorInvalidOperationType`**
- **`WebAuth`**

## Coverage by Section

| Section | Coverage | Required Coverage | Implemented | Total |
|---------|----------|-------------------|-------------|-------|
| Authentication Endpoints | 100% | 100% | 2 | 2 |
| Challenge Transaction Features | 100% | 100% | 9 | 9 |
| JWT Token Features | 100% | 100% | 4 | 4 |
| Client Domain Features | 100% | 0% | 3 | 3 |
| Verification Features | 100% | 100% | 6 | 6 |

## Detailed Field Comparison

### Authentication Endpoints

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `get_auth_challenge` | ✓ | ✅ | `getChallenge` | GET /auth endpoint - Returns challenge transaction |
| `post_auth_token` | ✓ | ✅ | `sendSignedChallengeTransaction` | POST /auth endpoint - Validates signed challenge and returns JWT token |

### Challenge Transaction Features

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `challenge_transaction_generation` | ✓ | ✅ | `getChallenge` | Generate challenge transaction with proper structure |
| `transaction_envelope_format` | ✓ | ✅ | `validateChallenge` | Challenge uses proper Stellar transaction envelope format |
| `sequence_number_zero` | ✓ | ✅ | `validateChallenge` | Challenge transaction has sequence number 0 |
| `manage_data_operations` | ✓ | ✅ | `validateChallenge` | Challenge uses ManageData operations for auth data |
| `home_domain_operation` | ✓ | ✅ | `validateChallenge` | First operation contains home_domain + " auth" as data name |
| `web_auth_domain_operation` |  | ✅ | `validateChallenge` | Optional operation with web_auth_domain for domain verification |
| `timebounds_enforcement` | ✓ | ✅ | `validateChallenge` | Challenge transaction has timebounds for expiration |
| `server_signature` | ✓ | ✅ | `validateChallenge` | Challenge is signed by server before sending to client |
| `nonce_generation` | ✓ | ✅ | `getChallenge` | Random nonce in ManageData operation value |

### JWT Token Features

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `jwt_token_generation` | ✓ | ✅ | `sendSignedChallengeTransaction` | Generate JWT token after successful challenge validation |
| `jwt_token_response` | ✓ | ✅ | `sendSignedChallengeTransaction` | Return JWT token in JSON response with "token" field |
| `jwt_token_validation` | ✓ | ⚙️ | - | Validate JWT token structure and signature |
| `jwt_expiration` | ✓ | ✅ | `sendSignedChallengeTransaction` | JWT token includes expiration time |
| `jwt_claims` | ✓ | ✅ | `sendSignedChallengeTransaction` | JWT token includes required claims (sub, iat, exp) |

### Client Domain Features

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `client_domain_parameter` |  | ✅ | `getChallenge` | Support optional client_domain parameter in GET /auth |
| `client_domain_operation` |  | ✅ | `validateChallenge` | Add client_domain ManageData operation to challenge |
| `client_domain_verification` |  | ⚙️ | - | Verify client domain by checking stellar.toml |
| `client_domain_signature` |  | ✅ | `signTransaction` | Require signature from client domain account |

### Verification Features

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `challenge_validation` | ✓ | ✅ | `validateChallenge` | Validate challenge transaction structure and content |
| `signature_verification` | ✓ | ✅ | `validateChallenge` | Verify all signatures on challenge transaction |
| `multi_signature_support` | ✓ | ✅ | `signTransaction` | Support multiple signatures on challenge (client account + signers) |
| `timebounds_validation` | ✓ | ✅ | `validateChallenge` | Validate challenge is within valid time window |
| `home_domain_validation` | ✓ | ✅ | `validateChallenge` | Validate home domain in challenge matches server |
| `memo_support` |  | ✅ | `getChallenge` | Support optional memo in challenge for muxed accounts |

## Implementation Gaps

🎉 **No gaps found!** All client-side features are implemented.

## Recommendations

✅ The SDK has full compatibility with SEP-0010 for all client-side features!

## Legend

- ✅ **Implemented**: Feature is implemented in SDK
- ❌ **Not Implemented**: Feature is missing from SDK
- ⚙️ **Server-Side**: Feature is implemented on the authentication server, not in client SDKs
- ✓ **Required**: Feature is required by SEP specification
- (blank) **Optional**: Feature is optional
