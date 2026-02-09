# SEP-09: Standard KYC Fields

SEP-09 defines a standard vocabulary for KYC (Know Your Customer) and AML (Anti-Money Laundering) data fields. When different services need to exchange customer information (deposits, withdrawals, cross-border payments), they use these field names so everyone speaks the same language.

**Use SEP-09 when:**
- Submitting KYC data via SEP-12
- Providing customer info for SEP-24 interactive flows
- Sending receiver details for SEP-31 cross-border payments
- Building anchor services that collect customer information

**Spec:** [SEP-0009 v1.18.0](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0009.md)

## Quick Example

This example shows how to create basic KYC fields for an individual customer and prepare them for API submission:

```php
<?php

use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;

// Build KYC fields for an individual
$person = new NaturalPersonKYCFields();
$person->firstName = 'John';
$person->lastName = 'Doe';
$person->emailAddress = 'john@example.com';
$person->birthDate = '1990-05-15';

// Wrap in container for complete KYC submission
$kyc = new StandardKYCFields();
$kyc->naturalPersonKYCFields = $person;

// Get fields as array for API submission
$fields = $person->fields();
// Returns: ['first_name' => 'John', 'last_name' => 'Doe', ...]
```

## Detailed Usage

### Natural Person Fields

Use `NaturalPersonKYCFields` when collecting KYC data for individual customers. This class covers personal identification, contact information, address, employment, tax, and identity document fields. Note that the spec also accepts `family_name`/`given_name` as aliases for `last_name`/`first_name`, but the SDK uses the more common `lastName`/`firstName` property names:

```php
<?php

use DateTime;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;

$person = new NaturalPersonKYCFields();

// Personal identification
$person->firstName = 'Maria';       // Maps to 'first_name' (spec also accepts 'given_name')
$person->lastName = 'Garcia';       // Maps to 'last_name' (spec also accepts 'family_name')
$person->additionalName = 'Elena';  // Middle name
$person->birthDate = '1985-03-20';  // ISO 8601 date format
$person->birthPlace = 'Madrid, Spain';
$person->birthCountryCode = 'ESP';  // ISO 3166-1 alpha-3
$person->sex = 'female';            // 'male', 'female', or 'other'

// Contact information
$person->emailAddress = 'maria@example.com';
$person->mobileNumber = '+34612345678';  // E.164 format
$person->mobileNumberFormat = 'E.164';   // Specify expected format (optional, defaults to E.164)

// Current address
$person->addressCountryCode = 'ESP';     // ISO 3166-1 alpha-3
$person->stateOrProvince = 'Madrid';
$person->city = 'Madrid';
$person->postalCode = '28001';
$person->address = "Calle Mayor 10\n28001 Madrid\nSpain";  // Multi-line full address

// Employment
$person->occupation = 2511;  // ISCO-08 code (Software developer)
$person->employerName = 'Tech Corp';
$person->employerAddress = 'Paseo de la Castellana 50, Madrid';

// Tax information
$person->taxId = '12345678Z';
$person->taxIdName = 'NIF';  // Name of tax ID type (SSN, ITIN, NIF, etc.)

// Identity document
$person->idType = 'passport';           // 'passport', 'drivers_license', 'id_card', etc.
$person->idNumber = 'AB1234567';
$person->idCountryCode = 'ESP';         // ISO 3166-1 alpha-3
$person->idIssueDate = new DateTime('2020-01-15');
$person->idExpirationDate = new DateTime('2030-01-14');

// Other
$person->languageCode = 'es';           // ISO 639-1
$person->ipAddress = '192.168.1.1';
$person->referralId = 'partner-12345';  // Origin or referral code

// Convert to array for API submission
$fieldData = $person->fields();
```

### Document Uploads

Binary files (photos, documents) are handled separately via `files()`. This separation allows text fields and binary files to be submitted through different API endpoints or form parts as required:

