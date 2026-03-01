---
name: stellar-php-sdk
description: Build Stellar blockchain applications in PHP using soneso/stellar-php-sdk. Use when generating PHP code for transaction building, signing, Horizon API queries, Soroban RPC, smart contract deployment and invocation, XDR encoding/decoding, and SEP protocol integration. Covers all 26 operations, 50 Horizon endpoints, 12 RPC methods, and 18 SEP implementations with synchronous Guzzle HTTP patterns.
license: Apache 2.0
compatibility: Requires PHP 8.0+, ext-bcmath, ext-gmp, and Composer
metadata:
  version: "1.0.0"
  sdk_version: "1.9.4"
  last_updated: "2026-02-22"
---

# Stellar SDK for PHP

## Overview

The `soneso/stellar-php-sdk` is a PHP 8.0+ library for the Stellar blockchain network. It provides 100% Horizon API coverage (50/50 endpoints), 100% Soroban RPC coverage (12/12 methods), 30/30 streaming endpoints, and 18 SEP implementations. All HTTP operations are synchronous using Guzzle 7. The root namespace is `Soneso\StellarSDK`.

## Installation

```bash
composer require soneso/stellar-php-sdk
```

> All code examples below assume `<?php declare(strict_types=1);` and relevant `use` imports.
>
> If you can't find a constructor or method signature in this file or the topic references, grep `references/api_reference.md` — it has all public class/method signatures.

## 1. Stellar Basics

Fundamental Stellar concepts and SDK patterns.

### Keys and KeyPairs

```php
use Soneso\StellarSDK\Crypto\KeyPair;

// Generate new keypair
$keyPair    = KeyPair::random();
$accountId  = $keyPair->getAccountId();   // G... public address
$secretSeed = $keyPair->getSecretSeed();  // S... secret seed
// IMPORTANT: Store secretSeed securely. Never log or expose it.

// From existing seed (never hardcode — use getenv() in production)
$keyPair    = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$publicOnly = KeyPair::fromAccountId($accountId); // public-key-only, cannot sign
```

### Accounts

```php
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Util\FriendBot;

$keyPair   = KeyPair::random();
$accountId = $keyPair->getAccountId();

// Fund on testnet using FriendBot (10,000 test XLM)
FriendBot::fundTestAccount($accountId);

// Query account
$sdk     = StellarSDK::getTestNetInstance();
$account = $sdk->requestAccount($accountId);

echo 'Sequence: ' . $account->getSequenceNumber() . PHP_EOL;
echo 'Subentry Count: ' . $account->getSubentryCount() . PHP_EOL;

foreach ($account->getBalances() as $balance) {
    if ($balance->getAssetType() === 'native') {
        echo 'XLM: ' . $balance->getBalance() . PHP_EOL;
    } else {
        echo $balance->getAssetCode() . ': ' . $balance->getBalance() . PHP_EOL;
    }
}

// Check existence without exception (returns bool)
$exists = $sdk->accountExists('GDEF...');
```

### Assets

```php
use Soneso\StellarSDK\Asset;

$xlm = Asset::native(); // Returns AssetTypeNative instance

// 1-4 char code -> AssetTypeCreditAlphanum4; 5-12 char -> AssetTypeCreditAlphanum12
$usdc = Asset::createNonNativeAsset('USDC', 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN');

// From canonical form
$asset = Asset::createFromCanonicalForm('USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN');
```

### Networks

```php
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;

// Testnet: Network::testnet() + StellarSDK::getTestNetInstance()
// Public:  Network::public()  + StellarSDK::getPublicNetInstance()
$network = Network::testnet();
$sdk     = StellarSDK::getTestNetInstance();

// Custom Horizon
$network = new Network('My Custom Network ; Passphrase');
$sdk     = new StellarSDK('https://my-horizon.example.com');
```

## 2. Horizon API - Fetching Data

Query patterns for retrieving blockchain data. All calls are synchronous.

### Query Accounts

