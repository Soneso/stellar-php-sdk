# SEP-01: Stellar Info File

**Status:** ✅ Supported  
**SDK Version:** 1.9.6  
**Generated:** 2026-04-03 21:56 UTC  
**Spec:** [https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0001.md](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0001.md)

## Overall Coverage

**Total Coverage:** 100.0% (72/72 fields)

- ✅ **Implemented:** 72/72
- ❌ **Not Implemented:** 0/72

## Coverage by Section

| Section | Coverage | Implemented | Total |
|---------|----------|-------------|-------|
| Fetching | 100.0% | 2 | 2 |
| General Information | 100.0% | 16 | 16 |
| Documentation | 100.0% | 17 | 17 |
| Point of Contact | 100.0% | 8 | 8 |
| Currency | 100.0% | 24 | 24 |
| Validator | 100.0% | 5 | 5 |

## Fetching

Fetching stellar.toml files

| Feature | Status | Notes |
|---------|--------|-------|
| `StellarToml.fromDomain` | ✅ Supported | `StellarToml` |
| `StellarToml.currencyFromUrl` | ✅ Supported | `StellarToml` |

## General Information

GeneralInformation class fields

| Feature | Status | Notes |
|---------|--------|-------|
| `GeneralInformation.version` | ✅ Supported | `GeneralInformation` |
| `GeneralInformation.networkPassphrase` | ✅ Supported | `GeneralInformation` |
| `GeneralInformation.federationServer` | ✅ Supported | `GeneralInformation` |
| `GeneralInformation.authServer` | ✅ Supported | `GeneralInformation` |
| `GeneralInformation.transferServer` | ✅ Supported | `GeneralInformation` |
| `GeneralInformation.transferServerSep24` | ✅ Supported | `GeneralInformation` |
| `GeneralInformation.kYCServer` | ✅ Supported | `GeneralInformation` |
| `GeneralInformation.webAuthEndpoint` | ✅ Supported | `GeneralInformation` |
| `GeneralInformation.webAuthForContractsEndpoint` | ✅ Supported | `GeneralInformation` |
| `GeneralInformation.signingKey` | ✅ Supported | `GeneralInformation` |
| `GeneralInformation.horizonUrl` | ✅ Supported | `GeneralInformation` |
| `GeneralInformation.accounts` | ✅ Supported | `GeneralInformation` |
| `GeneralInformation.uriRequestSigningKey` | ✅ Supported | `GeneralInformation` |
| `GeneralInformation.directPaymentServer` | ✅ Supported | `GeneralInformation` |
| `GeneralInformation.anchorQuoteServer` | ✅ Supported | `GeneralInformation` |
| `GeneralInformation.webAuthContractId` | ✅ Supported | `GeneralInformation` |

## Documentation

Documentation class fields

| Feature | Status | Notes |
|---------|--------|-------|
| `Documentation.orgName` | ✅ Supported | `Documentation` |
| `Documentation.orgDBA` | ✅ Supported | `Documentation` |
| `Documentation.orgUrl` | ✅ Supported | `Documentation` |
| `Documentation.orgLogo` | ✅ Supported | `Documentation` |
| `Documentation.orgDescription` | ✅ Supported | `Documentation` |
| `Documentation.orgPhysicalAddress` | ✅ Supported | `Documentation` |
| `Documentation.orgPhysicalAddressAttestation` | ✅ Supported | `Documentation` |
| `Documentation.orgPhoneNumber` | ✅ Supported | `Documentation` |
| `Documentation.orgPhoneNumberAttestation` | ✅ Supported | `Documentation` |
| `Documentation.orgKeybase` | ✅ Supported | `Documentation` |
| `Documentation.orgTwitter` | ✅ Supported | `Documentation` |
| `Documentation.orgGithub` | ✅ Supported | `Documentation` |
| `Documentation.orgOfficialEmail` | ✅ Supported | `Documentation` |
| `Documentation.orgSupportEmail` | ✅ Supported | `Documentation` |
| `Documentation.orgLicensingAuthority` | ✅ Supported | `Documentation` |
| `Documentation.orgLicenseType` | ✅ Supported | `Documentation` |
| `Documentation.orgLicenseNumber` | ✅ Supported | `Documentation` |

## Point of Contact

PointOfContact class fields

| Feature | Status | Notes |
|---------|--------|-------|
| `PointOfContact.name` | ✅ Supported | `PointOfContact` |
| `PointOfContact.email` | ✅ Supported | `PointOfContact` |
| `PointOfContact.keybase` | ✅ Supported | `PointOfContact` |
| `PointOfContact.telegram` | ✅ Supported | `PointOfContact` |
| `PointOfContact.twitter` | ✅ Supported | `PointOfContact` |
| `PointOfContact.github` | ✅ Supported | `PointOfContact` |
| `PointOfContact.idPhotoHash` | ✅ Supported | `PointOfContact` |
| `PointOfContact.verificationPhotoHash` | ✅ Supported | `PointOfContact` |

## Currency

Currency class fields

| Feature | Status | Notes |
|---------|--------|-------|
| `Currency.code` | ✅ Supported | `Currency` |
| `Currency.codeTemplate` | ✅ Supported | `Currency` |
| `Currency.issuer` | ✅ Supported | `Currency` |
| `Currency.status` | ✅ Supported | `Currency` |
| `Currency.displayDecimals` | ✅ Supported | `Currency` |
| `Currency.name` | ✅ Supported | `Currency` |
| `Currency.desc` | ✅ Supported | `Currency` |
| `Currency.conditions` | ✅ Supported | `Currency` |
| `Currency.image` | ✅ Supported | `Currency` |
| `Currency.fixedNumber` | ✅ Supported | `Currency` |
| `Currency.maxNumber` | ✅ Supported | `Currency` |
| `Currency.isUnlimited` | ✅ Supported | `Currency` |
| `Currency.isAssetAnchored` | ✅ Supported | `Currency` |
| `Currency.anchorAssetType` | ✅ Supported | `Currency` |
| `Currency.anchorAsset` | ✅ Supported | `Currency` |
| `Currency.attestationOfReserve` | ✅ Supported | `Currency` |
| `Currency.redemptionInstructions` | ✅ Supported | `Currency` |
| `Currency.collateralAddresses` | ✅ Supported | `Currency` |
| `Currency.collateralAddressMessages` | ✅ Supported | `Currency` |
| `Currency.collateralAddressSignatures` | ✅ Supported | `Currency` |
| `Currency.regulated` | ✅ Supported | `Currency` |
| `Currency.approvalServer` | ✅ Supported | `Currency` |
| `Currency.approvalCriteria` | ✅ Supported | `Currency` |
| `Currency.contract` | ✅ Supported | `Currency` |

## Validator

Validator class fields

| Feature | Status | Notes |
|---------|--------|-------|
| `Validator.alias` | ✅ Supported | `Validator` |
| `Validator.displayName` | ✅ Supported | `Validator` |
| `Validator.publicKey` | ✅ Supported | `Validator` |
| `Validator.host` | ✅ Supported | `Validator` |
| `Validator.history` | ✅ Supported | `Validator` |
