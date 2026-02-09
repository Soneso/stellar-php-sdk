# SEP-01: Stellar info file (stellar.toml)

The stellar.toml file is a standardized configuration file that anchors and organizations host at their domains. It tells wallets and other services how to interact with their accounts, assets, and services. The SDK fetches and parses these files so your application can discover anchor endpoints.

**When to use:** Use this when your application needs to discover an anchor's service endpoints (SEP-6, SEP-10, SEP-24, federation, etc.) by fetching their stellar.toml file.

See the [SEP-01 specification](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0001.md) for protocol details.

**Note for implementers:** When hosting a stellar.toml file:
- File size must not exceed **100KB**
- Return `Access-Control-Allow-Origin: *` header for CORS
- Set `Content-Type: text/plain` so browsers render the file instead of downloading it

## Quick example

This example demonstrates loading a stellar.toml file from a domain and accessing service endpoints:

```php
<?php

use Soneso\StellarSDK\SEP\Toml\StellarToml;

// Load stellar.toml from a domain
$stellarToml = StellarToml::fromDomain('testanchor.stellar.org');

// Get service endpoints
$info = $stellarToml->getGeneralInformation();
echo "Transfer Server: " . $info->transferServerSep24 . PHP_EOL;
echo "Web Auth: " . $info->webAuthEndpoint . PHP_EOL;
```

## Loading stellar.toml

### From a domain

The SDK automatically constructs the URL `https://DOMAIN/.well-known/stellar.toml` and fetches the file:

```php
<?php

use Soneso\StellarSDK\SEP\Toml\StellarToml;

$stellarToml = StellarToml::fromDomain('soneso.com');

// Access organization info
$docs = $stellarToml->getDocumentation();
if ($docs !== null) {
    echo "Organization: " . $docs->orgName . PHP_EOL;
    echo "Support: " . $docs->orgSupportEmail . PHP_EOL;
}
```

### From a string

If you already have the TOML content (e.g., from a cached copy or test fixture), you can parse it directly:

```php
<?php

use Soneso\StellarSDK\SEP\Toml\StellarToml;

$tomlContent = '
VERSION="2.0.0"
NETWORK_PASSPHRASE="Test SDF Network ; September 2015"
FEDERATION_SERVER="https://example.com/federation"
TRANSFER_SERVER_SEP0024="https://example.com/sep24"
WEB_AUTH_ENDPOINT="https://example.com/auth"
SIGNING_KEY="GCKX...PUBLIC_KEY"

[DOCUMENTATION]
ORG_NAME="Example Anchor"
ORG_URL="https://example.com"
';

$stellarToml = new StellarToml($tomlContent);
$info = $stellarToml->getGeneralInformation();
echo "Version: " . $info->version . PHP_EOL;
```

## Accessing data

### General information

The general information section contains service endpoints for SEP protocols and account information:

```php
<?php

use Soneso\StellarSDK\SEP\Toml\StellarToml;

$stellarToml = StellarToml::fromDomain('testanchor.stellar.org');
$info = $stellarToml->getGeneralInformation();

// Protocol version
$version = $info->version;                        // SEP-1 version (e.g., "2.0.0")

// Service endpoints
$federationServer = $info->federationServer;      // SEP-02 Federation
$transferServer = $info->transferServer;          // SEP-06 Deposit/Withdrawal
$transferServerSep24 = $info->transferServerSep24; // SEP-24 Interactive
$kycServer = $info->kYCServer;                    // SEP-12 KYC
$webAuthEndpoint = $info->webAuthEndpoint;        // SEP-10 Web Auth
$directPaymentServer = $info->directPaymentServer; // SEP-31 Direct Payments
$anchorQuoteServer = $info->anchorQuoteServer;    // SEP-38 Quotes

// SEP-45 Contract Web Authentication (Soroban)
$webAuthForContracts = $info->webAuthForContractsEndpoint; // SEP-45 endpoint
$webAuthContractId = $info->webAuthContractId;    // SEP-45 contract ID (C... address)

// Signing keys
$signingKey = $info->signingKey;                  // For SEP-10 challenges
$uriSigningKey = $info->uriRequestSigningKey;     // For SEP-07 URIs

// Deprecated (SEP-03 Compliance Protocol)
$authServer = $info->authServer;                  // Deprecated

// Network info
$networkPassphrase = $info->networkPassphrase;
$horizonUrl = $info->horizonUrl;

// Organization accounts
$accounts = $info->accounts; // Array of G... account IDs controlled by this domain
```

