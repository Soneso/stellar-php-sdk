# SEP-0009 (Standard KYC Fields) Compatibility Matrix

**Generated:** 2026-01-06 16:36:05

**SEP Version:** N/A

**SEP Status:** Active

**SDK Version:** 1.9.1

**SEP URL:** https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0009.md

## SEP Summary

This SEP defines a list of standard KYC, AML, and financial account-related
fields for use in Stellar ecosystem protocols. Applications on Stellar should
use these fields when sending or requesting KYC, AML, or financial
account-related information with other parties on Stellar. This is an evolving
list, so please suggest any missing fields that you use.

This is a list of possible fields that may be necessary to handle many
different use cases, there is no expectation that any particular fields be used
for a particular application. The best fields to use in a particular case is
determined by the needs of the application.

## Overall Coverage

**Total Coverage:** 100% (76/76 fields)

- ✅ **Implemented:** 76/76
- ❌ **Not Implemented:** 0/76

**Note:** All SEP-09 KYC fields are optional. Applications should use the fields relevant to their use case.

## Implementation Status

✅ **Implemented**

### Implementation Files

- `Soneso/StellarSDK/SEP/StandardKYCFields/OrganizationKYCFields.php`
- `Soneso/StellarSDK/SEP/StandardKYCFields/FinancialAccountKYCFields.php`
- `Soneso/StellarSDK/SEP/StandardKYCFields/CardKYCFields.php`
- `Soneso/StellarSDK/SEP/StandardKYCFields/StandardKYCFields.php`
- `Soneso/StellarSDK/SEP/StandardKYCFields/NaturalPersonKYCFields.php`

### Key Classes

- **`OrganizationKYCFields`**: KYC and AML fields for organizations (companies, legal entities).
This class provides standardized f
- **`FinancialAccountKYCFields`**: KYC fields for financial accounts (bank accounts, crypto addresses, mobile money).
This class provid
- **`CardKYCFields`**: KYC fields for payment cards (credit/debit card information).
This class provides standardized field
- **`StandardKYCFields`**: Container class for standard KYC and AML fields used in Stellar ecosystem protocols.
This class prov
- **`NaturalPersonKYCFields`**: KYC and AML fields for natural persons (individual customers).
This class provides standardized fiel

## Coverage by Section

| Section | Coverage | Implemented | Total |
|---------|----------|-------------|-------|
| Natural Person Fields | 100% | 34 | 34 |
| Organization Fields | 100% | 17 | 17 |
| Financial Account Fields | 100% | 14 | 14 |
| Card Fields | 100% | 11 | 11 |

## Detailed Field Comparison

### Natural Person Fields