```php
<?php

use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;

$person = new NaturalPersonKYCFields();
$person->firstName = 'John';
$person->lastName = 'Doe';

// Base64-encode document images for transmission
$person->photoIdFront = base64_encode(file_get_contents('/path/to/passport-front.jpg'));
$person->photoIdBack = base64_encode(file_get_contents('/path/to/passport-back.jpg'));
$person->notaryApprovalOfPhotoId = base64_encode(file_get_contents('/path/to/notary-approval.pdf'));
$person->photoProofResidence = base64_encode(file_get_contents('/path/to/utility-bill.pdf'));
$person->proofOfIncome = base64_encode(file_get_contents('/path/to/payslip.pdf'));
$person->proofOfLiveness = base64_encode(file_get_contents('/path/to/selfie-video.mp4'));

// Get text fields and file fields separately
$textFields = $person->fields();
$fileFields = $person->files();
```

### Organization Fields

Use `OrganizationKYCFields` for business customers. All organization field keys are automatically prefixed with `organization.` when calling `fields()` to match the SEP-09 dot notation convention:

```php
<?php

use Soneso\StellarSDK\SEP\StandardKYCFields\OrganizationKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;

$org = new OrganizationKYCFields();

// Corporate identity
$org->name = 'Acme Corporation S.L.';
$org->VATNumber = 'ESB12345678';
$org->registrationNumber = 'B-12345678';
$org->registrationDate = '2015-06-01';  // ISO 8601 date
$org->registeredAddress = 'Calle Gran Via 100, 28013 Madrid, Spain';

// Corporate structure
$org->numberOfShareholders = 3;
$org->shareholderName = 'John Smith';  // Query recursively for all UBOs
$org->directorName = 'Jane Doe';

// Contact details
$org->addressCountryCode = 'ESP';  // ISO 3166-1 alpha-3
$org->stateOrProvince = 'Madrid';
$org->city = 'Madrid';
$org->postalCode = '28013';
$org->website = 'https://acme-corp.example.com';
$org->email = 'compliance@acme-corp.example.com';
$org->phone = '+34911234567';  // E.164 format

// Wrap in container
$kyc = new StandardKYCFields();
$kyc->organizationKYCFields = $org;

// Organization fields use 'organization.' prefix
$fieldData = $org->fields();
// Returns: ['organization.name' => 'Acme Corporation S.L.', ...]
```

Organization documents can also be uploaded via the `files()` method, which returns fields with the appropriate `organization.` prefix:

```php
<?php

use Soneso\StellarSDK\SEP\StandardKYCFields\OrganizationKYCFields;

$org = new OrganizationKYCFields();
$org->name = 'Acme Corporation S.L.';

// Documents (base64 encoded)
$org->photoIncorporationDoc = base64_encode(file_get_contents('/path/to/incorporation.pdf'));
$org->photoProofAddress = base64_encode(file_get_contents('/path/to/business-utility-bill.pdf'));

// Get text fields and file fields separately
$textFields = $org->fields();
$fileFields = $org->files();
// Returns: ['organization.photo_incorporation_doc' => '...', 'organization.photo_proof_address' => '...']
```

### Financial Account Fields

`FinancialAccountKYCFields` supports bank accounts, crypto addresses, and mobile money for both individuals and organizations. It covers a wide variety of regional banking formats:

```php
<?php

use Soneso\StellarSDK\SEP\StandardKYCFields\FinancialAccountKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;

$person = new NaturalPersonKYCFields();
$person->firstName = 'John';
$person->lastName = 'Doe';

// Add bank account details
$bankAccount = new FinancialAccountKYCFields();
$bankAccount->bankName = 'First National Bank';      // Bank name (useful in regions without unified routing)
$bankAccount->bankAccountType = 'checking';          // 'checking' or 'savings'
$bankAccount->bankAccountNumber = '123456789012';
$bankAccount->bankNumber = '021000021';              // Routing number (US)
$bankAccount->bankBranchNumber = '001';
$bankAccount->bankPhoneNumber = '+12025551234';      // Bank contact number (E.164)

// Regional bank formats
$bankAccount->clabeNumber = '012345678901234567';          // Mexico (CLABE)
$bankAccount->cbuNumber = '0123456789012345678901';        // Argentina (CBU or CVU)
$bankAccount->cbuAlias = 'john.doe.acme';                  // Argentina (CBU/CVU alias)

// Mobile money (common in Africa and Asia)
$bankAccount->mobileMoneyNumber = '+254712345678';         // May differ from personal mobile
$bankAccount->mobileMoneyProvider = 'M-Pesa';

// Crypto
$bankAccount->cryptoAddress = 'GBH4TZYZ4IRCPO44CBOLFUHULU2WGALXTAVESQA6432MBJMABBB4GIYI';
$bankAccount->externalTransferMemo = 'user-12345';         // Destination tag/memo

// Note: cryptoMemo is deprecated - use externalTransferMemo instead

// Attach to person
$person->financialAccountKYCFields = $bankAccount;

// fields() includes nested financial account fields
$allFields = $person->fields();
```