### Organization documentation

The documentation section contains contact and compliance information about the organization:

```php
<?php

use Soneso\StellarSDK\SEP\Toml\StellarToml;

$stellarToml = StellarToml::fromDomain('testanchor.stellar.org');
$docs = $stellarToml->getDocumentation();

if ($docs !== null) {
    // Basic organization info
    echo "Name: " . $docs->orgName . PHP_EOL;
    echo "DBA: " . $docs->orgDBA . PHP_EOL;
    echo "URL: " . $docs->orgUrl . PHP_EOL;
    echo "Logo: " . $docs->orgLogo . PHP_EOL;
    echo "Description: " . $docs->orgDescription . PHP_EOL;
    
    // Physical address with attestation
    echo "Address: " . $docs->orgPhysicalAddress . PHP_EOL;
    echo "Address Proof: " . $docs->orgPhysicalAddressAttestation . PHP_EOL;
    
    // Phone number with attestation (E.164 format)
    echo "Phone: " . $docs->orgPhoneNumber . PHP_EOL;
    echo "Phone Proof: " . $docs->orgPhoneNumberAttestation . PHP_EOL;
    
    // Contact information
    echo "Official Email: " . $docs->orgOfficialEmail . PHP_EOL;
    echo "Support Email: " . $docs->orgSupportEmail . PHP_EOL;
    
    // Social accounts
    echo "Keybase: " . $docs->orgKeybase . PHP_EOL;
    echo "Twitter: " . $docs->orgTwitter . PHP_EOL;
    echo "GitHub: " . $docs->orgGithub . PHP_EOL;
    
    // Licensing information (for regulated entities)
    echo "Licensing Authority: " . $docs->orgLicensingAuthority . PHP_EOL;
    echo "License Type: " . $docs->orgLicenseType . PHP_EOL;
    echo "License Number: " . $docs->orgLicenseNumber . PHP_EOL;
}
```

### Principals (points of contact)

The principals section contains identifying information for the organization's primary contact persons:

```php
<?php

use Soneso\StellarSDK\SEP\Toml\StellarToml;

$stellarToml = StellarToml::fromDomain('testanchor.stellar.org');
$principals = $stellarToml->getPrincipals();

if ($principals !== null) {
    foreach ($principals as $principal) {
        // Basic contact info
        echo "Name: " . $principal->name . PHP_EOL;
        echo "Email: " . $principal->email . PHP_EOL;
        
        // Social accounts for verification
        echo "Keybase: " . $principal->keybase . PHP_EOL;
        echo "Telegram: " . $principal->telegram . PHP_EOL;
        echo "Twitter: " . $principal->twitter . PHP_EOL;
        echo "GitHub: " . $principal->github . PHP_EOL;
        
        // Identity verification hashes (SHA-256)
        echo "ID Photo Hash: " . $principal->idPhotoHash . PHP_EOL;
        echo "Verification Photo Hash: " . $principal->verificationPhotoHash . PHP_EOL;
        
        echo "---" . PHP_EOL;
    }
}
```

### Currencies (assets)

The currencies section provides information about assets issued by the organization, including both classic Stellar assets and Soroban token contracts:

