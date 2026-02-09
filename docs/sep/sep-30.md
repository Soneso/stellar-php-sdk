# SEP-30: Account Recovery

SEP-30 defines a protocol for recovering access to Stellar accounts when the owner loses their private key. Recovery servers act as additional signers on an account, allowing the user to regain control by proving their identity through alternate methods like email, phone, or another Stellar address.

Use SEP-30 when:
- Building a wallet with account recovery features
- You want to protect users from permanent key loss
- Implementing shared account access between multiple parties
- Setting up multi-device account access with recovery options

See the [SEP-30 specification](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0030.md) for protocol details.

## How Recovery Works

1. **Registration**: Register your account with a recovery server, providing identity information with authentication methods
2. **Add Signer**: Add the server's signer key to your Stellar account with appropriate weight
3. **Recovery**: If you lose your key, authenticate with the recovery server via alternate methods (email, phone, etc.)
4. **Sign Transaction**: The server signs a transaction that adds your new key to the account
5. **Submit**: Submit the signed transaction to the Stellar network to regain control

## Quick Example

This example shows the basic flow: register an account with a recovery server, then add the returned signer key to your Stellar account.

```php
<?php

use Soneso\StellarSDK\SEP\Recovery\RecoveryService;
use Soneso\StellarSDK\SEP\Recovery\SEP30Request;
use Soneso\StellarSDK\SEP\Recovery\SEP30RequestIdentity;
use Soneso\StellarSDK\SEP\Recovery\SEP30AuthMethod;

// Connect to recovery server
$service = new RecoveryService("https://recovery.example.com");

// Set up identity with authentication methods
$authMethods = [
    new SEP30AuthMethod("email", "user@example.com"),
    new SEP30AuthMethod("phone_number", "+14155551234"),
];
$identity = new SEP30RequestIdentity("owner", $authMethods);

// Register account with recovery server (requires SEP-10 JWT)
$request = new SEP30Request([$identity]);
$response = $service->registerAccount($accountId, $request, $jwtToken);

// Get the signer key to add to your account
$signerKey = $response->signers[0]->key;
echo "Add this signer to your account: $signerKey\n";
```

## Creating the Recovery Service

The `RecoveryService` class is the main entry point for all SEP-30 operations. Create an instance by providing the recovery server's base URL.

```php
<?php

use Soneso\StellarSDK\SEP\Recovery\RecoveryService;
use GuzzleHttp\Client;

// Basic usage - create service with recovery server URL
$service = new RecoveryService("https://recovery.example.com");

// Advanced usage - provide a custom Guzzle HTTP client for timeouts, proxies, etc.
$httpClient = new Client([
    'timeout' => 30,
    'connect_timeout' => 10,
]);
$service = new RecoveryService("https://recovery.example.com", $httpClient);
```

## Registering an Account

Before your account can be recovered, you must register it with one or more recovery servers. Registration requires a SEP-10 JWT token proving you control the account.

```php
<?php

use Soneso\StellarSDK\SEP\Recovery\RecoveryService;
use Soneso\StellarSDK\SEP\Recovery\SEP30Request;
use Soneso\StellarSDK\SEP\Recovery\SEP30RequestIdentity;
use Soneso\StellarSDK\SEP\Recovery\SEP30AuthMethod;

$service = new RecoveryService("https://recovery.example.com");

// Define how the user can prove their identity during recovery.
// Multiple authentication methods provide fallback options.
$authMethods = [
    new SEP30AuthMethod("stellar_address", "GXXXX..."), // SEP-10 auth (highest security)
    new SEP30AuthMethod("email", "user@example.com"),
    new SEP30AuthMethod("phone_number", "+14155551234"), // E.164 format required
];

// Create identity with role "owner" - roles are client-defined labels
// that help users understand their relationship to the account.
$identity = new SEP30RequestIdentity("owner", $authMethods);

// Register with the recovery server
$request = new SEP30Request([$identity]);
$response = $service->registerAccount($accountId, $request, $jwtToken);

// The response includes signer keys to add to your Stellar account.
// Signers are ordered from most recently added to least recently added.
echo "Account address: " . $response->address . "\n";
foreach ($response->signers as $signer) {
    echo "Signer key: " . $signer->key . "\n";
}
foreach ($response->identities as $identity) {
    echo "Identity role: " . $identity->role . "\n";
}
```