### Card Fields

`CardKYCFields` handles credit and debit card information. All card field keys are prefixed with `card.` to distinguish them from other fields. When possible, prefer using tokenized card data to minimize PCI-DSS compliance scope:

```php
<?php

use Soneso\StellarSDK\SEP\StandardKYCFields\CardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;

$person = new NaturalPersonKYCFields();
$person->firstName = 'John';
$person->lastName = 'Doe';

$card = new CardKYCFields();

// Card details
$card->number = '4111111111111111';
$card->expirationDate = '29-11';  // YY-MM format (November 2029)
$card->cvc = '123';
$card->holderName = 'JOHN DOE';
$card->network = 'Visa';          // Visa, Mastercard, AmEx, etc.

// Billing address
$card->address = "123 Main St\nApt 4B";
$card->city = 'New York';
$card->stateOrProvince = 'NY';    // ISO 3166-2 format
$card->postalCode = '10001';
$card->countryCode = 'US';        // ISO 3166-1 alpha-2 (note: 2-letter for cards)

// Prefer tokens over raw card numbers for PCI-DSS compliance
$card->token = 'tok_visa_4242';   // From Stripe, etc.

$person->cardKYCFields = $card;

// Card fields use 'card.' prefix
$allFields = $person->fields();
// Includes: ['card.number' => '4111...', 'card.expiration_date' => '29-11', ...]
```

### Combining with Organizations

Organizations can also have financial accounts and cards. When nested under an organization, financial account fields automatically receive the `organization.` prefix via the internal prefix parameter:

```php
<?php

use Soneso\StellarSDK\SEP\StandardKYCFields\FinancialAccountKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\OrganizationKYCFields;

$org = new OrganizationKYCFields();
$org->name = 'Acme Corp';
$org->VATNumber = 'US12-3456789';

$bankAccount = new FinancialAccountKYCFields();
$bankAccount->bankName = 'Business Bank';
$bankAccount->bankAccountNumber = '9876543210';
$bankAccount->bankNumber = '021000021';

$org->financialAccountKYCFields = $bankAccount;

// Organization financial fields get the 'organization.' prefix automatically
$fields = $org->fields();
// Returns: ['organization.name' => 'Acme Corp', 'organization.bank_name' => 'Business Bank', ...]
```

### Using Field Key Constants

Each KYC class exposes field key constants, which is useful when you need to reference specific fields programmatically or build custom field mappings:

```php
<?php

use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\OrganizationKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\FinancialAccountKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\CardKYCFields;

// Natural person field keys
echo NaturalPersonKYCFields::FIRST_NAME_KEY;              // 'first_name'
echo NaturalPersonKYCFields::LAST_NAME_KEY;               // 'last_name'
echo NaturalPersonKYCFields::EMAIL_ADDRESS_KEY;           // 'email_address'
echo NaturalPersonKYCFields::BIRTH_DATE_KEY;              // 'birth_date'
echo NaturalPersonKYCFields::MOBILE_NUMBER_FORMAT_KEY;    // 'mobile_number_format'
echo NaturalPersonKYCFields::PHOTO_ID_FRONT_KEY;          // 'photo_id_front'
echo NaturalPersonKYCFields::REFERRAL_ID_KEY;             // 'referral_id'

// Organization field keys (includes prefix)
echo OrganizationKYCFields::KEY_PREFIX;                   // 'organization.'
echo OrganizationKYCFields::NAME_KEY;                     // 'organization.name'
echo OrganizationKYCFields::VAT_NUMBER_KEY;               // 'organization.VAT_number'
echo OrganizationKYCFields::REGISTRATION_NUMBER_KEY;      // 'organization.registration_number'

// Financial account field keys
echo FinancialAccountKYCFields::BANK_NAME_KEY;            // 'bank_name'
echo FinancialAccountKYCFields::BANK_ACCOUNT_TYPE_KEY;    // 'bank_account_type'
echo FinancialAccountKYCFields::CLABE_NUMBER_KEY;         // 'clabe_number'
echo FinancialAccountKYCFields::CBU_NUMBER_KEY;           // 'cbu_number'
echo FinancialAccountKYCFields::MOBILE_MONEY_NUMBER_KEY;  // 'mobile_money_number'
echo FinancialAccountKYCFields::EXTERNAL_TRANSFER_MEMO_KEY; // 'external_transfer_memo'
echo FinancialAccountKYCFields::CRYPTO_ADDRESS_KEY;       // 'crypto_address'

// Card field keys (includes prefix)
echo CardKYCFields::NUMBER_KEY;                           // 'card.number'
echo CardKYCFields::EXPIRATION_DATE_KEY;                  // 'card.expiration_date'
echo CardKYCFields::TOKEN_KEY;                            // 'card.token'
echo CardKYCFields::HOLDER_NAME_KEY;                      // 'card.holder_name'
```

