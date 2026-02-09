# SEP-02: Federation protocol

Federation allows users to send payments using human-readable addresses like `bob*example.com` instead of raw account IDs like `GCEZWKCA5VLDNRLN3RPRJMRZOX3Z6G5CHCGSNFHEYVXM3XOJMDS674JZ`. It also enables organizations to map bank accounts or other external identifiers to Stellar accounts.

**When to use:** Building a wallet that supports sending payments to Stellar addresses, or implementing a service that resolves external identifiers (bank accounts, phone numbers) to Stellar accounts.

See the [SEP-02 specification](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0002.md) for protocol details.

## Address format

A Stellar address has two parts: `username*domain.com`
- **Username:** Any printable UTF-8 except `*` and `>` (emails and phone numbers are allowed)  
- **Domain:** Any valid RFC 1035 domain name

Examples: `bob*example.com`, `alice@gmail.com*stellar.org`, `+14155550100*bank.com`

## How address resolution works

When you resolve a Stellar address like `bob*example.com`, this happens:

1. **Parse the address** - Split on `*` to get username (`bob`) and domain (`example.com`)
2. **Fetch stellar.toml** - Download `https://example.com/.well-known/stellar.toml`
3. **Find federation server** - Extract the `FEDERATION_SERVER` URL from the TOML
4. **Query federation server** - Make GET request: `FEDERATION_SERVER/federation?q=bob*example.com&type=name`
5. **Get account details** - Server returns account ID and optional memo

The SDK handles this entire flow automatically with `Federation::resolveStellarAddress()`.

**Note:** Federation servers may rate-limit requests. If you're making many lookups, consider caching responses appropriately (but remember that some services use ephemeral account IDs, so cache duration should be short).

## Quick example

Resolve a Stellar address to get the destination account ID for a payment. This single method call handles the entire federation lookup process, including fetching the stellar.toml and querying the federation server.

```php
<?php

use Soneso\StellarSDK\SEP\Federation\Federation;

// Resolve a Stellar address to an account ID
$response = Federation::resolveStellarAddress('bob*soneso.com');

echo "Account: " . $response->getAccountId() . PHP_EOL;
echo "Memo: " . $response->getMemo() . PHP_EOL;
```

## Resolving Stellar addresses

Convert a Stellar address to an account ID and optional memo. The memo is important because some services (like exchanges) use a single Stellar account for all users and require a memo to identify the recipient.

```php
<?php

use Soneso\StellarSDK\SEP\Federation\Federation;

$response = Federation::resolveStellarAddress('bob*soneso.com');

// The destination account for payments
$accountId = $response->getAccountId();
echo "Account ID: " . $accountId . PHP_EOL;
// GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI

// Include memo if provided (required for some destinations)
$memo = $response->getMemo();
$memoType = $response->getMemoType();

if ($memo !== null) {
    echo "Memo ({$memoType}): " . $memo . PHP_EOL;
}

// Original address for confirmation
$address = $response->getStellarAddress();
echo "Address: " . $address . PHP_EOL;
// bob*soneso.com
```

**Important:** Don't cache federation responses. Some services use random account IDs for privacy, which may change over time.

## Reverse lookup (account ID to address)

Find the Stellar address associated with an account ID. Unlike forward lookups, reverse lookups require you to know which federation server to query since the account ID doesn't contain domain information.

```php
<?php

use Soneso\StellarSDK\SEP\Federation\Federation;

$accountId = 'GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI';
$federationServer = 'https://stellarid.io/federation';

$response = Federation::resolveStellarAccountId($accountId, $federationServer);

echo "Address: " . $response->getStellarAddress() . PHP_EOL;
// bob*soneso.com
```

## Transaction lookup

Query a federation server to get information about who sent a transaction. This is useful for identifying the sender of an incoming payment when the federation server supports transaction lookups.

```php
<?php

use Soneso\StellarSDK\SEP\Federation\Federation;

$txId = 'c1b368c00e9852351361e07cc58c54277e7a6366580044ab152b8db9cd8ec52a';
$federationServer = 'https://stellarid.io/federation';

// Returns federation record of the sender if known
$response = Federation::resolveStellarTransactionId($txId, $federationServer);

if ($response->getStellarAddress() !== null) {
    echo "Sender: " . $response->getStellarAddress() . PHP_EOL;
}
```

## Forward federation

Forward federation maps external identifiers (bank accounts, routing numbers, etc.) to Stellar accounts. Use this to pay someone who doesn't have a Stellar address but has another type of account that an anchor supports.