### Adding the Recovery Signer to Your Account

After registration, you must add the recovery server's signer key to your Stellar account. Configure account thresholds so the recovery server cannot unilaterally control your account.

```php
<?php

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Signer;

$sdk = StellarSDK::getTestNetInstance();

$accountKeyPair = KeyPair::fromSeed("SXXXXXX...");
$accountId = $accountKeyPair->getAccountId();
$account = $sdk->requestAccount($accountId);

// Add recovery server as a signer with weight 1.
// The signer key comes from the registration response.
$signerKey = $response->signers[0]->key;
$signerXdrKey = Signer::ed25519PublicKey(KeyPair::fromAccountId($signerKey));

$transaction = (new TransactionBuilder($account))
    // Add the recovery signer
    ->addOperation(
        (new SetOptionsOperationBuilder())
            ->setSigner($signerXdrKey, 1)
            ->build()
    )
    // Set thresholds so recovery requires multiple signers.
    // With threshold=2, both your key (weight 10) and recovery server (weight 1)
    // together can meet threshold, but recovery server alone cannot.
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

echo "Recovery signer added to account\n";
```

## Multi-Server Recovery

For better security, register with multiple recovery servers so no single server has full control. Each server provides a signer key with weight 1, and the account threshold is set to require cooperation from multiple servers.

```php
<?php

use Soneso\StellarSDK\SEP\Recovery\RecoveryService;
use Soneso\StellarSDK\SEP\Recovery\SEP30Request;
use Soneso\StellarSDK\SEP\Recovery\SEP30RequestIdentity;
use Soneso\StellarSDK\SEP\Recovery\SEP30AuthMethod;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Signer;

// Create identity (reused for both servers)
$authMethods = [new SEP30AuthMethod("email", "user@example.com")];
$identity = new SEP30RequestIdentity("owner", $authMethods);
$request = new SEP30Request([$identity]);

// Register with first recovery server
$service1 = new RecoveryService("https://recovery1.example.com");
$response1 = $service1->registerAccount($accountId, $request, $jwtToken1);
$signerKey1 = $response1->signers[0]->key;
$signerXdrKey1 = Signer::ed25519PublicKey(KeyPair::fromAccountId($signerKey1));

// Register with second recovery server
$service2 = new RecoveryService("https://recovery2.example.com");
$response2 = $service2->registerAccount($accountId, $request, $jwtToken2);
$signerKey2 = $response2->signers[0]->key;
$signerXdrKey2 = Signer::ed25519PublicKey(KeyPair::fromAccountId($signerKey2));

// Add both signers to your account with combined weight
$sdk = StellarSDK::getTestNetInstance();
$accountKeyPair = KeyPair::fromSeed("SXXXXXX...");
$account = $sdk->requestAccount($accountId);

$transaction = (new TransactionBuilder($account))
    ->addOperation(
        (new SetOptionsOperationBuilder())
            ->setSigner($signerXdrKey1, 1)
            ->build()
    )
    ->addOperation(
        (new SetOptionsOperationBuilder())
            ->setSigner($signerXdrKey2, 1)
            ->build()
    )
    // Set threshold to 2, requiring both recovery servers to sign
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

echo "Multi-server recovery configured\n";
```

## Recovering an Account

When you lose your private key, authenticate with the recovery server using one of your registered authentication methods (email, phone, etc.) to get a JWT. Then request the server to sign a transaction that adds your new key.

