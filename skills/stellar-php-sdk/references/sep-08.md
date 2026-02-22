# SEP-08: Regulated Assets

**Purpose:** Handle assets that require issuer approval for every transaction before submission to the Stellar network.
**Prerequisites:** None (but the asset issuer must be configured with proper authorization flags)
**SDK Namespace:** `Soneso\StellarSDK\SEP\RegulatedAssets`

SEP-08 defines a protocol for "regulated assets" — assets that require an issuer-run approval server to sign every transaction. This enables compliance with securities regulations, KYC/AML requirements, velocity limits, and jurisdiction-based restrictions.

**Spec:** [SEP-0008 v1.7.4](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0008.md)

## Table of Contents

- [Quick Start](#quick-start)
- [How Regulated Assets Work](#how-regulated-assets-work)
- [Creating the Service](#creating-the-service)
- [RegulatedAsset](#regulatedasset)
- [Checking Authorization Flags](#checking-authorization-flags)
- [postTransaction — Submitting for Approval](#posttransaction--submitting-for-approval)
- [Handling All Response Types](#handling-all-response-types)
- [postAction — Handling Action Required](#postaction--handling-action-required)
- [Complete Workflow Example](#complete-workflow-example)
- [Response Classes Reference](#response-classes-reference)
- [Error Handling](#error-handling)
- [Common Pitfalls](#common-pitfalls)

---

## Quick Start

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionSuccess;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionRejected;
use Soneso\StellarSDK\StellarSDK;

// Load stellar.toml from issuer domain — extracts regulated asset definitions
$service = RegulatedAssetsService::fromDomain('regulated-asset-issuer.com');

// Access discovered regulated assets
$asset = $service->regulatedAssets[0];
echo $asset->getCode() . ' issued by ' . $asset->getIssuer() . PHP_EOL;
echo 'Approval server: ' . $asset->approvalServer . PHP_EOL;

// Submit a signed transaction for approval (base64-encoded XDR envelope)
$txXdr = $transaction->toEnvelopeXdrBase64();
$response = $service->postTransaction(
    tx: $txXdr,
    approvalServer: $asset->approvalServer
);

if ($response instanceof SEP08PostTransactionSuccess) {
    // Approved — submit to Stellar network
    $sdk = StellarSDK::getTestNetInstance();
    $result = $sdk->submitTransactionEnvelopeXdrBase64($response->tx);
    echo 'Submitted: ' . $result->getHash() . PHP_EOL;
} elseif ($response instanceof SEP08PostTransactionRejected) {
    echo 'Rejected: ' . $response->error . PHP_EOL;
}
```

---

## How Regulated Assets Work

Per SEP-08, regulated assets require this setup and workflow:

1. **Issuer flags**: The asset issuer account has `AUTH_REQUIRED` and `AUTH_REVOCABLE` flags set. These allow the issuer to grant and revoke transaction authorization atomically.
2. **stellar.toml discovery**: The issuer's `stellar.toml` (SEP-01) defines assets as `regulated=true` and includes an `approval_server` URL. The toml **must** include `NETWORK_PASSPHRASE` for the SDK to initialize.
3. **Transaction composition**: Build and sign the transaction normally using the regulated asset. You do not need to add authorization operations yourself — the approval server handles that.
4. **Approval**: POST the signed transaction XDR to the approval server (not to Stellar network). The server evaluates compliance rules and responds with one of five statuses.
5. **Network submission**: If approved (`success` or `revised`), submit the returned signed transaction to the Stellar network.

---

## Creating the Service

### From domain (recommended)

Loads `stellar.toml` from the issuer's domain using SEP-01 discovery, then extracts all regulated asset definitions:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;

$service = RegulatedAssetsService::fromDomain('regulated-asset-issuer.com');

// The service exposes these public properties after initialization:
$service->regulatedAssets; // array<RegulatedAsset> — all regulated assets from stellar.toml
$service->tomlData;        // StellarToml — the StellarToml data passed to the constructor
$service->sdk;             // StellarSDK — Horizon client (for authorizationRequired checks)
$service->network;         // Network — Stellar network (from toml NETWORK_PASSPHRASE)
```

Signature:
```
RegulatedAssetsService::fromDomain(
    string   $domain,
    ?string  $horizonUrl  = null,   // override Horizon URL (default: from toml or known network)
    ?Network $network     = null,   // override network passphrase (default: from toml NETWORK_PASSPHRASE)
    ?Client  $httpClient  = null    // custom Guzzle client for all requests
): RegulatedAssetsService
```

### From StellarToml data

If you have already loaded `stellar.toml` data, pass it directly:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;
use Soneso\StellarSDK\SEP\Toml\StellarToml;

$toml = StellarToml::fromDomain('regulated-asset-issuer.com');
$service = new RegulatedAssetsService(tomlData: $toml);
```

Constructor signature:
```
new RegulatedAssetsService(
    StellarToml  $tomlData,
    ?string      $horizonUrl  = null,  // override Horizon URL
    ?Network     $network     = null,  // override network passphrase (default: from toml NETWORK_PASSPHRASE)
    ?Client      $httpClient  = null   // custom Guzzle client
)
```

**Requirements for initialization:** The `stellar.toml` data **must** include `NETWORK_PASSPHRASE`. Without it, the constructor throws `SEP08IncompleteInitData`. The Horizon URL is resolved from (in priority order): the `$horizonUrl` parameter, the toml `HORIZON_URL` field, or SDK defaults for public/testnet/futurenet networks.

### With custom HTTP client

Inject a custom Guzzle `Client` for timeouts, proxies, or SSL settings. The same client is used for both the `stellar.toml` fetch and all approval server requests:

```php
<?php declare(strict_types=1);

use GuzzleHttp\Client;
use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;

$httpClient = new Client([
    'timeout'         => 30,
    'connect_timeout' => 10,
    'headers'         => ['User-Agent' => 'MyWallet/1.0'],
]);

$service = RegulatedAssetsService::fromDomain(
    domain:     'regulated-asset-issuer.com',
    httpClient: $httpClient
);
```

---

## RegulatedAsset

`RegulatedAsset` extends `AssetTypeCreditAlphanum`, making it usable wherever a standard Stellar asset is expected. It adds approval server information specific to the SEP-08 workflow.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;

$service = RegulatedAssetsService::fromDomain('regulated-asset-issuer.com');

foreach ($service->regulatedAssets as $asset) {
    // Inherited from AssetTypeCreditAlphanum (via methods)
    $asset->getCode();       // string — e.g. "USDC"
    $asset->getIssuer();     // string — G... issuer account ID
    $asset->getType();       // string — "credit_alphanum4" or "credit_alphanum12"
    $asset->toXdr();         // XdrAsset — XDR representation

    // SEP-08 specific public properties (access directly, no getters)
    $asset->approvalServer;   // string — full URL of approval server endpoint
    $asset->approvalCriteria; // ?string — human-readable compliance description (may be null)
}
```

Constructor (for manual creation):
```
new RegulatedAsset(
    string  $code,
    string  $issuer,
    string  $approvalServer,
    ?string $approvalCriteria = null
)
```

Assets are only included in `$service->regulatedAssets` if the `stellar.toml` entry has `regulated=true`, a `code`, an `issuer`, and an `approval_server`. Entries missing any of these are silently skipped.

---

## Checking Authorization Flags

Before transacting, verify the issuer account has the required flags. Per SEP-08, regulated asset issuers must have both `AUTH_REQUIRED` and `AUTH_REVOCABLE` flags:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;

$service = RegulatedAssetsService::fromDomain('regulated-asset-issuer.com');
$asset = $service->regulatedAssets[0];

try {
    // Loads the issuer account from Horizon and checks both flags
    $requiresApproval = $service->authorizationRequired($asset);
} catch (HorizonRequestException $e) {
    echo 'Could not check issuer flags: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

if (!$requiresApproval) {
    echo 'Warning: issuer not configured for regulated assets' . PHP_EOL;
}
```

Signature:
```
authorizationRequired(RegulatedAsset $asset): bool
```

Returns `true` if the issuer has both `AUTH_REQUIRED` and `AUTH_REVOCABLE` flags set, `false` otherwise. Throws `HorizonRequestException` if Horizon is unreachable or the issuer account does not exist.

---

## postTransaction — Submitting for Approval

Build and sign a transaction normally, then submit the base64-encoded XDR to the approval server:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk     = StellarSDK::getTestNetInstance();
$service = RegulatedAssetsService::fromDomain('regulated-asset-issuer.com');
$regulatedAsset = $service->regulatedAssets[0];

$senderKeyPair  = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$senderAccount  = $sdk->requestAccount($senderKeyPair->getAccountId());

// Build a payment using the regulated asset
$asset   = Asset::createNonNativeAsset($regulatedAsset->getCode(), $regulatedAsset->getIssuer());
$payment = (new PaymentOperationBuilder(
    destinationAccountId: 'GDESTINATION...',
    asset:  $asset,
    amount: '100'
))->build();

$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation($payment)
    ->build();

// Sign with sender's key
$transaction->sign($senderKeyPair, Network::testnet());

// Convert to base64 XDR and submit to approval server
$txXdr    = $transaction->toEnvelopeXdrBase64();
$response = $service->postTransaction(
    tx:             $txXdr,
    approvalServer: $regulatedAsset->approvalServer
);
```

Signature:
```
postTransaction(string $tx, string $approvalServer): SEP08PostTransactionResponse
```

- `$tx` — base64-encoded XDR transaction envelope, signed by the user before submission
- `$approvalServer` — full URL of the approval server (from `$asset->approvalServer`)

Returns a `SEP08PostTransactionResponse` subclass. Throws `SEP08InvalidPostTransactionResponse` for malformed server responses, or `GuzzleException` for network failures.

---

## Handling All Response Types

The approval server returns one of five response types. Use `instanceof` checks to branch on the type:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionActionRequired;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionPending;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionRejected;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionRevised;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionSuccess;
use Soneso\StellarSDK\StellarSDK;

$response = $service->postTransaction($txXdr, $approvalServer);
$sdk      = StellarSDK::getTestNetInstance();

if ($response instanceof SEP08PostTransactionSuccess) {
    // Approved without modification — submit returned tx to network
    if ($response->message !== null) {
        echo 'Approval message: ' . $response->message . PHP_EOL; // ?string
    }
    $result = $sdk->submitTransactionEnvelopeXdrBase64($response->tx); // string tx

} elseif ($response instanceof SEP08PostTransactionRevised) {
    // Transaction was modified for compliance — review before submitting
    // $response->message is REQUIRED (string, never null) for revised
    echo 'Revised: ' . $response->message . PHP_EOL;
    // WARNING: Inspect $response->tx vs original — server may have added operations
    $result = $sdk->submitTransactionEnvelopeXdrBase64($response->tx);

} elseif ($response instanceof SEP08PostTransactionPending) {
    // Approval delayed — retry after $timeout milliseconds
    // $timeout is int, defaults to 0 if the server did not provide a value
    $timeoutMs = $response->timeout; // int — milliseconds (0 means unknown)
    if ($timeoutMs > 0) {
        echo 'Retry in ' . ($timeoutMs / 1000) . ' seconds' . PHP_EOL;
    }
    if ($response->message !== null) {
        echo 'Message: ' . $response->message . PHP_EOL; // ?string
    }
    // Resubmit $txXdr unchanged after waiting

} elseif ($response instanceof SEP08PostTransactionActionRequired) {
    // User must complete an action before approval — see postAction section
    echo 'Action required: ' . $response->message . PHP_EOL;          // string (required)
    echo 'Action URL: '      . $response->actionUrl . PHP_EOL;        // string (required)
    echo 'Method: '          . $response->actionMethod . PHP_EOL;     // string — "GET" or "POST", defaults to "GET"
    if ($response->actionFields !== null) {
        // array<string> of SEP-9 field names the server is requesting
        echo 'Fields: ' . implode(', ', $response->actionFields) . PHP_EOL;
    }

} elseif ($response instanceof SEP08PostTransactionRejected) {
    // Cannot be made compliant — do not retry without addressing the issue
    echo 'Rejected: ' . $response->error . PHP_EOL; // string (required)
}
```

### Response summary table

| Class | HTTP | Status value | Key fields |
|---|---|---|---|
| `SEP08PostTransactionSuccess` | 200 | `"success"` | `string $tx`, `?string $message` |
| `SEP08PostTransactionRevised` | 200 | `"revised"` | `string $tx`, `string $message` |
| `SEP08PostTransactionPending` | 200 | `"pending"` | `int $timeout` (ms, default 0), `?string $message` |
| `SEP08PostTransactionActionRequired` | 200 | `"action_required"` | `string $message`, `string $actionUrl`, `string $actionMethod` (default `"GET"`), `?array $actionFields` |
| `SEP08PostTransactionRejected` | 400 | `"rejected"` | `string $error` |

---

## postAction — Handling Action Required

When the server returns `action_required` with `actionMethod === "POST"`, you can programmatically submit the requested SEP-9 fields:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostActionDone;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostActionNextUrl;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionActionRequired;

$response = $service->postTransaction($txXdr, $approvalServer);

if ($response instanceof SEP08PostTransactionActionRequired) {
    echo 'Action required: ' . $response->message . PHP_EOL;

    if ($response->actionMethod === 'POST') {
        // Wallet has the required fields — submit them programmatically
        $actionResponse = $service->postAction(
            url:          $response->actionUrl,
            actionFields: [
                'email_address' => 'user@example.com',
                'mobile_number' => '+1234567890',
                'first_name'    => 'Jane',
                'last_name'     => 'Doe',
            ]
        );

        if ($actionResponse instanceof SEP08PostActionDone) {
            // Action complete — resubmit the ORIGINAL transaction unchanged
            echo 'Action done — resubmitting...' . PHP_EOL;
            $response = $service->postTransaction($txXdr, $approvalServer);
            // Handle this new response (likely success or revised)

        } elseif ($actionResponse instanceof SEP08PostActionNextUrl) {
            // More steps needed — user must complete action in browser
            echo 'Open in browser: ' . $actionResponse->nextUrl . PHP_EOL; // string
            if ($actionResponse->message !== null) {
                echo 'Message: ' . $actionResponse->message . PHP_EOL;     // ?string
            }
            // After user completes, resubmit the original transaction
        }

    } else {
        // actionMethod is "GET" (or server did not specify — defaults to "GET")
        // Direct user to open the URL in a browser
        echo 'Open URL in browser: ' . $response->actionUrl . PHP_EOL;
        // After user completes the action, resubmit $txXdr unchanged
    }
}
```

Signature:
```
postAction(
    string $url,
    array  $actionFields  // associative array: SEP-9 field names => values
): SEP08PostActionResponse
```

Returns either `SEP08PostActionDone` or `SEP08PostActionNextUrl`. Throws `SEP08InvalidPostActionResponse` for malformed responses, or `GuzzleException` for network failures.

### postAction response types

| Class | Result value | Key fields |
|---|---|---|
| `SEP08PostActionDone` | `"no_further_action_required"` | (none — empty class) |
| `SEP08PostActionNextUrl` | `"follow_next_url"` | `string $nextUrl`, `?string $message` |

---

## Complete Workflow Example

Full flow including all response type handling and error recovery:

```php
<?php declare(strict_types=1);

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08IncompleteInitData;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08InvalidPostTransactionResponse;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostActionDone;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostActionNextUrl;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionActionRequired;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionPending;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionRejected;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionRevised;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionSuccess;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

try {
    $sdk     = StellarSDK::getTestNetInstance();
    $service = RegulatedAssetsService::fromDomain('regulated-asset-issuer.com');
} catch (SEP08IncompleteInitData $e) {
    exit('stellar.toml is missing NETWORK_PASSPHRASE: ' . $e->getMessage());
} catch (Exception $e) {
    exit('Failed to load stellar.toml: ' . $e->getMessage());
}

if (empty($service->regulatedAssets)) {
    exit('No regulated assets found in stellar.toml');
}

$regulatedAsset = $service->regulatedAssets[0];

// Verify issuer is properly configured (makes a Horizon call)
try {
    if (!$service->authorizationRequired($regulatedAsset)) {
        echo 'Warning: issuer not properly configured for regulated assets' . PHP_EOL;
    }
} catch (HorizonRequestException $e) {
    echo 'Could not verify issuer flags: ' . $e->getMessage() . PHP_EOL;
}

// Build and sign the transaction
$senderKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());
$asset         = Asset::createNonNativeAsset($regulatedAsset->getCode(), $regulatedAsset->getIssuer());

$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation(
        (new PaymentOperationBuilder('GDESTINATION...', $asset, '100'))->build()
    )
    ->build();
$transaction->sign($senderKeyPair, Network::testnet());
$txXdr = $transaction->toEnvelopeXdrBase64();

// Submit for approval and handle all response types
try {
    $response = $service->postTransaction($txXdr, $regulatedAsset->approvalServer);
} catch (SEP08InvalidPostTransactionResponse $e) {
    exit('Approval server error: ' . $e->getMessage() . ' (HTTP ' . $e->getCode() . ')');
} catch (GuzzleException $e) {
    exit('Network error contacting approval server: ' . $e->getMessage());
}

$approvedTx = null;

if ($response instanceof SEP08PostTransactionSuccess) {
    echo 'Approved' . PHP_EOL;
    $approvedTx = $response->tx;

} elseif ($response instanceof SEP08PostTransactionRevised) {
    // Review what changed before accepting
    echo 'Revised: ' . $response->message . PHP_EOL;
    $approvedTx = $response->tx;

} elseif ($response instanceof SEP08PostTransactionPending) {
    $waitMs = $response->timeout; // int, 0 = unknown
    echo 'Pending. Retry in ' . ($waitMs > 0 ? $waitMs / 1000 . 's' : 'a moment') . PHP_EOL;

} elseif ($response instanceof SEP08PostTransactionActionRequired) {
    echo 'Action required: ' . $response->message . PHP_EOL;

    if ($response->actionMethod === 'POST') {
        $actionResponse = $service->postAction(
            url:          $response->actionUrl,
            actionFields: ['email_address' => 'user@example.com']
        );

        if ($actionResponse instanceof SEP08PostActionDone) {
            // Resubmit original transaction after action completed
            $response = $service->postTransaction($txXdr, $regulatedAsset->approvalServer);
            if ($response instanceof SEP08PostTransactionSuccess) {
                $approvedTx = $response->tx;
            }
        } elseif ($actionResponse instanceof SEP08PostActionNextUrl) {
            echo 'Complete action at: ' . $actionResponse->nextUrl . PHP_EOL;
        }
    } else {
        echo 'Open in browser: ' . $response->actionUrl . PHP_EOL;
    }

} elseif ($response instanceof SEP08PostTransactionRejected) {
    exit('Rejected: ' . $response->error);
}

// Submit approved transaction to Stellar network
if ($approvedTx !== null) {
    $result = $sdk->submitTransactionEnvelopeXdrBase64($approvedTx);
    echo 'Submitted: ' . $result->getHash() . PHP_EOL;
}
```

---

## Response Classes Reference

### SEP08PostTransactionSuccess

```php
class SEP08PostTransactionSuccess extends SEP08PostTransactionResponse
{
    public string  $tx;              // Base64 XDR envelope — submit to Stellar network
    public ?string $message = null;  // Optional human-readable info for the user
}
```

### SEP08PostTransactionRevised

```php
class SEP08PostTransactionRevised extends SEP08PostTransactionResponse
{
    public string $tx;       // Base64 XDR of revised, issuer-signed transaction
    public string $message;  // Required explanation of what was changed (never null)
}
```

### SEP08PostTransactionPending

```php
class SEP08PostTransactionPending extends SEP08PostTransactionResponse
{
    public int     $timeout = 0;     // Milliseconds to wait before retrying; 0 = unknown
    public ?string $message = null;  // Optional human-readable info
}
```

### SEP08PostTransactionActionRequired

```php
class SEP08PostTransactionActionRequired extends SEP08PostTransactionResponse
{
    public string  $message;              // Required description of the action needed
    public string  $actionUrl;            // URL for completing the action
    public string  $actionMethod = 'GET'; // "GET" or "POST" — defaults to "GET"
    public ?array  $actionFields = null;  // array<string> of SEP-9 field names, or null
}
```

### SEP08PostTransactionRejected

```php
class SEP08PostTransactionRejected extends SEP08PostTransactionResponse
{
    public string $error; // Human-readable rejection reason (never null)
}
```

### SEP08PostActionDone

```php
class SEP08PostActionDone extends SEP08PostActionResponse
{
    // No properties — empty class signals "no further action required"
    // After receiving this, resubmit the original transaction via postTransaction()
}
```

### SEP08PostActionNextUrl

```php
class SEP08PostActionNextUrl extends SEP08PostActionResponse
{
    public string  $nextUrl;         // URL where user completes remaining steps in browser
    public ?string $message = null;  // Optional human-readable info
}
```

---

## Error Handling

```php
<?php declare(strict_types=1);

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08IncompleteInitData;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08InvalidPostActionResponse;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08InvalidPostTransactionResponse;

try {
    $service  = RegulatedAssetsService::fromDomain('regulated-asset-issuer.com');
    $response = $service->postTransaction($txXdr, $approvalServer);

} catch (SEP08IncompleteInitData $e) {
    // stellar.toml is missing NETWORK_PASSPHRASE (or a custom network has no HORIZON_URL)
    // Fix: ensure stellar.toml has valid NETWORK_PASSPHRASE and HORIZON_URL fields
    echo 'stellar.toml incomplete: ' . $e->getMessage() . PHP_EOL;

} catch (SEP08InvalidPostTransactionResponse $e) {
    // Approval server returned malformed response, unknown status, or missing required fields
    // $e->getMessage() — details about what was invalid
    // $e->getCode()    — HTTP status code from the server
    echo 'Invalid approval server response (HTTP ' . $e->getCode() . '): ' . $e->getMessage() . PHP_EOL;

} catch (SEP08InvalidPostActionResponse $e) {
    // Action URL returned malformed response, unknown result, or missing next_url
    // $e->getMessage() — details; $e->getCode() — HTTP status
    echo 'Invalid action response (HTTP ' . $e->getCode() . '): ' . $e->getMessage() . PHP_EOL;

} catch (HorizonRequestException $e) {
    // Horizon call failed — issuer account not found, network unreachable
    // Thrown by authorizationRequired() or underlying SDK calls
    echo 'Horizon error: ' . $e->getMessage() . PHP_EOL;

} catch (GuzzleException $e) {
    // Network-level error: DNS failure, connection timeout, SSL error
    // Thrown by postTransaction() or postAction() on transport failure
    echo 'Network error: ' . $e->getMessage() . PHP_EOL;

} catch (Exception $e) {
    // stellar.toml fetch failed, or other unexpected error
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
```

### Exception reference

| Exception | Thrown by | Trigger |
|---|---|---|
| `SEP08IncompleteInitData` | Constructor, `fromDomain()` | `stellar.toml` missing `NETWORK_PASSPHRASE`, or custom network with no `HORIZON_URL` |
| `SEP08InvalidPostTransactionResponse` | `postTransaction()` | Malformed server response, unknown `status`, missing required fields, non-200/400 HTTP code |
| `SEP08InvalidPostActionResponse` | `postAction()` | Malformed action response, unknown `result`, missing `next_url`, non-200 HTTP code |
| `HorizonRequestException` | `authorizationRequired()` | Horizon API call failed (account not found, network unreachable) |
| `GuzzleException` | `postTransaction()`, `postAction()` | Network transport error (timeout, DNS, SSL) |
| `Exception` | `fromDomain()` | `stellar.toml` fetch failed |

---

## Common Pitfalls

**Pitfall: `$network` requires toml `NETWORK_PASSPHRASE` as fallback**

If you don't pass `$network` explicitly, the constructor reads `NETWORK_PASSPHRASE` from stellar.toml. If neither is available, it throws `SEP08IncompleteInitData`. Passing `$network` explicitly overrides the toml value.

```php
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;
use Soneso\StellarSDK\SEP\Toml\StellarToml;

// WRONG (pre-v1.9.4): $network was broken — passing it would throw unconditionally
// CORRECT (v1.9.4+): $network overrides toml NETWORK_PASSPHRASE
$service = new RegulatedAssetsService($toml);                              // uses NETWORK_PASSPHRASE from toml
$service = new RegulatedAssetsService($toml, network: Network::testnet()); // explicit override
```

**Wrong: accessing `$approvalServer` as a getter**

```php
// WRONG: there is no getApprovalServer() method on RegulatedAsset
$url = $asset->getApprovalServer(); // Fatal error

// CORRECT: access as a public property
$url = $asset->approvalServer;
```

**Wrong: accessing `$approvalCriteria` without null check**

```php
// WRONG: approvalCriteria is ?string — may be null
echo strlen($asset->approvalCriteria); // TypeError if null

// CORRECT: check first
if ($asset->approvalCriteria !== null) {
    echo 'Criteria: ' . $asset->approvalCriteria . PHP_EOL;
}
```

**Wrong: `SEP08PostTransactionPending::$timeout` is milliseconds, not seconds**

```php
// WRONG: treating timeout as seconds
$pending = $response; // SEP08PostTransactionPending
sleep($pending->timeout); // sleeps for 5000 seconds if timeout=5000!

// CORRECT: convert from milliseconds
sleep((int) ($pending->timeout / 1000));
```

**Wrong: `SEP08PostTransactionPending::$timeout` type — it is `int`, not `?int`**

```php
// WRONG: checking for null — timeout is always int (defaults to 0)
if ($response->timeout === null) { ... } // never true

// CORRECT: check for 0 to detect "unknown" wait time
if ($response->timeout === 0) {
    // Server did not specify wait time — use your own retry strategy
} else {
    sleep((int) ($response->timeout / 1000));
}
```

**Wrong: `SEP08PostTransactionRevised::$message` is a required `string`, not `?string`**

```php
// WRONG: null-checking message on revised response (it is never null)
if ($response instanceof SEP08PostTransactionRevised) {
    if ($response->message !== null) { // redundant — always set
        ...
    }
}

// NOTE: For success, $message IS nullable (?string)
//       For revised, $message is always string
if ($response instanceof SEP08PostTransactionSuccess) {
    if ($response->message !== null) { // correct — message is ?string here
        echo $response->message . PHP_EOL;
    }
}
```

**Wrong: `SEP08PostTransactionActionRequired::$actionMethod` defaults to `"GET"`, not `null`**

```php
// WRONG: checking for null action method — it defaults to "GET"
if ($response->actionMethod === null) {
    // This never executes — actionMethod is always "GET" or "POST"
}

// CORRECT: check for "POST" to use programmatic posting; otherwise use "GET" (browser)
if ($response->actionMethod === 'POST') {
    $service->postAction($response->actionUrl, $fields);
} else {
    // actionMethod is "GET" (or the server did not specify, which also defaults to "GET")
    echo 'Open in browser: ' . $response->actionUrl . PHP_EOL;
}
```

**Wrong: `SEP08PostActionNextUrl::$nextUrl` vs `$next_url`**

```php
// WRONG: snake_case — that is the JSON field name, not the PHP property
echo $response->next_url; // Undefined property

// CORRECT: camelCase PHP property
echo $response->nextUrl;
```

**Wrong: forgetting to resubmit the ORIGINAL transaction after `SEP08PostActionDone`**

```php
// WRONG: submitting actionUrl response directly to network — it contains no transaction
if ($actionResponse instanceof SEP08PostActionDone) {
    $sdk->submitTransactionEnvelopeXdrBase64($actionResponse->tx); // Fatal: no $tx property

// CORRECT: resubmit the original $txXdr via postTransaction()
if ($actionResponse instanceof SEP08PostActionDone) {
    $response = $service->postTransaction($txXdr, $approvalServer);
    // now handle the new postTransaction response
}
```

**Wrong: stellar.toml currencies that lack `code`, `issuer`, or `approval_server` appear in `regulatedAssets`**

```php
// These entries are silently skipped — not included in $service->regulatedAssets:
// [[CURRENCIES]] code="USDC" regulated=true approval_server="..."  <- missing issuer
// [[CURRENCIES]] code="USDC" issuer="G..." regulated=true          <- missing approval_server
// [[CURRENCIES]] issuer="G..." approval_server="..." regulated=true <- missing code

// CORRECT: always check that regulatedAssets is non-empty before using it
if (empty($service->regulatedAssets)) {
    throw new \RuntimeException('No regulated assets found in stellar.toml');
}
```

**Accessing `$service->tomlData` after construction**

```php
// tomlData is assigned in the constructor (v1.9.4+; was null in earlier versions)
$service = RegulatedAssetsService::fromDomain('example.com');
$currencies = $service->tomlData->currencies; // works
```

---

## Related SEPs

- [SEP-01](sep-01.md) — stellar.toml (defines regulated assets with `regulated=true`, `approval_server`, `approval_criteria`; must include `NETWORK_PASSPHRASE`)
- [SEP-09](sep-09.md) — Standard KYC fields (field names used in `action_required` `actionFields` and `postAction` requests)
- [SEP-10](sep-10.md) — Web Authentication (some approval servers require SEP-10 JWT for identity verification)
