# SEP-0047 (Contract Interface Discovery) Compatibility Matrix

**Generated:** 2026-02-10 12:45:17

**SEP Version:** 0.1.0

**SEP Status:** Draft

**SDK Version:** 1.9.3

**SEP URL:** https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0047.md

## SEP Summary

A standard for a contract to indicate which SEPs it claims to implement.

## Overall Coverage

**Total Coverage:** 100% (9/9 fields)

- ✅ **Implemented:** 9/9
- ❌ **Not Implemented:** 0/9

**Required Fields:** 100% (9/9)

## Implementation Status

✅ **Implemented**

### Implementation Files

- `Soneso/StellarSDK/Soroban/Contract/AssembledTransaction.php`
- `Soneso/StellarSDK/Soroban/Contract/ClientOptions.php`
- `Soneso/StellarSDK/Soroban/Contract/ContractSpec.php`
- `Soneso/StellarSDK/Soroban/Contract/SorobanClient.php`
- `Soneso/StellarSDK/Soroban/Contract/InstallRequest.php`
- `Soneso/StellarSDK/Soroban/Contract/NativeUnionVal.php`
- `Soneso/StellarSDK/Soroban/Contract/AssembledTransactionOptions.php`
- `Soneso/StellarSDK/Soroban/Contract/DeployRequest.php`
- `Soneso/StellarSDK/Soroban/Contract/SimulateHostFunctionResult.php`
- `Soneso/StellarSDK/Soroban/Contract/MethodOptions.php`
- `Soneso/StellarSDK/Soroban/Footprint.php`
- `Soneso/StellarSDK/Soroban/SorobanAuthorizedInvocation.php`
- `Soneso/StellarSDK/Soroban/Address.php`
- `Soneso/StellarSDK/Soroban/Responses/SimulateTransactionResponse.php`
- `Soneso/StellarSDK/Soroban/Responses/RestorePreamble.php`
- `Soneso/StellarSDK/Soroban/Responses/GetTransactionsResponse.php`
- `Soneso/StellarSDK/Soroban/Responses/GetHealthResponse.php`
- `Soneso/StellarSDK/Soroban/Responses/SimulateTransactionResult.php`
- `Soneso/StellarSDK/Soroban/Responses/GetFeeStatsResponse.php`
- `Soneso/StellarSDK/Soroban/Responses/GetVersionInfoResponse.php`
- `Soneso/StellarSDK/Soroban/Responses/GetTransactionResponse.php`
- `Soneso/StellarSDK/Soroban/Responses/LedgerEntryChange.php`
- `Soneso/StellarSDK/Soroban/Responses/SorobanRpcErrorResponse.php`
- `Soneso/StellarSDK/Soroban/Responses/TransactionEvents.php`
- `Soneso/StellarSDK/Soroban/Responses/GetNetworkResponse.php`
- `Soneso/StellarSDK/Soroban/Responses/GetLedgerEntriesResponse.php`
- `Soneso/StellarSDK/Soroban/Responses/InclusionFee.php`
- `Soneso/StellarSDK/Soroban/Responses/SorobanRpcResponse.php`
- `Soneso/StellarSDK/Soroban/Responses/GetEventsResponse.php`
- `Soneso/StellarSDK/Soroban/Responses/LedgerEntry.php`
- `Soneso/StellarSDK/Soroban/Responses/GetLatestLedgerResponse.php`
- `Soneso/StellarSDK/Soroban/Responses/LedgerInfo.php`
- `Soneso/StellarSDK/Soroban/Responses/EventInfo.php`
- `Soneso/StellarSDK/Soroban/Responses/SendTransactionResponse.php`
- `Soneso/StellarSDK/Soroban/Responses/SimulateTransactionResults.php`
- `Soneso/StellarSDK/Soroban/Responses/TransactionInfo.php`
- `Soneso/StellarSDK/Soroban/Responses/GetLedgersResponse.php`
- `Soneso/StellarSDK/Soroban/Exceptions/SorobanContractParserException.php`
- `Soneso/StellarSDK/Soroban/Requests/EventFilters.php`
- `Soneso/StellarSDK/Soroban/Requests/TopicFilter.php`
- `Soneso/StellarSDK/Soroban/Requests/GetEventsRequest.php`
- `Soneso/StellarSDK/Soroban/Requests/PaginationOptions.php`
- `Soneso/StellarSDK/Soroban/Requests/TopicFilters.php`
- `Soneso/StellarSDK/Soroban/Requests/GetTransactionsRequest.php`
- `Soneso/StellarSDK/Soroban/Requests/SimulateTransactionRequest.php`
- `Soneso/StellarSDK/Soroban/Requests/GetLedgersRequest.php`
- `Soneso/StellarSDK/Soroban/Requests/ResourceConfig.php`
- `Soneso/StellarSDK/Soroban/Requests/EventFilter.php`
- `Soneso/StellarSDK/Soroban/SorobanServer.php`
- `Soneso/StellarSDK/Soroban/SorobanContractInfo.php`
- `Soneso/StellarSDK/Soroban/SorobanAddressCredentials.php`
- `Soneso/StellarSDK/Soroban/SorobanAuthorizedFunction.php`
- `Soneso/StellarSDK/Soroban/SorobanCredentials.php`
- `Soneso/StellarSDK/Soroban/SorobanAuthorizationEntry.php`
- `Soneso/StellarSDK/Soroban/SorobanContractParser.php`
- `Soneso/StellarSDK/Soroban/AccountEd25519Signature.php`

