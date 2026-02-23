# SEP-45: Web Authentication for Contract Accounts

**Purpose:** Authenticate Soroban smart contract accounts (C... addresses) with anchor services and receive a JWT token for subsequent SEP calls.
**Prerequisites:** Requires SEP-01 stellar.toml (provides `WEB_AUTH_FOR_CONTRACTS_ENDPOINT`, `WEB_AUTH_CONTRACT_ID`, `SIGNING_KEY`)
**SDK Namespace:** `Soneso\StellarSDK\SEP\WebAuthForContracts`
**SEP-45 vs SEP-10:** SEP-45 is for contract accounts (C...). SEP-10 is for traditional accounts (G... and M...).

## Table of Contents

- [Quick Start](#quick-start)
- [Creating WebAuthForContracts](#creating-webauthforcontracts)
- [jwtToken() — the Complete Flow](#jwttoken--the-complete-flow)
- [Contracts Without Signature Requirements](#contracts-without-signature-requirements)
- [Client Domain Verification](#client-domain-verification)
- [Step-by-Step Authentication](#step-by-step-authentication)
- [Request Format](#request-format)
- [Response Objects](#response-objects)
- [Error Handling](#error-handling)
- [Testing with Mock Handlers](#testing-with-mock-handlers)
- [Common Pitfalls](#common-pitfalls)
- [SEP-45 vs SEP-10 Comparison](#sep-45-vs-sep-10-comparison)

---

## Quick Start

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;

// Your contract account (C... address) — must implement __check_auth
$contractId = 'CCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ';

// Signer registered in your contract's __check_auth — must have private key
$signer = KeyPair::fromSeed(getenv('CONTRACT_SIGNER_SEED'));

// Load config from anchor's stellar.toml and authenticate in one call
$webAuth = WebAuthForContracts::fromDomain('anchor.example.com', Network::testnet());
$jwtToken = $webAuth->jwtToken($contractId, [$signer]);

echo 'Authenticated! Token: ' . substr($jwtToken, 0, 50) . '...' . PHP_EOL;
```

---

## Creating WebAuthForContracts

### From domain (recommended)

`WebAuthForContracts::fromDomain()` fetches the anchor's `stellar.toml`, reads `WEB_AUTH_FOR_CONTRACTS_ENDPOINT`, `WEB_AUTH_CONTRACT_ID`, and `SIGNING_KEY`, and returns a configured instance. Throws `Exception` if any required field is missing.

```php
<?php declare(strict_types=1);

use Exception;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;

try {
    $webAuth = WebAuthForContracts::fromDomain('anchor.example.com', Network::testnet());
} catch (Exception $e) {
    echo 'Could not load WebAuth config: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
```

Signature:
```
WebAuthForContracts::fromDomain(
    string  $domain,
    Network $network,
    ?Client $httpClient = null
): WebAuthForContracts
```

### Manual construction

Use when you have the configuration values directly (e.g., cached, or for tests).

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;

$webAuth = new WebAuthForContracts(
    authEndpoint:      'https://auth.anchor.example.com/sep45',
    webAuthContractId: 'CCALHRGH5RXIDJDRLPPG4ZX2S563TB2QKKJR4STWKVQCYB6JVPYQXHRG',
    serverSigningKey:  'GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP',
    serverHomeDomain:  'anchor.example.com',
    network:           Network::testnet()
);
```

Constructor signature:
```
new WebAuthForContracts(
    string  $authEndpoint,       // WEB_AUTH_FOR_CONTRACTS_ENDPOINT — must be a valid URL
    string  $webAuthContractId,  // WEB_AUTH_CONTRACT_ID — must start with 'C'
    string  $serverSigningKey,   // SIGNING_KEY — must start with 'G'
    string  $serverHomeDomain,   // domain where stellar.toml was loaded from — must not be empty
    Network $network,
    ?Client $httpClient    = null,
    ?string $sorobanRpcUrl = null // defaults to soroban-testnet.stellar.org / soroban.stellar.org
)
```

The constructor validates all parameters and throws `InvalidArgumentException` if any is invalid.

### Custom Soroban RPC URL

By default the SDK uses `https://soroban-testnet.stellar.org` (testnet) or `https://soroban.stellar.org` (pubnet). Pass `$sorobanRpcUrl` to override.

```php
$webAuth = new WebAuthForContracts(
    authEndpoint:      'https://auth.anchor.example.com/sep45',
    webAuthContractId: 'CCALHRGH5RXIDJDRLPPG4ZX2S563TB2QKKJR4STWKVQCYB6JVPYQXHRG',
    serverSigningKey:  'GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP',
    serverHomeDomain:  'anchor.example.com',
    network:           Network::testnet(),
    httpClient:        null,
    sorobanRpcUrl:     'https://my-rpc.example.com'
);
```

---

## jwtToken() — the Complete Flow

`jwtToken()` executes the entire SEP-45 flow in one call:

1. GET challenge from server (`authorization_entries` + optional `network_passphrase`)
2. Validate `network_passphrase` if present
3. Decode and validate all authorization entries (contract address, function name, args, server signature, nonce consistency)
4. Auto-fetch current ledger via Soroban RPC to set `signatureExpirationLedger` (if signers provided and no explicit expiration)
5. Sign the client authorization entry with the provided keypairs
6. POST signed entries to server and return the JWT token string

Method signature:
```
jwtToken(
    string   $clientAccountId,              // C... contract address to authenticate
    array    $signers,                      // array<KeyPair> — keypairs with private keys; can be empty
    ?string  $homeDomain                = null, // defaults to $serverHomeDomain from stellar.toml
    ?string  $clientDomain              = null, // wallet domain for client attribution
    ?KeyPair $clientDomainKeyPair       = null, // wallet signing keypair (local signing)
    ?callable $clientDomainSigningCallback = null, // callback for remote client domain signing
    ?int     $signatureExpirationLedger = null  // defaults to current ledger + 10
): string
```

Returns the JWT token string. Throws on any failure — see [Error Handling](#error-handling).

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;

$webAuth = WebAuthForContracts::fromDomain('anchor.example.com', Network::testnet());

$contractId = 'CCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ';
$signer     = KeyPair::fromSeed(getenv('CONTRACT_SIGNER_SEED'));

// Simple: auto-expiration, default home domain
$jwtToken = $webAuth->jwtToken($contractId, [$signer]);

// With explicit home domain and custom expiration
$jwtToken = $webAuth->jwtToken(
    clientAccountId:            $contractId,
    signers:                    [$signer],
    homeDomain:                 'anchor.example.com',
    signatureExpirationLedger:  1500000
);
```

**Signature expiration:** When signers are provided and `$signatureExpirationLedger` is `null`, the SDK calls `SorobanServer::getLatestLedger()` and sets expiration to `sequence + 10` (~50–60 seconds). If the signers array is empty this Soroban RPC call is skipped entirely.

---

## Contracts Without Signature Requirements

Some contracts implement `__check_auth` without requiring signature verification. Pass an empty array for signers:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;

$webAuth = WebAuthForContracts::fromDomain('anchor.example.com', Network::testnet());

// Empty signers array — no signatures added, no Soroban RPC call made
$jwtToken = $webAuth->jwtToken(
    clientAccountId: 'CCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ',
    signers:         []
);
```

This only works if the anchor server also supports signature-less authentication.

---

## Client Domain Verification

Non-custodial wallets can prove their domain identity so the anchor can attribute requests to a specific wallet application. The wallet's `stellar.toml` must publish a `SIGNING_KEY`.

### Local signing (wallet owns the key)

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;

$webAuth = WebAuthForContracts::fromDomain('anchor.example.com', Network::testnet());

$contractId          = 'CCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ';
$signer              = KeyPair::fromSeed(getenv('CONTRACT_SIGNER_SEED'));
$clientDomainKeyPair = KeyPair::fromSeed(getenv('WALLET_SIGNING_KEY_SEED'));

$jwtToken = $webAuth->jwtToken(
    clientAccountId:    $contractId,
    signers:            [$signer],
    homeDomain:         'anchor.example.com',
    clientDomain:       'wallet.example.com',
    clientDomainKeyPair: $clientDomainKeyPair
);
```

### Remote signing via callback

When the client domain signing key is on a separate server, provide a callback. The callback receives a single `SorobanAuthorizationEntry` (the client domain entry) and must return a signed `SorobanAuthorizationEntry`.

```php
<?php declare(strict_types=1);

use GuzzleHttp\Client;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;

$webAuth    = WebAuthForContracts::fromDomain('anchor.example.com', Network::testnet());
$contractId = 'CCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ';
$signer     = KeyPair::fromSeed(getenv('CONTRACT_SIGNER_SEED'));

// Callback receives ONE SorobanAuthorizationEntry and must return a signed one
$clientDomainSigningCallback = function (SorobanAuthorizationEntry $entry): SorobanAuthorizationEntry {
    $httpClient = new Client();
    $response = $httpClient->post('https://signing-server.wallet.example.com/sign-sep45', [
        'json' => [
            'authorization_entry' => $entry->toBase64Xdr(),
            'network_passphrase'  => 'Test SDF Network ; September 2015',
        ],
        'headers' => ['Authorization' => 'Bearer ' . getenv('SIGNING_SERVER_TOKEN')],
    ]);
    $data = json_decode($response->getBody()->getContents(), true);
    return SorobanAuthorizationEntry::fromBase64Xdr($data['authorization_entry']);
};

// When using a callback (no clientDomainKeyPair), the SDK fetches the client domain's
// stellar.toml to get its SIGNING_KEY for validation — this makes one extra HTTP request
$jwtToken = $webAuth->jwtToken(
    clientAccountId:              $contractId,
    signers:                      [$signer],
    homeDomain:                   'anchor.example.com',
    clientDomain:                 'wallet.example.com',
    clientDomainKeyPair:          null,
    clientDomainSigningCallback:  $clientDomainSigningCallback
);
```

**WRONG/CORRECT — callback signature differs from SEP-10:**

```php
// WRONG: SEP-10 callback signature — receives a string (base64 XDR), returns a string
$sep10Callback = function (string $transactionXdr): string { ... };

// CORRECT: SEP-45 callback signature — receives SorobanAuthorizationEntry, returns SorobanAuthorizationEntry
$sep45Callback = function (SorobanAuthorizationEntry $entry): SorobanAuthorizationEntry { ... };
```

When `$clientDomain` is provided, you must supply either `$clientDomainKeyPair` or `$clientDomainSigningCallback`. Providing neither throws `InvalidArgumentException`.

---

## Step-by-Step Authentication

For maximum control, call each step individually.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;
use Soneso\StellarSDK\Soroban\SorobanServer;

$contractId = 'CCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ';
$signer     = KeyPair::fromSeed(getenv('CONTRACT_SIGNER_SEED'));
$homeDomain = 'anchor.example.com';

$webAuth = WebAuthForContracts::fromDomain($homeDomain, Network::testnet());

try {
    // Step 1: GET challenge from server
    $challengeResponse = $webAuth->getChallenge($contractId, $homeDomain);

    // Step 2: Decode authorization entries from base64 XDR
    $authEntries = $webAuth->decodeAuthorizationEntries(
        $challengeResponse->getAuthorizationEntries()
    );

    // Step 3: Validate challenge (security checks — always do this before signing)
    $webAuth->validateChallenge($authEntries, $contractId, $homeDomain);

    // Step 4: Get current ledger for signature expiration
    $sorobanServer = new SorobanServer('https://soroban-testnet.stellar.org');
    $latestLedger  = $sorobanServer->getLatestLedger();
    $expirationLedger = $latestLedger->sequence + 10;

    // Step 5: Sign client authorization entries
    $signedEntries = $webAuth->signAuthorizationEntries(
        authEntries:               $authEntries,
        clientAccountId:           $contractId,
        signers:                   [$signer],
        signatureExpirationLedger: $expirationLedger
    );

    // Step 6: POST signed entries and get JWT
    $jwtToken = $webAuth->sendSignedChallenge($signedEntries);

    echo 'JWT Token: ' . $jwtToken . PHP_EOL;

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
```

### Method signatures for low-level access

```
getChallenge(
    string  $clientAccountId,
    ?string $homeDomain   = null,  // defaults to $serverHomeDomain
    ?string $clientDomain = null
): ContractChallengeResponse

decodeAuthorizationEntries(string $base64Xdr): array<SorobanAuthorizationEntry>

validateChallenge(
    array   $authEntries,
    string  $clientAccountId,
    ?string $homeDomain            = null,  // defaults to $serverHomeDomain
    ?string $clientDomainAccountId = null
): void

signAuthorizationEntries(
    array     $authEntries,
    string    $clientAccountId,
    array     $signers,                          // array<KeyPair>
    ?int      $signatureExpirationLedger,
    ?KeyPair  $clientDomainKeyPair          = null,
    ?callable $clientDomainSigningCallback  = null,
    ?string   $clientDomainAccountId        = null
): array<SorobanAuthorizationEntry>

sendSignedChallenge(array $signedEntries): string  // returns JWT token
```

---

## Request Format

By default, the SDK submits signed challenges as `application/x-www-form-urlencoded`. To switch to JSON:

```php
// Default: form-urlencoded
$webAuth->setUseFormUrlEncoded(true);

// Switch to application/json
$webAuth->setUseFormUrlEncoded(false);
```

---

## Response Objects

### ContractChallengeResponse

Returned by `getChallenge()`. Contains the authorization entries that must be decoded, validated, and signed.

| Method | Return type | Description |
|--------|-------------|-------------|
| `getAuthorizationEntries()` | `string` | Base64-encoded XDR array of `SorobanAuthorizationEntry` objects |
| `getNetworkPassphrase()` | `?string` | Optional — server's network passphrase for validation |

### SubmitContractChallengeResponse

Internal response from the token POST endpoint. You only encounter this directly if calling `sendSignedChallenge()` and handling the raw return — `jwtToken()` extracts the token automatically.

| Method | Return type | Description |
|--------|-------------|-------------|
| `getJwtToken()` | `?string` | JWT token on success |
| `getError()` | `?string` | Error message on failure |

### JWT Claims

The JWT returned by `jwtToken()` contains standard claims:

| Claim | Description |
|-------|-------------|
| `sub` | Authenticated contract account (C... address) |
| `iss` | Token issuer (authentication server URI) |
| `iat` | Issued-at timestamp (Unix epoch) |
| `exp` | Expiration timestamp (Unix epoch) |
| `client_domain` | Present when client domain verification was performed |

---

## Error Handling

All challenge validation exceptions extend `ContractChallengeValidationError` which extends `\ErrorException`. Submit exceptions extend `\Exception` directly. All exception classes are in `Soneso\StellarSDK\SEP\WebAuthForContracts`.

```php
<?php declare(strict_types=1);

use InvalidArgumentException;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeRequestErrorResponse;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationError;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidAccount;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidArgs;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidContractAddress;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidFunctionName;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidHomeDomain;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidNetworkPassphrase;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidNonce;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidServerSignature;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidWebAuthDomain;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorMissingClientEntry;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorMissingServerEntry;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorSubInvocationsFound;
use Soneso\StellarSDK\SEP\WebAuthForContracts\SubmitContractChallengeErrorResponseException;
use Soneso\StellarSDK\SEP\WebAuthForContracts\SubmitContractChallengeTimeoutResponseException;
use Soneso\StellarSDK\SEP\WebAuthForContracts\SubmitContractChallengeUnknownResponseException;
use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;

$webAuth    = WebAuthForContracts::fromDomain('anchor.example.com', Network::testnet());
$contractId = 'CCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ';
$signer     = KeyPair::fromSeed(getenv('CONTRACT_SIGNER_SEED'));

try {
    $jwtToken = $webAuth->jwtToken($contractId, [$signer]);

} catch (InvalidArgumentException $e) {
    // Bad parameters: non-C... account, clientDomain without signing means,
    // invalid webAuthContractId/serverSigningKey in constructor
    echo 'Invalid arguments: ' . $e->getMessage() . PHP_EOL;

} catch (ContractChallengeRequestErrorResponse $e) {
    // GET challenge failed — bad account, rate limit, server error
    // $e->getHttpStatusCode() returns ?int
    echo 'Challenge request failed (HTTP ' . $e->getHttpStatusCode() . '): ' . $e->getMessage() . PHP_EOL;

} catch (ContractChallengeValidationErrorSubInvocationsFound $e) {
    // SECURITY CRITICAL: challenge contains sub-invocations that could authorize
    // unintended contract operations — do NOT sign, report to anchor
    error_log('SECURITY ALERT: sub-invocations in SEP-45 challenge from anchor');
    throw $e;

} catch (ContractChallengeValidationErrorInvalidContractAddress $e) {
    // Contract address in entries doesn't match WEB_AUTH_CONTRACT_ID — substitution attack
    echo 'Security error: contract address mismatch' . PHP_EOL;

} catch (ContractChallengeValidationErrorInvalidServerSignature $e) {
    // Server's entry is not signed by the expected SIGNING_KEY — possible MITM
    echo 'Security error: invalid server signature' . PHP_EOL;

} catch (ContractChallengeValidationErrorInvalidFunctionName $e) {
    // Function is not "web_auth_verify" — wrong or malicious contract function
    echo 'Invalid challenge: wrong function name' . PHP_EOL;

} catch (ContractChallengeValidationErrorInvalidNetworkPassphrase $e) {
    // network_passphrase in response doesn't match configured network — cross-network attack
    echo 'Network passphrase mismatch' . PHP_EOL;

} catch (ContractChallengeValidationErrorInvalidAccount $e) {
    // account arg in entries doesn't match the client contract ID
    echo 'Invalid challenge: account mismatch' . PHP_EOL;

} catch (ContractChallengeValidationErrorInvalidHomeDomain $e) {
    // home_domain arg doesn't match expected home domain
    echo 'Invalid challenge: home domain mismatch' . PHP_EOL;

} catch (ContractChallengeValidationErrorInvalidWebAuthDomain $e) {
    // web_auth_domain arg doesn't match the host of the auth endpoint URL
    echo 'Invalid challenge: web auth domain mismatch' . PHP_EOL;

} catch (ContractChallengeValidationErrorInvalidNonce $e) {
    // Nonce is missing or inconsistent across entries — replay protection violated
    echo 'Invalid challenge: nonce inconsistency' . PHP_EOL;

} catch (ContractChallengeValidationErrorInvalidArgs $e) {
    // Args not in expected Map<Symbol, String> format, or web_auth_domain_account
    // doesn't match server's SIGNING_KEY, or client_domain_account mismatch
    echo 'Invalid challenge: bad args' . PHP_EOL;

} catch (ContractChallengeValidationErrorMissingServerEntry $e) {
    // No authorization entry for the server account found
    echo 'Invalid challenge: missing server entry' . PHP_EOL;

} catch (ContractChallengeValidationErrorMissingClientEntry $e) {
    // No authorization entry for the client contract account found
    echo 'Invalid challenge: missing client entry' . PHP_EOL;

} catch (ContractChallengeValidationError $e) {
    // Catch-all for other challenge validation failures (malformed XDR, etc.)
    echo 'Challenge validation failed: ' . $e->getMessage() . PHP_EOL;

} catch (SubmitContractChallengeErrorResponseException $e) {
    // Server rejected signed entries — signer not in __check_auth, invalid sig, etc.
    // HTTP 200 or 400 with an "error" field in the JSON response body
    echo 'Authentication rejected: ' . $e->getMessage() . PHP_EOL;

} catch (SubmitContractChallengeTimeoutResponseException $e) {
    // HTTP 504 Gateway Timeout — server overloaded during transaction simulation
    echo 'Server timeout — retry later' . PHP_EOL;

} catch (SubmitContractChallengeUnknownResponseException $e) {
    // Unexpected HTTP status (not 200, 400, or 504)
    // $e->getHttpStatusCode() and $e->getResponseBody() for details
    echo 'Unexpected response (HTTP ' . $e->getHttpStatusCode() . '): ' . $e->getResponseBody() . PHP_EOL;
}
```

### Exception reference table

| Exception class | Trigger | Security level |
|-----------------|---------|----------------|
| `InvalidArgumentException` (PHP built-in) | Non-C... account, clientDomain without signing means, bad constructor params | Fix calling code |
| `ContractChallengeRequestErrorResponse` | GET challenge failed (bad account, rate limit, server error); `getHttpStatusCode()` available | Operational |
| `ContractChallengeValidationErrorSubInvocationsFound` | Challenge has sub-invocations that could authorize unintended contract ops | **CRITICAL** — abort, do not sign |
| `ContractChallengeValidationErrorInvalidContractAddress` | Entry contract address ≠ `WEB_AUTH_CONTRACT_ID` | **CRITICAL** — substitution attack |
| `ContractChallengeValidationErrorInvalidServerSignature` | Server entry not signed by expected `SIGNING_KEY` | **CRITICAL** — possible MITM |
| `ContractChallengeValidationErrorInvalidFunctionName` | Function name ≠ `"web_auth_verify"` | **CRITICAL** — wrong function |
| `ContractChallengeValidationErrorInvalidNetworkPassphrase` | `network_passphrase` in response ≠ configured network | High — cross-network attack |
| `ContractChallengeValidationErrorInvalidAccount` | `account` arg ≠ client contract ID | High — account substitution |
| `ContractChallengeValidationErrorInvalidHomeDomain` | `home_domain` arg ≠ expected home domain | High — domain confusion |
| `ContractChallengeValidationErrorInvalidWebAuthDomain` | `web_auth_domain` arg ≠ auth endpoint host | High — server spoofing |
| `ContractChallengeValidationErrorInvalidNonce` | Nonce missing or inconsistent across entries | High — replay attack |
| `ContractChallengeValidationErrorInvalidArgs` | Args not in Map format, `web_auth_domain_account` ≠ server signing key, client domain mismatch | High |
| `ContractChallengeValidationErrorMissingServerEntry` | No entry with credentials address = server signing key | High |
| `ContractChallengeValidationErrorMissingClientEntry` | No entry with credentials address = client contract ID | High |
| `ContractChallengeValidationError` | Generic validation failure (malformed XDR, empty entries, invalid entry type) | Unexpected server behavior |
| `SubmitContractChallengeErrorResponseException` | Server rejected signed entries (HTTP 200/400 with `"error"` field) | Operational — check signer registration |
| `SubmitContractChallengeTimeoutResponseException` | HTTP 504 Gateway Timeout | Operational — retry with backoff |
| `SubmitContractChallengeUnknownResponseException` | Unexpected HTTP status; `getHttpStatusCode()` + `getResponseBody()` available | Unexpected server behavior |

---

## Testing with Mock Handlers

`setMockHandler()` replaces the internal Guzzle HTTP client with a `MockHandler`. No network calls are made. Responses are consumed in order.

```php
<?php declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\SorobanAddressCredentials;
use Soneso\StellarSDK\Soroban\SorobanAuthorizedFunction;
use Soneso\StellarSDK\Soroban\SorobanAuthorizedInvocation;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Soroban\SorobanCredentials;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Xdr\XdrEncoder;
use Soneso\StellarSDK\Xdr\XdrInvokeContractArgs;
use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrSCVal;

// ── Server keypair (simulates the anchor's signing key) ──────────────────────
$serverKeyPair      = KeyPair::random();
$serverAccountId    = $serverKeyPair->getAccountId();
$webAuthContractId  = 'CA7A3N2BB35XMTFPAYWVZEF4TEYXW7DAEWDXJNQGUPR5SWSM2UVZCJM2';
$domain             = 'example.stellar.org';
$authServer         = 'https://auth.example.stellar.org';
$clientContractId   = 'CDZJIDQW5WTPAZ64PGIJGVEIDNK72LL3LKUZWG3G6GWXYQKI2JNIVFNV';
$network            = Network::testnet();

// ── Build args map for web_auth_verify ──────────────────────────────────────
$nonce = 'unique_nonce_' . time();
$argsMap = XdrSCVal::forMap([
    new XdrSCMapEntry(XdrSCVal::forSymbol('account'),               XdrSCVal::forString($clientContractId)),
    new XdrSCMapEntry(XdrSCVal::forSymbol('home_domain'),           XdrSCVal::forString($domain)),
    new XdrSCMapEntry(XdrSCVal::forSymbol('web_auth_domain'),       XdrSCVal::forString('auth.example.stellar.org')),
    new XdrSCMapEntry(XdrSCVal::forSymbol('web_auth_domain_account'), XdrSCVal::forString($serverAccountId)),
    new XdrSCMapEntry(XdrSCVal::forSymbol('nonce'),                 XdrSCVal::forString($nonce)),
]);

// ── Helper: build a single authorization entry ────────────────────────────
$buildEntry = function (string $credentialsAddress, int $nonceSeed) use ($webAuthContractId, $argsMap): SorobanAuthorizationEntry {
    $address = Address::fromAnyId($credentialsAddress);
    $credentials = new SorobanCredentials(
        new SorobanAddressCredentials($address, $nonceSeed, 1000000, XdrSCVal::forVec([]))
    );
    $contractAddress = Address::fromContractId(StrKey::decodeContractIdHex($webAuthContractId));
    $contractFn = new XdrInvokeContractArgs($contractAddress->toXdr(), 'web_auth_verify', [$argsMap]);
    $function   = new SorobanAuthorizedFunction($contractFn);
    $invocation = new SorobanAuthorizedInvocation($function, []);
    return new SorobanAuthorizationEntry($credentials, $invocation);
};

// ── Build server entry (pre-signed) + client entry ────────────────────────
$serverEntry = $buildEntry($serverAccountId, 12345);
$serverEntry->sign($serverKeyPair, $network);

$clientEntry = $buildEntry($clientContractId, 12346);

// ── Encode entries to base64 XDR ──────────────────────────────────────────
$entries = [$serverEntry, $clientEntry];
$bytes   = XdrEncoder::unsignedInteger32(count($entries));
foreach ($entries as $e) {
    $bytes .= $e->toXdr()->encode();
}
$challengeXdr = base64_encode($bytes);

// ── Set up mock responses ──────────────────────────────────────────────────
$mock = new MockHandler([
    new Response(200, [], json_encode([               // 1. Challenge GET
        'authorization_entries' => $challengeXdr,
        'network_passphrase'    => 'Test SDF Network ; September 2015',
    ])),
    new Response(200, [], json_encode([               // 2. Token POST
        'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.test',
    ])),
]);

// ── Create WebAuthForContracts and inject mock ──────────────────────────────
$webAuth = new WebAuthForContracts($authServer, $webAuthContractId, $serverAccountId, $domain, $network);
$webAuth->setMockHandler($mock);

$clientSigner = KeyPair::random();
$jwtToken = $webAuth->jwtToken($clientContractId, [$clientSigner], $domain);

echo 'Mock JWT: ' . $jwtToken . PHP_EOL;
```

**Key details for building a valid mock challenge:**
- The server entry credentials address must match `$serverSigningKey` (G... address)
- The client entry credentials address must match the `$clientAccountId` (C... address)
- The server entry must be signed with `$serverKeyPair` and the correct `Network` before encoding
- `web_auth_domain` in the args map must match the `host` portion of the `$authEndpoint` URL (including port if non-standard, e.g., `auth.example.stellar.org:8080`)
- The `nonce` arg must be identical across all entries
- No entry may contain sub-invocations

**Mock response ordering for client domain callback:**

When using `clientDomainSigningCallback` (without `clientDomainKeyPair`), the SDK fetches the client domain's stellar.toml after the challenge. Provide three mocked responses in this order:

```php
$mock = new MockHandler([
    new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])), // 1. Challenge
    new Response(200, [], 'SIGNING_KEY = "' . $clientDomainAccount . '"'),          // 2. stellar.toml
    new Response(200, [], json_encode(['token' => 'eyJ...'])),                       // 3. Token
]);
```

---

## Common Pitfalls

**WRONG: passing a G... or M... address to jwtToken()**

```php
// WRONG: jwtToken() requires a C... contract address — throws InvalidArgumentException
$webAuth->jwtToken('GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP', [$signer]);

// CORRECT: pass the C... contract address
$webAuth->jwtToken('CCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ', [$signer]);
```

**WRONG: signers must contain KeyPair objects with private keys**

```php
// WRONG: KeyPair::fromAccountId() has no private key and cannot produce signatures
$publicOnly = KeyPair::fromAccountId($accountId);
$webAuth->jwtToken($contractId, [$publicOnly]); // Server rejects — signature invalid

// CORRECT: use KeyPair::fromSeed() to include the private key
$signer = KeyPair::fromSeed(getenv('CONTRACT_SIGNER_SEED'));
$webAuth->jwtToken($contractId, [$signer]);
```

**WRONG: treating SubInvocationsFound as a recoverable error**

```php
// WRONG: logging and continuing — could authorize unintended contract operations
} catch (ContractChallengeValidationErrorSubInvocationsFound $e) {
    echo 'Warning: ' . $e->getMessage();
    // Do NOT attempt retry or sign

// CORRECT: abort, alert, do not retry
} catch (ContractChallengeValidationErrorSubInvocationsFound $e) {
    error_log('SECURITY ALERT: sub-invocations in challenge from ' . $authEndpoint);
    throw $e;
```

**WRONG: network mismatch between WebAuthForContracts and the anchor**

```php
// WRONG: pubnet WebAuthForContracts against a testnet anchor
$webAuth = new WebAuthForContracts($endpoint, $contractId, $signingKey, $domain, Network::public());
// → ContractChallengeValidationErrorInvalidServerSignature or InvalidNetworkPassphrase

// CORRECT: match the network to the anchor's actual network
$webAuth = new WebAuthForContracts($endpoint, $contractId, $signingKey, $domain, Network::testnet());
```

**WRONG: wrong constructor parameter order**

```php
// WRONG: confusing webAuthContractId (C...) and serverSigningKey (G...)
new WebAuthForContracts($endpoint, $serverSigningKey, $webAuthContractId, $domain, $network);
// → InvalidArgumentException: "webAuthContractId must be a contract address starting with 'C'"

// CORRECT: webAuthContractId (C...) comes before serverSigningKey (G...)
new WebAuthForContracts($endpoint, $webAuthContractId, $serverSigningKey, $domain, $network);
```

**WRONG: using setUseFormUrlEncoded() after the fact to switch to JSON, then forgetting the default**

The default is `useFormUrlEncoded = true` (form-urlencoded). The SDK docblock incorrectly states "By default, application/json is used" — the actual default is form-urlencoded. Verified in source.

```php
$webAuth->setUseFormUrlEncoded(false);  // switches to JSON
$webAuth->setUseFormUrlEncoded(true);   // returns to default: form-urlencoded
```

---

## SEP-45 vs SEP-10 Comparison

| Aspect | SEP-45 (`WebAuthForContracts`) | SEP-10 (`WebAuth`) |
|--------|-------------------------------|---------------------|
| Account type | Contract accounts (C...) | Traditional accounts (G... and M...) |
| stellar.toml key | `WEB_AUTH_FOR_CONTRACTS_ENDPOINT` | `WEB_AUTH_ENDPOINT` |
| Extra toml key | `WEB_AUTH_CONTRACT_ID` | — |
| Challenge format | Array of `SorobanAuthorizationEntry` (XDR) | Stellar transaction envelope (XDR) |
| Namespace | `Soneso\StellarSDK\SEP\WebAuthForContracts` | `Soneso\StellarSDK\SEP\WebAuth` |
| Main class | `WebAuthForContracts` | `WebAuth` |
| Challenge response field | `authorization_entries` | `transaction` |
| Client domain callback arg | `SorobanAuthorizationEntry` (one entry) | `string` (base64 XDR transaction) |
| Memo support | No | Yes (G... accounts only) |
| Muxed account support | No | Yes (M... addresses) |
| Replay protection | Signature expiration ledger + nonce | Transaction time bounds |
| Auth verification | Contract `__check_auth` invoked by server | Server verifies Ed25519 signature |
| Empty signers | Allowed (contract may not need signatures) | Not applicable |
| Exception hierarchy | `ContractChallengeValidationError extends \ErrorException` | `ChallengeValidationError extends \ErrorException` |

---

## Related SEPs

- [SEP-10](sep-10.md) — Web Authentication for traditional accounts (G... addresses)
- [SEP-01](sep-01.md) — stellar.toml discovery (provides `WEB_AUTH_FOR_CONTRACTS_ENDPOINT`, `WEB_AUTH_CONTRACT_ID`, `SIGNING_KEY`)
- [SEP-24](sep-24.md) — Interactive Deposit/Withdrawal (requires JWT)
- [SEP-12](sep-12.md) — KYC API (requires JWT)
- [SEP-38](sep-38.md) — Quotes API