| Field | Status | SDK Class | SDK Property | Description |
|-------|--------|-----------|--------------|-------------|
| `additional_name` | ✅ | `NaturalPersonKYCFields` | `additionalName` | Middle name or other additional name |
| `address` | ✅ | `NaturalPersonKYCFields` | `address` | Entire address (country, state, postal code, street address, etc.) as a multi-line string |
| `address_country_code` | ✅ | `NaturalPersonKYCFields` | `addressCountryCode` | Country code for current address |
| `birth_country_code` | ✅ | `NaturalPersonKYCFields` | `birthCountryCode` | ISO Code of country of birth (ISO 3166-1 alpha-3) |
| `birth_date` | ✅ | `NaturalPersonKYCFields` | `birthDate` | Date of birth (e.g., 1976-07-04) |
| `birth_place` | ✅ | `NaturalPersonKYCFields` | `birthPlace` | Place of birth (city, state, country; as on passport) |
| `city` | ✅ | `NaturalPersonKYCFields` | `city` | Name of city/town |
| `email_address` | ✅ | `NaturalPersonKYCFields` | `emailAddress` | Email address |
| `employer_address` | ✅ | `NaturalPersonKYCFields` | `employerAddress` | Address of employer |
| `employer_name` | ✅ | `NaturalPersonKYCFields` | `employerName` | Name of employer |
| `first_name` | ✅ | `NaturalPersonKYCFields` | `firstName` | Given or first name |
| `id_country_code` | ✅ | `NaturalPersonKYCFields` | `idCountryCode` | Country issuing passport or photo ID (ISO 3166-1 alpha-3) |
| `id_expiration_date` | ✅ | `NaturalPersonKYCFields` | `idExpirationDate` | ID expiration date |
| `id_issue_date` | ✅ | `NaturalPersonKYCFields` | `idIssueDate` | ID issue date |
| `id_number` | ✅ | `NaturalPersonKYCFields` | `idNumber` | Passport or ID number |
| `id_type` | ✅ | `NaturalPersonKYCFields` | `idType` | Type of ID (passport, drivers_license, id_card, etc.) |
| `ip_address` | ✅ | `NaturalPersonKYCFields` | `ipAddress` | IP address of customer's computer |
| `language_code` | ✅ | `NaturalPersonKYCFields` | `languageCode` | Primary language (ISO 639-1) |
| `last_name` | ✅ | `NaturalPersonKYCFields` | `lastName` | Family or last name |
| `mobile_number` | ✅ | `NaturalPersonKYCFields` | `mobileNumber` | Mobile phone number with country code, in E.164 format |
| `mobile_number_format` | ✅ | `NaturalPersonKYCFields` | `mobileNumberFormat` | Expected format of the mobile_number field (E.164, hash, etc.) |
| `notary_approval_of_photo_id` | ✅ | `NaturalPersonKYCFields` | `notaryApprovalOfPhotoId` | Image of notary's approval of photo ID or passport |
| `occupation` | ✅ | `NaturalPersonKYCFields` | `occupation` | Occupation ISCO code |
| `photo_id_back` | ✅ | `NaturalPersonKYCFields` | `photoIdBack` | Image of back of user's photo ID or passport |
| `photo_id_front` | ✅ | `NaturalPersonKYCFields` | `photoIdFront` | Image of front of user's photo ID or passport |
| `photo_proof_residence` | ✅ | `NaturalPersonKYCFields` | `photoProofResidence` | Image of a utility bill, bank statement or similar with the user's name and address |
| `postal_code` | ✅ | `NaturalPersonKYCFields` | `postalCode` | Postal or other code identifying user's locale |
| `proof_of_income` | ✅ | `NaturalPersonKYCFields` | `proofOfIncome` | Image of user's proof of income document |
| `proof_of_liveness` | ✅ | `NaturalPersonKYCFields` | `proofOfLiveness` | Video or image file of user as a liveness proof |
| `referral_id` | ✅ | `NaturalPersonKYCFields` | `referralId` | User's origin (such as an id in another application) or a referral code |
| `sex` | ✅ | `NaturalPersonKYCFields` | `sex` | Gender (male, female, or other) |
| `state_or_province` | ✅ | `NaturalPersonKYCFields` | `stateOrProvince` | Name of state/province/region/prefecture |
| `tax_id` | ✅ | `NaturalPersonKYCFields` | `taxId` | Tax identifier of user in their country (social security number in US) |
| `tax_id_name` | ✅ | `NaturalPersonKYCFields` | `taxIdName` | Name of the tax ID (SSN or ITIN in the US) |

### Organization Fields

| Field | Status | SDK Class | SDK Property | Description |
|-------|--------|-----------|--------------|-------------|
| `organization.VAT_number` | ✅ | `OrganizationKYCFields` | `VATNumber` | Organization VAT number |
| `organization.address_country_code` | ✅ | `OrganizationKYCFields` | `addressCountryCode` | Country code for current address |
| `organization.city` | ✅ | `OrganizationKYCFields` | `city` | Name of city/town |
| `organization.director_name` | ✅ | `OrganizationKYCFields` | `directorName` | Organization registered managing director |
| `organization.email` | ✅ | `OrganizationKYCFields` | `email` | Organization contact email |
| `organization.name` | ✅ | `OrganizationKYCFields` | `name` | Full organization name as on the incorporation papers |
| `organization.number_of_shareholders` | ✅ | `OrganizationKYCFields` | `numberOfShareholders` | Organization shareholder number |
| `organization.phone` | ✅ | `OrganizationKYCFields` | `phone` | Organization contact phone |
| `organization.photo_incorporation_doc` | ✅ | `OrganizationKYCFields` | `photoIncorporationDoc` | Image of incorporation documents |
| `organization.photo_proof_address` | ✅ | `OrganizationKYCFields` | `photoProofAddress` | Image of a utility bill, bank statement with the organization's name and address |
| `organization.postal_code` | ✅ | `OrganizationKYCFields` | `postalCode` | Postal or other code identifying organization's locale |
| `organization.registered_address` | ✅ | `OrganizationKYCFields` | `registeredAddress` | Organization registered address |
| `organization.registration_date` | ✅ | `OrganizationKYCFields` | `registrationDate` | Date the organization was registered |
| `organization.registration_number` | ✅ | `OrganizationKYCFields` | `registrationNumber` | Organization registration number |
| `organization.shareholder_name` | ✅ | `OrganizationKYCFields` | `shareholderName` | Name of shareholder (can be organization or person) |
| `organization.state_or_province` | ✅ | `OrganizationKYCFields` | `stateOrProvince` | Name of state/province/region/prefecture |
| `organization.website` | ✅ | `OrganizationKYCFields` | `website` | Organization website |

