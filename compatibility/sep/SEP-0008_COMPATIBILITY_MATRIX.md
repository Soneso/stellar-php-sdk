# SEP-0008 (Regulated Assets) Compatibility Matrix

**Generated:** 2025-10-16 16:00:19

**SEP Version:** N/A
**SEP Status:** Active
**SEP URL:** https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0008.md

## SEP Summary

Regulated Assets are assets that require an issuer’s approval (or a delegated
third party’s approval, such as a licensed securities exchange) on a
per-transaction basis. It standardizes the identification of such assets as
well as defines the protocol for performing compliance checks and requesting
issuer approval.

## Overall Coverage

**Total Coverage:** 100% (31/31 features)

- ✅ **Implemented:** 31/31
- ❌ **Not Implemented:** 0/31

**Required Fields:** 100% (26/26)

**Optional Fields:** 100% (5/5)

## Implementation Status

✅ **Implemented**

### Implementation Files

- `Soneso/StellarSDK/SEP/RegulatedAssets/SEP08PostActionDone.php`
- `Soneso/StellarSDK/SEP/RegulatedAssets/SEP08PostTransactionRevised.php`
- `Soneso/StellarSDK/SEP/RegulatedAssets/SEP08IncompleteInitData.php`
- `Soneso/StellarSDK/SEP/RegulatedAssets/SEP08PostTransactionActionRequired.php`
- `Soneso/StellarSDK/SEP/RegulatedAssets/SEP08PostTransactionRejected.php`
- `Soneso/StellarSDK/SEP/RegulatedAssets/SEP08PostActionNextUrl.php`
- `Soneso/StellarSDK/SEP/RegulatedAssets/RegulatedAssetsService.php`
- `Soneso/StellarSDK/SEP/RegulatedAssets/SEP08PostTransactionResponse.php`
- `Soneso/StellarSDK/SEP/RegulatedAssets/RegulatedAsset.php`
- `Soneso/StellarSDK/SEP/RegulatedAssets/SEP08PostActionResponse.php`
- `Soneso/StellarSDK/SEP/RegulatedAssets/SEP08PostTransactionSuccess.php`
- `Soneso/StellarSDK/SEP/RegulatedAssets/SEP08InvalidPostActionResponse.php`
- `Soneso/StellarSDK/SEP/RegulatedAssets/SEP08InvalidPostTransactionResponse.php`
- `Soneso/StellarSDK/SEP/RegulatedAssets/SEP08PostTransactionPending.php`

### Key Classes

- **`SEP08PostActionDone`**
- **`SEP08PostTransactionRevised`**
- **`SEP08IncompleteInitData`**
- **`SEP08PostTransactionActionRequired`**
- **`SEP08PostTransactionRejected`**
- **`SEP08PostActionNextUrl`**
- **`RegulatedAssetsService`**
- **`SEP08PostTransactionResponse`**
- **`RegulatedAsset`**
- **`SEP08PostActionResponse`**
- **`SEP08PostTransactionSuccess`**
- **`SEP08InvalidPostActionResponse`**
- **`SEP08InvalidPostTransactionResponse`**
- **`SEP08PostTransactionPending`**

## Coverage by Section

| Section | Coverage | Required Coverage | Implemented | Total |
|---------|----------|-------------------|-------------|-------|
| Approval Endpoint | 100% | 100% | 7 | 7 |
| Stellar TOML Fields | 100% | 100% | 2 | 2 |
| Success Response Fields | 100% | 100% | 3 | 3 |
| Revised Response Fields | 100% | 100% | 3 | 3 |
| Pending Response Fields | 100% | 100% | 3 | 3 |
| Action Required Response Fields | 100% | 100% | 5 | 5 |
| Rejected Response Fields | 100% | 100% | 2 | 2 |
| Action URL Handling | 100% | 100% | 4 | 4 |
| Authorization Flags | 100% | 100% | 2 | 2 |

## Detailed Field Comparison

### Approval Endpoint

