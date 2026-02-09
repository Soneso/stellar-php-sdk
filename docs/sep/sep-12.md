# SEP-12: KYC API

The [SEP-12](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md) protocol defines how to submit and manage customer information for Know Your Customer (KYC) requirements. Anchors use this to collect identity documents, personal information, and verification data before processing deposits, withdrawals, or payments.

Use SEP-12 when:
- An anchor requires identity verification before deposit/withdrawal
- You need to check what KYC information an anchor requires
- You want to update previously submitted customer information
- You need to verify contact information (phone, email)

This SDK implements SEP-12 v1.15.0.

## Table of Contents

- [Quick example](#quick-example)
- [Creating the KYC service](#creating-the-kyc-service)
- [Checking customer status](#checking-customer-status)
- [Submitting customer information](#submitting-customer-information)
  - [Personal information](#personal-information)
  - [Complete natural person fields](#complete-natural-person-fields)
  - [Financial account information](#financial-account-information)
  - [Uploading ID documents](#uploading-id-documents)
  - [Organization KYC](#organization-kyc)
- [Verifying contact information](#verifying-contact-information)
- [File upload endpoint](#file-upload-endpoint)
- [Callback notifications](#callback-notifications)
- [Deleting customer data](#deleting-customer-data)
- [Shared/omnibus accounts](#sharedomnibus-accounts)
- [Contract accounts (C... addresses)](#contract-accounts-c-addresses)
- [Transaction-based KYC](#transaction-based-kyc)
- [Error handling](#error-handling)
- [Customer statuses](#customer-statuses)
- [Field statuses](#field-statuses)
- [Related specifications](#related-specifications)

## Quick example

This example shows the typical KYC workflow: create the service, check what information is needed, then submit customer data.

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\GetCustomerInfoRequest;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;

// Create service from anchor's domain (discovers URL from stellar.toml)
$kycService = KYCService::fromDomain("testanchor.stellar.org");

// Check what info the anchor needs (requires JWT token from SEP-10 or SEP-45)
$request = new GetCustomerInfoRequest();
$request->jwt = $jwtToken;
$response = $kycService->getCustomerInfo($request);

echo "Status: " . $response->getStatus() . "\n";

// Submit customer information
$personFields = new NaturalPersonKYCFields();
$personFields->firstName = "Jane";
$personFields->lastName = "Doe";
$personFields->emailAddress = "jane@example.com";

$kycFields = new StandardKYCFields();
$kycFields->naturalPersonKYCFields = $personFields;

$putRequest = new PutCustomerInfoRequest();
$putRequest->jwt = $jwtToken;
$putRequest->KYCFields = $kycFields;

$putResponse = $kycService->putCustomerInfo($putRequest);
$customerId = $putResponse->getId(); // Save for future requests
```

## Creating the KYC service

### From Domain (Recommended)

The recommended approach discovers the KYC service URL automatically from the anchor's `stellar.toml` file. This uses the `KYC_SERVER` or `TRANSFER_SERVER` endpoint.

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use GuzzleHttp\Client;

// Loads service URL from stellar.toml automatically
$kycService = KYCService::fromDomain("testanchor.stellar.org");

// With custom HTTP client (for timeouts, proxies, etc.)
$httpClient = new Client(['timeout' => 30]);
$kycService = KYCService::fromDomain("testanchor.stellar.org", $httpClient);
```

### From Direct URL

Use this when you already know the KYC service endpoint URL.

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;

$kycService = new KYCService("https://api.anchor.com/kyc");
```

## Checking customer status

Before submitting data, check what fields the anchor requires. The response includes the customer's current verification status and lists required fields.

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\GetCustomerInfoRequest;

$kycService = KYCService::fromDomain("testanchor.stellar.org");

$request = new GetCustomerInfoRequest();
$request->jwt = $jwtToken; // Required: JWT from SEP-10 or SEP-45

// For existing customers, include their ID for faster lookup
$request->id = $customerId;

// Specify the type of operation (affects which fields are required)
$request->type = "sep31-sender"; // or "sep6-deposit", "sep31-receiver", etc.

// Request field descriptions in a specific language
$request->lang = "de"; // ISO 639-1 code, defaults to "en"

$response = $kycService->getCustomerInfo($request);

// Check customer status
$status = $response->getStatus();
echo "Status: $status\n"; // ACCEPTED, PROCESSING, NEEDS_INFO, or REJECTED

// Get customer ID (if registered)
$id = $response->getId();

// Get human-readable status message
$message = $response->getMessage();

// Check which fields are still needed
$fieldsNeeded = $response->getFields();
if ($fieldsNeeded !== null) {
    foreach ($fieldsNeeded as $fieldName => $field) {
        echo "Field: $fieldName\n";
        echo "  Type: " . $field->getType() . "\n"; // string, binary, number, date
        echo "  Description: " . $field->getDescription() . "\n";
        echo "  Required: " . ($field->isOptional() ? "No" : "Yes") . "\n";
        
        // Some fields have predefined valid values
        $choices = $field->getChoices();
        if ($choices !== null) {
            echo "  Valid values: " . implode(", ", $choices) . "\n";
        }
    }
}

// Check fields already provided and their verification status
$providedFields = $response->getProvidedFields();
if ($providedFields !== null) {
    foreach ($providedFields as $fieldName => $field) {
        echo "Provided: $fieldName\n";
        echo "  Status: " . $field->getStatus() . "\n"; // ACCEPTED, PROCESSING, REJECTED, VERIFICATION_REQUIRED
        
        // If rejected, get the reason
        if ($field->getStatus() === "REJECTED") {
            echo "  Error: " . $field->getError() . "\n";
        }
    }
}
```

## Submitting customer information

### Personal information

Submit basic personal information for individual customers. The `StandardKYCFields` container holds `NaturalPersonKYCFields` for individuals or `OrganizationKYCFields` for businesses.

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;

$kycService = KYCService::fromDomain("testanchor.stellar.org");

$personFields = new NaturalPersonKYCFields();
$personFields->firstName = "Jane";
$personFields->lastName = "Doe";
$personFields->emailAddress = "jane@example.com";
$personFields->mobileNumber = "+14155551234"; // E.164 format
$personFields->birthDate = "1990-05-15"; // ISO 8601 format

$kycFields = new StandardKYCFields();
$kycFields->naturalPersonKYCFields = $personFields;

$request = new PutCustomerInfoRequest();
$request->jwt = $jwtToken;
$request->KYCFields = $kycFields;
$request->type = "sep31-sender";

$response = $kycService->putCustomerInfo($request);
$customerId = $response->getId(); // Save this for future requests
```

### Complete natural person fields

The SDK supports all SEP-9 standard fields for natural persons. Here's a complete example showing all available fields.

```php
<?php

use DateTime;
use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;

$kycService = KYCService::fromDomain("testanchor.stellar.org");

$personFields = new NaturalPersonKYCFields();

// Name fields
$personFields->firstName = "Jane";
$personFields->lastName = "Doe";
$personFields->additionalName = "Marie"; // Middle name

// Address fields
$personFields->address = "123 Main St, Apt 4B";
$personFields->city = "San Francisco";
$personFields->stateOrProvince = "CA";
$personFields->postalCode = "94102";
$personFields->addressCountryCode = "USA"; // ISO 3166-1 alpha-3

// Contact information
$personFields->mobileNumber = "+14155551234"; // E.164 format
$personFields->mobileNumberFormat = "E.164"; // Optional: specify format
$personFields->emailAddress = "jane@example.com";
$personFields->languageCode = "en"; // ISO 639-1

// Birth information
$personFields->birthDate = "1990-05-15"; // ISO 8601
$personFields->birthPlace = "New York, NY, USA";
$personFields->birthCountryCode = "USA"; // ISO 3166-1 alpha-3

// Tax information
$personFields->taxId = "123-45-6789";
$personFields->taxIdName = "SSN"; // or "ITIN", etc.

// Employment
$personFields->occupation = 2512; // ISCO-08 code
$personFields->employerName = "Acme Corp";
$personFields->employerAddress = "456 Business Ave, New York, NY 10001";

// Identity document
$personFields->idType = "passport"; // or "drivers_license", "id_card"
$personFields->idNumber = "AB123456";
$personFields->idCountryCode = "USA"; // ISO 3166-1 alpha-3
$personFields->idIssueDate = new DateTime("2020-01-15");
$personFields->idExpirationDate = new DateTime("2030-01-15");

// Other fields
$personFields->sex = "female"; // or "male", "other"
$personFields->ipAddress = "192.168.1.1";
$personFields->referralId = "REF123"; // Referral or origin code

$kycFields = new StandardKYCFields();
$kycFields->naturalPersonKYCFields = $personFields;

$request = new PutCustomerInfoRequest();
$request->jwt = $jwtToken;
$request->KYCFields = $kycFields;

$response = $kycService->putCustomerInfo($request);
```

### Financial account information

For deposits and withdrawals, anchors often require banking or payment account details. Use `FinancialAccountKYCFields` for this information.

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\FinancialAccountKYCFields;

$kycService = KYCService::fromDomain("testanchor.stellar.org");

// Set up financial account details
$financialFields = new FinancialAccountKYCFields();

// Traditional bank account
$financialFields->bankName = "First National Bank";
$financialFields->bankAccountType = "checking"; // or "savings"
$financialFields->bankAccountNumber = "1234567890";
$financialFields->bankNumber = "021000021"; // Routing number (US)
$financialFields->bankBranchNumber = "001";
$financialFields->bankPhoneNumber = "+18005551234"; // E.164 format

// International transfer memo
$financialFields->externalTransferMemo = "WIRE-REF-12345";

// Mexico CLABE
$financialFields->clabeNumber = "032180000118359719";

// Argentina CBU/CVU
$financialFields->cbuNumber = "0110000000001234567890";
$financialFields->cbuAlias = "mi.cuenta.arg";

// Mobile money (for regions using mobile payments)
$financialFields->mobileMoneyNumber = "+254712345678";
$financialFields->mobileMoneyProvider = "M-Pesa";

// Cryptocurrency (if anchor supports crypto payouts)
$financialFields->cryptoAddress = "0x742d35Cc6634C0532925a3b844Bc9e7595f0AB12";

// Attach to person fields
$personFields = new NaturalPersonKYCFields();
$personFields->firstName = "Jane";
$personFields->lastName = "Doe";
$personFields->financialAccountKYCFields = $financialFields;

$kycFields = new StandardKYCFields();
$kycFields->naturalPersonKYCFields = $personFields;

$request = new PutCustomerInfoRequest();
$request->jwt = $jwtToken;
$request->KYCFields = $kycFields;

$response = $kycService->putCustomerInfo($request);
```

### Uploading ID documents

Binary fields like photos and documents are uploaded directly within the request. Load files as binary strings.

```php
<?php

use DateTime;
use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;

$kycService = KYCService::fromDomain("testanchor.stellar.org");

// Load ID document images as binary data
$idFrontBytes = file_get_contents('/path/to/id_front.jpg');
$idBackBytes = file_get_contents('/path/to/id_back.jpg');
$proofOfAddressBytes = file_get_contents('/path/to/utility_bill.pdf');
$proofOfIncomeBytes = file_get_contents('/path/to/bank_statement.pdf');
$selfieBytes = file_get_contents('/path/to/selfie_video.mp4');

$personFields = new NaturalPersonKYCFields();

// ID document details
$personFields->idType = "passport";
$personFields->idNumber = "AB123456";
$personFields->idCountryCode = "USA";
$personFields->idIssueDate = new DateTime("2020-01-15");
$personFields->idExpirationDate = new DateTime("2030-01-15");

// Document images (binary)
$personFields->photoIdFront = $idFrontBytes;
$personFields->photoIdBack = $idBackBytes;
$personFields->notaryApprovalOfPhotoId = null; // If required by anchor

// Proof of address (utility bill, bank statement)
$personFields->photoProofResidence = $proofOfAddressBytes;

// Proof of income (for high-value transactions)
$personFields->proofOfIncome = $proofOfIncomeBytes;

// Liveness proof (video selfie for identity verification)
$personFields->proofOfLiveness = $selfieBytes;

$kycFields = new StandardKYCFields();
$kycFields->naturalPersonKYCFields = $personFields;

$request = new PutCustomerInfoRequest();
$request->jwt = $jwtToken;
$request->id = $customerId; // Update existing customer
$request->KYCFields = $kycFields;

$response = $kycService->putCustomerInfo($request);
```

### Organization KYC

For business/corporate customers, use `OrganizationKYCFields`. All organization fields are automatically prefixed with `organization.` as per SEP-9.

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\OrganizationKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\FinancialAccountKYCFields;

$kycService = KYCService::fromDomain("testanchor.stellar.org");

$orgFields = new OrganizationKYCFields();

// Company identification
$orgFields->name = "Acme Corporation";
$orgFields->VATNumber = "DE123456789";
$orgFields->registrationNumber = "HRB 12345";
$orgFields->registrationDate = "2010-06-15"; // ISO 8601

// Registered address
$orgFields->registeredAddress = "456 Business Ave, Suite 100";
$orgFields->city = "New York";
$orgFields->stateOrProvince = "NY";
$orgFields->postalCode = "10001";
$orgFields->addressCountryCode = "USA"; // ISO 3166-1 alpha-3

// Corporate structure
$orgFields->numberOfShareholders = 3;
$orgFields->shareholderName = "John Smith"; // Ultimate beneficial owner
$orgFields->directorName = "Jane Doe";

// Contact information
$orgFields->website = "https://acme-corp.example.com";
$orgFields->email = "contact@acme-corp.example.com";
$orgFields->phone = "+12125551234"; // E.164 format

// Corporate documents (binary)
$orgFields->photoIncorporationDoc = file_get_contents('/path/to/incorporation.pdf');
$orgFields->photoProofAddress = file_get_contents('/path/to/business_utility_bill.pdf');

// Organization's bank account
$orgFinancialFields = new FinancialAccountKYCFields();
$orgFinancialFields->bankName = "Business Bank";
$orgFinancialFields->bankAccountNumber = "9876543210";
$orgFinancialFields->bankNumber = "021000021";
$orgFields->financialAccountKYCFields = $orgFinancialFields;

$kycFields = new StandardKYCFields();
$kycFields->organizationKYCFields = $orgFields;

$request = new PutCustomerInfoRequest();
$request->jwt = $jwtToken;
$request->KYCFields = $kycFields;
$request->type = "sep31-sender";

$response = $kycService->putCustomerInfo($request);
```

### Using custom fields

If an anchor requires non-standard fields, use `customFields` for text data and `customFiles` for binary data.

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;

$kycService = KYCService::fromDomain("testanchor.stellar.org");

$request = new PutCustomerInfoRequest();
$request->jwt = $jwtToken;
$request->id = $customerId;

// Custom text fields
$request->customFields = [
    "custom_field_1" => "custom value",
    "anchor_specific_id" => "ABC123",
];

// Custom binary files
$request->customFiles = [
    "additional_document" => file_get_contents('/path/to/document.pdf'),
];

$response = $kycService->putCustomerInfo($request);
```

## Verifying contact information

Some anchors require verification of contact information (phone or email) via a confirmation code. When a field has `VERIFICATION_REQUIRED` status, submit the code using the `PUT /customer` endpoint with `_verification` suffix.

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\GetCustomerInfoRequest;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;

$kycService = KYCService::fromDomain("testanchor.stellar.org");

// First, check if verification is required
$getRequest = new GetCustomerInfoRequest();
$getRequest->jwt = $jwtToken;
$getRequest->id = $customerId;
$response = $kycService->getCustomerInfo($getRequest);

$providedFields = $response->getProvidedFields();
if ($providedFields !== null) {
    foreach ($providedFields as $fieldName => $field) {
        if ($field->getStatus() === "VERIFICATION_REQUIRED") {
            echo "Verification required for: $fieldName\n";
            // Anchor has sent a code to the customer via SMS or email
        }
    }
}

// Submit verification code via PUT /customer with _verification suffix
$putRequest = new PutCustomerInfoRequest();
$putRequest->jwt = $jwtToken;
$putRequest->id = $customerId;
$putRequest->customFields = [
    "mobile_number_verification" => "123456", // Code sent via SMS
];

$verifyResponse = $kycService->putCustomerInfo($putRequest);
echo "Customer ID: " . $verifyResponse->getId() . "\n";
```

### Deprecated verification endpoint

The SDK also supports the deprecated `PUT /customer/verification` endpoint for backwards compatibility. New implementations should use the method above instead.

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerVerificationRequest;

$kycService = KYCService::fromDomain("testanchor.stellar.org");

// Deprecated: Use PUT /customer with _verification suffix instead
$request = new PutCustomerVerificationRequest();
$request->jwt = $jwtToken;
$request->id = $customerId;
$request->verificationFields = [
    "mobile_number_verification" => "123456",
    "email_address_verification" => "ABC123",
];

$response = $kycService->putCustomerVerification($request);
echo "Status: " . $response->getStatus() . "\n";
```

## File upload endpoint

For complex data structures that require `application/json`, upload files separately using the files endpoint, then reference them by `file_id` in customer requests.

### Upload a file

Upload a file and receive a `file_id` that can be referenced in subsequent `PUT /customer` requests.

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;

$kycService = KYCService::fromDomain("testanchor.stellar.org");

// Upload file first
$fileBytes = file_get_contents('/path/to/passport_front.jpg');
$fileResponse = $kycService->postCustomerFile($fileBytes, $jwtToken);

echo "File ID: " . $fileResponse->fileId . "\n";
echo "Content-Type: " . $fileResponse->contentType . "\n";
echo "Size: " . $fileResponse->size . " bytes\n";

// Optional: File may expire if not linked to a customer
if ($fileResponse->expiresAt !== null) {
    echo "Expires: " . $fileResponse->expiresAt . "\n";
}

// Reference the file in customer data using _file_id suffix
$request = new PutCustomerInfoRequest();
$request->jwt = $jwtToken;
$request->id = $customerId;
$request->customFields = [
    "photo_id_front_file_id" => $fileResponse->fileId,
];

$response = $kycService->putCustomerInfo($request);
```

### Retrieve file information

Get information about previously uploaded files by file ID or customer ID.

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;

$kycService = KYCService::fromDomain("testanchor.stellar.org");

// Get a specific file by ID
$response = $kycService->getCustomerFiles($jwtToken, fileId: "file_abc123");
foreach ($response->files as $file) {
    echo "File: " . $file->fileId . "\n";
    echo "  Type: " . $file->contentType . "\n";
    echo "  Size: " . $file->size . " bytes\n";
    if ($file->customerId !== null) {
        echo "  Customer: " . $file->customerId . "\n";
    }
}

// Get all files for a customer
$response = $kycService->getCustomerFiles($jwtToken, customerId: $customerId);
foreach ($response->files as $file) {
    echo "File: " . $file->fileId . " (" . $file->contentType . ")\n";
}
```

## Callback notifications

Register a callback URL to receive automatic notifications when customer status changes. This avoids polling the `GET /customer` endpoint.

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerCallbackRequest;

$kycService = KYCService::fromDomain("testanchor.stellar.org");

$request = new PutCustomerCallbackRequest();
$request->jwt = $jwtToken;
$request->id = $customerId;
$request->url = "https://myapp.com/kyc-callback";

// Optional: identify customer without ID
// $request->account = "GXXXXX..."; // Stellar account
// $request->memo = "12345"; // For shared accounts

$response = $kycService->putCustomerCallback($request);

if ($response->getStatusCode() === 200) {
    echo "Callback registered successfully\n";
}

// Your callback endpoint will receive POST requests with the same
// structure as GET /customer responses, plus a cryptographic signature
// in the Signature header for verification.
```

### Verifying callback signatures

The anchor signs callback requests using their `SIGNING_KEY`. Verify the `Signature` header to ensure authenticity.

```php
<?php

// In your callback endpoint handler:

// 1. Parse the Signature header: "t=<timestamp>, s=<base64 signature>"
$signatureHeader = $_SERVER['HTTP_SIGNATURE'] ?? $_SERVER['HTTP_X_STELLAR_SIGNATURE'] ?? '';
preg_match('/t=(\d+), s=(.+)/', $signatureHeader, $matches);
$timestamp = $matches[1];
$signature = base64_decode($matches[2]);

// 2. Verify freshness (reject if too old, e.g., > 2 minutes)
if (time() - (int)$timestamp > 120) {
    http_response_code(400);
    exit('Request too old');
}

// 3. Get request body
$body = file_get_contents('php://input');

// 4. Reconstruct signed payload: timestamp.host.body
$host = $_SERVER['HTTP_HOST'];
$payload = "$timestamp.$host.$body";

// 5. Verify signature using anchor's SIGNING_KEY from stellar.toml
// (Use Stellar SDK's signature verification)
```

## Deleting customer data

Request deletion of all stored customer data. This is useful for GDPR compliance or when a customer closes their account.

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;

$kycService = KYCService::fromDomain("testanchor.stellar.org");

$accountId = "GXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";

// Delete customer data
$response = $kycService->deleteCustomer($accountId, $jwtToken);

switch ($response->getStatusCode()) {
    case 200:
        echo "Customer data deleted successfully\n";
        break;
    case 401:
        echo "Authentication failed\n";
        break;
    case 404:
        echo "Customer not found\n";
        break;
}

// For shared accounts, include memo
$response = $kycService->deleteCustomer(
    account: $accountId,
    jwt: $jwtToken,
    memo: "12345",
    memoType: "id" // deprecated but supported for compatibility
);
```

## Shared/omnibus accounts

When multiple customers share a single Stellar account (common for exchanges and custodians), use memos to distinguish them. The memo should match the one used during SEP-10 or SEP-45 authentication.

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\GetCustomerInfoRequest;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;

$kycService = KYCService::fromDomain("testanchor.stellar.org");

// Get customer info with memo
$getRequest = new GetCustomerInfoRequest();
$getRequest->jwt = $jwtToken; // JWT should contain account:memo in sub claim
$getRequest->account = "GXXXXXX..."; // Optional: inferred from JWT
$getRequest->memo = "12345"; // Unique identifier for this customer
$getRequest->memoType = "id"; // Deprecated: should always be "id"

$response = $kycService->getCustomerInfo($getRequest);

// Submit customer info with memo
$personFields = new NaturalPersonKYCFields();
$personFields->firstName = "Jane";
$personFields->lastName = "Doe";

$kycFields = new StandardKYCFields();
$kycFields->naturalPersonKYCFields = $personFields;

$putRequest = new PutCustomerInfoRequest();
$putRequest->jwt = $jwtToken;
$putRequest->KYCFields = $kycFields;
$putRequest->memo = "12345"; // Must match JWT's sub value
$putRequest->memoType = "id"; // Deprecated but supported

$response = $kycService->putCustomerInfo($putRequest);
```

## Contract accounts (C... addresses)

For Soroban contract accounts (addresses starting with `C...`), authenticate using [SEP-45](sep-45.md) instead of SEP-10. The JWT token will contain the contract address.

> **Important:** When using contract accounts (C... addresses), you must **NOT** specify a `memo`. Contract addresses are unique identifiers and do not support memo-based sub-accounts.

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\GetCustomerInfoRequest;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;

$kycService = KYCService::fromDomain("testanchor.stellar.org");

// Contract account address (starts with C...)
$contractAccount = "CXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";

// Get customer info for contract account
// JWT obtained via SEP-45 authentication
$getRequest = new GetCustomerInfoRequest();
$getRequest->jwt = $sep45JwtToken; // From SEP-45 (not SEP-10)
$getRequest->account = $contractAccount;
// Do NOT set memo for contract accounts!

$response = $kycService->getCustomerInfo($getRequest);

// Submit customer info for contract account
$personFields = new NaturalPersonKYCFields();
$personFields->firstName = "Jane";
$personFields->lastName = "Doe";
$personFields->emailAddress = "jane@example.com";

$kycFields = new StandardKYCFields();
$kycFields->naturalPersonKYCFields = $personFields;

$putRequest = new PutCustomerInfoRequest();
$putRequest->jwt = $sep45JwtToken;
$putRequest->account = $contractAccount;
$putRequest->KYCFields = $kycFields;
// Do NOT set memo for contract accounts!

$response = $kycService->putCustomerInfo($putRequest);
```

## Transaction-based KYC

Some anchors require different KYC information based on transaction details (e.g., higher amounts require more verification). Use `transactionId` to link KYC to a specific transaction.

> **Important:** When using `transactionId`, the `type` parameter is **required**. Valid values include:
> - `sep6` - For SEP-6 deposit/withdrawal transactions
> - `sep31-sender` - For SEP-31 cross-border payment senders
> - `sep31-receiver` - For SEP-31 cross-border payment receivers

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\GetCustomerInfoRequest;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;

$kycService = KYCService::fromDomain("testanchor.stellar.org");

// Check KYC requirements for a specific transaction
$getRequest = new GetCustomerInfoRequest();
$getRequest->jwt = $jwtToken;
$getRequest->transactionId = "tx_abc123"; // From SEP-6 or SEP-31
$getRequest->type = "sep6"; // REQUIRED when using transactionId

$response = $kycService->getCustomerInfo($getRequest);

// For large transactions, anchor may require additional fields
$fieldsNeeded = $response->getFields();
if ($fieldsNeeded !== null && isset($fieldsNeeded['proof_of_income'])) {
    echo "Large transaction: proof of income required\n";
}

// Submit KYC for the transaction
$personFields = new NaturalPersonKYCFields();
$personFields->firstName = "Jane";
$personFields->lastName = "Doe";
$personFields->proofOfIncome = file_get_contents('/path/to/income_proof.pdf');

$kycFields = new StandardKYCFields();
$kycFields->naturalPersonKYCFields = $personFields;

$putRequest = new PutCustomerInfoRequest();
$putRequest->jwt = $jwtToken;
$putRequest->KYCFields = $kycFields;
$putRequest->transactionId = "tx_abc123";
$putRequest->type = "sep6";

$response = $kycService->putCustomerInfo($putRequest);
```

## Error handling

Handle various error conditions that may occur during KYC operations.

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\GetCustomerInfoRequest;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;
use Exception;

try {
    $kycService = KYCService::fromDomain("testanchor.stellar.org");
} catch (Exception $e) {
    // No KYC_SERVER or TRANSFER_SERVER in stellar.toml
    echo "Failed to discover KYC service: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    $request = new GetCustomerInfoRequest();
    $request->jwt = $jwtToken;
    $request->id = $customerId;
    
    $response = $kycService->getCustomerInfo($request);
    
    // Handle different statuses
    switch ($response->getStatus()) {
        case "ACCEPTED":
            echo "Customer verified! Proceeding...\n";
            break;
            
        case "PROCESSING":
            echo "KYC under review. Check back later.\n";
            echo "Message: " . $response->getMessage() . "\n";
            break;
            
        case "NEEDS_INFO":
            echo "Additional information required:\n";
            $fields = $response->getFields();
            if ($fields !== null) {
                foreach ($fields as $name => $field) {
                    $required = $field->isOptional() ? "(optional)" : "(required)";
                    echo "  - $name $required: " . $field->getDescription() . "\n";
                }
            }
            break;
            
        case "REJECTED":
            echo "KYC rejected: " . $response->getMessage() . "\n";
            // Customer cannot proceed - may need to contact support
            break;
    }
    
} catch (ClientException $e) {
    // 4xx errors
    $statusCode = $e->getResponse()->getStatusCode();
    $body = json_decode($e->getResponse()->getBody()->getContents(), true);
    
    switch ($statusCode) {
        case 400:
            echo "Bad request: " . ($body['error'] ?? 'Unknown error') . "\n";
            break;
        case 401:
            echo "Authentication failed - JWT may be expired\n";
            break;
        case 404:
            echo "Customer not found: " . ($body['error'] ?? 'Unknown customer') . "\n";
            break;
        default:
            echo "Client error ($statusCode): " . ($body['error'] ?? 'Unknown') . "\n";
    }
    
} catch (ServerException $e) {
    // 5xx errors
    echo "Server error: " . $e->getMessage() . "\n";
    echo "Try again later\n";
    
} catch (ConnectException $e) {
    // Network errors
    echo "Connection failed: " . $e->getMessage() . "\n";
}
```

### Handling PUT errors

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;
use GuzzleHttp\Exception\ClientException;

$kycService = KYCService::fromDomain("testanchor.stellar.org");

try {
    $personFields = new NaturalPersonKYCFields();
    $personFields->firstName = "Jane";
    $personFields->photoIdFront = file_get_contents('/path/to/id.jpg');
    
    $kycFields = new StandardKYCFields();
    $kycFields->naturalPersonKYCFields = $personFields;
    
    $request = new PutCustomerInfoRequest();
    $request->jwt = $jwtToken;
    $request->KYCFields = $kycFields;
    
    $response = $kycService->putCustomerInfo($request);
    echo "Customer ID: " . $response->getId() . "\n";
    
} catch (ClientException $e) {
    $body = json_decode($e->getResponse()->getBody()->getContents(), true);
    $error = $body['error'] ?? 'Unknown error';
    
    // Common error messages
    if (str_contains($error, 'cannot be decoded')) {
        echo "Invalid file format: $error\n";
    } elseif (str_contains($error, 'confirmation code')) {
        echo "Invalid verification code: $error\n";
    } elseif (str_contains($error, 'type')) {
        echo "Invalid customer type: $error\n";
    } else {
        echo "Error: $error\n";
    }
}
```

## Customer statuses

The `status` field in `GetCustomerInfoResponse` indicates the customer's position in the KYC process:

| Status | Description |
|--------|-------------|
| `ACCEPTED` | All required KYC fields accepted. Customer can proceed with transactions. May revert if issues found later. |
| `PROCESSING` | KYC information is being reviewed. Check back later for updates. |
| `NEEDS_INFO` | Additional information required. Check `fields` array for what's needed. |
| `REJECTED` | KYC permanently rejected. Customer cannot use the service. Check `message` for reason. |

## Field statuses

The `status` field in `GetCustomerInfoProvidedField` indicates the verification state of individual fields:

| Status | Description |
|--------|-------------|
| `ACCEPTED` | Field has been validated and accepted. |
| `PROCESSING` | Field is being reviewed. Check back later. |
| `REJECTED` | Field was rejected. Check `error` for reason. May be resubmitted if customer status is `NEEDS_INFO`. |
| `VERIFICATION_REQUIRED` | Field needs verification (e.g., confirmation code). Submit code with `_verification` suffix. |

## Related specifications

- [SEP-10](sep-10.md) - Web Authentication (provides JWT for KYC requests)
- [SEP-45](sep-45.md) - Web Authentication for Contract Accounts (C... addresses)
- [SEP-9](sep-09.md) - Standard KYC Fields specification
- [SEP-6](sep-06.md) - Deposit and Withdrawal (often requires KYC)
- [SEP-24](sep-24.md) - Interactive Deposit/Withdrawal (often requires KYC)
- [SEP-31](sep-31.md) - Cross-Border Payments (requires sender/receiver KYC)

---

[Back to SEP Overview](README.md)
