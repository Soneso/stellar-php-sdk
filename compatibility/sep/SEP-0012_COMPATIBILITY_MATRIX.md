# SEP-12: KYC API

**Status:** ✅ Supported  
**SDK Version:** 1.9.5  
**Generated:** 2026-03-11 21:41 UTC  
**Spec:** [https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md)

## Overall Coverage

**Total Coverage:** 100.0% (52/52 fields)

- ✅ **Implemented:** 52/52
- ❌ **Not Implemented:** 0/52

## Coverage by Section

| Section | Coverage | Implemented | Total |
|---------|----------|-------------|-------|
| Service Endpoints | 100.0% | 7 | 7 |
| GET /customer Request Parameters | 100.0% | 7 | 7 |
| GET /customer Response Fields | 100.0% | 5 | 5 |
| Field Object Fields | 100.0% | 4 | 4 |
| Provided Field Object Fields | 100.0% | 6 | 6 |
| PUT /customer Request Parameters | 100.0% | 9 | 9 |
| PUT /customer Response Fields | 100.0% | 1 | 1 |
| PUT /customer/callback Request Parameters | 100.0% | 5 | 5 |
| PUT /customer/verification Request Parameters | 100.0% | 2 | 2 |
| POST /customer/files Response Fields | 100.0% | 5 | 5 |
| GET /customer/files Response Fields | 100.0% | 1 | 1 |

## Service Endpoints

KYCService API methods

| Feature | Status | Notes |
|---------|--------|-------|
| `GET /customer` | ✅ Supported | `KYCService.getCustomerInfo()` |
| `PUT /customer` | ✅ Supported | `KYCService.putCustomerInfo()` |
| `PUT /customer/callback` | ✅ Supported | `KYCService.putCustomerCallback()` |
| `PUT /customer/verification` | ✅ Supported | `KYCService.putCustomerVerification()` |
| `DELETE /customer/:account` | ✅ Supported | `KYCService.deleteCustomer()` |
| `POST /customer/files` | ✅ Supported | `KYCService.postCustomerFile()` |
| `GET /customer/files` | ✅ Supported | `KYCService.getCustomerFiles()` |

## GET /customer Request Parameters

Parameters for GET /customer

| Feature | Status | Notes |
|---------|--------|-------|
| `id` | ✅ Supported | `GetCustomerInfoRequest.$id` |
| `account` | ✅ Supported | `GetCustomerInfoRequest.$account` |
| `memo` | ✅ Supported | `GetCustomerInfoRequest.$memo` |
| `memo_type` | ✅ Supported | `GetCustomerInfoRequest.$memoType` |
| `type` | ✅ Supported | `GetCustomerInfoRequest.$type` |
| `transaction_id` | ✅ Supported | `GetCustomerInfoRequest.$transactionId` |
| `lang` | ✅ Supported | `GetCustomerInfoRequest.$lang` |

## GET /customer Response Fields

Fields returned by GET /customer

| Feature | Status | Notes |
|---------|--------|-------|
| `id` | ✅ Supported | `GetCustomerInfoResponse.$id` |
| `status` | ✅ Supported | `Required. GetCustomerInfoResponse.$status` |
| `fields` | ✅ Supported | `GetCustomerInfoResponse.$fields` |
| `provided_fields` | ✅ Supported | `GetCustomerInfoResponse.$providedFields` |
| `message` | ✅ Supported | `GetCustomerInfoResponse.$message` |

## Field Object Fields

Properties of each required field entry

| Feature | Status | Notes |
|---------|--------|-------|
| `type` | ✅ Supported | `Required. GetCustomerInfoField.$type` |
| `description` | ✅ Supported | `GetCustomerInfoField.$description` |
| `choices` | ✅ Supported | `GetCustomerInfoField.$choices` |
| `optional` | ✅ Supported | `GetCustomerInfoField.$optional` |

## Provided Field Object Fields

Properties of each provided field entry

| Feature | Status | Notes |
|---------|--------|-------|
| `type` | ✅ Supported | `Required. GetCustomerInfoProvidedField.$type` |
| `description` | ✅ Supported | `GetCustomerInfoProvidedField.$description` |
| `choices` | ✅ Supported | `GetCustomerInfoProvidedField.$choices` |
| `optional` | ✅ Supported | `GetCustomerInfoProvidedField.$optional` |
| `status` | ✅ Supported | `GetCustomerInfoProvidedField.$status` |
| `error` | ✅ Supported | `GetCustomerInfoProvidedField.$error` |

## PUT /customer Request Parameters

Parameters for PUT /customer

| Feature | Status | Notes |
|---------|--------|-------|
| `id` | ✅ Supported | `PutCustomerInfoRequest.$id` |
| `account` | ✅ Supported | `PutCustomerInfoRequest.$account` |
| `memo` | ✅ Supported | `PutCustomerInfoRequest.$memo` |
| `memo_type` | ✅ Supported | `PutCustomerInfoRequest.$memoType` |
| `type` | ✅ Supported | `PutCustomerInfoRequest.$type` |
| `transaction_id` | ✅ Supported | `PutCustomerInfoRequest.$transactionId` |
| `kyc_fields` | ✅ Supported | `PutCustomerInfoRequest.$KYCFields` |
| `custom_fields` | ✅ Supported | `PutCustomerInfoRequest.$customFields` |
| `custom_files` | ✅ Supported | `PutCustomerInfoRequest.$customFiles` |

## PUT /customer Response Fields

Fields returned by PUT /customer

| Feature | Status | Notes |
|---------|--------|-------|
| `id` | ✅ Supported | `PutCustomerInfoResponse.getId()` |

## PUT /customer/callback Request Parameters

Parameters for PUT /customer/callback

| Feature | Status | Notes |
|---------|--------|-------|
| `url` | ✅ Supported | `PutCustomerCallbackRequest.$url` |
| `id` | ✅ Supported | `PutCustomerCallbackRequest.$id` |
| `account` | ✅ Supported | `PutCustomerCallbackRequest.$account` |
| `memo` | ✅ Supported | `PutCustomerCallbackRequest.$memo` |
| `memo_type` | ✅ Supported | `PutCustomerCallbackRequest.$memoType` |

## PUT /customer/verification Request Parameters

Parameters for PUT /customer/verification

| Feature | Status | Notes |
|---------|--------|-------|
| `id` | ✅ Supported | `PutCustomerVerificationRequest.$id` |
| `verification_fields` | ✅ Supported | `PutCustomerVerificationRequest.$verificationFields` |

## POST /customer/files Response Fields

Fields returned by POST /customer/files

| Feature | Status | Notes |
|---------|--------|-------|
| `file_id` | ✅ Supported | `Required. CustomerFileResponse.$fileId` |
| `content_type` | ✅ Supported | `Required. CustomerFileResponse.$contentType` |
| `size` | ✅ Supported | `Required. CustomerFileResponse.$size` |
| `expires_at` | ✅ Supported | `CustomerFileResponse.$expiresAt` |
| `customer_id` | ✅ Supported | `CustomerFileResponse.$customerId` |

## GET /customer/files Response Fields

Fields returned by GET /customer/files

| Feature | Status | Notes |
|---------|--------|-------|
| `files` | ✅ Supported | `Required. GetCustomerFilesResponse.$files` |
