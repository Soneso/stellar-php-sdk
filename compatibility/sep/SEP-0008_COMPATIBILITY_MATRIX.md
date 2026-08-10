# SEP-08: Regulated Assets

**Status:** ✅ Supported  
**SDK Version:** 1.12.0  
**Generated:** 2026-08-10 18:43 UTC  
**Spec:** [https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0008.md](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0008.md)

## Overall Coverage

**Total Coverage:** 100.0% (20/20 fields)

- ✅ **Implemented:** 20/20
- ❌ **Not Implemented:** 0/20

## Coverage by Section

| Section | Coverage | Implemented | Total |
|---------|----------|-------------|-------|
| Service Methods | 100.0% | 4 | 4 |
| Regulated Asset Fields | 100.0% | 2 | 2 |
| Success Response Fields | 100.0% | 2 | 2 |
| Revised Response Fields | 100.0% | 2 | 2 |
| Pending Response Fields | 100.0% | 2 | 2 |
| Action Required Response Fields | 100.0% | 4 | 4 |
| Rejected Response Fields | 100.0% | 1 | 1 |
| Action Done Response | 100.0% | 1 | 1 |
| Action Next URL Response Fields | 100.0% | 2 | 2 |

## Service Methods

RegulatedAssetsService core methods

| Feature | Status | Notes |
|---------|--------|-------|
| `postTransaction` | ✅ Supported | `RegulatedAssetsService.postTransaction()` |
| `postAction` | ✅ Supported | `RegulatedAssetsService.postAction()` |
| `authorizationRequired` | ✅ Supported | `RegulatedAssetsService.authorizationRequired()` |
| `fromDomain` | ✅ Supported | `RegulatedAssetsService::fromDomain()` |

## Regulated Asset Fields

RegulatedAsset class properties

| Feature | Status | Notes |
|---------|--------|-------|
| `approval_server` | ✅ Supported | `Required. RegulatedAsset.$approvalServer` |
| `approval_criteria` | ✅ Supported | `RegulatedAsset.$approvalCriteria` |

## Success Response Fields

POST /tx_approve → status=success

| Feature | Status | Notes |
|---------|--------|-------|
| `tx` | ✅ Supported | `Required. SEP08PostTransactionSuccess.$tx` |
| `message` | ✅ Supported | `SEP08PostTransactionSuccess.$message` |

## Revised Response Fields

POST /tx_approve → status=revised

| Feature | Status | Notes |
|---------|--------|-------|
| `tx` | ✅ Supported | `Required. SEP08PostTransactionRevised.$tx` |
| `message` | ✅ Supported | `Required. SEP08PostTransactionRevised.$message` |

## Pending Response Fields

POST /tx_approve → status=pending

| Feature | Status | Notes |
|---------|--------|-------|
| `timeout` | ✅ Supported | `Required. SEP08PostTransactionPending.$timeout` |
| `message` | ✅ Supported | `SEP08PostTransactionPending.$message` |

## Action Required Response Fields

POST /tx_approve → status=action_required

| Feature | Status | Notes |
|---------|--------|-------|
| `message` | ✅ Supported | `Required. SEP08PostTransactionActionRequired.$message` |
| `action_url` | ✅ Supported | `Required. SEP08PostTransactionActionRequired.$actionUrl` |
| `action_method` | ✅ Supported | `SEP08PostTransactionActionRequired.$actionMethod` |
| `action_fields` | ✅ Supported | `SEP08PostTransactionActionRequired.$actionFields` |

## Rejected Response Fields

POST /tx_approve → status=rejected

| Feature | Status | Notes |
|---------|--------|-------|
| `error` | ✅ Supported | `Required. SEP08PostTransactionRejected.$error` |

## Action Done Response

POST action_url → result=no_further_action_required

| Feature | Status | Notes |
|---------|--------|-------|
| `SEP08PostActionDone` | ✅ Supported | `SEP08PostActionDone` |

## Action Next URL Response Fields

POST action_url → result=follow_next_url

| Feature | Status | Notes |
|---------|--------|-------|
| `next_url` | ✅ Supported | `Required. SEP08PostActionNextUrl.$nextUrl` |
| `message` | ✅ Supported | `SEP08PostActionNextUrl.$message` |
