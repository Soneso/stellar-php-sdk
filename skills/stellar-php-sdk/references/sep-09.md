# SEP-09: Standard KYC Fields

**Purpose:** Standard vocabulary for KYC (Know Your Customer) and AML (Anti-Money Laundering) data fields.
**Prerequisites:** None
**SDK Namespace:** `Soneso\StellarSDK\SEP\StandardKYCFields`
**Standard:** [SEP-0009 v1.18.0](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0009.md)

SEP-09 fields are used by [SEP-12](sep-12.md) (`PutCustomerInfoRequest::$KYCFields`), SEP-24, and SEP-31.

## Table of Contents

- [Class overview](#class-overview)
- [StandardKYCFields container](#standardkycfields-container)
- [NaturalPersonKYCFields](#naturalpersonkycfields)
  - [All properties](#all-properties)
  - [File fields](#file-fields)
  - [fields() and files() output](#fields-and-files-output)
- [OrganizationKYCFields](#organizationkycfields)
  - [All properties](#all-properties-1)
  - [File fields](#file-fields-1)
  - [Organization prefix behavior](#organization-prefix-behavior)
- [FinancialAccountKYCFields](#financialaccountkycfields)
  - [All properties](#all-properties-2)
  - [Prefix parameter](#prefix-parameter)
- [CardKYCFields](#cardkycfields)
  - [All properties](#all-properties-3)
- [Field key constants](#field-key-constants)
- [Complete example: natural person with bank account](#complete-example-natural-person-with-bank-account)
- [Complete example: organization](#complete-example-organization)
- [Integration with SEP-12](#integration-with-sep-12)
- [Common pitfalls](#common-pitfalls)

---

## Class overview

| Class | Purpose | Prefix on `fields()` |
|-------|---------|----------------------|
| `StandardKYCFields` | Container for natural person + organization | n/a (no `fields()` method) |
| `NaturalPersonKYCFields` | Individual customer data | none |
| `OrganizationKYCFields` | Business/entity data | `organization.` |
| `FinancialAccountKYCFields` | Bank, mobile money, crypto | none (or prefix from parent) |
| `CardKYCFields` | Credit/debit card data | `card.` |

---

## StandardKYCFields container

`StandardKYCFields` is a simple container with two public properties. It does **not** have its own `fields()` method — call `fields()` on the nested objects directly, or pass the container to `PutCustomerInfoRequest::$KYCFields`.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\OrganizationKYCFields;

$kyc = new StandardKYCFields();

// Either or both may be set
$kyc->naturalPersonKYCFields  = new NaturalPersonKYCFields();   // ?NaturalPersonKYCFields
$kyc->organizationKYCFields   = new OrganizationKYCFields();    // ?OrganizationKYCFields
```

---

## NaturalPersonKYCFields

### All properties

```php
<?php declare(strict_types=1);

use DateTime;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\FinancialAccountKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\CardKYCFields;

$p = new NaturalPersonKYCFields();

// Identity
$p->lastName       = 'Doe';         // ?string  → 'last_name'   (spec alias: family_name)
$p->firstName      = 'John';        // ?string  → 'first_name'  (spec alias: given_name)
$p->additionalName = 'Michael';     // ?string  → 'additional_name' (middle name)
$p->sex            = 'male';        // ?string  → 'sex' (male | female | other)

// Birth
$p->birthDate         = '1990-05-15';    // ?string  → 'birth_date'         (ISO 8601 string)
$p->birthPlace        = 'New York, NY';  // ?string  → 'birth_place'
$p->birthCountryCode  = 'USA';           // ?string  → 'birth_country_code'  (ISO 3166-1 alpha-3)

// Contact
$p->emailAddress     = 'john@example.com';  // ?string → 'email_address'
$p->mobileNumber     = '+14155551234';      // ?string → 'mobile_number' (E.164)
$p->mobileNumberFormat = 'E.164';           // ?string → 'mobile_number_format' (optional)

// Current address
$p->address            = "123 Main St\nNew York, NY 10001"; // ?string → 'address' (multi-line)
$p->city               = 'New York';    // ?string → 'city'
$p->stateOrProvince    = 'NY';          // ?string → 'state_or_province'
$p->postalCode         = '10001';       // ?string → 'postal_code'
$p->addressCountryCode = 'USA';         // ?string → 'address_country_code' (ISO 3166-1 alpha-3)

// Identity document (text)
$p->idType          = 'passport';             // ?string  → 'id_type'
$p->idNumber        = 'AB123456';             // ?string  → 'id_number'
$p->idCountryCode   = 'USA';                  // ?string  → 'id_country_code' (ISO 3166-1 alpha-3)
$p->idIssueDate     = new DateTime('2020-01-15');  // ?DateTime → 'id_issue_date'      (formatted as ATOM)
$p->idExpirationDate = new DateTime('2030-01-15'); // ?DateTime → 'id_expiration_date' (formatted as ATOM)

// Tax
$p->taxId     = '123-45-6789';  // ?string → 'tax_id'
$p->taxIdName = 'SSN';          // ?string → 'tax_id_name'

// Employment
$p->occupation      = 2512;                   // ?int    → 'occupation' (ISCO-08 code, output as string)
$p->employerName    = 'Acme Corp';            // ?string → 'employer_name'
$p->employerAddress = '456 Business Ave';     // ?string → 'employer_address'

// Other
$p->languageCode = 'en';            // ?string → 'language_code' (ISO 639-1)
$p->ipAddress    = '192.168.1.1';   // ?string → 'ip_address'
$p->referralId   = 'REF123';        // ?string → 'referral_id'

// Nested objects (merged into fields() output)
$p->financialAccountKYCFields = new FinancialAccountKYCFields(); // ?FinancialAccountKYCFields
$p->cardKYCFields             = new CardKYCFields();             // ?CardKYCFields
```

### File fields

File fields are stored on the same object but returned **only** by `files()`, not by `fields()`. Assign raw bytes (from `file_get_contents()`); when submitting via SEP-12 `putCustomerInfo()`, the SDK sends them as multipart/form-data automatically.

```php
$p->photoIdFront            = file_get_contents('/path/to/id_front.jpg');  // ?string → 'photo_id_front'
$p->photoIdBack             = file_get_contents('/path/to/id_back.jpg');   // ?string → 'photo_id_back'
$p->notaryApprovalOfPhotoId = file_get_contents('/path/to/notary.pdf');    // ?string → 'notary_approval_of_photo_id'
$p->photoProofResidence     = file_get_contents('/path/to/utility.pdf');   // ?string → 'photo_proof_residence'
$p->proofOfIncome           = file_get_contents('/path/to/payslip.pdf');   // ?string → 'proof_of_income'
$p->proofOfLiveness         = file_get_contents('/path/to/selfie.mp4');    // ?string → 'proof_of_liveness'
```

### fields() and files() output

```php
// Text fields — omits all null fields, omits all file fields
$fields = $p->fields();
// array<string, mixed>  e.g. ['first_name' => 'John', 'last_name' => 'Doe', ...]

// Binary fields only — omits null fields
$files = $p->files();
// array<string, string>  e.g. ['photo_id_front' => '<raw bytes>', ...]

// occupation (int) is automatically converted to string in the output:
// $p->occupation = 2512  →  $fields['occupation'] == '2512'
//
// DateTime fields are formatted using DateTimeInterface::ATOM:
// $p->idIssueDate = new DateTime('2020-01-15')
//   →  $fields['id_issue_date'] == '2020-01-15T00:00:00+00:00'
//
// financialAccountKYCFields and cardKYCFields are merged into fields() output
```

---

## OrganizationKYCFields

### All properties

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\StandardKYCFields\OrganizationKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\FinancialAccountKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\CardKYCFields;

$org = new OrganizationKYCFields();

// Corporate identity
$org->name               = 'Acme Corp S.L.';     // ?string → 'organization.name'
$org->VATNumber          = 'ESB12345678';         // ?string → 'organization.VAT_number'   (mixed case key)
$org->registrationNumber = 'B-12345678';          // ?string → 'organization.registration_number'
$org->registrationDate   = '2015-06-01';          // ?string → 'organization.registration_date' (ISO 8601)
$org->registeredAddress  = '100 Gran Via, Madrid';// ?string → 'organization.registered_address'

// Corporate structure
$org->numberOfShareholders = 3;               // ?int    → 'organization.number_of_shareholders' (stays int in fields(), NOT strval'd like occupation)
$org->shareholderName      = 'John Smith';    // ?string → 'organization.shareholder_name'
$org->directorName         = 'Jane Doe';      // ?string → 'organization.director_name'

// Contact
$org->addressCountryCode = 'ESP';                   // ?string → 'organization.address_country_code'
$org->stateOrProvince    = 'Madrid';                // ?string → 'organization.state_or_province'
$org->city               = 'Madrid';               // ?string → 'organization.city'
$org->postalCode         = '28013';                 // ?string → 'organization.postal_code'
$org->website            = 'https://acme.example.com'; // ?string → 'organization.website'
$org->email              = 'info@acme.example.com';    // ?string → 'organization.email'
$org->phone              = '+34911234567';             // ?string → 'organization.phone'

// Nested objects
$org->financialAccountKYCFields = new FinancialAccountKYCFields(); // prefixed with 'organization.'
$org->cardKYCFields             = new CardKYCFields();             // NOT prefixed — keys stay 'card.*'
```

### File fields

```php
$org->photoIncorporationDoc = file_get_contents('/path/to/incorporation.pdf'); // ?string → 'organization.photo_incorporation_doc'
$org->photoProofAddress     = file_get_contents('/path/to/utility_bill.pdf'); // ?string → 'organization.photo_proof_address'

$files = $org->files();
// array<string, string>  e.g. ['organization.photo_incorporation_doc' => '<raw bytes>', ...]
```

### Organization prefix behavior

`OrganizationKYCFields::fields()` automatically applies the `'organization.'` prefix to all its own fields **and** to any nested `FinancialAccountKYCFields`. Card fields do NOT receive the `organization.` prefix — they always use the `card.` prefix regardless of nesting.

```php
$org = new OrganizationKYCFields();
$org->name = 'Acme Corp';

$bank = new FinancialAccountKYCFields();
$bank->bankName = 'Chase';
$org->financialAccountKYCFields = $bank;

$card = new CardKYCFields();
$card->number = '4111111111111111';
$org->cardKYCFields = $card;

$fields = $org->fields();
// 'organization.name'      => 'Acme Corp'         (org prefix applied)
// 'organization.bank_name' => 'Chase'             (financial gets org prefix)
// 'card.number'            => '4111111111111111'  (card prefix, NOT org prefix)
```

---

## FinancialAccountKYCFields

### All properties

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\StandardKYCFields\FinancialAccountKYCFields;

$fin = new FinancialAccountKYCFields();

// Traditional banking
$fin->bankName          = 'First National Bank';  // ?string → 'bank_name'
$fin->bankAccountType   = 'checking';             // ?string → 'bank_account_type' (checking | savings)
$fin->bankAccountNumber = '1234567890';           // ?string → 'bank_account_number'
$fin->bankNumber        = '021000021';            // ?string → 'bank_number' (routing number in US)
$fin->bankBranchNumber  = '001';                  // ?string → 'bank_branch_number'
$fin->bankPhoneNumber   = '+18005551234';         // ?string → 'bank_phone_number' (E.164)

// Transfer memo / destination tag
$fin->externalTransferMemo = 'WIRE-REF-12345';   // ?string → 'external_transfer_memo'

// Regional banking formats
$fin->clabeNumber = '032180000118359719';          // ?string → 'clabe_number' (Mexico CLABE)
$fin->cbuNumber   = '0110000000001234567890';      // ?string → 'cbu_number'   (Argentina CBU/CVU)
$fin->cbuAlias    = 'mi.cuenta.arg';               // ?string → 'cbu_alias'    (Argentina alias)

// Mobile money
$fin->mobileMoneyNumber   = '+254712345678';   // ?string → 'mobile_money_number' (E.164, may differ from personal mobile)
$fin->mobileMoneyProvider = 'M-Pesa';          // ?string → 'mobile_money_provider'

// Crypto
$fin->cryptoAddress = 'GABC...';              // ?string → 'crypto_address'
$fin->cryptoMemo    = '1234567890';           // ?string → 'crypto_memo' (DEPRECATED: use externalTransferMemo)
```

### Prefix parameter

`FinancialAccountKYCFields::fields()` takes an optional `?string $keyPrefix` (default `''`). Users do not normally call this directly — `NaturalPersonKYCFields` calls it without a prefix, and `OrganizationKYCFields` calls it with `'organization.'`. You can call it directly if building custom field arrays:

```php
$fin = new FinancialAccountKYCFields();
$fin->bankName = 'Chase';

// No prefix (when used with natural person)
$fields = $fin->fields();
// ['bank_name' => 'Chase']

// With organization prefix (mirrors what OrganizationKYCFields::fields() does internally)
$fields = $fin->fields('organization.');
// ['organization.bank_name' => 'Chase']
```

---

## CardKYCFields

### All properties

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\StandardKYCFields\CardKYCFields;

$card = new CardKYCFields();

$card->number         = '4111111111111111';          // ?string → 'card.number'
$card->expirationDate = '29-11';                     // ?string → 'card.expiration_date' (YY-MM format)
$card->cvc            = '123';                       // ?string → 'card.cvc'
$card->holderName     = 'JOHN DOE';                  // ?string → 'card.holder_name'
$card->network        = 'Visa';                      // ?string → 'card.network' (Visa, Mastercard, AmEx, etc.)
$card->token          = 'tok_stripe_test_token';     // ?string → 'card.token' (preferred over raw card data)

// Billing address
$card->address         = '123 Main St, Apt 4B';  // ?string → 'card.address'
$card->city            = 'New York';              // ?string → 'card.city'
$card->stateOrProvince = 'NY';                   // ?string → 'card.state_or_province' (ISO 3166-2)
$card->postalCode      = '10001';                 // ?string → 'card.postal_code'
$card->countryCode     = 'US';                   // ?string → 'card.country_code' (ISO 3166-1 alpha-2: 2-letter)

$fields = $card->fields();
// Returns only non-null fields with 'card.' prefix on all keys
```

---

## Field key constants

Every class exposes string constants for all its field keys. Use these instead of hardcoded strings to avoid typos.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\OrganizationKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\FinancialAccountKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\CardKYCFields;

// NaturalPersonKYCFields field keys
NaturalPersonKYCFields::FIRST_NAME_KEY;             // 'first_name'
NaturalPersonKYCFields::LAST_NAME_KEY;              // 'last_name'
NaturalPersonKYCFields::ADDITIONAL_NAME_KEY;        // 'additional_name'
NaturalPersonKYCFields::EMAIL_ADDRESS_KEY;          // 'email_address'
NaturalPersonKYCFields::MOBILE_NUMBER_KEY;          // 'mobile_number'
NaturalPersonKYCFields::MOBILE_NUMBER_FORMAT_KEY;   // 'mobile_number_format'
NaturalPersonKYCFields::BIRTH_DATE_KEY;             // 'birth_date'
NaturalPersonKYCFields::BIRTH_PLACE_KEY;            // 'birth_place'
NaturalPersonKYCFields::BIRTH_COUNTRY_CODE_KEY;     // 'birth_country_code'
NaturalPersonKYCFields::SEX_KEY;                    // 'sex'
NaturalPersonKYCFields::ADDRESS_KEY;                // 'address'
NaturalPersonKYCFields::CITY_KEY;                   // 'city'
NaturalPersonKYCFields::STATE_OR_PROVINCE_KEY;      // 'state_or_province'
NaturalPersonKYCFields::POSTAL_CODE_KEY;            // 'postal_code'
NaturalPersonKYCFields::ADDRESS_COUNTRY_CODE_KEY;   // 'address_country_code'
NaturalPersonKYCFields::ID_TYPE_KEY;                // 'id_type'
NaturalPersonKYCFields::ID_NUMBER_KEY;              // 'id_number'
NaturalPersonKYCFields::ID_COUNTRY_CODE_KEY;        // 'id_country_code'
NaturalPersonKYCFields::ID_ISSUE_DATE_KEY;          // 'id_issue_date'
NaturalPersonKYCFields::ID_EXPIRATION_DATE_KEY;     // 'id_expiration_date'
NaturalPersonKYCFields::TAX_ID_KEY;                 // 'tax_id'
NaturalPersonKYCFields::TAX_ID_NAME_KEY;            // 'tax_id_name'
NaturalPersonKYCFields::OCCUPATION_KEY;             // 'occupation'
NaturalPersonKYCFields::EMPLOYER_NAME_KEY;          // 'employer_name'
NaturalPersonKYCFields::EMPLOYER_ADDRESS_KEY;       // 'employer_address'
NaturalPersonKYCFields::LANGUAGE_CODE_KEY;          // 'language_code'
NaturalPersonKYCFields::IP_ADDRESS_KEY;             // 'ip_address'
NaturalPersonKYCFields::REFERRAL_ID_KEY;            // 'referral_id'
// File key constants:
NaturalPersonKYCFields::PHOTO_ID_FRONT_KEY;                 // 'photo_id_front'
NaturalPersonKYCFields::PHOTO_ID_BACK_KEY;                  // 'photo_id_back'
NaturalPersonKYCFields::NOTARY_APPROVAL_OF_PHOTO_ID_KEY;    // 'notary_approval_of_photo_id'
NaturalPersonKYCFields::PHOTO_PROOF_RESIDENCE_KEY;          // 'photo_proof_residence'
NaturalPersonKYCFields::PROOF_OF_INCOME_KEY;                // 'proof_of_income'
NaturalPersonKYCFields::PROOF_OF_LIVENESS_KEY;              // 'proof_of_liveness'

// OrganizationKYCFields field keys (all include 'organization.' prefix)
OrganizationKYCFields::KEY_PREFIX;                  // 'organization.'
OrganizationKYCFields::NAME_KEY;                    // 'organization.name'
OrganizationKYCFields::VAT_NUMBER_KEY;              // 'organization.VAT_number'   (mixed case)
OrganizationKYCFields::REGISTRATION_NUMBER_KEY;     // 'organization.registration_number'
OrganizationKYCFields::REGISTRATION_DATE_KEY;       // 'organization.registration_date'
OrganizationKYCFields::REGISTRATION_ADDRESS_KEY;    // 'organization.registered_address'  (note: 'registered', not 'registration')
OrganizationKYCFields::NUMBER_OF_SHAREHOLDERS_KEY;  // 'organization.number_of_shareholders'
OrganizationKYCFields::SHAREHOLDER_NAME_KEY;        // 'organization.shareholder_name'
OrganizationKYCFields::DIRECTOR_NAME_KEY;           // 'organization.director_name'
OrganizationKYCFields::ADDRESS_COUNTRY_CODE_KEY;    // 'organization.address_country_code'
OrganizationKYCFields::STATE_OR_PROVINCE_KEY;       // 'organization.state_or_province'
OrganizationKYCFields::CITY_KEY;                    // 'organization.city'
OrganizationKYCFields::POSTAL_CODE_KEY;             // 'organization.postal_code'
OrganizationKYCFields::WEBSITE_KEY;                 // 'organization.website'
OrganizationKYCFields::EMAIL_KEY;                   // 'organization.email'
OrganizationKYCFields::PHONE_KEY;                   // 'organization.phone'
// File key constants:
OrganizationKYCFields::PHOTO_INCORPORATION_DOC_KEY; // 'organization.photo_incorporation_doc'
OrganizationKYCFields::PHOTO_PROOF_ADDRESS_KEY;     // 'organization.photo_proof_address'

// FinancialAccountKYCFields field keys (no prefix — bare names)
FinancialAccountKYCFields::BANK_NAME_KEY;           // 'bank_name'
FinancialAccountKYCFields::BANK_ACCOUNT_TYPE_KEY;   // 'bank_account_type'
FinancialAccountKYCFields::BANK_ACCOUNT_NUMBER_KEY; // 'bank_account_number'
FinancialAccountKYCFields::BANK_NUMBER_KEY;         // 'bank_number'
FinancialAccountKYCFields::BANK_PHONE_NUMBER_KEY;   // 'bank_phone_number'
FinancialAccountKYCFields::BANK_BRANCH_NUMBER_KEY;  // 'bank_branch_number'
FinancialAccountKYCFields::EXTERNAL_TRANSFER_MEMO_KEY; // 'external_transfer_memo'
FinancialAccountKYCFields::CLABE_NUMBER_KEY;        // 'clabe_number'
FinancialAccountKYCFields::CBU_NUMBER_KEY;          // 'cbu_number'
FinancialAccountKYCFields::CBU_ALIAS_KEY;           // 'cbu_alias'
FinancialAccountKYCFields::MOBILE_MONEY_NUMBER_KEY; // 'mobile_money_number'
FinancialAccountKYCFields::MOBILE_MONEY_PROVIDER_KEY; // 'mobile_money_provider'
FinancialAccountKYCFields::CRYPTO_ADDRESS_KEY;      // 'crypto_address'
FinancialAccountKYCFields::CRYPTO_MEMO_KEY;         // 'crypto_memo' (deprecated)

// CardKYCFields field keys (all include 'card.' prefix)
CardKYCFields::NUMBER_KEY;          // 'card.number'
CardKYCFields::EXPIRATION_DATE_KEY; // 'card.expiration_date'
CardKYCFields::CVC_KEY;             // 'card.cvc'
CardKYCFields::HOLDER_NAME_KEY;     // 'card.holder_name'
CardKYCFields::NETWORK_KEY;         // 'card.network'
CardKYCFields::TOKEN_KEY;           // 'card.token'
CardKYCFields::ADDRESS_KEY;         // 'card.address'
CardKYCFields::CITY_KEY;            // 'card.city'
CardKYCFields::STATE_OR_PROVINCE_KEY; // 'card.state_or_province'
CardKYCFields::POSTAL_CODE_KEY;     // 'card.postal_code'
CardKYCFields::COUNTRY_CODE_KEY;    // 'card.country_code'
```

---

## Complete example: natural person with bank account

```php
<?php declare(strict_types=1);

use DateTime;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\FinancialAccountKYCFields;

$person = new NaturalPersonKYCFields();

// Identity
$person->firstName     = 'Jane';
$person->lastName      = 'Doe';
$person->birthDate     = '1990-05-15';           // ISO 8601 string
$person->birthCountryCode = 'USA';
$person->sex           = 'female';

// Address
$person->address            = '123 Main St, Apt 4B';
$person->city               = 'San Francisco';
$person->stateOrProvince    = 'CA';
$person->postalCode         = '94102';
$person->addressCountryCode = 'USA';

// Contact
$person->emailAddress = 'jane@example.com';
$person->mobileNumber = '+14155551234';

// Tax
$person->taxId     = '123-45-6789';
$person->taxIdName = 'SSN';

// ID document
$person->idType           = 'passport';
$person->idNumber         = 'AB123456';
$person->idCountryCode    = 'USA';
$person->idIssueDate      = new DateTime('2020-01-15');   // DateTime object
$person->idExpirationDate = new DateTime('2030-01-15');   // DateTime object

// Bank account
$bank = new FinancialAccountKYCFields();
$bank->bankName          = 'First National Bank';
$bank->bankAccountType   = 'checking';
$bank->bankAccountNumber = '1234567890';
$bank->bankNumber        = '021000021';   // routing number
$person->financialAccountKYCFields = $bank;

// Photo ID (binary) — retrieved separately via files()
$person->photoIdFront = file_get_contents('/path/to/passport_front.jpg');
$person->photoIdBack  = file_get_contents('/path/to/passport_back.jpg');

// Text fields for submission
$textFields = $person->fields();
// ['first_name' => 'Jane', 'last_name' => 'Doe', 'birth_date' => '1990-05-15',
//  'bank_name' => 'First National Bank', 'bank_account_number' => '1234567890', ...]

// File fields for submission
$fileFields = $person->files();
// ['photo_id_front' => '<raw bytes>', 'photo_id_back' => '<raw bytes>']

// Wrap in container and pass to SEP-12
$kyc = new StandardKYCFields();
$kyc->naturalPersonKYCFields = $person;
```

---

## Complete example: organization

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\OrganizationKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\FinancialAccountKYCFields;

$org = new OrganizationKYCFields();

$org->name               = 'TechCorp International Ltd';
$org->VATNumber          = 'VAT123456789';
$org->registrationNumber = 'REG2010123456';
$org->registrationDate   = '2010-05-15';
$org->registeredAddress  = '50 Canary Wharf, London EC2';
$org->addressCountryCode = 'GBR';
$org->city               = 'London';
$org->postalCode         = 'EC2V 8AB';
$org->directorName       = 'James Anderson';
$org->website            = 'https://www.techcorp.com';
$org->email              = 'compliance@techcorp.com';
$org->phone              = '+442071234567';
$org->numberOfShareholders = 3;

$bank = new FinancialAccountKYCFields();
$bank->bankName          = 'Barclays Bank';
$bank->bankAccountNumber = 'GB29NWBK60161331926819';
$org->financialAccountKYCFields = $bank;

$org->photoIncorporationDoc = file_get_contents('/path/to/certificate.pdf');

$fields = $org->fields();
// ['organization.name'      => 'TechCorp International Ltd',
//  'organization.VAT_number' => 'VAT123456789',
//  'organization.registered_address' => '50 Canary Wharf, London EC2',
//  'organization.bank_name' => 'Barclays Bank',
//  'organization.bank_account_number' => 'GB29NWBK60161331926819', ...]

$files = $org->files();
// ['organization.photo_incorporation_doc' => '<raw bytes>']

$kyc = new StandardKYCFields();
$kyc->organizationKYCFields = $org;
```

---

## Integration with SEP-12

Pass `StandardKYCFields` to `PutCustomerInfoRequest::$KYCFields`. The SEP-12 service calls `fields()` and `files()` internally on the nested objects.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;

$kycService = KYCService::fromDomain('testanchor.stellar.org');

$person = new NaturalPersonKYCFields();
$person->firstName    = 'John';
$person->lastName     = 'Doe';
$person->emailAddress = 'john@example.com';
$person->photoIdFront = file_get_contents('/path/to/id.jpg');

$kyc = new StandardKYCFields();
$kyc->naturalPersonKYCFields = $person;

$request = new PutCustomerInfoRequest();
$request->jwt       = $jwtToken;  // from SEP-10 authentication
$request->KYCFields = $kyc;       // UPPERCASE KYCFields — see pitfalls

$response = $kycService->putCustomerInfo($request);
$customerId = $response->getId();
```

For the full SEP-12 API (status polling, verification, file uploads, etc.) see [sep-12.md](sep-12.md).

---

## Common pitfalls

**WRONG: `$birthDate` is a string, but `$idIssueDate` and `$idExpirationDate` are DateTime objects**

```php
// WRONG: string for id dates
$person->idIssueDate     = '2020-01-15';       // type mismatch (?DateTime)
$person->idExpirationDate = '2030-01-15';      // type mismatch (?DateTime)

// WRONG: DateTime for birth date
$person->birthDate = new DateTime('1990-05-15'); // type mismatch (?string)

// CORRECT
$person->birthDate        = '1990-05-15';             // ISO 8601 string
$person->idIssueDate      = new DateTime('2020-01-15'); // DateTime object
$person->idExpirationDate = new DateTime('2030-01-15'); // DateTime object
```

**WRONG: `$occupation` expects int, not string**

```php
// WRONG: string
$person->occupation = '2512';  // type mismatch (?int)

// CORRECT: int (ISCO-08 code) — fields() converts to string internally
$person->occupation = 2512;
```

**WRONG: `OrganizationKYCFields::REGISTRATION_ADDRESS_KEY` value is `'organization.registered_address'`, not `'organization.registration_address'`**

```php
// WRONG: guessing the key name from the constant name
$key = 'organization.registration_address';   // does not exist in SEP-09

// CORRECT: use the constant — constant name is REGISTRATION_ADDRESS_KEY
//          but the value is 'organization.registered_address'
$key = OrganizationKYCFields::REGISTRATION_ADDRESS_KEY; // 'organization.registered_address'
// Property: $org->registeredAddress = '...'
```

**WRONG: `OrganizationKYCFields::VAT_NUMBER_KEY` is mixed-case**

```php
// WRONG: assuming lowercase
echo OrganizationKYCFields::VAT_NUMBER_KEY; // NOT 'organization.vat_number'

// CORRECT: the key preserves the spec's mixed case
echo OrganizationKYCFields::VAT_NUMBER_KEY; // 'organization.VAT_number'
```

**WRONG: card fields nested under an organization get the `organization.` prefix**

```php
// WRONG: assuming org prefix applies to card fields too
$fields = $org->fields();
$fields['organization.card.number']; // does not exist

// CORRECT: card fields always use 'card.' prefix, even under an organization
$fields['card.number']; // correct key
```

**WRONG: `StandardKYCFields` has a `fields()` method**

```php
// WRONG: calling fields() on the container
$kyc = new StandardKYCFields();
$kyc->naturalPersonKYCFields = $person;
$data = $kyc->fields(); // fatal error — method does not exist

// CORRECT: call fields() on the nested object
$data = $kyc->naturalPersonKYCFields->fields();

// Or pass the container to PutCustomerInfoRequest::$KYCFields (SEP-12 handles it)
$request->KYCFields = $kyc;
```

**WRONG: `$request->kycFields` (lowercase) for SEP-12 submission**

```php
// WRONG: lowercase — PHP silently creates a new undeclared property; KYC data is ignored
$request->kycFields = $kyc;

// CORRECT: uppercase KYCFields
$request->KYCFields = $kyc;
```

**WRONG: `FinancialAccountKYCFields` does not have a `files()` method**

```php
// WRONG: financial account fields have no binary fields
$fin->files(); // fatal error — method does not exist

// CORRECT: only NaturalPersonKYCFields and OrganizationKYCFields have files()
$personFiles = $person->files();
$orgFiles    = $org->files();
```

---
