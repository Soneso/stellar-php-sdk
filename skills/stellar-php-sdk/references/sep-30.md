# SEP-30: Account Recovery

**Purpose:** Recover access to Stellar accounts when the owner loses their private key. Recovery servers act as cosigners: register your account with them, then call on them to sign a key-rotation transaction if you ever lose your private key.
**Prerequisites:** Requires JWT from SEP-10 (see [sep-10.md](sep-10.md)) for initial registration and updates. Recovery (signing) uses a JWT from the server's alternate auth flow (email/phone/stellar_address).
**SDK Namespace:** `Soneso\StellarSDK\SEP\Recovery`

## Table of Contents

- [How Recovery Works](#how-recovery-works)
- [Creating the Service](#creating-the-service)
- [Registering an Account](#registering-an-account)
- [Adding the Recovery Signer to Your Stellar Account](#adding-the-recovery-signer-to-your-stellar-account)
- [Signing a Recovery Transaction](#signing-a-recovery-transaction)
- [Updating Identity Information](#updating-identity-information)
- [Getting Account Details](#getting-account-details)
- [Listing Accounts](#listing-accounts)
- [Deleting a Registration](#deleting-a-registration)
- [Error Handling](#error-handling)
- [Request and Response Objects](#request-and-response-objects)
- [Common Pitfalls](#common-pitfalls)

---

## How Recovery Works

1. **Register**: Call `registerAccount()` with your account address, an identity (role + auth methods), and your SEP-10 JWT. The server returns a signer public key.
2. **Add Signer**: Add the server's signer key to your Stellar account via `SetOptions` with weight=1. Set your account thresholds so the server alone cannot control the account.
3. **Recovery**: If you lose your key, authenticate to the recovery server via alternate means (email, phone). The server issues you a JWT proving that identity.
4. **Sign Transaction**: Build a transaction that adds your new key. Call `signTransaction()` with the recovery JWT. The server returns a base64 signature.
5. **Attach Signature**: Decode the signature, create an `XdrDecoratedSignature`, and attach it to the transaction envelope.
6. **Submit**: Submit the now-signed transaction to Horizon to regain control.

---

## Creating the Service

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Recovery\RecoveryService;
use GuzzleHttp\Client;

// Basic: service URL only (default Guzzle client)
$service = new RecoveryService("https://recovery.example.com");

// Advanced: custom Guzzle client (timeouts, proxies, etc.)
$httpClient = new Client(['timeout' => 30, 'connect_timeout' => 10]);
$service = new RecoveryService("https://recovery.example.com", $httpClient);

// Trailing slash is handled: both of the following are equivalent
$service = new RecoveryService("https://recovery.example.com");
$service = new RecoveryService("https://recovery.example.com/");
```

Constructor signature:
```
new RecoveryService(string $serviceAddress, ?Client $httpClient = null)
```

---

## Registering an Account

Call `registerAccount()` with:
- `$address` — the Stellar account address (G... format)
- `$request` — a `SEP30Request` containing one or more `SEP30RequestIdentity` objects
- `$jwt` — a SEP-10 JWT proving you control the account

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Recovery\RecoveryService;
use Soneso\StellarSDK\SEP\Recovery\SEP30Request;
use Soneso\StellarSDK\SEP\Recovery\SEP30RequestIdentity;
use Soneso\StellarSDK\SEP\Recovery\SEP30AuthMethod;
use Soneso\StellarSDK\SEP\Recovery\SEP30ConflictResponseException;

$service = new RecoveryService("https://recovery.example.com");

// Build authentication methods for this identity
// Multiple methods provide fallback options if one is compromised
$authMethods = [
    new SEP30AuthMethod("stellar_address", "GBUCAAMD7DYS7226CWUUOZ5Y2QF4JBJWIYU3UWJAFDGJVCR6EU5NJM5H"),
    new SEP30AuthMethod("phone_number", "+10000000001"),  // E.164 format required
    new SEP30AuthMethod("email", "person@example.com"),
];

// Identity role is a client-defined label ("owner", "sender", "receiver", etc.)
$identity = new SEP30RequestIdentity("owner", $authMethods);
$request = new SEP30Request([$identity]);

try {
    $response = $service->registerAccount($accountId, $request, $jwtToken);
} catch (SEP30ConflictResponseException $e) {
    // Account already registered — use updateIdentitiesForAccount() instead
    echo "Already registered: " . $e->getMessage() . PHP_EOL;
}

// Response contains the signer key(s) to add to your account
echo "Account: " . $response->address . PHP_EOL;
foreach ($response->signers as $signer) {
    echo "Add signer to account: " . $signer->key . PHP_EOL;
}
foreach ($response->identities as $identity) {
    echo "Identity role: " . ($identity->role ?? '(unspecified)') . PHP_EOL;
}
```

Method signature:
```
registerAccount(string $address, SEP30Request $request, string $jwt): SEP30AccountResponse
```

---

## Adding the Recovery Signer to Your Stellar Account

After registration, add the server's signer key to your account. For multi-server recovery, set thresholds so no single server can unilaterally control the account.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Signer;

$sdk = StellarSDK::getTestNetInstance();
$accountKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$accountId = $accountKeyPair->getAccountId();

// Signer key comes from the registerAccount() response
$signerKey = $response->signers[0]->key;
// WRONG: passing the G... string directly to setSigner()
// CORRECT: wrap it in Signer::ed25519PublicKey() with KeyPair::fromAccountId()
$signerXdrKey = Signer::ed25519PublicKey(KeyPair::fromAccountId($signerKey));

$account = $sdk->requestAccount($accountId);
$transaction = (new TransactionBuilder($account))
    ->addOperation(
        (new SetOptionsOperationBuilder())
            ->setSigner($signerXdrKey, 1)  // weight=1
            ->build()
    )
    // Optional: set thresholds so recovery server cannot act alone
    // With threshold=2 and your key at weight=10, server (weight=1) cannot act alone
    ->addOperation(
        (new SetOptionsOperationBuilder())
            ->setHighThreshold(2)
            ->setMediumThreshold(2)
            ->setLowThreshold(2)
            ->build()
    )
    ->build();

$transaction->sign($accountKeyPair, Network::testnet());
$sdk->submitTransaction($transaction);
echo "Recovery signer added." . PHP_EOL;
```

**Multi-server setup:** Register with two servers, add both signer keys, set thresholds to 2. Either server alone cannot control the account; both must cooperate to recover.

```php
// Register with server 1
$response1 = $service1->registerAccount($accountId, $request, $jwtToken);
$signerXdrKey1 = Signer::ed25519PublicKey(KeyPair::fromAccountId($response1->signers[0]->key));

// Register with server 2
$response2 = $service2->registerAccount($accountId, $request, $jwtToken);
$signerXdrKey2 = Signer::ed25519PublicKey(KeyPair::fromAccountId($response2->signers[0]->key));

$account = $sdk->requestAccount($accountId);
$transaction = (new TransactionBuilder($account))
    ->addOperation((new SetOptionsOperationBuilder())->setSigner($signerXdrKey1, 1)->build())
    ->addOperation((new SetOptionsOperationBuilder())->setSigner($signerXdrKey2, 1)->build())
    ->addOperation(
        (new SetOptionsOperationBuilder())
            ->setHighThreshold(2)->setMediumThreshold(2)->setLowThreshold(2)
            ->build()
    )
    ->build();
$transaction->sign($accountKeyPair, Network::testnet());
$sdk->submitTransaction($transaction);
```

---

## Signing a Recovery Transaction

When you need to recover an account, build a transaction that adds your new key, then get the recovery server to sign it.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Signer;
use Soneso\StellarSDK\SEP\Recovery\RecoveryService;
use Soneso\StellarSDK\Xdr\XdrDecoratedSignature;

$service = new RecoveryService("https://recovery.example.com");
$sdk = StellarSDK::getTestNetInstance();

// Use a JWT from alternate authentication (email/phone), not your main key
// Step 1: Find the signing address (the server's signer key for this account)
$accountDetails = $service->accountDetails($accountId, $recoveryJwt);
$signingAddress = $accountDetails->signers[0]->key;

// Step 2: Generate a new keypair to replace the lost key
$newKeyPair = KeyPair::random();
$newSignerXdrKey = Signer::ed25519PublicKey($newKeyPair);

// Step 3: Build the recovery transaction
$account = $sdk->requestAccount($accountId);
$transaction = (new TransactionBuilder($account))
    ->addOperation(
        (new SetOptionsOperationBuilder())
            ->setSigner($newSignerXdrKey, 10)  // high weight to regain control
            ->build()
    )
    ->build();

// Step 4: Request the recovery server to sign it
// $transaction passed as base64 XDR
$txBase64 = $transaction->toEnvelopeXdrBase64();
$signatureResponse = $service->signTransaction(
    $accountId,
    $signingAddress,
    $txBase64,
    $recoveryJwt
);

// Step 5: Attach the signature to the transaction
// The signature is base64-encoded; the hint is the last 4 bytes of the signer's public key
$signerKeyPair = KeyPair::fromAccountId($signingAddress);
$hint = $signerKeyPair->getHint();
$signatureBytes = base64_decode($signatureResponse->signature);
$decoratedSignature = new XdrDecoratedSignature($hint, $signatureBytes);
$transaction->addSignature($decoratedSignature);

// For multi-server recovery: repeat steps 4-5 for each server, then submit
$sdk->submitTransaction($transaction);

echo "Account recovered! New seed: " . $newKeyPair->getSecretSeed() . PHP_EOL;
echo "Store this seed securely!" . PHP_EOL;
```

Method signature:
```
signTransaction(string $address, string $signingAddress, string $transaction, string $jwt): SEP30SignatureResponse
```

The `$transaction` parameter is the base64-encoded XDR envelope string (from `$transaction->toEnvelopeXdrBase64()`).

---

## Updating Identity Information

Replace all existing identities on a registered account. This is a **full replacement**, not a merge — any identity not included in the request will be removed.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Recovery\RecoveryService;
use Soneso\StellarSDK\SEP\Recovery\SEP30Request;
use Soneso\StellarSDK\SEP\Recovery\SEP30RequestIdentity;
use Soneso\StellarSDK\SEP\Recovery\SEP30AuthMethod;

$service = new RecoveryService("https://recovery.example.com");

// New set of identities — completely replaces existing ones
$authMethods = [
    new SEP30AuthMethod("email", "newemail@example.com"),
    new SEP30AuthMethod("phone_number", "+14155559999"),
];
$identity = new SEP30RequestIdentity("owner", $authMethods);

$request = new SEP30Request([$identity]);
$response = $service->updateIdentitiesForAccount($accountId, $request, $jwtToken);

echo "Update successful." . PHP_EOL;
foreach ($response->identities as $identity) {
    echo "Role: " . ($identity->role ?? '(unspecified)') . PHP_EOL;
}
```

Method signature:
```
updateIdentitiesForAccount(string $address, SEP30Request $request, string $jwt): SEP30AccountResponse
```

---

## Getting Account Details

Retrieve current registration state: identities, authentication status, and signer keys.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Recovery\RecoveryService;

$service = new RecoveryService("https://recovery.example.com");

$response = $service->accountDetails($accountId, $jwtToken);

echo "Address: " . $response->address . PHP_EOL;

foreach ($response->identities as $identity) {
    // authenticated is ?bool — null means not returned by server (unauthenticated context)
    $auth = ($identity->authenticated === true) ? " (authenticated)" : "";
    echo "  Role: " . ($identity->role ?? '(unspecified)') . $auth . PHP_EOL;
}

foreach ($response->signers as $signer) {
    echo "  Signer: " . $signer->key . PHP_EOL;
}

// Use the most recent signer for key rotation
$latestSigner = $response->signers[0]->key;
```

Method signature:
```
accountDetails(string $address, string $jwt): SEP30AccountResponse
```

---

## Listing Accounts

List all accounts that the authenticated identity has access to. Results are paginated; use the last account's address as the `$after` cursor for the next page.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Recovery\RecoveryService;

$service = new RecoveryService("https://recovery.example.com");

// First page (no cursor)
$response = $service->accounts($jwtToken);

echo "Found " . count($response->accounts) . " accounts" . PHP_EOL;
foreach ($response->accounts as $account) {
    echo "  " . $account->address . PHP_EOL;
    foreach ($account->identities as $identity) {
        $auth = ($identity->authenticated === true) ? " (you)" : "";
        echo "    Role: " . ($identity->role ?? '(unspecified)') . $auth . PHP_EOL;
    }
}

// Next page: pass the last account address as $after
if (count($response->accounts) > 0) {
    $lastAddress = end($response->accounts)->address;
    $nextPage = $service->accounts($jwtToken, after: $lastAddress);
    echo "Next page: " . count($nextPage->accounts) . " accounts" . PHP_EOL;
}
```

Method signature:
```
accounts(string $jwt, ?string $after = null): SEP30AccountsResponse
```

The `$after` parameter is a cursor (account address string). Omit it or pass `null` for the first page.

---

## Deleting a Registration

Remove an account from the recovery server. This is **irrecoverable**. After deletion, also remove the server's signer key from your Stellar account.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Recovery\RecoveryService;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Signer;

$service = new RecoveryService("https://recovery.example.com");

// Get the signer key before deletion so we can remove it from the account
$details = $service->accountDetails($accountId, $jwtToken);
$signerToRemove = $details->signers[0]->key;

// Delete from recovery server
$response = $service->deleteAccount($accountId, $jwtToken);
echo "Deleted from recovery server." . PHP_EOL;

// Remove the signer from the Stellar account (weight 0 removes it)
$sdk = StellarSDK::getTestNetInstance();
$accountKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$account = $sdk->requestAccount($accountId);

$signerXdrKey = Signer::ed25519PublicKey(KeyPair::fromAccountId($signerToRemove));
$transaction = (new TransactionBuilder($account))
    ->addOperation(
        (new SetOptionsOperationBuilder())
            ->setSigner($signerXdrKey, 0)  // weight 0 removes the signer
            ->build()
    )
    ->build();
$transaction->sign($accountKeyPair, Network::testnet());
$sdk->submitTransaction($transaction);
echo "Recovery signer removed from Stellar account." . PHP_EOL;
```

Method signature:
```
deleteAccount(string $address, string $jwt): SEP30AccountResponse
```

Returns the final account state before deletion.

---

## Error Handling

The SDK throws typed exceptions for each HTTP error code. Always catch `GuzzleException` for network-level failures.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Recovery\RecoveryService;
use Soneso\StellarSDK\SEP\Recovery\SEP30Request;
use Soneso\StellarSDK\SEP\Recovery\SEP30RequestIdentity;
use Soneso\StellarSDK\SEP\Recovery\SEP30AuthMethod;
use Soneso\StellarSDK\SEP\Recovery\SEP30BadRequestResponseException;
use Soneso\StellarSDK\SEP\Recovery\SEP30UnauthorizedResponseException;
use Soneso\StellarSDK\SEP\Recovery\SEP30NotFoundResponseException;
use Soneso\StellarSDK\SEP\Recovery\SEP30ConflictResponseException;
use Soneso\StellarSDK\SEP\Recovery\SEP30UnknownResponseException;
use GuzzleHttp\Exception\GuzzleException;

$service = new RecoveryService("https://recovery.example.com");

try {
    $authMethods = [new SEP30AuthMethod("email", "user@example.com")];
    $identity = new SEP30RequestIdentity("owner", $authMethods);
    $request = new SEP30Request([$identity]);
    $response = $service->registerAccount($accountId, $request, $jwtToken);

} catch (SEP30BadRequestResponseException $e) {
    // HTTP 400: invalid request data, malformed JSON, invalid auth method types/values,
    // or (for signTransaction) the transaction contains unauthorized operations
    echo "Bad request (400): " . $e->getMessage() . PHP_EOL;

} catch (SEP30UnauthorizedResponseException $e) {
    // HTTP 401: JWT missing, invalid, expired, or does not prove account ownership
    echo "Unauthorized (401): " . $e->getMessage() . PHP_EOL;

} catch (SEP30NotFoundResponseException $e) {
    // HTTP 404: account not registered, signing address not recognized,
    // or authenticated identity does not have access to this account
    echo "Not found (404): " . $e->getMessage() . PHP_EOL;

} catch (SEP30ConflictResponseException $e) {
    // HTTP 409: account already registered (use updateIdentitiesForAccount() instead),
    // or update conflicts with server state
    echo "Conflict (409): " . $e->getMessage() . PHP_EOL;

} catch (SEP30UnknownResponseException $e) {
    // Other HTTP errors (5xx, etc.) — server issues or unexpected responses
    echo "Unknown error (" . $e->getCode() . "): " . $e->getMessage() . PHP_EOL;

} catch (GuzzleException $e) {
    // Network-level failure: connection refused, timeout, DNS failure, etc.
    echo "Network error: " . $e->getMessage() . PHP_EOL;
}
```

### Exception reference

| Exception | HTTP | Typical cause |
|-----------|------|---------------|
| `SEP30BadRequestResponseException` | 400 | Invalid fields, bad auth method values, unauthorized transaction ops |
| `SEP30UnauthorizedResponseException` | 401 | Missing/expired/invalid JWT |
| `SEP30NotFoundResponseException` | 404 | Account not registered, signing address unknown, identity has no access |
| `SEP30ConflictResponseException` | 409 | Account already registered (duplicate), state conflict |
| `SEP30UnknownResponseException` | Other | 5xx errors, unexpected server responses |

All exception classes extend `Exception`. Use `$e->getMessage()` for the error text and `$e->getCode()` for the HTTP status code.

---

## Request and Response Objects

### SEP30AuthMethod

Single authentication method for an identity.

```php
// Constructor
new SEP30AuthMethod(string $type, string $value)

// Standard types
new SEP30AuthMethod("stellar_address", "GBUCAAMD7DYS7226CWUUOZ5Y2QF4JBJWIYU3UWJAFDGJVCR6EU5NJM5H")
new SEP30AuthMethod("phone_number", "+10000000001")  // E.164 format: +[country][number], no spaces
new SEP30AuthMethod("email", "person@example.com")

// Access via public properties or getters/setters
$method->type;    // string
$method->value;   // string
$method->getType();
$method->getValue();
```

### SEP30RequestIdentity

Identity with a role and one or more authentication methods. The `role` is a client-defined label. The JSON key for auth methods is `auth_methods` — the PHP property is `authMethods`.

```php
// Constructor
new SEP30RequestIdentity(string $role, array $authMethods)

$identity->role;        // string
$identity->authMethods; // array<SEP30AuthMethod>
$identity->getRole();
$identity->getAuthMethods();
```

Common roles: `"owner"` (single user), `"sender"` / `"receiver"` (account sharing), `"device"` (multi-device).

### SEP30Request

Container for one or more identities. Serializes to `{"identities": [...]}`.

```php
new SEP30Request(array $identities)  // array<SEP30RequestIdentity>

$request->identities;    // array<SEP30RequestIdentity>
$request->getIdentities();
```

### SEP30AccountResponse

Returned by `registerAccount()`, `updateIdentitiesForAccount()`, `accountDetails()`, `deleteAccount()`.

```php
$response->address;    // string — the Stellar account address
$response->identities; // array<SEP30ResponseIdentity>
$response->signers;    // array<SEP30ResponseSigner>

// Also accessible via getters
$response->getAddress();
$response->getIdentities();
$response->getSigners();
```

### SEP30ResponseIdentity

```php
$identity->role;          // ?string — may be null if the server does not return a role
$identity->authenticated; // ?bool — true when this identity authenticated the current request,
                          // false when explicitly unauthenticated, null when not returned

$identity->getRole();          // ?string
$identity->getAuthenticated(); // ?bool
```

### SEP30ResponseSigner

```php
$signer->key;    // string — G... public key to add as a signer on the Stellar account
$signer->getKey();
```

### SEP30SignatureResponse

Returned by `signTransaction()`.

```php
$signatureResponse->signature;         // string — base64-encoded signature bytes
$signatureResponse->networkPassphrase; // string — e.g. "Test SDF Network ; September 2015"

$signatureResponse->getSignature();
$signatureResponse->getNetworkPassphrase();
```

### SEP30AccountsResponse

Returned by `accounts()`.

```php
$response->accounts;    // array<SEP30AccountResponse>
$response->getAccounts();
```

---

## Common Pitfalls

**Wrong: passing G... string directly to `Signer::ed25519PublicKey()`**

```php
// WRONG: setSigner() does not accept a raw string
(new SetOptionsOperationBuilder())->setSigner($signerKey, 1)->build();

// CORRECT: wrap the key in Signer::ed25519PublicKey() using a public-only KeyPair
$signerXdrKey = Signer::ed25519PublicKey(KeyPair::fromAccountId($signerKey));
(new SetOptionsOperationBuilder())->setSigner($signerXdrKey, 1)->build();
```

**Wrong: assembling `XdrDecoratedSignature` from the wrong source**

```php
// WRONG: using the account address instead of the signing address for the hint
$hint = KeyPair::fromAccountId($accountId)->getHint();

// CORRECT: use the signing address (the server's signer key, from $accountDetails->signers[0]->key)
$hint = KeyPair::fromAccountId($signingAddress)->getHint();
$signatureBytes = base64_decode($signatureResponse->signature);
$decoratedSig = new XdrDecoratedSignature($hint, $signatureBytes);
$transaction->addSignature($decoratedSig);
```

**Wrong: passing a signed Transaction object instead of base64 XDR to `signTransaction()`**

```php
// WRONG: $transaction is a Transaction object, not what signTransaction() expects
$service->signTransaction($accountId, $signingAddress, $transaction, $jwt);

// CORRECT: serialize to base64 XDR first
$txBase64 = $transaction->toEnvelopeXdrBase64();
$service->signTransaction($accountId, $signingAddress, $txBase64, $jwt);
```

**Wrong: re-registering instead of updating**

```php
// WRONG: calling registerAccount() on an already-registered account throws SEP30ConflictResponseException
$service->registerAccount($accountId, $request, $jwt);

// CORRECT: use updateIdentitiesForAccount() for changes
$service->updateIdentitiesForAccount($accountId, $request, $jwt);
```

**Wrong: phone number format**

```php
// WRONG: spaces, missing +, or missing country code
new SEP30AuthMethod("phone_number", "415 555 1234");        // missing + and country code
new SEP30AuthMethod("phone_number", "+1 415 555 1234");     // has spaces

// CORRECT: E.164 format — leading +, country code, digits only, no spaces
new SEP30AuthMethod("phone_number", "+14155551234");
```

**Wrong: forgetting to remove server signer after `deleteAccount()`**

```php
// WRONG: deleting from the recovery server but leaving the signer on-chain
// The signer still exists on the Stellar account and could be misused if the server
// is later compromised or the signing key is leaked.
$service->deleteAccount($accountId, $jwt);
// (no follow-up on-chain operation)

// CORRECT: also remove the signer from the Stellar account using weight=0
$signerXdrKey = Signer::ed25519PublicKey(KeyPair::fromAccountId($signerKeyToRemove));
$transaction = (new TransactionBuilder($account))
    ->addOperation((new SetOptionsOperationBuilder())->setSigner($signerXdrKey, 0)->build())
    ->build();
```

**Note: `updateIdentitiesForAccount()` fully replaces identities**

The PUT operation is not additive. If you have two identities and call `updateIdentitiesForAccount()` with only one, the second identity is deleted. Always include all identities you want to keep.

---

## Related SEPs

- [SEP-10](sep-10.md) — Web Authentication (required to obtain the JWT for `registerAccount()` and `updateIdentitiesForAccount()`; also used as the `stellar_address` auth method type)
