# SEP-02: Federation Protocol

**Purpose:** Resolve `user*domain.com` federation addresses to Stellar account IDs and optional memo data for payments.
**Prerequisites:** None (for forward lookups); federation server URL (for reverse/txid/forward lookups)
**SDK Namespace:** `Soneso\StellarSDK\SEP\Federation`

## Table of Contents

- [How It Works](#how-it-works)
- [Quick Example](#quick-example)
- [Federation::resolveStellarAddress](#federationresolvestellaraddress)
- [Federation::resolveStellarAccountId](#federationresolvestellaraccountid)
- [Federation::resolveStellarTransactionId](#federationresolvestellartransactionid)
- [Federation::resolveForward](#federationresolveforward)
- [FederationResponse Properties](#federationresponse-properties)
- [Building a Payment with Federation](#building-a-payment-with-federation)
- [FederationRequestBuilder (Low-Level)](#federationrequestbuilder-low-level)
- [Mock Handler for Tests](#mock-handler-for-tests)
- [Error Handling](#error-handling)
- [Common Pitfalls](#common-pitfalls)

## How It Works

A Stellar address has two parts: `username*domain.com`

When you call `Federation::resolveStellarAddress('bob*example.com')`, the SDK:
1. Splits on `*` to get username (`bob`) and domain (`example.com`)
2. Fetches `https://example.com/.well-known/stellar.toml`
3. Reads the `FEDERATION_SERVER` URL from the TOML
4. Makes `GET FEDERATION_SERVER?q=bob*example.com&type=name`
5. Returns a `FederationResponse` with the account ID and optional memo

The SDK handles the entire flow. Only `resolveStellarAddress` does the TOML lookup automatically; the other methods require the federation server URL as a parameter.

## Quick Example

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Federation\Federation;

$response = Federation::resolveStellarAddress('bob*soneso.com');

echo $response->getAccountId() . PHP_EOL;       // GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI
echo $response->getStellarAddress() . PHP_EOL;  // bob*soneso.com
echo $response->getMemoType() . PHP_EOL;        // text
echo $response->getMemo() . PHP_EOL;            // hello memo text
```

## Federation::resolveStellarAddress

```php
public static function resolveStellarAddress(
    string $address,
    ?Client $httpClient = null
): FederationResponse
```

Resolves a Stellar address to an account ID and optional memo. Fetches `stellar.toml` automatically to find the federation server.

```php
<?php declare(strict_types=1);

use Exception;
use Soneso\StellarSDK\SEP\Federation\Federation;

$response = Federation::resolveStellarAddress('bob*soneso.com');

$accountId = $response->getAccountId();    // string|null  destination account for payments
$address   = $response->getStellarAddress(); // string|null  "bob*soneso.com"
$memo      = $response->getMemo();          // string|null  memo value
$memoType  = $response->getMemoType();      // string|null  "text", "id", or "hash"
```

**Important:** Do not cache federation responses. Some services generate random account IDs to protect user privacy; those IDs may change between lookups.

## Federation::resolveStellarAccountId

```php
public static function resolveStellarAccountId(
    string $accountId,
    string $federationServerUrl,
    ?Client $httpClient = null
): FederationResponse
```

Reverse lookup: finds the Stellar address associated with a known account ID. You must supply the federation server URL because the account ID contains no domain information.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Federation\Federation;

$response = Federation::resolveStellarAccountId(
    'GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI',
    'https://stellarid.io/federation'
);

echo $response->getStellarAddress() . PHP_EOL; // bob*soneso.com
```

To find the federation server URL for a domain, use SEP-01:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Toml\StellarToml;

$stellarToml = StellarToml::fromDomain('soneso.com');
$federationServer = $stellarToml->getGeneralInformation()->federationServer;
// https://stellarid.io/federation
```

## Federation::resolveStellarTransactionId

```php
public static function resolveStellarTransactionId(
    string $txId,
    string $federationServerUrl,
    ?Client $httpClient = null
): FederationResponse
```

Looks up who sent a transaction. Useful for identifying the sender of an incoming payment when the federation server supports `type=txid` queries.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Federation\Federation;

$response = Federation::resolveStellarTransactionId(
    'ae05181b239bd4a64ba2fb8086901479a0bde86f8e912150e74241fe4f5f0948',
    'https://stellarid.io/federation'
);

if ($response->getStellarAddress() !== null) {
    echo 'Sender: ' . $response->getStellarAddress() . PHP_EOL;
}
```

## Federation::resolveForward

```php
public static function resolveForward(
    array $queryParameters,
    string $federationServerUrl,
    ?Client $httpClient = null
): FederationResponse
```

Forward federation maps external identifiers (bank accounts, phone numbers, routing numbers) to Stellar accounts. The `$queryParameters` array is anchor-specific; always include `forward_type` to identify the type of external account.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Federation\Federation;

// Bank account routing via SWIFT
$response = Federation::resolveForward(
    queryParameters: [
        'forward_type' => 'bank_account',
        'swift'        => 'BOPBPHMM',
        'acct'         => '2382376',
    ],
    federationServerUrl: 'https://stellarid.io/federation'
);

echo 'Deposit to: ' . $response->getAccountId() . PHP_EOL;

if ($response->getMemo() !== null) {
    echo 'Memo (' . $response->getMemoType() . '): ' . $response->getMemo() . PHP_EOL;
}
```

## FederationResponse Properties

`FederationResponse` extends `Response`. All properties are nullable — only fields returned by the federation server will be set.

| Method | Return type | Description |
|--------|-------------|-------------|
| `getAccountId()` | `?string` | Stellar account ID (G-address) for the payment destination |
| `getStellarAddress()` | `?string` | Federation address in `user*domain.com` format |
| `getMemoType()` | `?string` | Memo type: `"text"`, `"id"`, or `"hash"` |
| `getMemo()` | `?string` | Memo value to include with the payment |

The JSON field names the SDK parses from federation server responses:

```json
{
  "stellar_address": "bob*soneso.com",
  "account_id": "GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI",
  "memo_type": "text",
  "memo": "hello memo text"
}
```

**Hash memos:** When `getMemoType()` is `"hash"`, the value returned by `getMemo()` is base64-encoded. Decode it with `base64_decode()` before passing it to `Memo::hash()`, which expects exactly 32 raw bytes.

## Building a Payment with Federation

This complete example resolves a recipient address and builds a transaction with the correct memo.

```php
<?php declare(strict_types=1);

use Exception;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\SEP\Federation\Federation;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk        = StellarSDK::getTestNetInstance();
$network    = Network::testnet();
$senderKP   = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$senderId   = $senderKP->getAccountId();

// Resolve recipient
$fedResponse    = Federation::resolveStellarAddress('bob*soneso.com');
$destinationId  = $fedResponse->getAccountId();

// Load sender account (for sequence number)
$senderAccount = $sdk->requestAccount($senderId);

// Build payment operation
$paymentOp = (new PaymentOperationBuilder($destinationId, Asset::native(), '10'))->build();

// Build transaction
$txBuilder = (new TransactionBuilder($senderAccount))->addOperation($paymentOp);

// Attach memo if the federation response includes one
if ($fedResponse->getMemo() !== null) {
    $memoType = $fedResponse->getMemoType();
    if ($memoType === 'text') {
        $txBuilder->addMemo(Memo::text($fedResponse->getMemo()));
    } elseif ($memoType === 'id') {
        $txBuilder->addMemo(Memo::id((int)$fedResponse->getMemo()));
    } elseif ($memoType === 'hash') {
        // hash memo is base64-encoded in the federation response
        $txBuilder->addMemo(Memo::hash(base64_decode($fedResponse->getMemo())));
    }
}

$transaction = $txBuilder->build();
$transaction->sign($senderKP, $network);

try {
    $result = $sdk->submitTransaction($transaction);
    if ($result->isSuccessful()) {
        echo 'Payment sent! Hash: ' . $result->getHash() . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Submission failed: ' . $e->getMessage() . PHP_EOL;
}
```

## FederationRequestBuilder (Low-Level)

Use `FederationRequestBuilder` directly when you need to inspect the built URL, inject a mock HTTP client for tests, or craft non-standard queries.

```php
<?php declare(strict_types=1);

use GuzzleHttp\Client;
use Soneso\StellarSDK\SEP\Federation\FederationRequestBuilder;

$httpClient      = new Client();
$federationServer = 'https://stellarid.io/federation';

// Name lookup (type=name)
$builder = (new FederationRequestBuilder($httpClient, $federationServer))
    ->forStringToLookUp('bob*soneso.com')
    ->forType('name');

// Inspect URL before executing (useful for debugging)
echo $builder->buildUrl() . PHP_EOL;
// https://stellarid.io/federation?q=bob%2Asoneso.com&type=name

$response = $builder->execute();
echo $response->getAccountId() . PHP_EOL;
```

### FederationRequestBuilder methods

| Method | Returns | Description |
|--------|---------|-------------|
| `__construct(Client $httpClient, string $serviceAddress)` | — | Constructor |
| `forType(string $type)` | `FederationRequestBuilder` | Sets the `type` query parameter |
| `forStringToLookUp(string $stringToLookUp)` | `FederationRequestBuilder` | Sets the `q` query parameter |
| `forQueryParameters(array $queryParameters)` | `FederationRequestBuilder` | Merges additional query parameters (used for `type=forward`) |
| `buildUrl()` | `string` | Returns the complete URL with all query parameters |
| `execute()` | `FederationResponse` | Builds URL and executes the request |
| `request(string $url)` | `FederationResponse` | Executes a request to a given URL directly |

### Query type values for `forType()`

| Value | Description | Pair with |
|-------|-------------|-----------|
| `"name"` | Forward lookup by Stellar address | `forStringToLookUp('user*domain.com')` |
| `"id"` | Reverse lookup by account ID | `forStringToLookUp('G...')` |
| `"txid"` | Transaction sender lookup | `forStringToLookUp('txhash...')` |
| `"forward"` | Forward to external identifier | `forQueryParameters([...])` |

```php
<?php declare(strict_types=1);

use GuzzleHttp\Client;
use Soneso\StellarSDK\SEP\Federation\FederationRequestBuilder;

$httpClient       = new Client();
$federationServer = 'https://stellarid.io/federation';

// Reverse lookup (type=id)
$response = (new FederationRequestBuilder($httpClient, $federationServer))
    ->forType('id')
    ->forStringToLookUp('GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI')
    ->execute();

echo $response->getStellarAddress() . PHP_EOL; // bob*soneso.com

// Forward lookup (type=forward) — forQueryParameters merges into the query string
$response = (new FederationRequestBuilder($httpClient, $federationServer))
    ->forType('forward')
    ->forQueryParameters([
        'forward_type' => 'bank_account',
        'swift'        => 'BOPBPHMM',
        'acct'         => '2382376',
    ])
    ->execute();

echo $response->getAccountId() . PHP_EOL;
```

## Mock Handler for Tests

Use Guzzle's `MockHandler` to test federation logic without making real HTTP requests. This is the same pattern used in the SDK's own test suite.

```php
<?php declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Soneso\StellarSDK\SEP\Federation\Federation;
use Soneso\StellarSDK\SEP\Federation\FederationRequestBuilder;

// Canned federation server response body
$mockBody = json_encode([
    'stellar_address' => 'bob*soneso.com',
    'account_id'      => 'GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI',
    'memo_type'       => 'text',
    'memo'            => 'hello memo text',
]);

$mock  = new MockHandler([new Response(200, [], $mockBody)]);
$stack = HandlerStack::create($mock);
$httpClient = new Client(['handler' => $stack]);

// Use FederationRequestBuilder directly with the mocked client
$response = (new FederationRequestBuilder($httpClient, 'https://stellarid.io/federation'))
    ->forStringToLookUp('bob*soneso.com')
    ->forType('name')
    ->execute();

echo $response->getAccountId() . PHP_EOL;      // GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI
echo $response->getStellarAddress() . PHP_EOL; // bob*soneso.com
echo $response->getMemoType() . PHP_EOL;       // text
echo $response->getMemo() . PHP_EOL;           // hello memo text
```

**Note:** `Federation::resolveStellarAddress()` fetches `stellar.toml` first (a separate HTTP call), so mocking it end-to-end requires queuing two responses or using `FederationRequestBuilder` directly as shown above.

Pass the custom client to `Federation` static methods via the optional `$httpClient` parameter:

```php
// Federation static methods accept an optional third httpClient parameter
$response = Federation::resolveStellarAccountId(
    'GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI',
    'https://stellarid.io/federation',
    httpClient: $httpClient   // named argument (PHP 8.0+)
);
```

## Error Handling

```php
<?php declare(strict_types=1);

use Exception;
use InvalidArgumentException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\SEP\Federation\Federation;

// 1. Address missing '*' — throws InvalidArgumentException immediately (no network call)
try {
    Federation::resolveStellarAddress('invalid-no-asterisk');
} catch (InvalidArgumentException $e) {
    echo $e->getMessage() . PHP_EOL;
    // "Invalid federation address: invalid-no-asterisk"
}

// 2. Domain has no FEDERATION_SERVER in stellar.toml — throws Exception
try {
    Federation::resolveStellarAddress('user*domain-without-federation.example');
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
    // "no federation server found for domain: domain-without-federation.example"
}

// 3. Federation server returns HTTP error (404 user not found, 500 server error, etc.)
try {
    $response = Federation::resolveStellarAddress('nonexistent*soneso.com');
} catch (HorizonRequestException $e) {
    echo 'HTTP ' . $e->getStatusCode() . ': ' . $e->getMessage() . PHP_EOL;
}
```

### Exception summary

| Exception | When thrown |
|-----------|-------------|
| `InvalidArgumentException` | Address does not contain `*` |
| `Exception` | Domain's `stellar.toml` has no `FEDERATION_SERVER` |
| `HorizonRequestException` | Federation server returns an HTTP error (404, 500, etc.) |

## Common Pitfalls

**Forgetting the memo when sending to exchanges:**

```php
// WRONG: ignoring the memo — exchange cannot credit the recipient
$tx = (new TransactionBuilder($account))
    ->addOperation($paymentOp)
    ->build();

// CORRECT: always check for and attach the federation memo
if ($fedResponse->getMemo() !== null) {
    $txBuilder->addMemo(Memo::text($fedResponse->getMemo())); // adjust type as needed
}
```

**Not decoding base64 for hash memos:**

```php
// WRONG: passes base64 string directly — Memo::hash() expects raw bytes
$txBuilder->addMemo(Memo::hash($fedResponse->getMemo()));

// CORRECT: decode first; federation servers base64-encode hash memo values
$txBuilder->addMemo(Memo::hash(base64_decode($fedResponse->getMemo())));
```

**Using `resolveStellarAddress` for reverse lookups:**

```php
// WRONG: resolveStellarAddress expects "user*domain.com" format
Federation::resolveStellarAddress('GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI');
// Throws InvalidArgumentException — G-addresses contain no '*'

// CORRECT: use resolveStellarAccountId for reverse lookups
Federation::resolveStellarAccountId(
    'GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI',
    'https://stellarid.io/federation'
);
```

**Passing `null` federation server URL string to methods that require it:**

```php
// resolveStellarAccountId, resolveStellarTransactionId, resolveForward — all require
// the federation server URL as a non-optional string.
// Get it from stellar.toml if you don't have it:
$url = StellarToml::fromDomain('example.com')->getGeneralInformation()->federationServer;
if ($url === null) {
    throw new RuntimeException('Domain does not publish a federation server');
}
Federation::resolveStellarAccountId($accountId, $url);
```

**Mocking `resolveStellarAddress` end-to-end requires two queued responses:**

`resolveStellarAddress` makes two HTTP calls: one to fetch `stellar.toml` and one to the federation server. When mocking, queue both responses, or use `FederationRequestBuilder` directly to skip the TOML lookup.

## Related SEPs

- [SEP-01 stellar.toml](sep-01.md) — where `FEDERATION_SERVER` is published; needed for reverse/txid/forward lookups