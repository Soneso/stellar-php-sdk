# SEP-0045 (Stellar Web Authentication for Contract Accounts) Compatibility Matrix

**Generated:** 2026-02-10 12:45:17

**SEP Version:** 0.1.1

**SEP Status:** Draft

**SDK Version:** 1.9.3

**SEP URL:** https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md

## SEP Summary

This SEP defines the standard way for clients such as wallets or exchanges to
create authenticated web sessions on behalf of a user who holds a contract
account. A wallet may want to authenticate with any web service which requires
a contract account ownership verification, for example, to upload KYC
information to an anchor in an authenticated way as described in
[SEP-12](sep-0012.md).

This SEP is based on [SEP-10](sep-0010.md), but does not replace it. This SEP
only supports `C` (contract) accounts. SEP-10 only supports `G` and `M`
accounts. Services wishing to support all accounts should implement both SEPs.

## Overall Coverage

**Total Coverage:** 100% (41/41 features)

- ✅ **Implemented:** 41/41
- ❌ **Not Implemented:** 0/41

_Note: Excludes 1 server-side-only feature(s) not applicable to client SDKs_

**Required Fields:** 100% (30/30)

**Optional Fields:** 100% (11/11)

## Implementation Status

✅ **Implemented**

### Implementation Files

- `Soneso/StellarSDK/SEP/WebAuthForContracts/SubmitContractChallengeTimeoutResponseException.php`
- `Soneso/StellarSDK/SEP/WebAuthForContracts/ContractChallengeValidationErrorInvalidAccount.php`
- `Soneso/StellarSDK/SEP/WebAuthForContracts/WebAuthForContracts.php`
- `Soneso/StellarSDK/SEP/WebAuthForContracts/ContractChallengeValidationErrorInvalidServerSignature.php`
- `Soneso/StellarSDK/SEP/WebAuthForContracts/ContractChallengeRequestErrorResponse.php`
- `Soneso/StellarSDK/SEP/WebAuthForContracts/ContractChallengeValidationErrorMissingClientEntry.php`
- `Soneso/StellarSDK/SEP/WebAuthForContracts/SubmitContractChallengeErrorResponseException.php`
- `Soneso/StellarSDK/SEP/WebAuthForContracts/ContractChallengeValidationErrorInvalidWebAuthDomain.php`
- `Soneso/StellarSDK/SEP/WebAuthForContracts/ContractChallengeValidationErrorInvalidArgs.php`
- `Soneso/StellarSDK/SEP/WebAuthForContracts/ContractChallengeValidationErrorInvalidNetworkPassphrase.php`
- `Soneso/StellarSDK/SEP/WebAuthForContracts/ContractChallengeResponse.php`
- `Soneso/StellarSDK/SEP/WebAuthForContracts/ContractChallengeValidationErrorInvalidFunctionName.php`
- `Soneso/StellarSDK/SEP/WebAuthForContracts/ContractChallengeValidationErrorInvalidHomeDomain.php`
- `Soneso/StellarSDK/SEP/WebAuthForContracts/ContractChallengeValidationErrorSubInvocationsFound.php`
- `Soneso/StellarSDK/SEP/WebAuthForContracts/ContractChallengeValidationError.php`
- `Soneso/StellarSDK/SEP/WebAuthForContracts/ContractChallengeRequestBuilder.php`
- `Soneso/StellarSDK/SEP/WebAuthForContracts/ContractChallengeValidationErrorInvalidNonce.php`
- `Soneso/StellarSDK/SEP/WebAuthForContracts/SubmitContractChallengeResponse.php`
- `Soneso/StellarSDK/SEP/WebAuthForContracts/ContractChallengeValidationErrorMissingServerEntry.php`
- `Soneso/StellarSDK/SEP/WebAuthForContracts/SubmitContractChallengeUnknownResponseException.php`
- `Soneso/StellarSDK/SEP/WebAuthForContracts/ContractChallengeValidationErrorInvalidContractAddress.php`

### Key Classes

