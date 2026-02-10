# SEP-0012 (KYC API) Compatibility Matrix

**Generated:** 2026-02-10 12:43:35

**SEP Version:** 1.15.0

**SEP Status:** Active

**SDK Version:** 1.9.3

**SEP URL:** https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md

## SEP Summary

This SEP defines a standard way for stellar clients to upload KYC (or other)
information to anchors and other services. [SEP-6](sep-0006.md) and
[SEP-31](sep-0031.md) use this protocol, but it can serve as a stand-alone
service as well.

This SEP was made with these goals in mind:

- interoperability
- Allow a customer to enter their KYC information to their wallet once and use
  it across many services without re-entering information manually
- handle the most common 80% of use cases
- handle image and binary data
- support the set of fields defined in [SEP-9](sep-0009.md)
- support authentication via [SEP-10](sep-0010.md)
- support the provision of data for [SEP-6](sep-0006.md),
  [SEP-24](sep-0024.md), [SEP-31](sep-0031.md), and others
- give customers control over their data by supporting complete data erasure

To support this protocol an anchor acts as a server and implements the
specified REST API endpoints, while a wallet implements a client that consumes
the API. The goal is interoperability, so a wallet implements a single client
according to the protocol, and will be able to interact with any compliant
anchor. Similarly, an anchor that implements the API endpoints according to the
protocol will work with any compliant wallet.

## Overall Coverage

**Total Coverage:** 100% (28/28 fields)

- ✅ **Implemented:** 28/28
- ❌ **Not Implemented:** 0/28

**Required Fields:** 100% (13/13)

**Optional Fields:** 100% (15/15)

## Implementation Status

✅ **Implemented**

### Implementation Files

- `Soneso/StellarSDK/SEP/KYCService/PutCustomerInfoResponse.php`
- `Soneso/StellarSDK/SEP/KYCService/PutCustomerVerificationRequest.php`
- `Soneso/StellarSDK/SEP/KYCService/PutCustomerVerificationRequestBuilder.php`
- `Soneso/StellarSDK/SEP/KYCService/GetCustomerFilesResponse.php`
- `Soneso/StellarSDK/SEP/KYCService/GetCustomerInfoRequestBuilder.php`
- `Soneso/StellarSDK/SEP/KYCService/GetCustomerFilesRequestBuilder.php`
- `Soneso/StellarSDK/SEP/KYCService/KYCService.php`
- `Soneso/StellarSDK/SEP/KYCService/GetCustomerInfoRequest.php`
- `Soneso/StellarSDK/SEP/KYCService/PostCustomerFileRequestBuilder.php`
- `Soneso/StellarSDK/SEP/KYCService/PutCustomerInfoRequestBuilder.php`
- `Soneso/StellarSDK/SEP/KYCService/PutCustomerCallbackRequest.php`
- `Soneso/StellarSDK/SEP/KYCService/GetCustomerInfoResponse.php`
- `Soneso/StellarSDK/SEP/KYCService/PutCustomerInfoRequest.php`
- `Soneso/StellarSDK/SEP/KYCService/GetCustomerInfoProvidedField.php`
- `Soneso/StellarSDK/SEP/KYCService/CustomerFileResponse.php`
- `Soneso/StellarSDK/SEP/KYCService/GetCustomerInfoField.php`

### Key Classes

- **`PutCustomerInfoResponse`**
- **`PutCustomerVerificationRequest`**
- **`PutCustomerVerificationRequestBuilder`**
- **`GetCustomerFilesResponse`**
- **`GetCustomerInfoRequestBuilder`**
- **`GetCustomerFilesRequestBuilder`**
- **`KYCService`**
- **`GetCustomerInfoRequest`**
- **`PostCustomerFileRequestBuilder`**
- **`PutCustomerInfoRequestBuilder`**
- **`PutCustomerCallbackRequest`**
- **`GetCustomerInfoResponse`**
- **`PutCustomerInfoRequest`**
- **`GetCustomerInfoProvidedField`**
- **`CustomerFileResponse`**
- **`GetCustomerInfoField`**

