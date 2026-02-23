# SEP-10: Stellar Web Authentication

**Purpose:** Prove ownership of a Stellar account to an anchor or service and receive a JWT token for authenticated API calls.
**Prerequisites:** Requires SEP-01 stellar.toml (provides `WEB_AUTH_ENDPOINT` and `SIGNING_KEY`)
**SDK Namespace:** `Soneso\StellarSDK\SEP\WebAuth`

## Table of Contents

- [Quick Start](#quick-start)
- [Creating WebAuth](#creating-webauth)
- [jwtToken() — the Complete Flow](#jwttoken--the-complete-flow)
- [Standard Authentication](#standard-authentication)
- [Multi-Signature Authentication](#multi-signature-authentication)
- [Memo-Based Authentication](#memo-based-authentication)
- [Muxed Account Authentication](#muxed-account-authentication)
- [Client Domain Verification](#client-domain-verification)
- [Multiple Home Domains](#multiple-home-domains)
- [Response Objects](#response-objects)
- [Error Handling](#error-handling)
- [Testing with Mock Handlers](#testing-with-mock-handlers)
- [Common Pitfalls](#common-pitfalls)

---

## Quick Start

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

// Load config from anchor's stellar.toml and run the full SEP-10 flow in one call
$webAuth = WebAuth::fromDomain('testanchor.stellar.org', Network::testnet());

$userKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$jwtToken = $webAuth->jwtToken($userKeyPair->getAccountId(), [$userKeyPair]);

// Use $jwtToken as Bearer token for SEP-12, SEP-24, SEP-31, etc.
echo 'Authenticated! Token: ' . substr($jwtToken, 0, 50) . '...' . PHP_EOL;
```

---

## Creating WebAuth

### From domain (recommended)

`WebAuth::fromDomain()` fetches the anchor's `stellar.toml`, reads `WEB_AUTH_ENDPOINT` and `SIGNING_KEY`, and returns a configured `WebAuth` instance. Throws `Exception` if the toml is missing or the required fields are absent.

```php
<?php declare(strict_types=1);

use Exception;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

try {
    $webAuth = WebAuth::fromDomain('testanchor.stellar.org', Network::testnet());
} catch (Exception $e) {
    echo 'Could not load WebAuth config: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
```

Signature:
```
WebAuth::fromDomain(string $domain, Network $network, ?Client $httpClient = null): WebAuth
```

### Manual construction

Use when you already have the endpoint and signing key (e.g., you loaded stellar.toml separately or are writing tests).

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

$webAuth = new WebAuth(
    authEndpoint: 'https://testanchor.stellar.org/auth',
    serverSigningKey: 'GCUZ6YLL5RQBTYLTTQLPCM73C5XAIUGK2TIMWQH7HPSGWVS2KJ2F3CHS',
    serverHomeDomain: 'testanchor.stellar.org',
    network: Network::testnet()
);
```

Constructor signature:
```
new WebAuth(
    string   $authEndpoint,
    string   $serverSigningKey,
    string   $serverHomeDomain,
    Network  $network,
    ?Client  $httpClient = null
)
```

---

## jwtToken() — the Complete Flow

`jwtToken()` performs all SEP-10 steps internally:

1. Requests a challenge transaction from the auth endpoint (GET)
2. Validates the challenge (sequence number = 0, server signature, time bounds, operation types, source accounts, home domain, web\_auth\_domain)
3. Signs the transaction with the provided signers
4. Submits the signed transaction to the auth endpoint (POST)
5. Returns the JWT token string

Method signature:
```
jwtToken(
    string   $clientAccountId,           // G... or M... account address
    array    $signers,                   // array<KeyPair> — must include private keys
    ?int     $memo = null,               // ID memo for shared accounts (G... accounts only)
    ?string  $homeDomain = null,         // override home domain when server serves multiple
    ?string  $clientDomain = null,       // wallet domain for client attribution
    ?KeyPair $clientDomainKeyPair = null, // wallet signing keypair (if local)
    ?callable $clientDomainSigningCallback = null // callback for remote signing
): string
```

Returns the JWT token string. Throws exceptions on any failure — see [Error Handling](#error-handling).

---

## Standard Authentication

For a single-signature account that owns its own keys. The account does not need to exist on-chain — SEP-10 only proves key ownership.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

$webAuth = WebAuth::fromDomain('testanchor.stellar.org', Network::testnet());

$userKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));

$jwtToken = $webAuth->jwtToken(
    clientAccountId: $userKeyPair->getAccountId(),
    signers: [$userKeyPair]
);

echo 'JWT: ' . $jwtToken . PHP_EOL;
```

---

## Multi-Signature Authentication

For accounts that require multiple signers to meet the server's threshold. Provide all required keypairs in the `$signers` array — the combined weight must satisfy the server's requirements.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

$webAuth = WebAuth::fromDomain('testanchor.stellar.org', Network::testnet());

$signer1 = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED_1'));
$signer2 = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED_2'));

// Both signers sign the challenge. Their combined weight must meet the threshold.
$jwtToken = $webAuth->jwtToken(
    clientAccountId: $signer1->getAccountId(),
    signers: [$signer1, $signer2]
);

echo 'JWT: ' . $jwtToken . PHP_EOL;
```

---

## Memo-Based Authentication

For services that distinguish users sharing a single Stellar account via an integer memo. The `$memo` parameter must be a positive integer.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

$webAuth = WebAuth::fromDomain('testanchor.stellar.org', Network::testnet());

$sharedAccountKeyPair = KeyPair::fromSeed(getenv('STELLAR_SHARED_SEED'));
$userId = 1234567890; // Integer user ID

$jwtToken = $webAuth->jwtToken(
    clientAccountId: $sharedAccountKeyPair->getAccountId(), // G... address
    signers: [$sharedAccountKeyPair],
    memo: $userId
);

echo 'JWT for user ' . $userId . ': ' . $jwtToken . PHP_EOL;
```

**Important:** `$memo` only works with G... (non-muxed) account IDs. Passing a memo together with an M... address throws `InvalidArgumentException`.

---

## Muxed Account Authentication

Muxed accounts (M... addresses) embed a user ID into the account address itself as an alternative to memos. Pass the M... address as `$clientAccountId` and the underlying G... keypair in `$signers`.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

$webAuth = WebAuth::fromDomain('testanchor.stellar.org', Network::testnet());

$baseKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$muxedAccount = new MuxedAccount($baseKeyPair->getAccountId(), 1234567890);

$jwtToken = $webAuth->jwtToken(
    clientAccountId: $muxedAccount->getAccountId(), // returns M... address
    signers: [$baseKeyPair]                          // sign with the underlying G... key
);

echo 'JWT: ' . $jwtToken . PHP_EOL;
```

**Important:** You cannot use both a muxed account (M...) address and the `$memo` parameter simultaneously.

```php
// WRONG: memo with M... address — throws InvalidArgumentException
$webAuth->jwtToken($muxedAccount->getAccountId(), [$keyPair], memo: 12345);

// CORRECT: use one or the other, never both
$webAuth->jwtToken($muxedAccount->getAccountId(), [$keyPair]); // muxed account only
$webAuth->jwtToken($gAddress, [$keyPair], memo: 12345);         // G... + memo only
```

---

## Client Domain Verification

Non-custodial wallets can prove their identity to anchors by including a client domain signature. Anchors can then provide tailored experiences for users of known, trusted wallets.

### Local signing (wallet has the key)

Provide `$clientDomain` and `$clientDomainKeyPair`. The wallet's `stellar.toml` must publish a `SIGNING_KEY` that matches the keypair.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

$webAuth = WebAuth::fromDomain('testanchor.stellar.org', Network::testnet());

$userKeyPair = KeyPair::fromSeed(getenv('STELLAR_USER_SEED'));
$walletKeyPair = KeyPair::fromSeed(getenv('STELLAR_WALLET_SEED'));

$jwtToken = $webAuth->jwtToken(
    clientAccountId: $userKeyPair->getAccountId(),
    signers: [$userKeyPair],
    clientDomain: 'mywallet.com',
    clientDomainKeyPair: $walletKeyPair
);

echo 'JWT: ' . $jwtToken . PHP_EOL;
```

### Remote signing callback (key on a separate server)

When the wallet's signing key is stored on a dedicated signing server, provide a callback instead. The SDK loads the wallet's `stellar.toml` to get its `SIGNING_KEY` for validation, then calls the callback with the base64-encoded transaction XDR. The callback must return the signed transaction as base64-encoded XDR.

```php
<?php declare(strict_types=1);

use Exception;
use GuzzleHttp\Client;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

$webAuth = WebAuth::fromDomain('testanchor.stellar.org', Network::testnet());
$userKeyPair = KeyPair::fromSeed(getenv('STELLAR_USER_SEED'));

// Callback: receives base64 XDR, must return signed base64 XDR
$signingCallback = function (string $transactionXdr): string {
    $httpClient = new Client();
    $response = $httpClient->post('https://signing-server.mywallet.com/sign', [
        'json' => [
            'transaction' => $transactionXdr,
            'network_passphrase' => 'Test SDF Network ; September 2015',
        ],
        'headers' => ['Authorization' => 'Bearer ' . getenv('SIGNING_SERVER_TOKEN')],
    ]);
    $data = json_decode($response->getBody()->getContents(), true);
    if (!isset($data['transaction'])) {
        throw new Exception('Invalid signing server response');
    }
    return $data['transaction'];
};

$jwtToken = $webAuth->jwtToken(
    clientAccountId: $userKeyPair->getAccountId(),
    signers: [$userKeyPair],
    clientDomain: 'mywallet.com',
    clientDomainSigningCallback: $signingCallback
);

echo 'JWT: ' . $jwtToken . PHP_EOL;
```

Callback signature: `function(string $transactionXdr): string`

When `$clientDomain` is provided, you must supply either `$clientDomainKeyPair` or `$clientDomainSigningCallback` — not both, and not neither.

```php
// WRONG: clientDomain without a keypair or callback — throws InvalidArgumentException
$webAuth->jwtToken($accountId, [$keyPair], clientDomain: 'mywallet.com');
```

---

## Multiple Home Domains

When an anchor's auth server handles multiple home domains, use `$homeDomain` to specify which domain the challenge should be issued for.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

$webAuth = WebAuth::fromDomain('testanchor.stellar.org', Network::testnet());
$userKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));

$jwtToken = $webAuth->jwtToken(
    clientAccountId: $userKeyPair->getAccountId(),
    signers: [$userKeyPair],
    homeDomain: 'other-domain.com'
);
```

---

## Response Objects

The `jwtToken()` method returns a plain `string` (the JWT). Internally it uses two response classes that you only encounter if you access the SDK at a lower level:

### ChallengeResponse

Returned from the challenge endpoint (GET). Contains the base64-encoded transaction XDR.

| Method | Return type | Description |
|--------|-------------|-------------|
| `getTransaction()` | `string` | Base64-encoded XDR transaction envelope |
| `setTransaction(string $tx)` | `void` | Setter |

### SubmitCompletedChallengeResponse

Returned from the token endpoint (POST). Contains either the JWT token or an error.

| Method | Return type | Description |
|--------|-------------|-------------|
| `getJwtToken()` | `?string` | JWT token string on success |
| `getError()` | `?string` | Error message from server on failure |

You rarely need to instantiate these directly — `jwtToken()` handles the full flow. They are exposed for advanced use cases and testing.

---

## Error Handling

All exceptions are in the `Soneso\StellarSDK\SEP\WebAuth` namespace unless noted. All challenge validation exceptions extend `\ErrorException`. The submit exceptions also extend `\ErrorException`.

```php
<?php declare(strict_types=1);

use InvalidArgumentException;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeRequestErrorResponse;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationError;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidHomeDomain;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidMemoType;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidMemoValue;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidOperationType;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidSeqNr;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidSignature;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidSourceAccount;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidTimeBounds;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidWebAuthDomain;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorMemoAndMuxedAccount;
use Soneso\StellarSDK\SEP\WebAuth\SubmitCompletedChallengeErrorResponseException;
use Soneso\StellarSDK\SEP\WebAuth\SubmitCompletedChallengeTimeoutResponseException;
use Soneso\StellarSDK\SEP\WebAuth\SubmitCompletedChallengeUnknownResponseException;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

try {
    $webAuth = WebAuth::fromDomain('testanchor.stellar.org', Network::testnet());
    $userKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));

    $jwtToken = $webAuth->jwtToken($userKeyPair->getAccountId(), [$userKeyPair]);

} catch (InvalidArgumentException $e) {
    // Bad call parameters — e.g., memo with muxed account, or clientDomain without keypair/callback
    echo 'Invalid arguments: ' . $e->getMessage() . PHP_EOL;

} catch (ChallengeRequestErrorResponse $e) {
    // Server rejected the challenge GET request (rate limit, bad account, server error)
    echo 'Challenge request failed: ' . $e->getMessage() . PHP_EOL;

} catch (ChallengeValidationErrorInvalidSeqNr $e) {
    // SECURITY: challenge has a non-zero sequence number — could be an executable transaction
    echo 'SECURITY: invalid sequence number — do not proceed' . PHP_EOL;

} catch (ChallengeValidationErrorInvalidSignature $e) {
    // Challenge not signed by the expected server key, or has the wrong number of signatures
    echo 'Invalid server signature — check stellar.toml SIGNING_KEY' . PHP_EOL;

} catch (ChallengeValidationErrorInvalidTimeBounds $e) {
    // Challenge expired or not yet valid — request a fresh challenge
    echo 'Challenge expired — retry to get a fresh one' . PHP_EOL;

} catch (ChallengeValidationErrorInvalidHomeDomain $e) {
    // First op key does not match "<serverHomeDomain> auth"
    echo 'Home domain mismatch in challenge' . PHP_EOL;

} catch (ChallengeValidationErrorInvalidWebAuthDomain $e) {
    // web_auth_domain operation value does not match the auth endpoint host
    echo 'Web auth domain mismatch' . PHP_EOL;

} catch (ChallengeValidationErrorInvalidSourceAccount $e) {
    // Wrong source account on an operation (first op must be client; others must be server or client-domain account)
    echo 'Invalid source account in challenge operation' . PHP_EOL;

} catch (ChallengeValidationErrorInvalidOperationType $e) {
    // SECURITY: challenge contains a non-ManageData operation — could execute funds transfer
    echo 'SECURITY: invalid operation type — server may be malicious' . PHP_EOL;

} catch (ChallengeValidationErrorInvalidMemoType $e) {
    // Memo in challenge is not MEMO_NONE or MEMO_ID (e.g., server sent MEMO_TEXT)
    echo 'Invalid memo type in challenge' . PHP_EOL;

} catch (ChallengeValidationErrorInvalidMemoValue $e) {
    // Memo in challenge does not match the requested memo (or memo missing when expected)
    echo 'Memo value mismatch in challenge' . PHP_EOL;

} catch (ChallengeValidationErrorMemoAndMuxedAccount $e) {
    // Challenge has both a memo and an M... source account — mutually exclusive
    echo 'Challenge has both memo and muxed account' . PHP_EOL;

} catch (ChallengeValidationError $e) {
    // Catch-all for other challenge validation issues (malformed XDR, empty operations, etc.)
    echo 'Challenge validation failed: ' . $e->getMessage() . PHP_EOL;

} catch (SubmitCompletedChallengeErrorResponseException $e) {
    // Server rejected signed challenge — insufficient signers, bad signatures, etc.
    echo 'Authentication rejected: ' . $e->getMessage() . PHP_EOL;

} catch (SubmitCompletedChallengeTimeoutResponseException $e) {
    // Server returned HTTP 504 Gateway Timeout — retry with backoff
    echo 'Server timeout — retry later' . PHP_EOL;

} catch (SubmitCompletedChallengeUnknownResponseException $e) {
    // Server returned an unexpected HTTP status code
    echo 'Unexpected server response: ' . $e->getMessage() . PHP_EOL;
}
```

### Exception reference table

| Exception class | Trigger | Action |
|-----------------|---------|--------|
| `InvalidArgumentException` (PHP built-in) | Bad parameters: memo + M... account, or `clientDomain` without signing means | Fix calling code |
| `ChallengeRequestErrorResponse` | Server rejected challenge GET (bad account, rate limit, etc.) | Check account format, respect rate limits |
| `ChallengeValidationErrorInvalidSeqNr` | Challenge sequence number != 0 | **Security risk** — abort |
| `ChallengeValidationErrorInvalidSignature` | Wrong server signature or wrong number of signatures | Verify stellar.toml `SIGNING_KEY` |
| `ChallengeValidationErrorInvalidTimeBounds` | Challenge time window has passed or not started | Retry — get a fresh challenge |
| `ChallengeValidationErrorInvalidHomeDomain` | First op key != `"<serverHomeDomain> auth"` | Check serverHomeDomain config |
| `ChallengeValidationErrorInvalidWebAuthDomain` | `web_auth_domain` op value != auth endpoint host | Server config mismatch |
| `ChallengeValidationErrorInvalidSourceAccount` | Wrong source on any operation | Server config issue |
| `ChallengeValidationErrorInvalidOperationType` | Non-ManageData operation in challenge | **Security risk** — server may be malicious |
| `ChallengeValidationErrorInvalidMemoType` | Memo is not `MEMO_NONE` or `MEMO_ID` | Server config issue |
| `ChallengeValidationErrorInvalidMemoValue` | Memo value missing or doesn't match request | Server config issue |
| `ChallengeValidationErrorMemoAndMuxedAccount` | Challenge has both memo and M... source | Use memo OR muxed, not both |
| `ChallengeValidationError` | Generic validation failure (malformed XDR, zero operations, etc.) | Unexpected server behavior |
| `SubmitCompletedChallengeErrorResponseException` | Server rejected signed challenge (HTTP 400) | Provide all required signers |
| `SubmitCompletedChallengeTimeoutResponseException` | HTTP 504 Gateway Timeout | Retry with exponential backoff |
| `SubmitCompletedChallengeUnknownResponseException` | Unexpected HTTP status code | Check server logs |

### Retry pattern for transient errors

```php
<?php declare(strict_types=1);

use Exception;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidTimeBounds;
use Soneso\StellarSDK\SEP\WebAuth\SubmitCompletedChallengeTimeoutResponseException;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

function authenticateWithRetry(
    WebAuth $webAuth,
    string $accountId,
    array $signers,
    int $maxAttempts = 3
): string {
    $lastException = null;
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        try {
            return $webAuth->jwtToken($accountId, $signers);
        } catch (ChallengeValidationErrorInvalidTimeBounds $e) {
            // Challenge expired — get a fresh one immediately
            $lastException = $e;
        } catch (SubmitCompletedChallengeTimeoutResponseException $e) {
            // Server overloaded — back off before retrying
            $lastException = $e;
            sleep(2 ** $attempt); // 2, 4, 8 seconds
        }
    }
    throw $lastException ?? new Exception("Authentication failed after $maxAttempts attempts");
}

$webAuth = WebAuth::fromDomain('testanchor.stellar.org', Network::testnet());
$userKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$jwtToken = authenticateWithRetry($webAuth, $userKeyPair->getAccountId(), [$userKeyPair]);
```

---

## Testing with Mock Handlers

`WebAuth::setMockHandler()` replaces the internal Guzzle HTTP client with a `MockHandler`. Use this for unit tests — no network calls are made.

```php
<?php declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\ManageDataOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\TransactionBuilder;
use phpseclib3\Math\BigInteger;
use DateTime;

// Server keypair (simulates the anchor's signing key)
$serverKeyPair = KeyPair::random();
$serverAccountId = $serverKeyPair->getAccountId();
$domain = 'place.domain.com';
$authServer = 'http://api.stellar.org/auth';

// Client keypair
$clientKeyPair = KeyPair::random();
$clientAccountId = $clientKeyPair->getAccountId();

// Build a valid challenge transaction (mimics what the server would produce)
$transactionAccount = new Account($serverAccountId, new BigInteger(-1)); // seq -1 → seq 0 after build()
$now = time();
$transaction = (new TransactionBuilder($transactionAccount))
    ->addOperation(
        (new ManageDataOperationBuilder($domain . ' auth', random_bytes(64)))
            ->setMuxedSourceAccount(MuxedAccount::fromAccountId($clientAccountId))
            ->build()
    )
    ->addOperation(
        (new ManageDataOperationBuilder('web_auth_domain', 'api.stellar.org'))
            ->setSourceAccount($serverAccountId)
            ->build()
    )
    ->addMemo(Memo::none())
    ->setTimeBounds(new TimeBounds(
        (new DateTime)->setTimestamp($now - 1),
        (new DateTime)->setTimestamp($now + 300)
    ))
    ->build();
$transaction->sign($serverKeyPair, Network::testnet());

$challengeJson = json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);
$tokenJson = json_encode(['token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...']);

// Create WebAuth with manual config (matches the challenge transaction)
$webAuth = new WebAuth($authServer, $serverAccountId, $domain, Network::testnet());

$mock = new MockHandler([
    new Response(200, [], $challengeJson), // challenge GET response
    new Response(200, [], $tokenJson),     // token POST response
]);
$webAuth->setMockHandler($mock);

$jwtToken = $webAuth->jwtToken($clientAccountId, [$clientKeyPair]);
echo 'Mock JWT: ' . $jwtToken . PHP_EOL;
```

**Key details for building a valid mock challenge:**
- The `TransactionAccount` sequence starts at `-1` (BigInteger) — `build()` increments it to `0`
- The first ManageData op key must be `"<serverHomeDomain> auth"` and its source must be the client account
- The `web_auth_domain` op source must be the server signing key; its value must match the `host` part of `$authServer`
- The transaction must be signed by the server's keypair with the correct `Network`
- Time bounds must include the current time

---

## Common Pitfalls

**Wrong: mixing memo with muxed account**

```php
$muxed = new MuxedAccount($gAddress, 12345);

// WRONG: throws InvalidArgumentException — memo and M... address are mutually exclusive
$webAuth->jwtToken($muxed->getAccountId(), [$keyPair], memo: 99);

// CORRECT: choose one method of user identification
$webAuth->jwtToken($muxed->getAccountId(), [$keyPair]);          // muxed account
$webAuth->jwtToken($gAddress, [$keyPair], memo: 99);              // G... + memo
```

**Wrong: network passphrase mismatch**

The `Network` passed to `WebAuth` must match the network the server signed the challenge with. If they differ, `ChallengeValidationErrorInvalidSignature` is thrown even though the challenge was technically valid.

```php
// WRONG: WebAuth on public network but anchor signed for testnet
$webAuth = new WebAuth($endpoint, $signingKey, $domain, Network::public());
// → ChallengeValidationErrorInvalidSignature (signatures won't verify)

// CORRECT: match the network to the anchor's actual network
$webAuth = new WebAuth($endpoint, $signingKey, $domain, Network::testnet());
```

**Wrong: omitting client domain signing means**

```php
// WRONG: clientDomain provided but no keypair or callback — throws InvalidArgumentException
$webAuth->jwtToken($accountId, [$keyPair], clientDomain: 'mywallet.com');

// CORRECT: provide the keypair for local signing
$webAuth->jwtToken($accountId, [$keyPair],
    clientDomain: 'mywallet.com',
    clientDomainKeyPair: $walletKeyPair
);

// OR: provide a callback for remote signing
$webAuth->jwtToken($accountId, [$keyPair],
    clientDomain: 'mywallet.com',
    clientDomainSigningCallback: $callback
);
```

**Wrong: `$signers` array must contain `KeyPair` objects with secret keys**

```php
// WRONG: KeyPair::fromAccountId() has no private key and cannot sign
$publicOnly = KeyPair::fromAccountId($accountId);
$webAuth->jwtToken($accountId, [$publicOnly]); // signing fails silently or server rejects

// CORRECT: KeyPair::fromSeed() includes the private key
$fullKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$webAuth->jwtToken($accountId, [$fullKeyPair]);
```

**Wrong: treating `ChallengeValidationErrorInvalidSeqNr` and `ChallengeValidationErrorInvalidOperationType` as recoverable**

These two exceptions indicate potential malicious server behavior. Never retry or ignore them.

```php
// CORRECT: treat these as fatal security errors
try {
    $jwt = $webAuth->jwtToken($accountId, [$keyPair]);
} catch (ChallengeValidationErrorInvalidSeqNr $e) {
    // Non-zero sequence number: signing this could execute a real transaction
    error_log('SECURITY ALERT: Invalid seq nr from auth server ' . $authEndpoint);
    throw $e; // do not retry
} catch (ChallengeValidationErrorInvalidOperationType $e) {
    // Non-ManageData op: could be a payment or account modification
    error_log('SECURITY ALERT: Non-ManageData op from auth server ' . $authEndpoint);
    throw $e; // do not retry
}
```

---

## JWT Token Structure

The JWT returned by `jwtToken()` is a standard JSON Web Token. The SDK does not decode JWTs — use any JWT library or [jwt.io](https://jwt.io) for inspection.

Standard claims in the token:

| Claim | Description |
|-------|-------------|
| `sub` | Authenticated account — G... address, M... address, or `G...:memo` for memo auth |
| `iss` | Token issuer (the authentication server URL) |
| `iat` | Issued-at timestamp (Unix epoch) |
| `exp` | Expiration timestamp (Unix epoch) |
| `client_domain` | Present when client domain verification was performed |

Use the token as a `Bearer` header for SEP-12 (KYC), SEP-24 (interactive deposit/withdrawal), SEP-31 (cross-border payments), and any other authenticated anchor API.

```php
// Example: using JWT with a Guzzle request to a SEP-24 endpoint
$response = $httpClient->get('https://anchor.example.com/sep24/info', [
    'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
]);
```

---

## Related SEPs

- [SEP-01](sep-01.md) — stellar.toml discovery (provides `WEB_AUTH_ENDPOINT` and `SIGNING_KEY` consumed by `WebAuth::fromDomain()`)
- [SEP-06](sep-06.md) — Deposit/Withdrawal API (requires SEP-10 JWT)
- [SEP-12](sep-12.md) — KYC API (requires SEP-10 JWT)
- [SEP-24](sep-24.md) — Interactive Deposit/Withdrawal (requires SEP-10 JWT)
- [SEP-31](sep-31.md) — Cross-Border Payments (requires SEP-10 JWT)
- [SEP-45](sep-45.md) — Web Authentication for Soroban Contract Accounts