```php
$sdk = StellarSDK::getTestNetInstance();

// Query accounts with filters (builder pattern)
$response = $sdk->accounts()
    ->forSigner('GABC...')
    ->limit(10)
    ->order('desc')
    ->execute();

foreach ($response->getAccounts() as $acct) {
    echo $acct->getAccountId() . PHP_EOL;
}
```

For single account queries, see `$sdk->requestAccount()` in [Accounts](#accounts) above.

### Query Transactions

```php
$sdk = StellarSDK::getTestNetInstance();

// Same builder pattern as accounts — forAccount(), limit(), order(), cursor()
$response = $sdk->transactions()
    ->forAccount('GABC...')
    ->limit(5)
    ->order('desc')
    ->execute();

foreach ($response->getTransactions() as $tx) {
    echo $tx->getHash() . ' ledger=' . $tx->getLedger() . PHP_EOL;
}

// Pagination: cursor from last result
$arr = $response->getTransactions()->toArray();
$nextPage = $sdk->transactions()->forAccount('GABC...')
    ->cursor(end($arr)->getPagingToken())->limit(5)->order('desc')->execute();

// Single transaction by hash
$tx = $sdk->requestTransaction('abc123def...');
```

For all Horizon endpoints, advanced queries, and pagination patterns:
[Horizon API Reference](./references/horizon_api.md)

## 3. Horizon API - Streaming

Real-time update patterns using Server-Sent Events. Streaming runs in a blocking `while(true)` loop.

### Stream Payments

```php
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;

// Stream payments for an account (blocking infinite loop)
$sdk->payments()
    ->forAccount($accountId)
    ->cursor('now')
    ->stream(function (PaymentOperationResponse $payment) {
        echo $payment->getFrom() . ' -> ' . $payment->getTo() . ': ' . $payment->getAmount() . PHP_EOL;
    });
// WRONG: return false to stop streaming -- return values are IGNORED
// CORRECT: throw an exception to break out, or use exit() in a forked child process
```

The same pattern applies to all streamable resources (`transactions()`, `ledgers()`, `operations()`, etc.). Auto-reconnects on server errors with a 10-second delay. **Streams block indefinitely** — use `pcntl_fork()` to run a stream alongside other operations (see streaming guide).

For reconnection patterns and all 30 streaming endpoints:
[Horizon Streaming Guide](./references/horizon_streaming.md)

## 4. Transactions & Operations

Complete transaction lifecycle: Build -> Sign -> Submit.

### Transaction Lifecycle

```php
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Memo;

$sdk     = StellarSDK::getTestNetInstance();
$network = Network::testnet();

// 1. Load source account keypair
$sourceKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$sourceId      = $sourceKeyPair->getAccountId();

// 2. Fetch source account (provides sequence number)
$sourceAccount = $sdk->requestAccount($sourceId);

// 3. Build the transaction
$paymentOp = (new PaymentOperationBuilder(
    'GDEST...', // destination account ID
    Asset::native(),
    '100.50'    // amount in XLM
))->build();

// A transaction can contain up to 100 operations.
$transaction = (new TransactionBuilder($sourceAccount))
    ->addOperation($paymentOp)
    ->addMemo(Memo::text('Payment for services'))
    ->setMaxOperationFee(200) // stroops per operation
    ->build();

// 4. Sign the transaction
$transaction->sign($sourceKeyPair, $network);

// 5. Submit to Horizon
$response = $sdk->submitTransaction($transaction);
if ($response->isSuccessful()) {
    echo 'Success! Hash: ' . $response->getHash() . PHP_EOL;
} else {
    $codes = $response->getExtras()->getResultCodes();
    echo 'Failed: ' . $codes->getTransactionResultCode() . PHP_EOL;
    echo 'Op codes: ' . json_encode($codes->getOperationsResultCodes()) . PHP_EOL;
}
// For HorizonRequestException handling, see Error Handling section
```

### Common Operations

**Change Trust (Establish Trustline):**

```php
use Soneso\StellarSDK\ChangeTrustOperationBuilder;

$asset = Asset::createNonNativeAsset('USDC', 'GISSUER...');
$trustOp = (new ChangeTrustOperationBuilder($asset))->build();
// Optional: set limit with ChangeTrustOperationBuilder($asset, '1000.00')
```

**Manage Sell Offer (DEX):**

```php
use Soneso\StellarSDK\ManageSellOfferOperationBuilder;

$selling = Asset::createNonNativeAsset('USDC', 'GISSUER...');
$buying  = Asset::native();

// Create new offer (offerId defaults to 0)
$offerOp = (new ManageSellOfferOperationBuilder(
    $selling, $buying, '100.00', '0.50' // amount selling, price
))->build();

// Query offers to get offer ID
$offers = $sdk->offers()->forAccount($accountId)->execute()->getOffers();
$offerId = $offers->toArray()[0]->getOfferId(); // WRONG: getId() — CORRECT: getOfferId()

// Update existing offer (amount '0' deletes it)
$updateOp = (new ManageSellOfferOperationBuilder($selling, $buying, '200.00', '0.55'))
    ->setOfferId($offerId)->build();
```

For all 26 operations with parameters and examples:
[Operations Reference](./references/operations.md)

## 5. Soroban RPC API

RPC endpoint patterns for Soroban smart contract queries.

```php
use Soneso\StellarSDK\Soroban\SorobanServer;

$server = new SorobanServer('https://soroban-testnet.stellar.org');
$server->enableLogging = true; // optional: debug request/response
$health = $server->getHealth(); // ->status
```

For all 12 RPC methods including event queries and transaction simulation:
[RPC Reference](./references/rpc.md)

## 6. Smart Contracts

Contract deployment and invocation patterns using the high-level `SorobanClient`.

### Deploy Contract

```php
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;
use Soneso\StellarSDK\Soroban\Contract\InstallRequest;
use Soneso\StellarSDK\Soroban\Contract\DeployRequest;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$keyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$rpcUrl  = 'https://soroban-testnet.stellar.org';
$network = Network::testnet();

// Step 1: Install WASM bytecode (returns wasm hash)
$wasmBytes = file_get_contents('/path/to/contract.wasm');
$wasmHash = SorobanClient::install(new InstallRequest(
    sourceAccountKeyPair: $keyPair,
    wasmBytes: $wasmBytes,
    network: $network,
    rpcUrl: $rpcUrl,
));

// Step 2: Deploy contract instance
$client = SorobanClient::deploy(new DeployRequest(
    sourceAccountKeyPair: $keyPair,
    wasmHash: $wasmHash,
    network: $network,
    rpcUrl: $rpcUrl,
    constructorArgs: [XdrSCVal::forSymbol('init')], // optional constructor args
));
echo 'Contract ID: ' . $client->getContractId() . PHP_EOL;
```

### Invoke Contract Function

```php
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\MethodOptions;

// Create client for an existing contract
$client = SorobanClient::forClientOptions(new ClientOptions(
    sourceAccountKeyPair: $keyPair,
    contractId: 'CABC...',
    network: $network,
    rpcUrl: $rpcUrl,
));

// Read call (simulation only, no transaction submitted)
$result = $client->invokeMethod('get_count');
echo 'Count: ' . $result->u32 . PHP_EOL;

// Write call (simulates, signs, submits automatically)
$result = $client->invokeMethod('increment', [XdrSCVal::forU32(5)]);

// With custom options (fee, timeout)
$result = $client->invokeMethod('expensive_operation', [XdrSCVal::forSymbol('data')],
    methodOptions: new MethodOptions(fee: 10000, timeoutInSeconds: 60));
```

For contract authorization, multi-auth workflows, and remote signing:
[Smart Contracts Guide](./references/soroban_contracts.md)

## 7. XDR Encoding & Decoding

XDR (External Data Representation) is Stellar's binary serialization format.

### Transaction XDR Roundtrip

```php
use Soneso\StellarSDK\AbstractTransaction;

// Encode: Transaction -> base64 XDR
$xdrBase64 = $transaction->toEnvelopeXdrBase64();

// Decode: base64 XDR -> Transaction
$decoded = AbstractTransaction::fromEnvelopeBase64XdrString($xdrBase64);
echo 'Source: ' . $decoded->getSourceAccount()->getAccountId() . PHP_EOL;
```

### Working with Soroban XDR Values (XdrSCVal)

```php
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrUInt128Parts;

// Common types: forBool(), forU32(), forI64(), forString(), forSymbol(), forBytes(), forVoid()
$symVal  = XdrSCVal::forSymbol('transfer');
$addrVal = XdrSCVal::forAddress(XdrSCAddress::forAccountId('GABC...'));
$u128Val = XdrSCVal::forU128(new XdrUInt128Parts(hi: 0, lo: 1000000));

// Vec (array of XdrSCVal) and Map (array of XdrSCMapEntry)
$vecVal = XdrSCVal::forVec([XdrSCVal::forU32(1), XdrSCVal::forU32(2)]);
$mapVal = XdrSCVal::forMap([new XdrSCMapEntry($symVal, XdrSCVal::forU32(42))]);

// Serialize to/from base64 XDR
$base64  = $symVal->toBase64Xdr();
$decoded = XdrSCVal::fromBase64Xdr($base64);
```

To submit a pre-signed XDR envelope: `$sdk->submitTransactionEnvelopeXdrBase64($signedXdrBase64)`.

For all XdrSCVal factory methods and type mapping:
[XDR Reference](./references/xdr.md) | [Contract Arguments](./references/soroban_contracts.md)

## 8. Error Handling & Troubleshooting

### Horizon Errors

```php
use Soneso\StellarSDK\Exceptions\HorizonRequestException;

try {
    $account = $sdk->requestAccount('GINVALID...');
} catch (HorizonRequestException $e) {
    $statusCode   = $e->getStatusCode();         // e.g., 404
    $retryAfter   = $e->getRetryAfter();         // seconds (429 rate limit), or null
    $horizonError = $e->getHorizonErrorResponse();
    if ($horizonError !== null) {
        echo $horizonError->getTitle() . ': ' . $horizonError->getDetail() . PHP_EOL;
        // For transaction errors: $horizonError->getExtras()->getResultCodesTransaction()
    }
}
```

Transaction submission error handling (non-exception path) is shown in the [Transaction Lifecycle](#transaction-lifecycle) example above.

### Soroban RPC Errors

```php
$txResponse = $server->getTransaction('abc123...');
if ($txResponse->error !== null) {
    echo 'RPC error: ' . $txResponse->error->getMessage() . PHP_EOL;
}
// Check status: 'SUCCESS', 'FAILED', or 'NOT_FOUND' (pending/invalid)
match ($txResponse->status) {
    'SUCCESS'   => /* transaction succeeded */,
    'FAILED'    => /* transaction failed */,
    'NOT_FOUND' => /* pending or invalid hash */,
};
```

For comprehensive error catalog, result codes, and retry patterns:
[Troubleshooting Guide](./references/troubleshooting.md)

## 9. Security Best Practices

Critical patterns: never hardcode secret seeds (use `getenv()` or vault), verify transaction details before signing, validate network passphrase, validate user inputs (account ID format, amount precision). Always use string amounts to avoid floating-point precision errors.

For complete security patterns including input validation, transaction verification, and key management:
[Security Guide](./references/security.md)

## 10. SEP Implementations

The PHP SDK implements 18 Stellar Ecosystem Proposals (SEPs). Most commonly used: SEP-01 (Stellar TOML discovery), SEP-02 (Federation address resolution), SEP-05 (BIP-39 mnemonic key derivation), SEP-10 (Web Authentication for account ownership proof), SEP-24 (Interactive deposit/withdrawal flows). All SEP classes are under the `Soneso\StellarSDK\SEP\` namespace.

For all SEP examples and the complete implementation table:
[SEP Reference](./references/sep.md)

## 11. Advanced Features

- **Multi-signature accounts** — add signers + set thresholds in a SINGLE transaction to avoid lockout → [Multi-Sig Example](./references/advanced.md)
- **Sponsored reserves** — three-operation pattern (Begin → Create → End), both parties sign → [Sponsorship Example](./references/advanced.md)
- **Fee bump transactions** — `FeeBumpTransactionBuilder` → [Fee Bump Example](./references/advanced.md)
- **Liquidity pools** — AMM with `AssetTypePoolShare` → [Pools Example](./references/advanced.md)
- **Muxed accounts** — `MuxedAccount` with M... addresses → [Muxed Example](./references/advanced.md)
- **Async submission** — `submitAsyncTransaction()` → [Async Example](./references/advanced.md)

## Reference Documentation

Links to comprehensive reference guides:

- [Operations Reference](./references/operations.md) - All 26 Stellar operations with examples
- [Horizon API Reference](./references/horizon_api.md) - Complete Horizon endpoint coverage (50/50)
- [Horizon Streaming Guide](./references/horizon_streaming.md) - SSE patterns for all 30 streaming endpoints
- [RPC Reference](./references/rpc.md) - All 12 Soroban RPC methods
- [Smart Contracts Guide](./references/soroban_contracts.md) - Contract deployment, invocation, auth
- [XDR Guide](./references/xdr.md) - XDR encoding/decoding and debugging
- [Troubleshooting Guide](./references/troubleshooting.md) - Error codes and solutions
- [Security Guide](./references/security.md) - Production security patterns
- [API Reference (Signatures)](./references/api_reference.md) - All public class/method signatures (grep for any class or method not covered above)

## Common Pitfalls

**Stroop precision:** Stellar amounts have 7 decimal places. Always use string amounts to avoid floating-point errors.

```php
// WRONG: floating point
$amount = 100.1234567; // May lose precision

// CORRECT: string amount
$amount = '100.1234567';
```

**Sequence number staleness:** `TransactionBuilder->build()` mutates the source account's sequence number. A good practice is to fetch the account immediately before building. Don't increment manually — `build()` handles it. Stale sequence numbers cause `tx_bad_seq` errors.

```php
// CORRECT: reload account, build() increments sequence internally
$account = $sdk->requestAccount($accountId); // on-chain seq N
$tx = (new TransactionBuilder($account))->addOperation($op)->build(); // uses seq N+1
$sdk->submitTransaction($tx);

// WRONG: manually incrementing — build() already does this
$account = $sdk->requestAccount($accountId); // on-chain seq N
$account->incrementSequenceNumber(); // now N+1
$tx = (new TransactionBuilder($account))->addOperation($op)->build(); // seq N+2 — tx_bad_seq
```

**Sequence numbers are BigInteger objects:**

```php
use phpseclib3\Math\BigInteger;

// WRONG: arithmetic operators on BigInteger
$seqNum = $account->getSequenceNumber() - 1; // TypeError

// CORRECT: use BigInteger methods
$seqNum = $account->getSequenceNumber()->subtract(new BigInteger(1));
$account = new Account($account->getAccountId(), $seqNum); // account with modified seq num
```

**Collection count — use method, not function:**

```php
// WRONG: PHP count() — SDK collections do NOT implement Countable
count($account->getSigners()); // TypeError or returns 1 (misleading)

// CORRECT: use the ->count() method on all SDK collection objects
$account->getSigners()->count();
// Same for: TransactionsResponse, OperationsResponse, OffersResponse,
// PaymentsResponse, TradesResponse, EffectsResponse, etc.
```


**Insufficient signatures return `op_bad_auth`, not `tx_bad_auth`:** When a multi-sig transaction lacks enough signature weight, the transaction-level code is `tx_failed` and the auth failure is in the operation codes.

```php
// WRONG: checking transaction code for auth failure
$txCode = $extras->getResultCodesTransaction(); // returns 'tx_failed'
if ($txCode === 'tx_bad_auth') { ... } // never matches

// CORRECT: check operation codes for op_bad_auth
$opCodes = $extras->getResultCodesOperation(); // returns ['op_bad_auth']
```

**Fee calculation:** The fee is per operation. For a transaction with N operations at `setMaxOperationFee(200)`, the total fee is N * 200 stroops. The minimum base fee is 100 stroops per operation (`StellarConstants::MIN_BASE_FEE_STROOPS`).
