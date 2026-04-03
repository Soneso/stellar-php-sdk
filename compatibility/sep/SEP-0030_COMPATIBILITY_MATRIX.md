# SEP-30: Account Recovery: multi-party recovery of Stellar accounts

**Status:** ✅ Supported  
**SDK Version:** 1.9.6  
**Generated:** 2026-04-03 21:56 UTC  
**Spec:** [https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0030.md](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0030.md)

## Overall Coverage

**Total Coverage:** 100.0% (20/20 fields)

- ✅ **Implemented:** 20/20
- ❌ **Not Implemented:** 0/20

## Coverage by Section

| Section | Coverage | Implemented | Total |
|---------|----------|-------------|-------|
| Service Endpoints | 100.0% | 6 | 6 |
| Request Fields | 100.0% | 1 | 1 |
| Request Identity Fields | 100.0% | 2 | 2 |
| Auth Method Fields | 100.0% | 2 | 2 |
| Account Response Fields | 100.0% | 3 | 3 |
| Response Identity Fields | 100.0% | 2 | 2 |
| Response Signer Fields | 100.0% | 1 | 1 |
| Signature Response Fields | 100.0% | 2 | 2 |
| Accounts List Response Fields | 100.0% | 1 | 1 |

## Service Endpoints

RecoveryService API methods

| Feature | Status | Notes |
|---------|--------|-------|
| `POST /accounts/:address` | ✅ Supported | `RecoveryService.registerAccount()` |
| `PUT /accounts/:address` | ✅ Supported | `RecoveryService.updateIdentitiesForAccount()` |
| `POST /accounts/:address/sign/:signing_address` | ✅ Supported | `RecoveryService.signTransaction()` |
| `GET /accounts/:address` | ✅ Supported | `RecoveryService.accountDetails()` |
| `DELETE /accounts/:address` | ✅ Supported | `RecoveryService.deleteAccount()` |
| `GET /accounts` | ✅ Supported | `RecoveryService.accounts()` |

## Request Fields

SEP30Request properties

| Feature | Status | Notes |
|---------|--------|-------|
| `identities` | ✅ Supported | `Required. SEP30Request.$identities` |

## Request Identity Fields

SEP30RequestIdentity properties

| Feature | Status | Notes |
|---------|--------|-------|
| `role` | ✅ Supported | `Required. SEP30RequestIdentity.$role` |
| `auth_methods` | ✅ Supported | `Required. SEP30RequestIdentity.$authMethods` |

## Auth Method Fields

SEP30AuthMethod properties

| Feature | Status | Notes |
|---------|--------|-------|
| `type` | ✅ Supported | `Required. SEP30AuthMethod.$type` |
| `value` | ✅ Supported | `Required. SEP30AuthMethod.$value` |

## Account Response Fields

SEP30AccountResponse properties

| Feature | Status | Notes |
|---------|--------|-------|
| `address` | ✅ Supported | `Required. SEP30AccountResponse.$address` |
| `identities` | ✅ Supported | `Required. SEP30AccountResponse.$identities` |
| `signers` | ✅ Supported | `Required. SEP30AccountResponse.$signers` |

## Response Identity Fields

SEP30ResponseIdentity properties

| Feature | Status | Notes |
|---------|--------|-------|
| `role` | ✅ Supported | `SEP30ResponseIdentity.$role` |
| `authenticated` | ✅ Supported | `SEP30ResponseIdentity.$authenticated` |

## Response Signer Fields

SEP30ResponseSigner properties

| Feature | Status | Notes |
|---------|--------|-------|
| `key` | ✅ Supported | `Required. SEP30ResponseSigner.$key` |

## Signature Response Fields

SEP30SignatureResponse properties

| Feature | Status | Notes |
|---------|--------|-------|
| `signature` | ✅ Supported | `Required. SEP30SignatureResponse.$signature` |
| `network_passphrase` | ✅ Supported | `Required. SEP30SignatureResponse.$networkPassphrase` |

## Accounts List Response Fields

SEP30AccountsResponse properties

| Feature | Status | Notes |
|---------|--------|-------|
| `accounts` | ✅ Supported | `Required. SEP30AccountsResponse.$accounts` |