```php
<?php

use Soneso\StellarSDK\SEP\Federation\Federation;

// Pay to a bank account via an anchor
$params = [
    'forward_type' => 'bank_account',
    'swift' => 'BOPBPHMM',
    'acct' => '2382376'
];

$federationServer = 'https://stellarid.io/federation';
$response = Federation::resolveForward($params, $federationServer);

echo "Deposit to: " . $response->getAccountId() . PHP_EOL;

// Use the memo to identify the recipient
if ($response->getMemo() !== null) {
    echo "Memo ({$response->getMemoType()}): " . $response->getMemo() . PHP_EOL;
}
```

## Building a payment with federation

This complete example shows how to send a payment using a Stellar address. It resolves the recipient's address, builds a transaction with the appropriate memo, and submits it to the network.

```php
<?php

use Exception;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\SEP\Federation\Federation;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk = StellarSDK::getTestNetInstance();

// Sender's keypair
$senderKeyPair = KeyPair::fromSeed('SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CPMLIHJPFV5RXN5M6CSS');
$senderAccountId = $senderKeyPair->getAccountId();

// Resolve recipient's Stellar address
$recipient = 'alice*testanchor.stellar.org';
$response = Federation::resolveStellarAddress($recipient);

$destinationId = $response->getAccountId();

// Load sender account
$senderAccount = $sdk->requestAccount($senderAccountId);

// Build payment operation
$paymentOp = (new PaymentOperationBuilder($destinationId, Asset::native(), '10'))
    ->build();

// Build transaction
$txBuilder = new TransactionBuilder($senderAccount);
$txBuilder->addOperation($paymentOp);

// Include memo if federation response requires it
if ($response->getMemo() !== null) {
    $memoType = $response->getMemoType();
    if ($memoType === 'text') {
        $txBuilder->addMemo(Memo::text($response->getMemo()));
    } elseif ($memoType === 'id') {
        $txBuilder->addMemo(Memo::id((int)$response->getMemo()));
    } elseif ($memoType === 'hash') {
        // Hash memo values are base64-encoded in federation responses
        $txBuilder->addMemo(Memo::hash(base64_decode($response->getMemo())));
    }
}

$transaction = $txBuilder->build();
$transaction->sign($senderKeyPair, Network::testnet());

try {
    $sdk->submitTransaction($transaction);
    echo "Payment sent to {$recipient}" . PHP_EOL;
} catch (Exception $e) {
    echo "Payment failed: " . $e->getMessage() . PHP_EOL;
}
```

## Error handling

Federation lookups can fail for various reasons. This example demonstrates how to handle the most common error scenarios: invalid address format, missing federation server configuration, and unknown users.

```php
<?php

use Exception;
use InvalidArgumentException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\SEP\Federation\Federation;

// Invalid address format (missing *)
// Throws InvalidArgumentException immediately without making network requests
try {
    Federation::resolveStellarAddress('invalid-no-asterisk');
} catch (InvalidArgumentException $e) {
    echo "Invalid format: " . $e->getMessage() . PHP_EOL;
    // Output: Invalid format: Invalid federation address: invalid-no-asterisk
}

// Domain without federation server configured in stellar.toml
// Throws Exception when stellar.toml doesn't contain FEDERATION_SERVER
try {
    Federation::resolveStellarAddress('user*domain-without-federation.com');
} catch (Exception $e) {
    echo "No federation server: " . $e->getMessage() . PHP_EOL;
}

// User not found (404) or federation server error (500, etc.)
try {
    $response = Federation::resolveStellarAddress('nonexistent*soneso.com');
    echo "Account: " . $response->getAccountId() . PHP_EOL;
} catch (HorizonRequestException $e) {
    // 404 = user not found, 500 = server error, etc.
    echo "Federation error: " . $e->getMessage() . PHP_EOL;
}
```

### Exception summary

| Exception | When Thrown |
|-----------|-------------|
| `InvalidArgumentException` | Address doesn't contain `*` character |
| `Exception` | Domain's stellar.toml doesn't have `FEDERATION_SERVER` |
| `HorizonRequestException` | Federation server returns HTTP error (404, 500, etc.) |

## Custom HTTP client

All Federation methods accept an optional Guzzle HTTP client parameter. This is useful for configuring timeouts, proxies, custom headers, or mocking responses in tests.

```php
<?php

use GuzzleHttp\Client;
use Soneso\StellarSDK\SEP\Federation\Federation;

// Create a custom HTTP client with specific settings
$httpClient = new Client([
    'timeout' => 5.0,           // 5 second timeout
    'connect_timeout' => 2.0,   // 2 second connection timeout
    'headers' => [
        'User-Agent' => 'MyWallet/1.0'
    ]
]);

// Pass the custom client to any Federation method
$response = Federation::resolveStellarAddress('bob*soneso.com', $httpClient);

echo "Account: " . $response->getAccountId() . PHP_EOL;
```

