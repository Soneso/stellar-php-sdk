# SEP-01: Stellar Info File (stellar.toml)

**Purpose:** Fetch and parse a domain's `stellar.toml` file to discover anchor service endpoints and asset information.
**Prerequisites:** None
**SDK Namespace:** `Soneso\StellarSDK\SEP\Toml`

## Table of Contents

- [Loading stellar.toml](#loading-stellartoml)
- [Service Endpoint Discovery](#service-endpoint-discovery-general-information)
- [Organization Documentation](#organization-documentation)
- [Currencies (Assets)](#currencies-assets)
- [Principals](#principals)
- [Validators](#validators)
- [Collection Methods](#collection-methods)
- [Error Handling](#error-handling)
- [Common Pitfalls](#common-pitfalls)
- [Typical Integration Pattern](#typical-integration-pattern)

## Loading stellar.toml

### From a domain

`StellarToml::fromDomain()` constructs `https://DOMAIN/.well-known/stellar.toml`, fetches it, and returns a parsed object. Throws `Exception` on network failure or non-200 response.

```php
<?php declare(strict_types=1);

use Exception;
use Soneso\StellarSDK\SEP\Toml\StellarToml;

try {
    $stellarToml = StellarToml::fromDomain('testanchor.stellar.org');
} catch (Exception $e) {
    echo 'Failed to load stellar.toml: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

$info = $stellarToml->getGeneralInformation();
echo 'Web Auth: ' . $info->webAuthEndpoint . PHP_EOL;
echo 'SEP-24: '   . $info->transferServerSep24 . PHP_EOL;
echo 'Signing Key: ' . $info->signingKey . PHP_EOL;
```

### From a TOML string

Parse an already-fetched or cached TOML content string directly:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Toml\StellarToml;

$tomlContent = '
VERSION="2.0.0"
NETWORK_PASSPHRASE="Test SDF Network ; September 2015"
WEB_AUTH_ENDPOINT="https://example.com/auth"
TRANSFER_SERVER_SEP0024="https://example.com/sep24"
SIGNING_KEY="GBBHQ7H4V6RRORKYLHTCAWP6MOHNORRFJSDPXDFYDGJB2LPZUFPXUEW3"
';

$stellarToml = new StellarToml($tomlContent);
$info = $stellarToml->getGeneralInformation();
echo 'Version: ' . $info->version . PHP_EOL;
```

### With a custom HTTP client

Pass a Guzzle `Client` instance to configure timeouts, proxies, or SSL options:

```php
<?php declare(strict_types=1);

use GuzzleHttp\Client;
use Soneso\StellarSDK\SEP\Toml\StellarToml;

$httpClient = new Client(['timeout' => 10, 'connect_timeout' => 5]);
$stellarToml = StellarToml::fromDomain('testanchor.stellar.org', $httpClient);
```

## Service Endpoint Discovery (General Information)

This is the most common use of SEP-01: discovering which SEP services an anchor supports. All fields are `?string` and `null` when the anchor does not support that protocol.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Toml\StellarToml;

$stellarToml = StellarToml::fromDomain('testanchor.stellar.org');
$info = $stellarToml->getGeneralInformation();

// SEP protocol endpoints — null if the anchor does not support that SEP
$info->federationServer;          // string|null  SEP-02 Federation
$info->transferServer;            // string|null  SEP-06 Deposit/Withdrawal
$info->transferServerSep24;       // string|null  SEP-24 Interactive Deposit/Withdrawal
$info->kYCServer;                 // string|null  SEP-12 KYC
$info->webAuthEndpoint;           // string|null  SEP-10 Web Authentication
$info->directPaymentServer;       // string|null  SEP-31 Cross-Border Payments
$info->anchorQuoteServer;         // string|null  SEP-38 Quotes
$info->uriRequestSigningKey;      // string|null  SEP-07 URI signing key

// SEP-45 Web Authentication for Soroban contract accounts
$info->webAuthForContractsEndpoint; // string|null  SEP-45 endpoint
$info->webAuthContractId;           // string|null  SEP-45 contract address (C...)

// Signing key for SEP-10 challenge verification
$info->signingKey;                // string|null  G... public key

// Network and infrastructure info
$info->version;                   // string|null  SEP-01 spec version (e.g. "2.0.0")
$info->networkPassphrase;         // string|null
$info->horizonUrl;                // string|null  anchor's public Horizon instance
$info->accounts;                  // string[]     G... accounts controlled by this domain (never null, may be empty)

// Deprecated
$info->authServer;                // string|null  SEP-03 Compliance (deprecated)
```

Always null-check endpoints before using them:

```php
if ($info->webAuthEndpoint === null) {
    throw new \RuntimeException('Anchor does not support SEP-10 authentication');
}
if ($info->transferServerSep24 === null) {
    throw new \RuntimeException('Anchor does not support SEP-24');
}
```

## Organization Documentation

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Toml\StellarToml;

$stellarToml = StellarToml::fromDomain('testanchor.stellar.org');
$docs = $stellarToml->getDocumentation(); // returns ?Documentation

if ($docs !== null) {
    $docs->orgName;                       // string|null  legal name
    $docs->orgDBA;                        // string|null  doing-business-as name
    $docs->orgUrl;                        // string|null  official URL
    $docs->orgLogo;                       // string|null  PNG logo URL
    $docs->orgDescription;                // string|null  short description
    $docs->orgPhysicalAddress;            // string|null
    $docs->orgPhysicalAddressAttestation; // string|null  URL to proof document
    $docs->orgPhoneNumber;                // string|null  E.164 format
    $docs->orgPhoneNumberAttestation;     // string|null  URL to phone bill image
    $docs->orgOfficialEmail;              // string|null
    $docs->orgSupportEmail;               // string|null
    $docs->orgKeybase;                    // string|null
    $docs->orgTwitter;                    // string|null
    $docs->orgGithub;                     // string|null
    $docs->orgLicensingAuthority;         // string|null  for regulated entities
    $docs->orgLicenseType;                // string|null
    $docs->orgLicenseNumber;              // string|null
}
```

## Currencies (Assets)

`getCurrencies()` returns a `?Currencies` collection (iterable). Each element is a `Currency` object. For anchors with many assets, some entries may link to separate TOML files instead of embedding data inline.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Toml\StellarToml;

$stellarToml = StellarToml::fromDomain('testanchor.stellar.org');
$currencies = $stellarToml->getCurrencies(); // returns ?Currencies

if ($currencies !== null) {
    foreach ($currencies as $currency) {
        // Token identifier — one of these will be set per currency entry
        $currency->code;           // string|null  asset code (e.g. "USDC")
        $currency->issuer;         // string|null  G... issuer (classic Stellar assets)
        $currency->contract;       // string|null  C... contract address (SEP-41 tokens)
        $currency->codeTemplate;   // string|null  wildcard pattern (e.g. "CORN????????")

        // Display info
        $currency->name;           // string|null  short display name
        $currency->desc;           // string|null  description
        $currency->conditions;     // string|null  terms or conditions
        $currency->image;          // string|null  PNG logo URL
        $currency->displayDecimals; // int|null    preferred decimal places
        $currency->status;         // string|null  "live", "dead", "test", or "private"

        // Supply model — at most one of these is set
        $currency->fixedNumber;    // int|null     total fixed supply
        $currency->maxNumber;      // int|null     maximum supply cap
        $currency->isUnlimited;    // bool|null    true = dilutable at issuer's discretion

        // Anchored asset info
        $currency->isAssetAnchored;       // bool|null
        $currency->anchorAssetType;       // string|null  "fiat", "crypto", "nft", "stock", "bond", "commodity", "realestate", "other"
        $currency->anchorAsset;           // string|null  e.g. "USD", "BTC"
        $currency->attestationOfReserve;  // string|null  URL to audit/proof
        $currency->redemptionInstructions; // string|null

        // Crypto-backed collateral proof
        $currency->collateralAddresses;          // string[]|null
        $currency->collateralAddressMessages;    // string[]|null
        $currency->collateralAddressSignatures;  // string[]|null

        // SEP-08 Regulated Assets
        $currency->regulated;        // bool|null
        $currency->approvalServer;   // string|null
        $currency->approvalCriteria; // string|null

        // Linked currency — only set when this entry points to a separate TOML file
        $currency->toml;             // string|null  URL to separate currency TOML
    }
}
```

### Linked currencies

When `$currency->toml` is set, the entry contains only that URL; all other fields are `null`. Fetch the full currency data with `StellarToml::currencyFromUrl()`:

```php
<?php declare(strict_types=1);

use Exception;
use Soneso\StellarSDK\SEP\Toml\StellarToml;

$stellarToml = StellarToml::fromDomain('example.com');
$currencies = $stellarToml->getCurrencies();

if ($currencies !== null) {
    foreach ($currencies as $currency) {
        if ($currency->toml !== null) {
            // Entry is a link — fetch the actual currency data
            try {
                $linked = StellarToml::currencyFromUrl($currency->toml);
                echo $linked->code . ':' . $linked->issuer . PHP_EOL;
            } catch (Exception $e) {
                echo 'Failed to fetch linked currency: ' . $e->getMessage() . PHP_EOL;
            }
        } else {
            // Inline entry
            echo $currency->code . ':' . $currency->issuer . PHP_EOL;
        }
    }
}
```

## Principals

`getPrincipals()` returns a `?Principals` collection. Each element is a `PointOfContact` object.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Toml\StellarToml;

$stellarToml = StellarToml::fromDomain('testanchor.stellar.org');
$principals = $stellarToml->getPrincipals(); // returns ?Principals

if ($principals !== null) {
    foreach ($principals as $principal) {
        $principal->name;                 // string|null  full legal name
        $principal->email;                // string|null  business email
        $principal->keybase;              // string|null
        $principal->telegram;             // string|null
        $principal->twitter;              // string|null
        $principal->github;               // string|null
        $principal->idPhotoHash;          // string|null  SHA-256 of government ID photo
        $principal->verificationPhotoHash; // string|null  SHA-256 of verification photo
    }
}
```

## Validators

`getValidators()` returns a `?Validators` collection. Each element is a `Validator` object. Most anchors do not run validators; this section is populated primarily by node operators.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Toml\StellarToml;

$stellarToml = StellarToml::fromDomain('stellar.org');
$validators = $stellarToml->getValidators(); // returns ?Validators

if ($validators !== null) {
    foreach ($validators as $validator) {
        $validator->alias;       // string|null  config name, e.g. "sdf-1"
        $validator->displayName; // string|null  human-readable name
        $validator->publicKey;   // string|null  G... node account
        $validator->host;        // string|null  "domain.com:11625" or "IP:port"
        $validator->history;     // string|null  history archive URL
    }
}
```

## Collection Methods

`Currencies`, `Principals`, and `Validators` extend `IteratorIterator`. Use `->toArray()` to get a plain PHP array when you need indexed access:

```php
$currencies = $stellarToml->getCurrencies();
if ($currencies !== null) {
    $arr = $currencies->toArray(); // array<Currency>
    echo 'First asset: ' . $arr[0]->code . PHP_EOL;
    echo 'Count: ' . $currencies->count() . PHP_EOL;
}
```

## Error Handling

All SEP-01 methods throw generic `Exception`. There are no SEP-01-specific exception classes.

| Method | Throws | Message pattern |
|--------|--------|-----------------|
| `StellarToml::fromDomain()` | `Exception` | `"Stellar toml not found. Response status code {code}"` on non-200 HTTP |
| `StellarToml::fromDomain()` | `Exception` | `"Stellar toml not found. {guzzle message}"` on network failure |
| `new StellarToml($string)` | `Exception` | TOML parse error if content is malformed |
| `StellarToml::currencyFromUrl()` | `Exception` | `"Currency toml not found. Response status code {code}"` on non-200 HTTP |
| `StellarToml::currencyFromUrl()` | `Exception` | `"Currency toml not found. {guzzle message}"` on network failure |

All exceptions can be caught with a single `catch (Exception $e)`. Use the message string to distinguish causes:

```php
<?php declare(strict_types=1);

use Exception;
use Soneso\StellarSDK\SEP\Toml\StellarToml;

try {
    $stellarToml = StellarToml::fromDomain('anchor.example.com');
} catch (Exception $e) {
    $msg = $e->getMessage();
    if (str_contains($msg, 'Response status code')) {
        echo 'HTTP error fetching stellar.toml' . PHP_EOL;
    } else {
        echo 'Network or parse error: ' . $msg . PHP_EOL;
    }
}
```

## Common Pitfalls

**Wrong property name for KYC server:**

```php
// WRONG: $info->kycServer -- property does not exist
// CORRECT: $info->kYCServer -- uppercase YC
$kycServer = $info->kYCServer;
```

**Not checking for null before accessing endpoints:**

```php
// WRONG: will error if anchor doesn't publish this endpoint
$server = new InteractiveService($info->transferServerSep24);

// CORRECT: check first
if ($info->transferServerSep24 === null) {
    throw new \RuntimeException('SEP-24 not supported');
}
$server = new InteractiveService($info->transferServerSep24);
```

**Using PHP `count()` on collections:**

```php
// WRONG: Currencies/Principals/Validators do NOT implement Countable
count($stellarToml->getCurrencies()); // TypeError or wrong result

// CORRECT: use the ->count() method
$stellarToml->getCurrencies()->count();
```

## Typical Integration Pattern

Most SEP integrations start with SEP-01 discovery, then SEP-10 authentication:

```php
<?php declare(strict_types=1);

use Exception;
use Soneso\StellarSDK\SEP\Toml\StellarToml;

$domain = 'testanchor.stellar.org';

try {
    $stellarToml = StellarToml::fromDomain($domain);
} catch (Exception $e) {
    exit('Cannot reach anchor: ' . $e->getMessage());
}

$info = $stellarToml->getGeneralInformation();

// Verify the anchor supports what we need
if ($info->webAuthEndpoint === null || $info->signingKey === null) {
    exit('Anchor does not support SEP-10');
}
if ($info->transferServerSep24 === null) {
    exit('Anchor does not support SEP-24');
}

// Pass discovered values to SEP-10 and SEP-24 clients
$webAuthEndpoint    = $info->webAuthEndpoint;    // → use with SEP-10
$transferServer     = $info->transferServerSep24; // → use with SEP-24
$signingKey         = $info->signingKey;          // → verify SEP-10 challenges
```