### Integration with SEP-12

These KYC field classes work directly with the SEP-12 KYC service. Here's how to submit KYC data to an anchor:

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;

// Build the KYC fields
$person = new NaturalPersonKYCFields();
$person->firstName = 'John';
$person->lastName = 'Doe';
$person->emailAddress = 'john@example.com';

$kyc = new StandardKYCFields();
$kyc->naturalPersonKYCFields = $person;

// Create KYC service and submit
$kycService = new KYCService('https://anchor.example.com/kyc');

$request = new PutCustomerInfoRequest();
$request->jwt = $jwtToken;  // From SEP-10 authentication
$request->KYCFields = $kyc;

try {
    $response = $kycService->putCustomerInfo($request);
    echo "Customer ID: " . $response->getId();
} catch (\Exception $e) {
    // Handle errors (network issues, validation failures, etc.)
    echo "KYC submission failed: " . $e->getMessage();
}
```

## Field Reference

### Natural Person Fields

| Field | Type | Description |
|-------|------|-------------|
| `first_name`, `last_name` | string | Name fields (spec also accepts `given_name`, `family_name`) |
| `additional_name` | string | Middle name or other additional name |
| `email_address` | string | Email (RFC 5322) |
| `mobile_number` | string | Phone (E.164 format by default) |
| `mobile_number_format` | string | Expected format of mobile_number (e.g., E.164, hash) |
| `birth_date` | string | ISO 8601 date (e.g., 1976-07-04) |
| `birth_place` | string | Place of birth as on passport |
| `birth_country_code` | string | ISO 3166-1 alpha-3 |
| `sex` | string | male, female, other |
| `address` | string | Full address as multi-line string |
| `city`, `postal_code` | string | Address fields |
| `state_or_province` | string | State/province/region name |
| `address_country_code` | string | ISO 3166-1 alpha-3 |
| `id_type` | string | passport, drivers_license, id_card |
| `id_number`, `id_country_code` | string | Document details |
| `id_issue_date`, `id_expiration_date` | DateTime | Document dates (ISO 8601) |
| `tax_id`, `tax_id_name` | string | Tax information |
| `occupation` | int | ISCO-08 code |
| `employer_name`, `employer_address` | string | Employment details |
| `language_code` | string | ISO 639-1 code |
| `ip_address` | string | Customer's IP address |
| `referral_id` | string | Origin or referral code |

**File fields** (base64 encoded):

| Field | Description |
|-------|-------------|
| `photo_id_front` | Front of photo ID or passport |
| `photo_id_back` | Back of photo ID or passport |
| `notary_approval_of_photo_id` | Notary approval of photo ID |
| `photo_proof_residence` | Utility bill, bank statement, etc. |
| `proof_of_income` | Income verification document |
| `proof_of_liveness` | Video or image as liveness proof |

### Organization Fields

All prefixed with `organization.`:

| Field | Type | Description |
|-------|------|-------------|
| `name` | string | Legal name as on incorporation |
| `VAT_number`, `registration_number` | string | Corporate IDs |
| `registration_date` | string | Date registered (ISO 8601) |
| `registered_address` | string | Legal address |
| `number_of_shareholders` | int | Shareholder count |
| `shareholder_name`, `director_name` | string | Key persons |
| `address_country_code` | string | ISO 3166-1 alpha-3 |
| `state_or_province`, `city`, `postal_code` | string | Address fields |
| `website`, `email`, `phone` | string | Contact info |

**File fields** (base64 encoded, prefixed with `organization.`):

| Field | Description |
|-------|-------------|
| `photo_incorporation_doc` | Incorporation documents |
| `photo_proof_address` | Business utility bill, bank statement |

### Financial Account Fields

| Field | Type | Description |
|-------|------|-------------|
| `bank_name` | string | Bank name (useful in regions without unified routing) |
| `bank_account_type` | string | checking, savings |
| `bank_account_number`, `bank_number` | string | Account/routing numbers |
| `bank_branch_number` | string | Branch identifier |
| `bank_phone_number` | string | Bank contact (E.164) |
| `clabe_number` | string | Mexico (CLABE) |
| `cbu_number`, `cbu_alias` | string | Argentina (CBU/CVU) |
| `mobile_money_number` | string | Mobile money phone (E.164) |
| `mobile_money_provider` | string | Mobile money service name |
| `crypto_address` | string | Cryptocurrency address |
| `external_transfer_memo` | string | Destination tag/memo |
| `crypto_memo` | string | **Deprecated** - use `external_transfer_memo` |

### Card Fields

All prefixed with `card.`:

| Field | Type | Description |
|-------|------|-------------|
| `number`, `cvc` | string | Card number and security code |
| `expiration_date` | string | YY-MM format (e.g., 29-11) |
| `holder_name`, `network` | string | Cardholder and brand |
| `token` | string | Payment processor token |
| `address`, `city`, `state_or_province` | string | Billing address |
| `postal_code` | string | Billing postal code |
| `country_code` | string | ISO 3166-1 alpha-2 (2-letter) |

## Error Handling

When submitting KYC data via SEP-12, various errors can occur. Here's how to handle common scenarios:

```php
<?php

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;