| Field | Required | Status | SDK Implementation | Description |
|-------|----------|--------|--------------------|-------------|
| `POST /tx_approve endpoint` | ✓ | ✅ | `RegulatedAssetsService::postTransaction()` | Accepts signed transaction for compliance checking and signing |
| `tx parameter` | ✓ | ✅ | `postTransaction($tx)` | Base64 encoded transaction envelope XDR signed by the user |
| `success status` | ✓ | ✅ | `SEP08PostTransactionSuccess` | Transaction is compliant and signed without revision |
| `revised status` | ✓ | ✅ | `SEP08PostTransactionRevised` | Transaction was revised to be made compliant |
| `pending status` | ✓ | ✅ | `SEP08PostTransactionPending` | Issuer could not determine approval status at this time |
| `action_required status` | ✓ | ✅ | `SEP08PostTransactionActionRequired` | User must complete an action before transaction can be approved |
| `rejected status` | ✓ | ✅ | `SEP08PostTransactionRejected` | Transaction is not compliant and could not be revised |

### Stellar TOML Fields

| Field | Required | Status | SDK Implementation | Description |
|-------|----------|--------|--------------------|-------------|
| `approval_server` | ✓ | ✅ | `RegulatedAsset::$approvalServer` | URL of the approval service that signs validated transactions |
| `approval_criteria` |  | ✅ | `RegulatedAsset::$approvalCriteria` | Human readable string explaining issuer requirements for approving transactions |

### Success Response Fields

| Field | Required | Status | SDK Implementation | Description |
|-------|----------|--------|--------------------|-------------|
| `status` | ✓ | ✅ | - | Must be "success" |
| `tx` | ✓ | ✅ | - | Transaction envelope XDR with issuer signature(s) added |
| `message` |  | ✅ | - | Human readable information to pass to user |

### Revised Response Fields

| Field | Required | Status | SDK Implementation | Description |
|-------|----------|--------|--------------------|-------------|
| `status` | ✓ | ✅ | - | Must be "revised" |
| `tx` | ✓ | ✅ | - | Revised compliant transaction envelope XDR signed by issuer |
| `message` | ✓ | ✅ | - | Explanation of modifications made to transaction |

### Pending Response Fields

| Field | Required | Status | SDK Implementation | Description |
|-------|----------|--------|--------------------|-------------|
| `status` | ✓ | ✅ | - | Must be "pending" |
| `timeout` | ✓ | ✅ | - | Milliseconds to wait before resubmitting (0 if unknown) |
| `message` |  | ✅ | - | Human readable information to pass to user |

### Action Required Response Fields

| Field | Required | Status | SDK Implementation | Description |
|-------|----------|--------|--------------------|-------------|
| `status` | ✓ | ✅ | - | Must be "action_required" |
| `message` | ✓ | ✅ | - | Information about the required action |
| `action_url` | ✓ | ✅ | - | URL where user can complete required actions |
| `action_method` |  | ✅ | - | GET or POST indicating request type (defaults to GET) |
| `action_fields` |  | ✅ | - | Array of SEP-9 fields client may provide to action_url |

### Rejected Response Fields

| Field | Required | Status | SDK Implementation | Description |
|-------|----------|--------|--------------------|-------------|
| `status` | ✓ | ✅ | - | Must be "rejected" |
| `error` | ✓ | ✅ | - | Explanation why transaction is not compliant |

### Action URL Handling

| Field | Required | Status | SDK Implementation | Description |
|-------|----------|--------|--------------------|-------------|
| `GET method support` | ✓ | ✅ | - | Support for GET requests to action URL |
| `POST method support` | ✓ | ✅ | - | Support for POST requests to action URL with action fields |
| `no_further_action response` | ✓ | ✅ | - | Action completed, transaction can be resubmitted |
| `follow_next_url response` | ✓ | ✅ | - | Additional action required at provided next URL |

### Authorization Flags

| Field | Required | Status | SDK Implementation | Description |
|-------|----------|--------|--------------------|-------------|
| `authorization_required` | ✓ | ✅ | `RegulatedAssetsService::authorizationRequired()` | Flag indicating issuer must authorize each trustline |
| `authorization_revocable` | ✓ | ✅ | `RegulatedAssetsService::authorizationRequired()` | Flag indicating issuer can revoke trustline authorization |

## Implementation Gaps

None - Full implementation achieved!

## Recommendations

✅ The SDK has full compatibility with SEP-0008!

## Legend

- ✅ **Implemented**: Field is implemented in SDK
- ❌ **Not Implemented**: Field is missing from SDK
- ✓ **Required**: Field is required by SEP specification
- (blank) **Optional**: Field is optional