## Finding the federation server

Each domain publishes its federation server URL in stellar.toml. The `resolveStellarAddress()` method does this lookup automatically, but you can also fetch it directly when needed for reverse lookups or manual queries.

```php
<?php

use Soneso\StellarSDK\SEP\Toml\StellarToml;

// Get federation server URL from stellar.toml
$stellarToml = StellarToml::fromDomain('soneso.com');
$federationServer = $stellarToml->getGeneralInformation()->federationServer;

echo "Federation Server: " . $federationServer . PHP_EOL;
// https://stellarid.io/federation
```

**Note:** `Federation::resolveStellarAddress()` does this lookup automatically. You only need this for reverse lookups or when using `FederationRequestBuilder` directly.

## Using FederationRequestBuilder directly

Use `FederationRequestBuilder` when you need fine-grained control over federation queries: custom HTTP clients, URL inspection for debugging, or non-standard federation parameters.

```php
<?php

use GuzzleHttp\Client;
use Soneso\StellarSDK\SEP\Federation\FederationRequestBuilder;

$httpClient = new Client();
$federationServer = 'https://stellarid.io/federation';

// Resolve by name (type=name)
$requestBuilder = (new FederationRequestBuilder($httpClient, $federationServer))
    ->forType('name')
    ->forStringToLookUp('bob*soneso.com');

// Inspect the URL before executing (useful for debugging)
echo "Request URL: " . $requestBuilder->buildUrl() . PHP_EOL;
// https://stellarid.io/federation?type=name&q=bob*soneso.com

$response = $requestBuilder->execute();
echo "Account: " . $response->getAccountId() . PHP_EOL;
```

### Query types

The `forType()` method accepts these values:

| Type | Description | Use With |
|------|-------------|----------|
| `name` | Stellar address lookup | `forStringToLookUp('user*domain.com')` |
| `id` | Reverse lookup by account ID | `forStringToLookUp('G...')` |
| `txid` | Transaction sender lookup | `forStringToLookUp('txhash...')` |
| `forward` | Forward to external identifier | `forQueryParameters([...])` |

### More examples

Reverse lookup by account ID to find the associated Stellar address:

```php
<?php

use GuzzleHttp\Client;
use Soneso\StellarSDK\SEP\Federation\FederationRequestBuilder;

$httpClient = new Client();
$federationServer = 'https://stellarid.io/federation';

$response = (new FederationRequestBuilder($httpClient, $federationServer))
    ->forType('id')
    ->forStringToLookUp('GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI')
    ->execute();

echo "Address: " . $response->getStellarAddress() . PHP_EOL;
```

Forward lookup with custom parameters for routing payments to external financial systems:

```php
<?php

use GuzzleHttp\Client;
use Soneso\StellarSDK\SEP\Federation\FederationRequestBuilder;

$httpClient = new Client();
$federationServer = 'https://stellarid.io/federation';

$response = (new FederationRequestBuilder($httpClient, $federationServer))
    ->forType('forward')
    ->forQueryParameters([
        'forward_type' => 'bank_account',
        'swift' => 'BOPBPHMM',
        'acct' => '2382376'
    ])
    ->execute();

echo "Deposit to: " . $response->getAccountId() . PHP_EOL;
```

## FederationResponse properties

The `FederationResponse` object contains all the information returned by the federation server:

| Method | Returns | Description |
|--------|---------|-------------|
| `getAccountId()` | `?string` | Stellar account ID (G-address) for payments |
| `getStellarAddress()` | `?string` | Stellar address in `user*domain.com` format |
| `getMemo()` | `?string` | Memo value to include with payment |
| `getMemoType()` | `?string` | Memo type: `text`, `id`, or `hash` |

**Note on hash memos:** When `getMemoType()` returns `hash`, the memo value from `getMemo()` is base64-encoded. Decode it before creating a `Memo::hash()`. This is necessary because `Memo::hash()` expects raw bytes (exactly 32 bytes), not a base64 string. The federation server encodes the binary hash as base64 for safe JSON transport.

## Related SEPs

- [SEP-01 stellar.toml](sep-01.md) - Where the `FEDERATION_SERVER` URL is published
- [SEP-10 Authentication](sep-10.md) - Some federation servers may require authentication

---

[Back to SEP Overview](README.md)
