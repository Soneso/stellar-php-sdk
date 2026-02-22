# SEP-12: KYC API

**Purpose:** Submit and manage customer KYC information for Know Your Customer compliance with anchors.
**Prerequisites:** Requires JWT from SEP-10 (see [sep-10.md](sep-10.md)); for contract accounts requires SEP-45
**SDK Namespace:** `Soneso\StellarSDK\SEP\KYCService`
**Standard KYC Fields:** `Soneso\StellarSDK\SEP\StandardKYCFields` — see [sep-09.md](sep-09.md) for all field classes, properties, constants, and prefix behavior

## Table of Contents

- [Service initialization](#service-initialization)
- [Get customer info](#get-customer-info)
- [Put customer info](#put-customer-info)
  - [Natural person fields](#natural-person-fields)
  - [Organization fields](#organization-fields)
  - [Financial account fields](#financial-account-fields)
  - [File uploads (binary fields)](#file-uploads-binary-fields)
  - [Custom fields and files](#custom-fields-and-files)
- [Put customer verification (deprecated)](#put-customer-verification-deprecated)
- [Put customer callback](#put-customer-callback)
- [Post customer file](#post-customer-file)
- [Get customer files](#get-customer-files)
- [Delete customer](#delete-customer)
- [Error handling](#error-handling)
- [Response reference](#response-reference)
- [Common pitfalls](#common-pitfalls)

---

## Service initialization

### From domain (recommended)

`KYCService::fromDomain()` fetches the anchor's `stellar.toml`, reads `KYC_SERVER` (or falls back to `TRANSFER_SERVER`), and returns a configured `KYCService`. Throws `Exception` if neither field is found.

```php
<?php declare(strict_types=1);

use Exception;
use GuzzleHttp\Client;
use Soneso\StellarSDK\SEP\KYCService\KYCService;

try {
    $kycService = KYCService::fromDomain('testanchor.stellar.org');
} catch (Exception $e) {
    echo 'No KYC service found in stellar.toml: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

// With custom HTTP client (timeouts, proxies, etc.)
$httpClient = new Client(['timeout' => 30]);
$kycService = KYCService::fromDomain('testanchor.stellar.org', $httpClient);
```

### Manual construction

Use when you already know the KYC endpoint URL.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\KYCService\KYCService;

// Trailing slash is stripped automatically
$kycService = new KYCService('https://api.anchor.com/kyc');
```

Constructor signature:
```
new KYCService(string $serviceAddress, ?Client $httpClient = null)
```

---

## Get customer info

Retrieve a customer's current verification status and the fields the anchor needs.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\KYCService\GetCustomerInfoRequest;
use Soneso\StellarSDK\SEP\KYCService\GetCustomerInfoField;
use Soneso\StellarSDK\SEP\KYCService\GetCustomerInfoProvidedField;
use Soneso\StellarSDK\SEP\KYCService\KYCService;

$kycService = KYCService::fromDomain('testanchor.stellar.org');

$request = new GetCustomerInfoRequest();
$request->jwt = $jwtToken;        // required: JWT from SEP-10 or SEP-45

// Optional: identify customer
$request->id = $customerId;       // anchor-assigned customer ID from a previous PUT
$request->account = 'GABC...';   // Stellar account (deprecated, inferred from JWT)
$request->memo = '12345';         // integer memo for shared/omnibus accounts
$request->memoType = 'id';        // deprecated; memos are always type id

// Optional: filter what fields are required
$request->type = 'sep31-sender';  // e.g. sep6, sep31-sender, sep31-receiver
$request->transactionId = 'tx_abc123'; // link to a specific transaction
$request->lang = 'en';            // ISO 639-1; defaults to "en"

$response = $kycService->getCustomerInfo($request);

// Customer status: ACCEPTED, PROCESSING, NEEDS_INFO, or REJECTED
echo 'Status: ' . $response->getStatus() . PHP_EOL;
echo 'ID: ' . $response->getId() . PHP_EOL;
echo 'Message: ' . $response->getMessage() . PHP_EOL;

// Fields the anchor still needs (null unless status is NEEDS_INFO or no customer yet)
$fields = $response->getFields(); // ?array<string, GetCustomerInfoField>
if ($fields !== null) {
    foreach ($fields as $fieldName => $field) {
        // $field->getType()        — "string", "binary", "number", or "date"
        // $field->getDescription() — human-readable description
        // $field->isOptional()     — bool; false means required
        // $field->getChoices()     — ?array<string> of valid values, or null
        $required = $field->isOptional() ? 'optional' : 'required';
        echo "$fieldName ($required): " . $field->getDescription() . PHP_EOL;
        $choices = $field->getChoices();
        if ($choices !== null) {
            echo '  Choices: ' . implode(', ', $choices) . PHP_EOL;
        }
    }
}

// Fields already provided and their status
$providedFields = $response->getProvidedFields(); // ?array<string, GetCustomerInfoProvidedField>
if ($providedFields !== null) {
    foreach ($providedFields as $fieldName => $field) {
        // $field->getStatus() — ACCEPTED, PROCESSING, REJECTED, or VERIFICATION_REQUIRED
        // $field->getError()  — string|null; set when status is REJECTED
        echo "$fieldName: " . $field->getStatus() . PHP_EOL;
        if ($field->getStatus() === 'REJECTED') {
            echo '  Reason: ' . $field->getError() . PHP_EOL;
        }
    }
}
```

---

## Put customer info

Submit or update customer data. Returns a `PutCustomerInfoResponse` with `getId(): ?string` — save this ID for future requests.

### Natural person fields

```php
<?php declare(strict_types=1);

use DateTime;
use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;

$kycService = KYCService::fromDomain('testanchor.stellar.org');

$personFields = new NaturalPersonKYCFields();

// Name
$personFields->firstName = 'Jane';
$personFields->lastName = 'Doe';
$personFields->additionalName = 'Marie';        // middle name

// Address
$personFields->address = '123 Main St, Apt 4B';
$personFields->city = 'San Francisco';
$personFields->stateOrProvince = 'CA';
$personFields->postalCode = '94102';
$personFields->addressCountryCode = 'USA';      // ISO 3166-1 alpha-3

// Contact
$personFields->mobileNumber = '+14155551234';   // E.164 format
$personFields->mobileNumberFormat = 'E.164';    // optional; defaults to E.164
$personFields->emailAddress = 'jane@example.com';
$personFields->languageCode = 'en';             // ISO 639-1

// Birth
$personFields->birthDate = '1990-05-15';        // ISO 8601 string
$personFields->birthPlace = 'New York, NY';
$personFields->birthCountryCode = 'USA';        // ISO 3166-1 alpha-3

// Tax
$personFields->taxId = '123-45-6789';
$personFields->taxIdName = 'SSN';

// Employment
$personFields->occupation = 2512;               // int: ISCO-08 code
$personFields->employerName = 'Acme Corp';
$personFields->employerAddress = '456 Business Ave, New York, NY';

// ID document (text fields)
$personFields->idType = 'passport';             // passport, drivers_license, id_card
$personFields->idNumber = 'AB123456';
$personFields->idCountryCode = 'USA';
$personFields->idIssueDate = new DateTime('2020-01-15');      // DateTime object
$personFields->idExpirationDate = new DateTime('2030-01-15'); // DateTime object

// Other
$personFields->sex = 'female';                  // male, female, other
$personFields->ipAddress = '192.168.1.1';
$personFields->referralId = 'REF123';

$kycFields = new StandardKYCFields();
$kycFields->naturalPersonKYCFields = $personFields;

$request = new PutCustomerInfoRequest();
$request->jwt = $jwtToken;
$request->KYCFields = $kycFields;
$request->type = 'sep31-sender';

// To update an existing customer, set their ID
// $request->id = $customerId;

$response = $kycService->putCustomerInfo($request);
$customerId = $response->getId(); // save for future requests
echo 'Customer ID: ' . $customerId . PHP_EOL;
```

### Organization fields

All organization fields are automatically sent with the `organization.` prefix per SEP-9.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\OrganizationKYCFields;

$kycService = KYCService::fromDomain('testanchor.stellar.org');

$orgFields = new OrganizationKYCFields();
$orgFields->name = 'Acme Corporation';           // organization.name
$orgFields->VATNumber = 'DE123456789';           // organization.VAT_number
$orgFields->registrationNumber = 'HRB 12345';   // organization.registration_number
$orgFields->registrationDate = '2010-06-15';    // ISO 8601 string
$orgFields->registeredAddress = '456 Business Ave';
$orgFields->city = 'New York';
$orgFields->stateOrProvince = 'NY';
$orgFields->postalCode = '10001';
$orgFields->addressCountryCode = 'USA';
$orgFields->numberOfShareholders = 3;           // int
$orgFields->shareholderName = 'John Smith';
$orgFields->directorName = 'Jane Doe';
$orgFields->website = 'https://acme.example.com';
$orgFields->email = 'contact@acme.example.com';
$orgFields->phone = '+12125551234';             // E.164 format

// Binary document uploads
$orgFields->photoIncorporationDoc = file_get_contents('/path/to/incorporation.pdf');
$orgFields->photoProofAddress = file_get_contents('/path/to/utility_bill.pdf');

$kycFields = new StandardKYCFields();
$kycFields->organizationKYCFields = $orgFields;

$request = new PutCustomerInfoRequest();
$request->jwt = $jwtToken;
$request->KYCFields = $kycFields;

$response = $kycService->putCustomerInfo($request);
```

### Financial account fields

Attach financial account details to either a natural person or an organization.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\FinancialAccountKYCFields;

$kycService = KYCService::fromDomain('testanchor.stellar.org');

$financialFields = new FinancialAccountKYCFields();

// Traditional bank account
$financialFields->bankName = 'First National Bank';
$financialFields->bankAccountType = 'checking';       // checking or savings
$financialFields->bankAccountNumber = '1234567890';
$financialFields->bankNumber = '021000021';            // routing number (US)
$financialFields->bankBranchNumber = '001';
$financialFields->bankPhoneNumber = '+18005551234';    // E.164

// Transfer memo / reference
$financialFields->externalTransferMemo = 'WIRE-REF-12345';

// Mexico CLABE
$financialFields->clabeNumber = '032180000118359719';

// Argentina CBU/CVU
$financialFields->cbuNumber = '0110000000001234567890';
$financialFields->cbuAlias = 'mi.cuenta.arg';

// Mobile money
$financialFields->mobileMoneyNumber = '+254712345678';
$financialFields->mobileMoneyProvider = 'M-Pesa';

// Crypto payout address
$financialFields->cryptoAddress = '0x742d35Cc6634C0532925a3b844Bc9e7595f0AB12';

// Attach to person (or $orgFields->financialAccountKYCFields = $financialFields for org)
$personFields = new NaturalPersonKYCFields();
$personFields->firstName = 'Jane';
$personFields->lastName = 'Doe';
$personFields->financialAccountKYCFields = $financialFields;

$kycFields = new StandardKYCFields();
$kycFields->naturalPersonKYCFields = $personFields;

$request = new PutCustomerInfoRequest();
$request->jwt = $jwtToken;
$request->KYCFields = $kycFields;

$response = $kycService->putCustomerInfo($request);
```

### File uploads (binary fields)

Binary fields (photos, documents) are stored as PHP strings of raw bytes and sent via multipart/form-data automatically. Assign `file_get_contents()` output directly.

```php
<?php declare(strict_types=1);

use DateTime;
use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;

$kycService = KYCService::fromDomain('testanchor.stellar.org');

$personFields = new NaturalPersonKYCFields();
$personFields->idType = 'passport';
$personFields->idNumber = 'AB123456';
$personFields->idCountryCode = 'USA';
$personFields->idIssueDate = new DateTime('2020-01-15');
$personFields->idExpirationDate = new DateTime('2030-01-15');

// Assign binary content directly from file_get_contents()
$personFields->photoIdFront = file_get_contents('/path/to/id_front.jpg');
$personFields->photoIdBack = file_get_contents('/path/to/id_back.jpg');
$personFields->notaryApprovalOfPhotoId = file_get_contents('/path/to/notary.pdf');
$personFields->photoProofResidence = file_get_contents('/path/to/utility_bill.pdf');
$personFields->proofOfIncome = file_get_contents('/path/to/bank_statement.pdf');
$personFields->proofOfLiveness = file_get_contents('/path/to/selfie_video.mp4');

$kycFields = new StandardKYCFields();
$kycFields->naturalPersonKYCFields = $personFields;

$request = new PutCustomerInfoRequest();
$request->jwt = $jwtToken;
$request->id = $customerId;   // update existing customer
$request->KYCFields = $kycFields;

$response = $kycService->putCustomerInfo($request);
```

Available binary fields on `NaturalPersonKYCFields`:

| Property | Field key sent |
|---|---|
| `$photoIdFront` | `photo_id_front` |
| `$photoIdBack` | `photo_id_back` |
| `$notaryApprovalOfPhotoId` | `notary_approval_of_photo_id` |
| `$photoProofResidence` | `photo_proof_residence` |
| `$proofOfIncome` | `proof_of_income` |
| `$proofOfLiveness` | `proof_of_liveness` |

Available binary fields on `OrganizationKYCFields`:

| Property | Field key sent |
|---|---|
| `$photoIncorporationDoc` | `organization.photo_incorporation_doc` |
| `$photoProofAddress` | `organization.photo_proof_address` |

### Custom fields and files

For anchor-specific fields not covered by SEP-9, use `customFields` (text) and `customFiles` (binary).

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;

$kycService = KYCService::fromDomain('testanchor.stellar.org');

$request = new PutCustomerInfoRequest();
$request->jwt = $jwtToken;
$request->id = $customerId;

// Custom text fields — array<string, string>
$request->customFields = [
    'custom_field_1'     => 'custom value',
    'anchor_specific_id' => 'ABC123',
    // Verification codes also go here:
    'mobile_number_verification' => '123456',
];

// Custom binary files — array<string, string> (field name => raw bytes)
$request->customFiles = [
    'additional_document' => file_get_contents('/path/to/document.pdf'),
];

$response = $kycService->putCustomerInfo($request);
```

---

## Put customer verification (deprecated)

The `PUT /customer/verification` endpoint is deprecated. The preferred approach is to submit verification codes via `PUT /customer` using `customFields` with the `_verification` suffix (shown above in [Custom fields and files](#custom-fields-and-files)).

The deprecated endpoint is supported for backwards compatibility:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerVerificationRequest;

$kycService = KYCService::fromDomain('testanchor.stellar.org');

// DEPRECATED: use PUT /customer with customFields instead
$request = new PutCustomerVerificationRequest();
$request->jwt = $jwtToken;
$request->id = $customerId;
$request->verificationFields = [
    'mobile_number_verification' => '2735021',
    'email_address_verification' => 'ABC123',
];

// Returns GetCustomerInfoResponse (same as getCustomerInfo())
$response = $kycService->putCustomerVerification($request);
echo 'Status: ' . $response->getStatus() . PHP_EOL;
```

Return type is `GetCustomerInfoResponse` (same as `getCustomerInfo()`), not `PutCustomerInfoResponse`.

---

## Put customer callback

Register a URL to receive POST notifications when a customer's status changes. The new URL replaces any previously registered callback.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerCallbackRequest;

$kycService = KYCService::fromDomain('testanchor.stellar.org');

$request = new PutCustomerCallbackRequest();
$request->jwt = $jwtToken;
$request->url = 'https://myapp.com/kyc-callback';

// Identify customer — use one of: id, account+memo, or account alone
$request->id = $customerId;       // preferred: anchor-assigned ID
// $request->account = 'GABC...'; // or Stellar account
// $request->memo = '12345';       // with memo for shared accounts
// $request->memoType = 'id';      // deprecated

// Returns Psr\Http\Message\ResponseInterface
$response = $kycService->putCustomerCallback($request);
echo 'HTTP ' . $response->getStatusCode() . PHP_EOL; // 200 on success
```

The anchor POSTs to your callback URL with the same JSON body as `GET /customer` responses, plus a `Signature` header (`t=<timestamp>, s=<base64_signature>`) signed with the anchor's `SIGNING_KEY`.

---

## Post customer file

Upload a file and receive a `file_id` to reference in subsequent `PUT /customer` requests. Useful when the anchor requires `application/json` bodies (which don't support binary data directly).

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;

$kycService = KYCService::fromDomain('testanchor.stellar.org');

// Upload the file first
$fileBytes = file_get_contents('/path/to/passport_front.jpg');

// Returns CustomerFileResponse
$fileResponse = $kycService->postCustomerFile($fileBytes, $jwtToken);

echo 'File ID: '      . $fileResponse->fileId . PHP_EOL;       // string
echo 'Content-Type: ' . $fileResponse->contentType . PHP_EOL;  // string
echo 'Size: '         . $fileResponse->size . ' bytes' . PHP_EOL; // int
echo 'Customer ID: '  . ($fileResponse->customerId ?? 'not linked yet') . PHP_EOL; // ?string

if ($fileResponse->expiresAt !== null) {
    // ?string: ISO 8601 date; file is discarded if not linked to a customer by this time
    echo 'Expires: ' . $fileResponse->expiresAt . PHP_EOL;
}

// Reference the file in a customer PUT using the field name + _file_id suffix
$request = new PutCustomerInfoRequest();
$request->jwt = $jwtToken;
$request->id = $customerId;
$request->customFields = [
    'photo_id_front_file_id' => $fileResponse->fileId,
];

$response = $kycService->putCustomerInfo($request);
```

Method signature:
```
postCustomerFile(string $fileBytes, string $jwt): CustomerFileResponse
```

`CustomerFileResponse` public properties: `string $fileId`, `string $contentType`, `int $size`, `?string $expiresAt`, `?string $customerId`.

---

## Get customer files

Retrieve information about uploaded files, either by file ID or customer ID.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\KYCService\KYCService;

$kycService = KYCService::fromDomain('testanchor.stellar.org');

// Get a specific file by ID
$response = $kycService->getCustomerFiles($jwtToken, fileId: 'file_abc123');

// Get all files for a customer
$response = $kycService->getCustomerFiles($jwtToken, customerId: $customerId);

// Both parameters together (filter to specific file for a customer)
$response = $kycService->getCustomerFiles($jwtToken, fileId: 'file_abc123', customerId: $customerId);

// $response->files is array<CustomerFileResponse>
foreach ($response->files as $file) {
    echo $file->fileId . ': ' . $file->contentType . ' (' . $file->size . ' bytes)' . PHP_EOL;
    if ($file->customerId !== null) {
        echo '  Linked to customer: ' . $file->customerId . PHP_EOL;
    }
}
```

Method signature:
```
getCustomerFiles(string $jwt, ?string $fileId = null, ?string $customerId = null): GetCustomerFilesResponse
```

`GetCustomerFilesResponse` public property: `array<CustomerFileResponse> $files` (empty array when no files found).

---

## Delete customer

Delete all personal data stored by the anchor for a given Stellar account. Used for GDPR compliance or account closure.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\KYCService\KYCService;

$kycService = KYCService::fromDomain('testanchor.stellar.org');

$accountId = 'GXXXXXX...';

// Returns Psr\Http\Message\ResponseInterface
$response = $kycService->deleteCustomer($accountId, $jwtToken);

switch ($response->getStatusCode()) {
    case 200:
        echo 'Customer data deleted' . PHP_EOL;
        break;
    case 404:
        echo 'Customer not found' . PHP_EOL;
        break;
}

// For shared/omnibus accounts, include memo to identify the specific customer
$response = $kycService->deleteCustomer(
    account: $accountId,
    jwt: $jwtToken,
    memo: '12345',
    memoType: 'id',   // deprecated but supported for compatibility
);
```

Method signature:
```
deleteCustomer(string $account, string $jwt, ?string $memo = null, ?string $memoType = null): ResponseInterface
```

---

## Error handling

`getCustomerInfo()` and `putCustomerInfo()` throw `GuzzleHttp\Exception\GuzzleException` on HTTP errors (4xx, 5xx, network failures).

```php
<?php declare(strict_types=1);

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;
use Soneso\StellarSDK\SEP\KYCService\GetCustomerInfoRequest;
use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;

$kycService = KYCService::fromDomain('testanchor.stellar.org');

try {
    $request = new GetCustomerInfoRequest();
    $request->jwt = $jwtToken;
    $request->id = $customerId;
    $response = $kycService->getCustomerInfo($request);

    switch ($response->getStatus()) {
        case 'ACCEPTED':
            echo 'Verified — proceed with transaction' . PHP_EOL;
            break;
        case 'PROCESSING':
            echo 'Under review: ' . $response->getMessage() . PHP_EOL;
            break;
        case 'NEEDS_INFO':
            $fields = $response->getFields();
            if ($fields !== null) {
                foreach ($fields as $name => $field) {
                    echo 'Required: ' . $name . ' — ' . $field->getDescription() . PHP_EOL;
                }
            }
            break;
        case 'REJECTED':
            echo 'Rejected: ' . $response->getMessage() . PHP_EOL;
            break;
    }

} catch (ClientException $e) {
    // 4xx errors
    $statusCode = $e->getResponse()->getStatusCode();
    $body = json_decode($e->getResponse()->getBody()->getContents(), true);
    $error = $body['error'] ?? 'Unknown error';

    switch ($statusCode) {
        case 400:
            echo 'Bad request: ' . $error . PHP_EOL;
            break;
        case 401:
            echo 'Authentication failed — JWT may be expired or invalid' . PHP_EOL;
            break;
        case 404:
            echo 'Customer not found: ' . $error . PHP_EOL;
            break;
        default:
            echo "Client error ($statusCode): $error" . PHP_EOL;
    }

} catch (ServerException $e) {
    echo 'Server error — try again later: ' . $e->getMessage() . PHP_EOL;

} catch (ConnectException $e) {
    echo 'Network failure: ' . $e->getMessage() . PHP_EOL;
}
```

`putCustomerCallback()` and `deleteCustomer()` return `Psr\Http\Message\ResponseInterface` directly (they do not throw on 4xx — check `getStatusCode()` manually).

`postCustomerFile()` throws `GuzzleException` on failure, including HTTP 413 when the file exceeds the server's size limit.

---

## Response reference

### GetCustomerInfoResponse

| Method | Return type | Description |
|--------|-------------|-------------|
| `getId()` | `?string` | Anchor-assigned customer ID |
| `getStatus()` | `string` | `ACCEPTED`, `PROCESSING`, `NEEDS_INFO`, or `REJECTED` |
| `getMessage()` | `?string` | Human-readable status message |
| `getFields()` | `?array<string, GetCustomerInfoField>` | Fields still needed (keyed by SEP-9 field name) |
| `getProvidedFields()` | `?array<string, GetCustomerInfoProvidedField>` | Fields already received (keyed by SEP-9 field name) |

### GetCustomerInfoField

| Method | Return type | Description |
|--------|-------------|-------------|
| `getType()` | `string` | `string`, `binary`, `number`, or `date` |
| `getDescription()` | `?string` | Human-readable field description |
| `isOptional()` | `bool` | `false` = required; `true` = optional |
| `getChoices()` | `?array<string>` | Valid values, or `null` if unconstrained |

### GetCustomerInfoProvidedField

Same methods as `GetCustomerInfoField`, plus:

| Method | Return type | Description |
|--------|-------------|-------------|
| `getStatus()` | `?string` | `ACCEPTED`, `PROCESSING`, `REJECTED`, or `VERIFICATION_REQUIRED` |
| `getError()` | `?string` | Rejection reason when status is `REJECTED` |

### PutCustomerInfoResponse

| Method | Return type | Description |
|--------|-------------|-------------|
| `getId()` | `?string` | Anchor-assigned customer ID |

### CustomerFileResponse

| Property | Type | Description |
|----------|------|-------------|
| `$fileId` | `string` | Unique file identifier |
| `$contentType` | `string` | MIME type of the file |
| `$size` | `int` | File size in bytes |
| `$expiresAt` | `?string` | ISO 8601 expiry date, or `null` |
| `$customerId` | `?string` | Linked customer ID, or `null` |

### GetCustomerFilesResponse

| Property | Type | Description |
|----------|------|-------------|
| `$files` | `array<CustomerFileResponse>` | List of files; empty array if none |

---

## Common pitfalls

**WRONG property name: `$KYCFields` is uppercase**

```php
// WRONG: lowercase — PHP will silently create a new property
$request->kycFields = $kycFields;

// CORRECT: uppercase K, Y, C
$request->KYCFields = $kycFields;
```

**WRONG: accessing CustomerFileResponse via getters — it has public properties**

```php
// WRONG: there are no getter methods on CustomerFileResponse
$id = $fileResponse->getFileId();          // fatal error
$ct = $fileResponse->getContentType();     // fatal error

// CORRECT: access public properties directly
$id = $fileResponse->fileId;
$ct = $fileResponse->contentType;
$sz = $fileResponse->size;
$ex = $fileResponse->expiresAt;
$cu = $fileResponse->customerId;
```

**WRONG: accessing GetCustomerFilesResponse->files via a getter**

```php
// WRONG: no getter on GetCustomerFilesResponse
$files = $response->getFiles();  // fatal error

// CORRECT: public property
$files = $response->files;       // array<CustomerFileResponse>
```

**WRONG: idIssueDate and idExpirationDate expect DateTime objects, not strings**

```php
// WRONG: string — PHP will create a DateTime from string silently in some contexts,
// but the property is typed DateTime|null
$personFields->idIssueDate = '2020-01-15';       // type mismatch

// CORRECT: DateTime object
$personFields->idIssueDate = new DateTime('2020-01-15');
$personFields->idExpirationDate = new DateTime('2030-01-15');
```

**WRONG: occupation expects int, not string**

```php
// WRONG: string
$personFields->occupation = '2512';  // type mismatch (typed int|null)

// CORRECT: int (ISCO-08 code)
$personFields->occupation = 2512;
```

**WRONG: putCustomerVerification() returns GetCustomerInfoResponse, not PutCustomerInfoResponse**

```php
// WRONG: calling getId() and expecting customer ID
$response = $kycService->putCustomerVerification($request);
$customerId = $response->getId(); // getId() here is GetCustomerInfoResponse::getId()
                                   // returns the customer's ID (correct), but
                                   // this is NOT a PutCustomerInfoResponse

// CORRECT: use getStatus() to check verification result
$response = $kycService->putCustomerVerification($request);
echo $response->getStatus(); // ACCEPTED, NEEDS_INFO, etc.
```

**WRONG: deleteCustomer() first parameter is the account ID, not the customer ID**

```php
// WRONG: passing the anchor-assigned customer ID (UUID)
$kycService->deleteCustomer($customerId, $jwtToken);  // 404

// CORRECT: first argument is the Stellar account ID (G... address)
$kycService->deleteCustomer('GABC...stellarAccountId', $jwtToken);
```

**WRONG: putCustomerCallback() and deleteCustomer() throw on HTTP errors by default with Guzzle**

By default Guzzle throws `ClientException` on 4xx responses. If you need to suppress throwing, configure `http_errors => false` on the `Client`. Otherwise, wrap in try/catch.

---

## Customer statuses

| Status | Meaning |
|--------|---------|
| `ACCEPTED` | All required info verified. Customer may proceed. |
| `PROCESSING` | Info under review. Check back later. |
| `NEEDS_INFO` | Additional fields required. See `getFields()`. |
| `REJECTED` | Permanently rejected. See `getMessage()` for reason. |

## Field statuses

| Status | Meaning |
|--------|---------|
| `ACCEPTED` | Field validated. |
| `PROCESSING` | Field under review. |
| `REJECTED` | Field rejected. See `getError()` for reason. |
| `VERIFICATION_REQUIRED` | Code sent to customer (SMS/email); submit code with `_verification` suffix. |

---
