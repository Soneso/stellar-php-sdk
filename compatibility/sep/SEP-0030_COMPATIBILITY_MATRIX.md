# SEP-0030 (Account Recovery: multi-party recovery of Stellar accounts) Compatibility Matrix

**Generated:** 2025-10-16 15:10:20

**SEP Version:** 0.8.1
**SEP Status:** Draft
**SEP URL:** https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0030.md

## SEP Summary

This protocol defines an API that enables an individual (e.g., a user or
wallet) to regain access to a Stellar account that it owns after the individual
has lost its private key without providing any third party control of the
account. Using this protocol, the user or wallet will preregister the account
and a phone number, email, or other form of authentication with one or more
servers implementing the protocol and add those servers as signers of the
account. If two or more servers are used with appropriate signer configuration
no individual server will have control of the account, but collectively, they
may help the individual recover access to the account. The protocol also
enables individuals to pass control of a Stellar account to another individual.

## Overall Coverage

**Total Coverage:** 100% (32/32 fields)

- ✅ **Implemented:** 32/32
- ❌ **Not Implemented:** 0/32

**Required Fields:** 100% (24/24)

**Optional Fields:** 100% (8/8)

## Implementation Status

✅ **Implemented**

### Implementation Files

- `Soneso/StellarSDK/SEP/Recovery/SEP30ResponseSigner.php`
- `Soneso/StellarSDK/SEP/Recovery/SEP30UnknownResponseException.php`
- `Soneso/StellarSDK/SEP/Recovery/SEP30Request.php`
- `Soneso/StellarSDK/SEP/Recovery/SEP30ConflictResponseException.php`
- `Soneso/StellarSDK/SEP/Recovery/RecoveryService.php`
- `Soneso/StellarSDK/SEP/Recovery/SEP30RequestIdentity.php`
- `Soneso/StellarSDK/SEP/Recovery/SEP30AccountsResponse.php`
- `Soneso/StellarSDK/SEP/Recovery/SEP30AccountResponse.php`
- `Soneso/StellarSDK/SEP/Recovery/SEP30NotFoundResponseException.php`
- `Soneso/StellarSDK/SEP/Recovery/SEP30AuthMethod.php`
- `Soneso/StellarSDK/SEP/Recovery/SEP30ResponseIdentity.php`
- `Soneso/StellarSDK/SEP/Recovery/SEP30UnauthorizedResponseException.php`
- `Soneso/StellarSDK/SEP/Recovery/SEP30SignatureResponse.php`
- `Soneso/StellarSDK/SEP/Recovery/SEP30BadRequestResponseException.php`

### Key Classes

- **`SEP30ResponseSigner`**: 
- **`SEP30UnknownResponseException`**: 
- **`SEP30Request`**: 
- **`SEP30ConflictResponseException`**: 
- **`RecoveryService`**: Implements SEP-0030 - Account Recovery: multi-party recovery of Stellar accounts.
See <a href="https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0030.md" target="_blank">Account Recovery: multi-party recovery of Stellar accounts.</a>
- **`SEP30RequestIdentity`**: 
- **`SEP30AccountsResponse`**: 
- **`SEP30AccountResponse`**: 
- **`SEP30NotFoundResponseException`**: 
- **`SEP30AuthMethod`**: 
- **`SEP30ResponseIdentity`**: 
- **`SEP30UnauthorizedResponseException`**: 
- **`SEP30SignatureResponse`**: 
- **`SEP30BadRequestResponseException`**: 

## Coverage by Section

| Section | Coverage | Required Coverage | Implemented | Total |
|---------|----------|-------------------|-------------|-------|
| API Endpoints | 100% | 100% | 6 | 6 |
| Request Fields | 100% | 100% | 7 | 7 |
| Response Fields | 100% | 100% | 9 | 9 |
| Error Codes | 100% | 0% | 4 | 4 |
| Recovery Features | 100% | 100% | 6 | 6 |

## Detailed Field Comparison

### API Endpoints

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `register_account` | ✓ | ✅ | `registerAccount` | POST /accounts/{address} - Register an account for recovery |
| `update_account` | ✓ | ✅ | `updateIdentitiesForAccount` | PUT /accounts/{address} - Update identities for an account |
| `get_account` | ✓ | ✅ | `accountDetails` | GET /accounts/{address} - Retrieve account details |
| `delete_account` | ✓ | ✅ | `deleteAccount` | DELETE /accounts/{address} - Delete account record |
| `list_accounts` | ✓ | ✅ | `accounts` | GET /accounts - List accessible accounts |
| `sign_transaction` | ✓ | ✅ | `signTransaction` | POST /accounts/{address}/sign/{signing-address} - Sign a transaction |

### Request Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `identities` | ✓ | ✅ | `identities` | Array of identity objects for account recovery |
| `role` | ✓ | ✅ | `role` | Role of the identity (owner or other) |
| `auth_methods` | ✓ | ✅ | `authMethods` | Array of authentication methods for the identity |
| `type` | ✓ | ✅ | `type` | Type of authentication method |
| `value` | ✓ | ✅ | `value` | Value of the authentication method (address, phone, email, etc.) |
| `transaction` | ✓ | ✅ | `signTransaction` | Base64-encoded XDR transaction envelope to sign |
| `after` |  | ✅ | `accounts` | Cursor for pagination in list accounts endpoint |

### Response Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `address` | ✓ | ✅ | `address` | Stellar address of the registered account |
| `identities` | ✓ | ✅ | `identities` | Array of registered identity objects |
| `signers` | ✓ | ✅ | `signers` | Array of signer objects for the account |
| `role` | ✓ | ✅ | `role` | Role of the identity in response |
| `authenticated` |  | ✅ | `authenticated` | Whether the identity has been authenticated |
| `key` | ✓ | ✅ | `key` | Public key of the signer |
| `signature` | ✓ | ✅ | `signature` | Base64-encoded signature of the transaction |
| `network_passphrase` | ✓ | ✅ | `networkPassphrase` | Network passphrase used for signing |
| `accounts` | ✓ | ✅ | `accounts` | Array of account objects in list response |

### Error Codes

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `Bad Request` |  | ✅ | `SEP30BadRequestResponseException` | Invalid request parameters or malformed data |
| `Unauthorized` |  | ✅ | `SEP30UnauthorizedResponseException` | Missing or invalid JWT token |
| `Not Found` |  | ✅ | `SEP30NotFoundResponseException` | Account or resource not found |
| `Conflict` |  | ✅ | `SEP30ConflictResponseException` | Account already exists or conflicting operation |

### Recovery Features

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `multi_party_recovery` | ✓ | ✅ | `signers` | Support for multi-server account recovery |
| `flexible_auth_methods` | ✓ | ✅ | `SEP30AuthMethod` | Support for multiple authentication method types |
| `transaction_signing` | ✓ | ✅ | `signTransaction` | Server-side transaction signing for recovery |
| `account_sharing` |  | ✅ | `role` | Support for shared account access |
| `identity_roles` | ✓ | ✅ | `role` | Support for owner and other identity roles |
| `pagination` |  | ✅ | `accounts` | Pagination support in list accounts endpoint |

## Implementation Gaps

🎉 **No gaps found!** All fields are implemented.

## Recommendations

✅ The SDK has full compatibility with SEP-0030!

## Legend

- ✅ **Implemented**: Field is implemented in SDK
- ❌ **Not Implemented**: Field is missing from SDK
- ✓ **Required**: Field is required by SEP specification
- (blank) **Optional**: Field is optional