### Financial Account Fields

| Field | Status | SDK Class | SDK Property | Description |
|-------|--------|-----------|--------------|-------------|
| `bank_account_number` | ✅ | `FinancialAccountKYCFields` | `bankAccountNumber` | Number identifying bank account |
| `bank_account_type` | ✅ | `FinancialAccountKYCFields` | `bankAccountType` | Type of bank account |
| `bank_branch_number` | ✅ | `FinancialAccountKYCFields` | `bankBranchNumber` | Number identifying bank branch |
| `bank_name` | ✅ | `FinancialAccountKYCFields` | `bankName` | Name of the bank |
| `bank_number` | ✅ | `FinancialAccountKYCFields` | `bankNumber` | Number identifying bank in national banking system (routing number in US) |
| `bank_phone_number` | ✅ | `FinancialAccountKYCFields` | `bankPhoneNumber` | Phone number with country code for bank |
| `cbu_alias` | ✅ | `FinancialAccountKYCFields` | `cbuAlias` | The alias for a CBU or CVU |
| `cbu_number` | ✅ | `FinancialAccountKYCFields` | `cbuNumber` | Clave Bancaria Uniforme (CBU) or Clave Virtual Uniforme (CVU) |
| `clabe_number` | ✅ | `FinancialAccountKYCFields` | `clabeNumber` | Bank account number for Mexico |
| `crypto_address` | ✅ | `FinancialAccountKYCFields` | `cryptoAddress` | Address for a cryptocurrency account |
| `crypto_memo` | ✅ | `FinancialAccountKYCFields` | `cryptoMemo` | A destination tag/memo used to identify a transaction |
| `external_transfer_memo` | ✅ | `FinancialAccountKYCFields` | `externalTransferMemo` | A destination tag/memo used to identify a transaction |
| `mobile_money_number` | ✅ | `FinancialAccountKYCFields` | `mobileMoneyNumber` | Mobile phone number in E.164 format with which a mobile money account is associated |
| `mobile_money_provider` | ✅ | `FinancialAccountKYCFields` | `mobileMoneyProvider` | Name of the mobile money service provider |

### Card Fields

| Field | Status | SDK Class | SDK Property | Description |
|-------|--------|-----------|--------------|-------------|
| `card.address` | ✅ | `CardKYCFields` | `address` | Entire address (country, state, postal code, street address, etc.) as a multi-line string |
| `card.city` | ✅ | `CardKYCFields` | `city` | Name of city/town |
| `card.country_code` | ✅ | `CardKYCFields` | `countryCode` | Billing address country code in ISO 3166-1 alpha-2 code (e.g., US) |
| `card.cvc` | ✅ | `CardKYCFields` | `cvc` | CVC number (Digits on the back of the card) |
| `card.expiration_date` | ✅ | `CardKYCFields` | `expirationDate` | Expiration month and year in YY-MM format (e.g., 29-11, November 2029) |
| `card.holder_name` | ✅ | `CardKYCFields` | `holderName` | Name of the card holder |
| `card.network` | ✅ | `CardKYCFields` | `network` | Brand of the card/network it operates within (e.g., Visa, Mastercard, AmEx, etc.) |
| `card.number` | ✅ | `CardKYCFields` | `number` | Card number |
| `card.postal_code` | ✅ | `CardKYCFields` | `postalCode` | Billing address postal code |
| `card.state_or_province` | ✅ | `CardKYCFields` | `stateOrProvince` | Name of state/province/region/prefecture in ISO 3166-2 format |
| `card.token` | ✅ | `CardKYCFields` | `token` | Token representation of the card in some external payment system (e.g., Stripe) |

## Implementation Gaps

🎉 **No gaps found!** All SEP-09 standard KYC fields are implemented.

## Recommendations

✅ The SDK has full compatibility with SEP-09 Standard KYC Fields!

**All standard KYC/AML fields are implemented**, including:
- Natural person identity and contact information
- Organization/business entity details
- Financial account information (bank, crypto, mobile money)
- Payment card details

## Legend

- ✅ **Implemented**: Field is implemented in SDK
- ❌ **Not Implemented**: Field is missing from SDK
- All SEP-09 fields are **optional** - use only the fields relevant to your application
