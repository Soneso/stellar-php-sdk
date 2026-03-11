# SEP-45: Stellar Web Authentication for Contract Accounts

**Status:** ✅ Supported  
**SDK Version:** 1.9.5  
**Generated:** 2026-03-11 21:41 UTC  
**Spec:** [https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md)

## Overall Coverage

**Total Coverage:** 100.0% (25/25 fields)

- ✅ **Implemented:** 25/25
- ❌ **Not Implemented:** 0/25

## Coverage by Section

| Section | Coverage | Implemented | Total |
|---------|----------|-------------|-------|
| Authentication Flow | 100.0% | 5 | 5 |
| Challenge Features | 100.0% | 6 | 6 |
| Challenge Validation | 100.0% | 12 | 12 |
| Response Models | 100.0% | 2 | 2 |

## Authentication Flow

Contract web authentication client methods

| Feature | Status | Notes |
|---------|--------|-------|
| `fromDomain` | ✅ Supported | `WebAuthForContracts.fromDomain()` |
| `jwtToken` | ✅ Supported | `WebAuthForContracts.jwtToken()` |
| `getChallenge` | ✅ Supported | `WebAuthForContracts.getChallenge()` |
| `sendSignedChallenge` | ✅ Supported | `WebAuthForContracts.sendSignedChallenge()` |
| `setUseFormUrlEncoded` | ✅ Supported | `WebAuthForContracts.setUseFormUrlEncoded()` |

## Challenge Features

SEP-45 challenge features supported via jwtToken() parameters

| Feature | Status | Notes |
|---------|--------|-------|
| `client_domain` | ✅ Supported | `WebAuthForContracts.jwtToken($clientDomain)` |
| `client_domain_signing` | ✅ Supported | `WebAuthForContracts.jwtToken($clientDomainKeyPair, $clientDomainSigningCallback)` |
| `multi_signer_support` | ✅ Supported | `WebAuthForContracts.jwtToken($signers)` |
| `signature_expiration_ledger` | ✅ Supported | `WebAuthForContracts.jwtToken($signatureExpirationLedger)` |
| `decodeAuthorizationEntries` | ✅ Supported | `WebAuthForContracts.decodeAuthorizationEntries()` |
| `signAuthorizationEntries` | ✅ Supported | `WebAuthForContracts.signAuthorizationEntries()` |

## Challenge Validation

Challenge validation checks (each error class = one validation)

| Feature | Status | Notes |
|---------|--------|-------|
| `contract_address_validation` | ✅ Supported | `ContractChallengeValidationErrorInvalidContractAddress` |
| `function_name_validation` | ✅ Supported | `ContractChallengeValidationErrorInvalidFunctionName` |
| `server_signature_validation` | ✅ Supported | `ContractChallengeValidationErrorInvalidServerSignature` |
| `home_domain_validation` | ✅ Supported | `ContractChallengeValidationErrorInvalidHomeDomain` |
| `web_auth_domain_validation` | ✅ Supported | `ContractChallengeValidationErrorInvalidWebAuthDomain` |
| `account_validation` | ✅ Supported | `ContractChallengeValidationErrorInvalidAccount` |
| `nonce_validation` | ✅ Supported | `ContractChallengeValidationErrorInvalidNonce` |
| `args_validation` | ✅ Supported | `ContractChallengeValidationErrorInvalidArgs` |
| `network_passphrase_validation` | ✅ Supported | `ContractChallengeValidationErrorInvalidNetworkPassphrase` |
| `sub_invocations_check` | ✅ Supported | `ContractChallengeValidationErrorSubInvocationsFound` |
| `server_entry_validation` | ✅ Supported | `ContractChallengeValidationErrorMissingServerEntry` |
| `client_entry_validation` | ✅ Supported | `ContractChallengeValidationErrorMissingClientEntry` |

## Response Models

Challenge and token response handling

| Feature | Status | Notes |
|---------|--------|-------|
| `ContractChallengeResponse` | ✅ Supported | `ContractChallengeResponse` |
| `SubmitContractChallengeResponse` | ✅ Supported | `SubmitContractChallengeResponse` |
