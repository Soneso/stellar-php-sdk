# SEP-09: Standard KYC Fields

**Status:** ✅ Supported  
**SDK Version:** 1.9.6  
**Generated:** 2026-04-03 21:56 UTC  
**Spec:** [https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0009.md](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0009.md)

## Overall Coverage

**Total Coverage:** 100.0% (76/76 fields)

- ✅ **Implemented:** 76/76
- ❌ **Not Implemented:** 0/76

## Coverage by Section

| Section | Coverage | Implemented | Total |
|---------|----------|-------------|-------|
| Natural Person Fields | 100.0% | 34 | 34 |
| Organization Fields | 100.0% | 17 | 17 |
| Financial Account Fields | 100.0% | 14 | 14 |
| Card Fields | 100.0% | 11 | 11 |

## Natural Person Fields

Natural person KYC field coverage

| Feature | Status | Notes |
|---------|--------|-------|
| `NaturalPersonKYCFields.lastName` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.firstName` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.additionalName` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.addressCountryCode` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.stateOrProvince` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.city` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.postalCode` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.address` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.mobileNumber` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.mobileNumberFormat` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.emailAddress` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.birthDate` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.birthPlace` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.birthCountryCode` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.taxId` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.taxIdName` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.occupation` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.employerName` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.employerAddress` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.languageCode` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.idType` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.idCountryCode` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.idIssueDate` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.idExpirationDate` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.idNumber` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.photoIdFront` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.photoIdBack` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.notaryApprovalOfPhotoId` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.ipAddress` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.photoProofResidence` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.sex` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.proofOfIncome` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.proofOfLiveness` | ✅ Supported | `NaturalPersonKYCFields` |
| `NaturalPersonKYCFields.referralId` | ✅ Supported | `NaturalPersonKYCFields` |

## Organization Fields

Organization KYC field coverage

| Feature | Status | Notes |
|---------|--------|-------|
| `OrganizationKYCFields.name` | ✅ Supported | `OrganizationKYCFields` |
| `OrganizationKYCFields.VATNumber` | ✅ Supported | `OrganizationKYCFields` |
| `OrganizationKYCFields.registrationNumber` | ✅ Supported | `OrganizationKYCFields` |
| `OrganizationKYCFields.registrationDate` | ✅ Supported | `OrganizationKYCFields` |
| `OrganizationKYCFields.registeredAddress` | ✅ Supported | `OrganizationKYCFields` |
| `OrganizationKYCFields.numberOfShareholders` | ✅ Supported | `OrganizationKYCFields` |
| `OrganizationKYCFields.shareholderName` | ✅ Supported | `OrganizationKYCFields` |
| `OrganizationKYCFields.addressCountryCode` | ✅ Supported | `OrganizationKYCFields` |
| `OrganizationKYCFields.stateOrProvince` | ✅ Supported | `OrganizationKYCFields` |
| `OrganizationKYCFields.city` | ✅ Supported | `OrganizationKYCFields` |
| `OrganizationKYCFields.postalCode` | ✅ Supported | `OrganizationKYCFields` |
| `OrganizationKYCFields.directorName` | ✅ Supported | `OrganizationKYCFields` |
| `OrganizationKYCFields.website` | ✅ Supported | `OrganizationKYCFields` |
| `OrganizationKYCFields.email` | ✅ Supported | `OrganizationKYCFields` |
| `OrganizationKYCFields.phone` | ✅ Supported | `OrganizationKYCFields` |
| `OrganizationKYCFields.photoIncorporationDoc` | ✅ Supported | `OrganizationKYCFields` |
| `OrganizationKYCFields.photoProofAddress` | ✅ Supported | `OrganizationKYCFields` |

## Financial Account Fields

Financial account KYC field coverage

| Feature | Status | Notes |
|---------|--------|-------|
| `FinancialAccountKYCFields.bankName` | ✅ Supported | `FinancialAccountKYCFields` |
| `FinancialAccountKYCFields.bankAccountType` | ✅ Supported | `FinancialAccountKYCFields` |
| `FinancialAccountKYCFields.bankAccountNumber` | ✅ Supported | `FinancialAccountKYCFields` |
| `FinancialAccountKYCFields.bankNumber` | ✅ Supported | `FinancialAccountKYCFields` |
| `FinancialAccountKYCFields.bankPhoneNumber` | ✅ Supported | `FinancialAccountKYCFields` |
| `FinancialAccountKYCFields.bankBranchNumber` | ✅ Supported | `FinancialAccountKYCFields` |
| `FinancialAccountKYCFields.externalTransferMemo` | ✅ Supported | `FinancialAccountKYCFields` |
| `FinancialAccountKYCFields.clabeNumber` | ✅ Supported | `FinancialAccountKYCFields` |
| `FinancialAccountKYCFields.cbuNumber` | ✅ Supported | `FinancialAccountKYCFields` |
| `FinancialAccountKYCFields.cbuAlias` | ✅ Supported | `FinancialAccountKYCFields` |
| `FinancialAccountKYCFields.mobileMoneyNumber` | ✅ Supported | `FinancialAccountKYCFields` |
| `FinancialAccountKYCFields.mobileMoneyProvider` | ✅ Supported | `FinancialAccountKYCFields` |
| `FinancialAccountKYCFields.cryptoAddress` | ✅ Supported | `FinancialAccountKYCFields` |
| `FinancialAccountKYCFields.cryptoMemo` | ✅ Supported | `FinancialAccountKYCFields` |

## Card Fields

Card KYC field coverage

| Feature | Status | Notes |
|---------|--------|-------|
| `CardKYCFields.number` | ✅ Supported | `CardKYCFields` |
| `CardKYCFields.expirationDate` | ✅ Supported | `CardKYCFields` |
| `CardKYCFields.cvc` | ✅ Supported | `CardKYCFields` |
| `CardKYCFields.holderName` | ✅ Supported | `CardKYCFields` |
| `CardKYCFields.network` | ✅ Supported | `CardKYCFields` |
| `CardKYCFields.postalCode` | ✅ Supported | `CardKYCFields` |
| `CardKYCFields.countryCode` | ✅ Supported | `CardKYCFields` |
| `CardKYCFields.stateOrProvince` | ✅ Supported | `CardKYCFields` |
| `CardKYCFields.city` | ✅ Supported | `CardKYCFields` |
| `CardKYCFields.address` | ✅ Supported | `CardKYCFields` |
| `CardKYCFields.token` | ✅ Supported | `CardKYCFields` |