```php
<?php

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Signer;
use Soneso\StellarSDK\SEP\Recovery\RecoveryService;
use Soneso\StellarSDK\Xdr\XdrDecoratedSignature;

$service = new RecoveryService("https://recovery.example.com");

// Get account details to find the signing address.
// The JWT here proves your identity via alternate auth (email/phone).
$accountDetails = $service->accountDetails($accountId, $recoveryJwt);
$signingAddress = $accountDetails->signers[0]->key;

// Generate a new keypair for the recovered account
$newKeyPair = KeyPair::random();
$newSignerXdrKey = Signer::ed25519PublicKey($newKeyPair);

// Build a transaction to add the new key with high weight
$sdk = StellarSDK::getTestNetInstance();
$account = $sdk->requestAccount($accountId);

$operation = (new SetOptionsOperationBuilder())
    ->setSigner($newSignerXdrKey, 10) // High weight to regain control
    ->build();

$transaction = (new TransactionBuilder($account))
    ->addOperation($operation)
    ->build();

// Get the recovery server to sign the transaction
$txBase64 = $transaction->toEnvelopeXdrBase64();
$signatureResponse = $service->signTransaction(
    $accountId,
    $signingAddress,
    $txBase64,
    $recoveryJwt // JWT proving identity via alternate auth
);

// Add the server's signature to the transaction.
// Create the hint from the signing address (last 4 bytes of public key).
$signerKeyPair = KeyPair::fromAccountId($signingAddress);
$hint = $signerKeyPair->getHint();
$signatureBytes = base64_decode($signatureResponse->signature);
$decoratedSignature = new XdrDecoratedSignature($hint, $signatureBytes);
$transaction->addSignature($decoratedSignature);

// For multi-server recovery, repeat the signing process with each server
// and add all signatures before submitting.

// Submit the signed transaction
$sdk->submitTransaction($transaction);

echo "Account recovered! New key: " . $newKeyPair->getSecretSeed() . "\n";
echo "Store this seed securely!\n";
```

## Updating Identity Information

Update authentication methods for a registered account. This completely replaces all existing identities - identities not included in the request will be removed.

```php
<?php

use Soneso\StellarSDK\SEP\Recovery\RecoveryService;
use Soneso\StellarSDK\SEP\Recovery\SEP30Request;
use Soneso\StellarSDK\SEP\Recovery\SEP30RequestIdentity;
use Soneso\StellarSDK\SEP\Recovery\SEP30AuthMethod;

$service = new RecoveryService("https://recovery.example.com");

// New auth methods completely replace existing ones.
// Use this to add new methods, remove compromised ones, or update contact info.
$newAuthMethods = [
    new SEP30AuthMethod("email", "newemail@example.com"),
    new SEP30AuthMethod("phone_number", "+14155559999"),
    new SEP30AuthMethod("stellar_address", "GNEWADDRESS..."),
];
$identity = new SEP30RequestIdentity("owner", $newAuthMethods);

$request = new SEP30Request([$identity]);
$response = $service->updateIdentitiesForAccount($accountId, $request, $jwtToken);

echo "Identities updated successfully\n";
foreach ($response->identities as $identity) {
    echo "Role: " . $identity->role . "\n";
}
```

## Shared Account Access

SEP-30 supports multiple parties sharing access to an account. Each party has their own identity with a unique role, allowing both to recover the account.

```php
<?php

use Soneso\StellarSDK\SEP\Recovery\RecoveryService;
use Soneso\StellarSDK\SEP\Recovery\SEP30Request;
use Soneso\StellarSDK\SEP\Recovery\SEP30RequestIdentity;
use Soneso\StellarSDK\SEP\Recovery\SEP30AuthMethod;

$service = new RecoveryService("https://recovery.example.com");

// Primary owner - can recover the account
$ownerAuth = [
    new SEP30AuthMethod("email", "owner@example.com"),
    new SEP30AuthMethod("phone_number", "+14155551111"),
];
$ownerIdentity = new SEP30RequestIdentity("sender", $ownerAuth);

// Shared user - can also recover the account
$receiverAuth = [
    new SEP30AuthMethod("email", "partner@example.com"),
    new SEP30AuthMethod("phone_number", "+14155552222"),
];
$receiverIdentity = new SEP30RequestIdentity("receiver", $receiverAuth);

// Register both identities - either party can initiate recovery
$request = new SEP30Request([$ownerIdentity, $receiverIdentity]);
$response = $service->registerAccount($accountId, $request, $jwtToken);

echo "Shared account registered\n";
echo "Both 'sender' and 'receiver' can now recover this account\n";
```

## Getting Account Details

Check registration status, view current signers, and see which identity is currently authenticated. Use this to monitor for key rotation and verify your recovery setup.