## Coverage by Section

| Section | Coverage | Required Coverage | Implemented | Total |
|---------|----------|-------------------|-------------|-------|
| API Endpoints | 100% | 100% | 7 | 7 |
| Request Parameters | 100% | 0% | 7 | 7 |
| Response Fields | 100% | 100% | 5 | 5 |
| Field Type Specifications | 100% | 100% | 6 | 6 |
| Authentication | 100% | 100% | 1 | 1 |
| File Upload | 100% | 100% | 1 | 1 |
| SEP-9 Integration | 100% | 100% | 1 | 1 |

## Detailed Field Comparison

### API Endpoints

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `get_customer` | ✓ | ✅ | `getCustomerInfo` | GET /customer - Check the status of a customers info |
| `put_customer` | ✓ | ✅ | `putCustomerInfo` | PUT /customer - Upload customer information to an anchor |
| `put_customer_verification` | ✓ | ✅ | `putCustomerVerification` | PUT /customer/verification - Verify customer fields with confirmation codes |
| `delete_customer` | ✓ | ✅ | `deleteCustomer` | DELETE /customer/{account} - Delete all personal information about a customer |
| `put_customer_callback` | ✓ | ✅ | `putCustomerCallback` | PUT /customer/callback - Register a callback URL for customer status updates |
| `post_customer_files` | ✓ | ✅ | `postCustomerFile` | POST /customer/files - Upload binary files for customer KYC |
| `get_customer_files` | ✓ | ✅ | `getCustomerFiles` | GET /customer/files - Get metadata about uploaded files |

### Request Parameters

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `id` |  | ✅ | - | ID of the customer as returned in previous PUT request |
| `account` |  | ✅ | - | Stellar account ID (G...) of the customer |
| `memo` |  | ✅ | - | Memo that uniquely identifies a customer in shared accounts |
| `memo_type` |  | ✅ | - | Type of memo: text, id, or hash |
| `type` |  | ✅ | - | Type of action the customer is being KYCd for |
| `transaction_id` |  | ✅ | - | Transaction ID with which customer info is associated |
| `lang` |  | ✅ | - | Language code (ISO 639-1) for human-readable responses |

### Response Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `id` |  | ✅ | - | ID of the customer |
| `status` | ✓ | ✅ | - | Status of customer KYC process |
| `fields` |  | ✅ | - | Fields the anchor has not yet received |
| `provided_fields` |  | ✅ | - | Fields the anchor has received |
| `message` |  | ✅ | - | Human readable message describing KYC status |

### Field Type Specifications

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `type` | ✓ | ✅ | - | Data type of field value |
| `description` |  | ✅ | - | Human-readable description of the field |
| `choices` |  | ✅ | - | Array of valid values for this field |
| `optional` |  | ✅ | - | Whether this field is required to proceed |
| `status` | ✓ | ✅ | - | Status of provided field |
| `error` |  | ✅ | - | Description of why field was rejected |

### Authentication

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `jwt_authentication` | ✓ | ✅ | `JWT Token` | All endpoints require SEP-10 JWT authentication via Authorization header |

### File Upload

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `multipart_file_upload` | ✓ | ✅ | `multipart/form-data` | Binary files uploaded using multipart/form-data for photo_id, proof_of_address, etc. |

### SEP-9 Integration

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `standard_kyc_fields` | ✓ | ✅ | `StandardKYCFields` | Supports all SEP-9 standard KYC fields for natural persons and organizations |

## Implementation Gaps

🎉 **No gaps found!** All fields are implemented.

## Recommendations

✅ The SDK has full compatibility with SEP-0012!

## Legend

- ✅ **Implemented**: Field is implemented in SDK
- ❌ **Not Implemented**: Field is missing from SDK
- ✓ **Required**: Field is required by SEP specification
- (blank) **Optional**: Field is optional