```php
<?php

use Soneso\StellarSDK\SEP\Toml\StellarToml;

$stellarToml = StellarToml::fromDomain('testanchor.stellar.org');
$currencies = $stellarToml->getCurrencies();

if ($currencies !== null) {
    foreach ($currencies as $currency) {
        // Basic token info
        echo "Code: " . $currency->code . PHP_EOL;
        echo "Name: " . $currency->name . PHP_EOL;
        echo "Description: " . $currency->desc . PHP_EOL;
        echo "Conditions: " . $currency->conditions . PHP_EOL;
        echo "Status: " . $currency->status . PHP_EOL;  // live, dead, test, or private
        echo "Decimals: " . $currency->displayDecimals . PHP_EOL;
        echo "Image: " . $currency->image . PHP_EOL;
        
        // Token identifier (one of these will be set)
        echo "Issuer: " . $currency->issuer . PHP_EOL;       // G... for classic assets
        echo "Contract: " . $currency->contract . PHP_EOL;   // C... for Soroban contracts (SEP-41)
        echo "Code Template: " . $currency->codeTemplate . PHP_EOL; // Pattern for multiple assets
        
        // Supply information (mutually exclusive)
        echo "Fixed Number: " . $currency->fixedNumber . PHP_EOL;
        echo "Max Number: " . $currency->maxNumber . PHP_EOL;
        echo "Unlimited: " . ($currency->isUnlimited ? 'Yes' : 'No') . PHP_EOL;
        
        // Anchored asset information
        echo "Is Anchored: " . ($currency->isAssetAnchored ? 'Yes' : 'No') . PHP_EOL;
        echo "Anchor Type: " . $currency->anchorAssetType . PHP_EOL;  // fiat, crypto, nft, stock, bond, commodity, realestate, other
        echo "Anchor Asset: " . $currency->anchorAsset . PHP_EOL;
        echo "Attestation: " . $currency->attestationOfReserve . PHP_EOL;
        echo "Redemption: " . $currency->redemptionInstructions . PHP_EOL;
        
        // Collateral proof for crypto-backed tokens
        if ($currency->collateralAddresses !== null) {
            echo "Collateral Addresses: " . implode(', ', $currency->collateralAddresses) . PHP_EOL;
            echo "Collateral Messages: " . implode(', ', $currency->collateralAddressMessages ?? []) . PHP_EOL;
            echo "Collateral Signatures: " . implode(', ', $currency->collateralAddressSignatures ?? []) . PHP_EOL;
        }
        
        // SEP-08 Regulated Assets
        echo "Regulated: " . ($currency->regulated ? 'Yes' : 'No') . PHP_EOL;
        echo "Approval Server: " . $currency->approvalServer . PHP_EOL;
        echo "Approval Criteria: " . $currency->approvalCriteria . PHP_EOL;
        
        echo "---" . PHP_EOL;
    }
}
```

### Linked currencies

Some stellar.toml files link to separate TOML files for detailed currency information. Use `currencyFromUrl()` to fetch the full currency data:

```php
<?php

use Exception;
use Soneso\StellarSDK\SEP\Toml\StellarToml;

$stellarToml = StellarToml::fromDomain('example.com');
$currencies = $stellarToml->getCurrencies();

if ($currencies !== null) {
    foreach ($currencies as $currency) {
        // Check if currency details are in a separate file
        if ($currency->toml !== null) {
            try {
                $linkedCurrency = StellarToml::currencyFromUrl($currency->toml);
                echo "Code: " . $linkedCurrency->code . PHP_EOL;
                echo "Issuer: " . $linkedCurrency->issuer . PHP_EOL;
                echo "Name: " . $linkedCurrency->name . PHP_EOL;
            } catch (Exception $e) {
                echo "Failed to load linked currency: " . $e->getMessage() . PHP_EOL;
            }
        } else {
            // Currency data is inline
            echo "Code: " . $currency->code . PHP_EOL;
        }
    }
}
```

### Validators

The validators section is for organizations running Stellar validator nodes. Combined with SEP-20, it allows public declaration of nodes and archive locations:

```php
<?php

use Soneso\StellarSDK\SEP\Toml\StellarToml;

$stellarToml = StellarToml::fromDomain('stellar.org');
$validators = $stellarToml->getValidators();

if ($validators !== null) {
    foreach ($validators as $validator) {
        echo "Alias: " . $validator->alias . PHP_EOL;         // Config name (e.g., "sdf-1")
        echo "Display Name: " . $validator->displayName . PHP_EOL;
        echo "Public Key: " . $validator->publicKey . PHP_EOL; // G... account
        echo "Host: " . $validator->host . PHP_EOL;           // IP:port or domain:port
        echo "History: " . $validator->history . PHP_EOL;     // Archive URL
        echo "---" . PHP_EOL;
    }
}
```