```php
<?php

use Soneso\StellarSDK\SEP\Recovery\RecoveryService;

$service = new RecoveryService("https://recovery.example.com");

$response = $service->accountDetails($accountId, $jwtToken);

echo "Account: " . $response->address . "\n";

echo "\nIdentities:\n";
foreach ($response->identities as $identity) {
    $authStatus = $identity->authenticated ? " (authenticated)" : "";
    echo "  Role: " . $identity->role . $authStatus . "\n";
}

echo "\nSigners (ordered most recent first):\n";
foreach ($response->signers as $signer) {
    echo "  Key: " . $signer->key . "\n";
}

// Best practice: periodically check for new signers and update your account
// to use the most recent one (key rotation)
$latestSigner = $response->signers[0]->key;
echo "\nLatest signer for key rotation: " . $latestSigner . "\n";
```

## Listing Registered Accounts

List all accounts accessible by the authenticated identity. This is useful for identity providers or users managing multiple accounts. Results are paginated using cursor-based pagination.

```php
<?php

use Soneso\StellarSDK\SEP\Recovery\RecoveryService;

$service = new RecoveryService("https://recovery.example.com");

// Get first page of accounts
$response = $service->accounts($jwtToken);

echo "Found " . count($response->accounts) . " accounts:\n";
foreach ($response->accounts as $account) {
    echo "  Address: " . $account->address . "\n";
    foreach ($account->identities as $identity) {
        $auth = $identity->authenticated ? " (you)" : "";
        echo "    Role: " . $identity->role . $auth . "\n";
    }
}

// Pagination: use the last account address as cursor for next page
if (count($response->accounts) > 0) {
    $lastAddress = end($response->accounts)->address;
    $nextPage = $service->accounts($jwtToken, after: $lastAddress);
    
    if (count($nextPage->accounts) > 0) {
        echo "\nNext page has " . count($nextPage->accounts) . " more accounts\n";
    }
}
```

## Deleting Registration

Remove your account from the recovery server. This operation is **irrecoverable** - once deleted, you cannot recover the account through this server. Remember to also remove the server's signer from your Stellar account.

```php
<?php

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
$signerToRemoveXdrKey = Signer::ed25519PublicKey(KeyPair::fromAccountId($signerToRemove));

// Delete registration from recovery server
$response = $service->deleteAccount($accountId, $jwtToken);
echo "Account deleted from recovery server\n";

// Important: also remove the server's signer from your Stellar account
$sdk = StellarSDK::getTestNetInstance();
$accountKeyPair = KeyPair::fromSeed("SXXXXXX...");
$account = $sdk->requestAccount($accountId);

$transaction = (new TransactionBuilder($account))
    ->addOperation(
        (new SetOptionsOperationBuilder())
            ->setSigner($signerToRemoveXdrKey, 0) // Weight 0 removes the signer
            ->build()
    )
    ->build();

$transaction->sign($accountKeyPair, Network::testnet());
$sdk->submitTransaction($transaction);

echo "Recovery signer removed from Stellar account\n";
```

## Error Handling

The SDK throws specific exceptions for different error conditions. Handle these appropriately in your application.

```php
<?php

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
    echo "Registration successful!\n";
    
} catch (SEP30BadRequestResponseException $e) {
    // HTTP 400 - Invalid request data, malformed JSON, invalid auth methods,
    // or transaction contains unauthorized operations (for signing)
    echo "Bad request: " . $e->getMessage() . "\n";
    echo "HTTP status: " . $e->getCode() . "\n";
    
} catch (SEP30UnauthorizedResponseException $e) {
    // HTTP 401 - JWT token missing, invalid, expired, or doesn't prove
    // ownership of the account
    echo "Unauthorized: " . $e->getMessage() . "\n";
    echo "Please obtain a valid SEP-10 JWT token\n";
    
} catch (SEP30NotFoundResponseException $e) {
    // HTTP 404 - Account not registered, signing address not recognized,
    // or authenticated identity doesn't have access
    echo "Not found: " . $e->getMessage() . "\n";
    
} catch (SEP30ConflictResponseException $e) {
    // HTTP 409 - Account already registered (for registration),
    // or update conflicts with server state
    echo "Conflict: " . $e->getMessage() . "\n";
    echo "Account may already be registered. Try updateIdentitiesForAccount() instead.\n";
    
} catch (SEP30UnknownResponseException $e) {
    // Other HTTP errors (5xx, etc.) - server issues, unexpected responses
    echo "Unexpected error: " . $e->getMessage() . "\n";
    echo "HTTP status: " . $e->getCode() . "\n";
    
} catch (GuzzleException $e) {
    // Network or HTTP client errors - connection refused, timeout, etc.
    echo "Network error: " . $e->getMessage() . "\n";
}
```