$person = new NaturalPersonKYCFields();
$person->firstName = 'John';
$person->lastName = 'Doe';

$kyc = new StandardKYCFields();
$kyc->naturalPersonKYCFields = $person;

$kycService = new KYCService('https://anchor.example.com/kyc');
$request = new PutCustomerInfoRequest();
$request->jwt = $jwtToken;
$request->KYCFields = $kyc;

try {
    $response = $kycService->putCustomerInfo($request);
    echo "Success! Customer ID: " . $response->getId();
} catch (ClientException $e) {
    // HTTP 4xx errors from the anchor
    $statusCode = $e->getResponse()->getStatusCode();
    
    if ($statusCode === 400) {
        // Invalid request - check required fields
        echo "Invalid KYC data: " . $e->getMessage();
    } elseif ($statusCode === 401 || $statusCode === 403) {
        // Authentication/authorization issue
        echo "Auth error - check JWT token: " . $e->getMessage();
    } elseif ($statusCode === 404) {
        // Customer not found (for updates)
        echo "Customer not found: " . $e->getMessage();
    } else {
        echo "Request failed: " . $e->getMessage();
    }
} catch (GuzzleException $e) {
    // Network or other errors
    echo "Request error: " . $e->getMessage();
}
```

## Security Considerations

- **Transmit over HTTPS only** - KYC data contains sensitive PII
- **Encrypt at rest** - Store collected data encrypted
- **Card data requires PCI-DSS** - Prefer tokenization over raw card numbers
- **Minimize collection** - Only request fields you actually need
- **Respect data regulations** - GDPR, CCPA, and local privacy laws apply
- **Use secure file handling** - Validate and sanitize uploaded documents
- **Implement access controls** - Audit logging and proper authorization

## Related SEPs

- [SEP-12](sep-12.md) - KYC API (submits SEP-09 fields to anchors)
- [SEP-24](sep-24.md) - Interactive deposit/withdrawal (may collect SEP-09 data)
- [SEP-31](sep-31.md) - Cross-border payments (uses SEP-09 for receiver info)

---

[Back to SEP Overview](README.md)