## Error handling

The SDK throws exceptions when the stellar.toml file cannot be fetched or parsed. Always wrap network calls in try-catch blocks:

```php
<?php

use Exception;
use Soneso\StellarSDK\SEP\Toml\StellarToml;

// Handle network failures
try {
    $stellarToml = StellarToml::fromDomain('nonexistent-domain.invalid');
} catch (Exception $e) {
    // Domain unreachable, DNS failure, or stellar.toml not found (404)
    echo "Failed to load stellar.toml: " . $e->getMessage() . PHP_EOL;
}

// Handle TOML parsing errors
try {
    $badToml = "this is not valid TOML [[[";
    $stellarToml = new StellarToml($badToml);
} catch (Exception $e) {
    echo "Failed to parse stellar.toml: " . $e->getMessage() . PHP_EOL;
}
```

After loading, check for missing optional data before using it. Not all anchors implement every SEP:

```php
<?php

use Soneso\StellarSDK\SEP\Toml\StellarToml;

$stellarToml = StellarToml::fromDomain('example.com');
$info = $stellarToml->getGeneralInformation();

// Check for SEP support before using endpoints
if ($info->webAuthEndpoint === null) {
    echo "This anchor doesn't support SEP-10 authentication" . PHP_EOL;
}

if ($info->transferServerSep24 === null) {
    echo "This anchor doesn't support SEP-24 interactive deposits" . PHP_EOL;
}

if ($info->kYCServer === null) {
    echo "This anchor doesn't support SEP-12 KYC" . PHP_EOL;
}

// Documentation section may also be null
$docs = $stellarToml->getDocumentation();
if ($docs === null) {
    echo "No organization documentation available" . PHP_EOL;
}
```

## Custom HTTP client

You can provide a custom Guzzle HTTP client for testing or to configure timeouts, proxies, and other HTTP options:

```php
<?php

use GuzzleHttp\Client;
use Soneso\StellarSDK\SEP\Toml\StellarToml;

// Create a custom HTTP client with specific settings
$httpClient = new Client([
    'timeout' => 10,
    'connect_timeout' => 5,
    'verify' => true,  // SSL verification
]);

$stellarToml = StellarToml::fromDomain('testanchor.stellar.org', $httpClient);
```

## Testing your stellar.toml

Use these tools to validate your stellar.toml configuration:

- **[Stellar Anchor Validator](https://anchor-tests.stellar.org/)** - Comprehensive test suite for anchor implementations, including stellar.toml validation
- **[stellar.toml checker](https://stellar.sui.li)** - Quick validation tool for stellar.toml files

## Related SEPs

SEPs that rely on stellar.toml for endpoint discovery or configuration:

- [SEP-02 Federation](sep-02.md) - `FEDERATION_SERVER`
- [SEP-06 Deposit/Withdrawal](sep-06.md) - `TRANSFER_SERVER`
- [SEP-07 URI Scheme](sep-07.md) - `URI_REQUEST_SIGNING_KEY`
- [SEP-08 Regulated Assets](sep-08.md) - Currency `approval_server`
- [SEP-10 Authentication](sep-10.md) - `WEB_AUTH_ENDPOINT`, `SIGNING_KEY`
- [SEP-12 KYC](sep-12.md) - `KYC_SERVER`
- [SEP-24 Interactive](sep-24.md) - `TRANSFER_SERVER_SEP0024`
- [SEP-31 Cross-Border](sep-31.md) - `DIRECT_PAYMENT_SERVER`
- [SEP-38 Quotes](sep-38.md) - `ANCHOR_QUOTE_SERVER`
- [SEP-45 Contract Auth](sep-45.md) - `WEB_AUTH_FOR_CONTRACTS_ENDPOINT`, `WEB_AUTH_CONTRACT_ID`

---

[Back to SEP Overview](README.md)