## Authentication Methods

SEP-30 defines three standard authentication types. Recovery servers may also support custom types.

| Type | Format | Example | Security Notes |
|------|--------|---------|----------------|
| `stellar_address` | G... public key | `GDUAB...` | Highest security - requires SEP-10 cryptographic proof |
| `phone_number` | E.164 format with + | `+14155551234` | Vulnerable to SIM swapping attacks |
| `email` | Standard email | `user@example.com` | Security depends on email provider |

### Phone Number Format

Phone numbers must follow ITU-T E.164 international format:
- Include country code with leading `+`
- No spaces or formatting
- Example: `+14155551234` (not `+1 415 555 1234` or `(415) 555-1234`)

```php
<?php

use Soneso\StellarSDK\SEP\Recovery\SEP30AuthMethod;

// Correct E.164 format
$phoneAuth = new SEP30AuthMethod("phone_number", "+14155551234");

// These formats are INCORRECT and may fail:
// "+1 415 555 1234"  (has spaces)
// "(415) 555-1234"   (missing country code, has formatting)
// "4155551234"       (missing + and country code)
```

## Identity Roles

Roles are client-defined labels stored by the server and returned in responses. They help users understand their relationship to an account but are not validated or enforced by the server.

Common role patterns:

| Role | Use Case |
|------|----------|
| `owner` | Single-user recovery - the account owner |
| `sender` | Account sharing - the person sharing the account |
| `receiver` | Account sharing - the person receiving shared access |
| `device` | Multi-device access - represents a specific device |
| `backup` | Backup identity with alternate authentication |

## Security Considerations

### Multi-Server Setup
- Use 2+ recovery servers with account threshold set to require multiple signatures
- No single server should have enough weight to unilaterally control the account
- Example: Each server weight=1, threshold=2

### Signer Weights and Thresholds
- Give each recovery server weight=1
- Set account thresholds to require multiple signers (e.g., threshold=2 for two servers)
- Your own key should have higher weight (e.g., weight=10) for normal operations

### Authentication Security
- `stellar_address` provides cryptographic proof via SEP-10 (strongest)
- Phone numbers are vulnerable to SIM swapping - evaluate risk for high-value accounts
- Email security depends on your email provider

### Key Rotation
- Recovery servers may rotate their signing keys over time
- Periodically check `accountDetails()` for new signers
- Update your account to use the most recent signer (first in the array)
- Old signers remain valid until explicitly removed

### General Best Practices
- Always use HTTPS for recovery server communication
- Store JWT tokens securely and never log them
- After deleting registration, remove the signer from your Stellar account
- Test your recovery setup before you actually need it

## Related SEPs

- [SEP-10](sep-10.md) - Web Authentication (required for `stellar_address` auth method and registration)

## Further Reading

- [SDK test cases](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/Unit/SEP/Recovery/RecoveryTest.php) - Complete examples of SEP-30 operations

## SDK Classes Reference

| Class | Description |
|-------|-------------|
| `RecoveryService` | Main service class for all SEP-30 operations |
| `SEP30Request` | Request containing identities for registration/update |
| `SEP30RequestIdentity` | Identity with role and authentication methods |
| `SEP30AuthMethod` | Single authentication method (type and value) |
| `SEP30AccountResponse` | Response with account address, identities, and signers |
| `SEP30AccountsResponse` | Response containing list of accounts (pagination) |
| `SEP30SignatureResponse` | Response with signature and network passphrase |
| `SEP30ResponseIdentity` | Identity in response with role and authenticated flag |
| `SEP30ResponseSigner` | Signer key in response |
| `SEP30BadRequestResponseException` | HTTP 400 error |
| `SEP30UnauthorizedResponseException` | HTTP 401 error |
| `SEP30NotFoundResponseException` | HTTP 404 error |
| `SEP30ConflictResponseException` | HTTP 409 error |
| `SEP30UnknownResponseException` | Other HTTP errors |

---

[Back to SEP Overview](README.md)