### Key Classes

- **`AssembledTransaction`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`ClientOptions`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`ContractSpec`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`SorobanClient`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`InstallRequest`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`NativeUnionVal`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`AssembledTransactionOptions`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`DeployRequest`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`SimulateHostFunctionResult`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`MethodOptions`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`Footprint`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`SorobanAuthorizedInvocation`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`Address`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`SimulateTransactionResponse`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`RestorePreamble`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`GetTransactionsResponse`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`GetHealthResponse`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`SimulateTransactionResult`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`GetFeeStatsResponse`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`GetVersionInfoResponse`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`GetTransactionResponse`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`LedgerEntryChange`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`SorobanRpcErrorResponse`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`TransactionEvents`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`GetNetworkResponse`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`GetLedgerEntriesResponse`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`InclusionFee`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`SorobanRpcResponse`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`GetEventsResponse`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`LedgerEntry`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`GetLatestLedgerResponse`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`LedgerInfo`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`EventInfo`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`SendTransactionResponse`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`SimulateTransactionResults`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`TransactionInfo`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`GetLedgersResponse`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`SorobanContractParserException`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`EventFilters`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`TopicFilter`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`GetEventsRequest`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`PaginationOptions`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`TopicFilters`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`GetTransactionsRequest`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`SimulateTransactionRequest`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`GetLedgersRequest`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`ResourceConfig`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`EventFilter`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`SorobanServer`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`SorobanContractInfo`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`SorobanAddressCredentials`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`SorobanAuthorizedFunction`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`SorobanCredentials`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`SorobanAuthorizationEntry`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`SorobanContractParser`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
- **`AccountEd25519Signature`**: Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.

## Coverage by Section

| Section | Coverage | Required Coverage | Implemented | Total |
|---------|----------|-------------------|-------------|-------|
| SEP Declaration | 100% | 100% | 3 | 3 |
| Meta Entry Format | 100% | 100% | 3 | 3 |
| Implementation Support | 100% | 100% | 3 | 3 |

## Detailed Field Comparison

### SEP Declaration

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `sep_meta_key` | ✓ | ✅ | `parseSupportedSeps` | Support for "sep" meta entry key to indicate implemented SEPs |
| `comma_separated_list` | ✓ | ✅ | `parseSupportedSeps` | Parse comma-separated list of SEP numbers from meta value |
| `multiple_sep_entries` | ✓ | ✅ | `parseSupportedSeps` | Support for multiple "sep" meta entries with combined values |

### Meta Entry Format

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `sep_number_format` | ✓ | ✅ | `parseSupportedSeps` | Parse SEP numbers in various formats (e.g., "41", "0041", "SEP-41") |
| `whitespace_handling` | ✓ | ✅ | `parseSupportedSeps` | Trim whitespace from SEP numbers in comma-separated list |
| `empty_value_handling` | ✓ | ✅ | `parseSupportedSeps` | Handle empty or missing "sep" meta entries gracefully |

### Implementation Support

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `parse_supported_seps` | ✓ | ✅ | `parseSupportedSeps` | Parse and extract list of supported SEPs from contract metadata |
| `expose_supported_seps` | ✓ | ✅ | `supportedSeps` | Expose supportedSeps property on contract info object |
| `validate_sep_format` | ✓ | ✅ | `parseSupportedSeps` | Validate SEP number format and filter invalid entries |

## Implementation Gaps

🎉 **No gaps found!** All fields are implemented.

## Recommendations

✅ The SDK has full compatibility with SEP-0047!

## Legend

- ✅ **Implemented**: Field is implemented in SDK
- ❌ **Not Implemented**: Field is missing from SDK
- ✓ **Required**: Field is required by SEP specification
- (blank) **Optional**: Field is optional