- **`SubmitContractChallengeTimeoutResponseException`**
- **`ContractChallengeValidationErrorInvalidAccount`**
- **`WebAuthForContracts`**
- **`ContractChallengeValidationErrorInvalidServerSignature`**
- **`ContractChallengeRequestErrorResponse`**
- **`ContractChallengeValidationErrorMissingClientEntry`**
- **`SubmitContractChallengeErrorResponseException`**
- **`ContractChallengeValidationErrorInvalidWebAuthDomain`**
- **`ContractChallengeValidationErrorInvalidArgs`**
- **`ContractChallengeValidationErrorInvalidNetworkPassphrase`**
- **`ContractChallengeResponse`**
- **`ContractChallengeValidationErrorInvalidFunctionName`**
- **`ContractChallengeValidationErrorInvalidHomeDomain`**
- **`ContractChallengeValidationErrorSubInvocationsFound`**
- **`ContractChallengeValidationError`**
- **`ContractChallengeRequestBuilder`**
- **`ContractChallengeValidationErrorInvalidNonce`**
- **`SubmitContractChallengeResponse`**
- **`ContractChallengeValidationErrorMissingServerEntry`**
- **`SubmitContractChallengeUnknownResponseException`**
- **`ContractChallengeValidationErrorInvalidContractAddress`**

## Coverage by Section

| Section | Coverage | Required Coverage | Implemented | Total |
|---------|----------|-------------------|-------------|-------|
| Authentication Endpoints | 100% | 100% | 3 | 3 |
| Challenge Features | 100% | 100% | 6 | 6 |
| Signature Features | 100% | 100% | 5 | 5 |
| Client Domain Features | 100% | 100% | 5 | 5 |
| Validation Features | 100% | 100% | 9 | 9 |
| JWT Token Features | 100% | 100% | 5 | 5 |
| Exception Types | 100% | 100% | 8 | 8 |

## Detailed Field Comparison

### Authentication Endpoints

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `get_auth_challenge` | ✓ | ✅ | `getChallenge` | GET /auth endpoint - Returns authorization entries for contract accounts |
| `post_auth_token` | ✓ | ✅ | `sendSignedChallenge` | POST /auth endpoint - Validates signed authorization entries and returns JWT token |
| `stellar_toml_discovery` | ✓ | ✅ | `fromDomain` | Automatic discovery of WEB_AUTH_FOR_CONTRACTS_ENDPOINT and WEB_AUTH_CONTRACT_ID from stellar.toml |

### Challenge Features

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `authorization_entry_decoding` | ✓ | ✅ | `decodeAuthorizationEntries` | Decode base64 XDR encoded authorization entries from server |
| `authorization_entry_encoding` | ✓ | ✅ | `sendSignedChallenge` | Encode signed authorization entries to base64 XDR for submission |
| `contract_invocation_parsing` | ✓ | ✅ | `validateChallenge` | Parse web_auth_verify contract invocation from authorization entries |
| `args_map_parsing` | ✓ | ✅ | `extractArgsFromEntry` | Parse args map containing account, home_domain, web_auth_domain, nonce |
| `nonce_consistency` | ✓ | ✅ | `validateChallenge` | Verify nonce is consistent across all authorization entries |
| `network_passphrase_validation` |  | ✅ | `jwtToken` | Validate network_passphrase if provided by server |

### Signature Features

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `client_entry_signing` | ✓ | ✅ | `signAuthorizationEntries` | Sign client authorization entry with provided signers |
| `multi_signer_support` | ✓ | ✅ | `jwtToken` | Support multiple signers for multi-sig contracts |
| `empty_signers_support` |  | ✅ | `jwtToken` | Support empty signers array for contracts without signature requirements |
| `signature_expiration_ledger` | ✓ | ✅ | `signAuthorizationEntries` | Set signature expiration ledger in credentials for replay protection |
| `auto_expiration_ledger` |  | ✅ | `jwtToken` | Auto-fill signature expiration ledger from Soroban RPC (current + 10) |

### Client Domain Features

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `client_domain_parameter` |  | ✅ | `getChallenge` | Support optional client_domain parameter in GET /auth |
| `client_domain_entry` |  | ✅ | `validateChallenge` | Handle client domain authorization entry in challenge |
| `client_domain_local_signing` |  | ✅ | `signAuthorizationEntries` | Sign client domain entry with local keypair |
| `client_domain_callback_signing` |  | ✅ | `signAuthorizationEntries` | Sign client domain entry via remote callback |
| `client_domain_toml_lookup` |  | ✅ | `jwtToken` | Lookup client domain signing key from stellar.toml |

### Validation Features

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `server_entry_validation` | ✓ | ✅ | `validateChallenge` | Validate server authorization entry exists |
| `client_entry_validation` | ✓ | ✅ | `validateChallenge` | Validate client authorization entry exists |
| `server_signature_verification` | ✓ | ✅ | `verifyServerSignature` | Verify server signature on authorization entry using SIGNING_KEY |
| `contract_address_validation` | ✓ | ✅ | `validateChallenge` | Validate contract address matches WEB_AUTH_CONTRACT_ID from stellar.toml |
| `function_name_validation` | ✓ | ✅ | `validateChallenge` | Validate function name is web_auth_verify |
| `sub_invocations_check` | ✓ | ✅ | `validateChallenge` | Reject authorization entries with sub-invocations |
| `home_domain_validation` | ✓ | ✅ | `validateChallenge` | Validate home_domain argument matches expected domain |
| `web_auth_domain_validation` | ✓ | ✅ | `validateChallenge` | Validate web_auth_domain argument matches server domain |
| `account_validation` | ✓ | ✅ | `validateChallenge` | Validate account argument matches client contract account |

### JWT Token Features

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `jwt_token_response` | ✓ | ✅ | `sendSignedChallenge` | Parse JWT token from server response |
| `complete_auth_flow` | ✓ | ✅ | `jwtToken` | Execute complete authentication flow via jwtToken method |
| `form_urlencoded_support` |  | ✅ | `setUseFormUrlEncoded` | Support application/x-www-form-urlencoded for POST request |
| `json_content_support` |  | ✅ | `sendSignedChallenge` | Support application/json for POST request |
| `timeout_handling` |  | ✅ | `SubmitContractChallengeTimeoutResponseException` | Handle HTTP 504 timeout responses |
| `jwt_token_generation` | ✓ | ⚙️ Server | N/A | Generate JWT token after successful challenge validation |

### Exception Types

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `invalid_contract_address_exception` | ✓ | ✅ | `ContractChallengeValidationErrorInvalidContractAddress` | Exception for contract address mismatch |
| `invalid_function_name_exception` | ✓ | ✅ | `ContractChallengeValidationErrorInvalidFunctionName` | Exception for invalid function name |
| `invalid_server_signature_exception` | ✓ | ✅ | `ContractChallengeValidationErrorInvalidServerSignature` | Exception for invalid server signature |
| `sub_invocations_exception` | ✓ | ✅ | `ContractChallengeValidationErrorSubInvocationsFound` | Exception when sub-invocations found |
| `missing_server_entry_exception` | ✓ | ✅ | `ContractChallengeValidationErrorMissingServerEntry` | Exception when server entry is missing |
| `missing_client_entry_exception` | ✓ | ✅ | `ContractChallengeValidationErrorMissingClientEntry` | Exception when client entry is missing |
| `challenge_request_error_exception` | ✓ | ✅ | `ContractChallengeRequestErrorResponse` | Exception for challenge request errors |
| `submit_challenge_error_exception` | ✓ | ✅ | `SubmitContractChallengeErrorResponseException` | Exception for challenge submission errors |

## Implementation Gaps

🎉 **No gaps found!** All fields are implemented.

## Recommendations

✅ The SDK has full compatibility with SEP-0045!

## Legend

- ✅ **Implemented**: Field is implemented in SDK
- ❌ **Not Implemented**: Field is missing from SDK
- ⚙️ **Server**: Server-side only feature (not applicable to client SDKs)
- ✓ **Required**: Field is required by SEP specification
- (blank) **Optional**: Field is optional

**Note:** Excludes 1 server-side-only feature(s) not applicable to client SDKs
